<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Actividad 4.2: API sin autenticación. Cada prueba verifica el código HTTP
 * Y la estructura JSON estandarizada {success, message, data, errors}.
 */
class ApiV42Test extends TestCase
{
    use RefreshDatabase;

    private Categoria $categoria;
    private Usuario $dueno;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $rol = Rol::create(['nombre' => 'cliente']);
        $this->dueno = Usuario::create(['rol_id' => $rol->id, 'nombre' => 'Donante', 'email' => 'd@example.com']);
        $this->categoria = Categoria::create(['nombre' => 'Ropa']);
    }

    /** El proyecto puede usar borrado lógico en productos (Actividad 2.6); las pruebas se adaptan. */
    /**
     * Imagen PNG válida de 1x1 píxel creada a mano: no requiere la extensión GD de PHP
     * (UploadedFile::fake()->image() sí la necesita).
     */
    private function png(string $nombre = 'foto.png'): UploadedFile
    {
        $contenido = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($nombre, $contenido);
    }

    /** Ruta de BD (storage/productos/x.png) -> ruta dentro del disco público (productos/x.png). */
    private function enDisco(?string $ruta): string
    {
        return Str::after((string) $ruta, 'storage/');
    }

    private function usaBorradoLogico(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive(Producto::class), true);
    }

    private function producto(array $extra = []): Producto
    {
        $p = new Producto();
        $p->forceFill(array_merge([
            'categoria_id' => $this->categoria->id, 'usuario_id' => $this->dueno->id,
            'nombre' => 'Chamarra', 'descripcion' => 'Buen estado', 'talla' => 'M',
            'condicion' => 'usado', 'precio' => 250, 'stock' => 3,
        ], $extra))->save();

        return $p;
    }

    private function validos(array $extra = []): array
    {
        return array_merge([
            'nombre' => 'Vestido de verano', 'descripcion' => 'Nuevo', 'categoria_id' => $this->categoria->id,
            'usuario_id' => $this->dueno->id, 'talla' => 'S', 'condicion' => 'nuevo', 'precio' => 180.5, 'stock' => 5,
        ], $extra);
    }

    /** Estructura estándar: exactamente 4 campos y tipos correctos. */
    private function assertEstructura($respuesta, bool $exito): void
    {
        $respuesta->assertHeader('Content-Type', 'application/json');
        $json = $respuesta->json();
        $this->assertIsArray($json);
        $this->assertSame(['success', 'message', 'data', 'errors'], array_keys($json));
        $this->assertSame($exito, $json['success']);
        $this->assertIsString($json['message']);
        $this->assertNotSame('', $json['message']);
        if ($exito) {
            $this->assertNull($json['errors']);
        } else {
            $this->assertNull($json['data']);
        }
    }

    // ----------------------------------------------------------------- consulta

    public function test_consultar_coleccion(): void
    {
        $this->producto();
        $r = $this->getJson('/api/v1/productos');
        $r->assertOk();
        $this->assertEstructura($r, true);
        $this->assertCount(1, $r->json('data'));
        $r->assertJsonPath('data.0.categoria.nombre', 'Ropa');
    }

    public function test_consultar_registro_existente(): void
    {
        $p = $this->producto();
        $r = $this->getJson("/api/v1/productos/{$p->id}");
        $r->assertOk()->assertJsonPath('data.id', $p->id)->assertJsonPath('data.precio', 250);
        $this->assertEstructura($r, true);
    }

    public function test_consultar_inexistente_devuelve_404_json(): void
    {
        $r = $this->getJson('/api/v1/productos/9999');
        $r->assertNotFound()->assertJsonPath('message', 'El producto solicitado no existe');
        $this->assertEstructura($r, false);
    }

    public function test_identificador_invalido_devuelve_400_json(): void
    {
        foreach (['abc', '0', '-5', '1.5'] as $id) {
            $r = $this->getJson("/api/v1/productos/{$id}");
            $r->assertStatus(400);
            $this->assertEstructura($r, false);
        }
    }

    public function test_filtros_y_parametros_invalidos(): void
    {
        $this->producto(['nombre' => 'Botas', 'condicion' => 'nuevo']);
        $this->producto(['nombre' => 'Camisa']);
        $this->assertCount(1, $this->getJson('/api/v1/productos?buscar=Botas')->json('data'));
        $this->assertCount(1, $this->getJson('/api/v1/productos?condicion=nuevo')->json('data'));

        $r = $this->getJson('/api/v1/productos?condicion=roto');
        $r->assertStatus(422);
        $this->assertEstructura($r, false);
    }

    // ----------------------------------------------------------------- creación

    public function test_crear_registro_valido(): void
    {
        $r = $this->postJson('/api/v1/productos', $this->validos());
        $r->assertCreated()->assertJsonPath('data.nombre', 'Vestido de verano')->assertJsonPath('data.imagen', null);
        $this->assertEstructura($r, true);
        $this->assertDatabaseHas('productos', ['nombre' => 'Vestido de verano']);
    }

    public function test_crear_con_datos_incompletos_devuelve_422(): void
    {
        $r = $this->postJson('/api/v1/productos', []);
        $r->assertStatus(422)->assertJsonPath('message', 'Los datos enviados no son válidos');
        $this->assertEstructura($r, false);
        foreach (['nombre', 'categoria_id', 'usuario_id', 'condicion', 'precio', 'stock'] as $campo) {
            $r->assertJsonValidationErrorFor($campo, 'errors');
        }
    }

    public function test_crear_con_datos_invalidos_devuelve_422(): void
    {
        $r = $this->postJson('/api/v1/productos', $this->validos([
            'nombre' => 'ab', 'precio' => 'barato', 'stock' => -1, 'condicion' => 'roto', 'categoria_id' => 9999,
        ]));
        $r->assertStatus(422);
        $this->assertEstructura($r, false);
        $this->assertSame('El nombre debe tener al menos 3 caracteres.', $r->json('errors.nombre.0'));
        $this->assertSame('La categoría indicada no existe.', $r->json('errors.categoria_id.0'));
        $this->assertDatabaseCount('productos', 0);
    }

    public function test_precio_cero_o_demasiado_grande_es_invalido(): void
    {
        $this->postJson('/api/v1/productos', $this->validos(['precio' => 0]))->assertStatus(422);
        $this->postJson('/api/v1/productos', $this->validos(['precio' => 1000000]))->assertStatus(422);
    }

    public function test_json_malformado_devuelve_400(): void
    {
        $r = $this->call('POST', '/api/v1/productos', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{"nombre": ');
        $r->assertStatus(400);
        $this->assertEstructura($r, false);
    }

    public function test_content_type_no_admitido_devuelve_415(): void
    {
        $r = $this->call('POST', '/api/v1/productos', [], [], [], ['CONTENT_TYPE' => 'text/plain'], 'hola');
        $r->assertStatus(415);
        $this->assertEstructura($r, false);
    }

    // ------------------------------------------------------------- actualización

    public function test_actualizar_parcialmente_con_patch_conserva_los_demas_campos(): void
    {
        $p = $this->producto();
        $r = $this->patchJson("/api/v1/productos/{$p->id}", ['precio' => 199.9]);
        $r->assertOk()->assertJsonPath('data.precio', 199.9)->assertJsonPath('data.nombre', 'Chamarra');
        $this->assertEstructura($r, true);
    }

    public function test_reemplazar_con_put_exige_el_recurso_completo(): void
    {
        $p = $this->producto();
        $r = $this->putJson("/api/v1/productos/{$p->id}", ['precio' => 10]);
        $r->assertStatus(422);
        $this->assertEstructura($r, false);

        $r = $this->putJson("/api/v1/productos/{$p->id}", $this->validos(['nombre' => 'Reemplazado', 'talla' => null, 'descripcion' => null]));
        $r->assertOk()->assertJsonPath('data.nombre', 'Reemplazado')->assertJsonPath('data.talla', null);
    }

    public function test_actualizar_con_datos_invalidos_devuelve_422(): void
    {
        $p = $this->producto();
        $r = $this->patchJson("/api/v1/productos/{$p->id}", ['stock' => 'muchos']);
        $r->assertStatus(422);
        $this->assertEstructura($r, false);
    }

    public function test_actualizar_inexistente_devuelve_404(): void
    {
        foreach (['patchJson', 'putJson'] as $m) {
            $r = $this->$m('/api/v1/productos/9999', $this->validos());
            $r->assertNotFound();
            $this->assertEstructura($r, false);
        }
    }

    // -------------------------------------------------------------- eliminación

    public function test_eliminar_registro_existente(): void
    {
        $p = $this->producto();
        $r = $this->deleteJson("/api/v1/productos/{$p->id}");
        $r->assertOk()->assertJsonPath('data.id', $p->id);
        $this->assertEstructura($r, true);

        if ($this->usaBorradoLogico()) {
            $this->assertSoftDeleted('productos', ['id' => $p->id]);
        } else {
            $this->assertDatabaseMissing('productos', ['id' => $p->id]);
        }

        // En cualquier caso el registro deja de ser consultable.
        $this->getJson("/api/v1/productos/{$p->id}")->assertNotFound();
    }

    public function test_eliminar_inexistente_devuelve_404(): void
    {
        $r = $this->deleteJson('/api/v1/productos/9999');
        $r->assertNotFound();
        $this->assertEstructura($r, false);
    }

    public function test_eliminar_producto_borra_su_imagen_salvo_borrado_logico(): void
    {
        $r = $this->post('/api/v1/productos', $this->validos() + ['imagen' => $this->png('a.jpg')], ['Accept' => 'application/json']);
        $ruta = $r->json('data.imagen');
        Storage::disk('public')->assertExists($this->enDisco($ruta));

        $this->deleteJson('/api/v1/productos/'.$r->json('data.id'))->assertOk();

        // Con borrado lógico el archivo se conserva (el registro puede restaurarse).
        $this->usaBorradoLogico()
            ? Storage::disk('public')->assertExists($this->enDisco($ruta))
            : Storage::disk('public')->assertMissing($this->enDisco($ruta));
    }

    // ----------------------------------------------------------------- imágenes

    public function test_crear_producto_con_imagen_y_consultarlo(): void
    {
        $r = $this->post('/api/v1/productos', $this->validos() + ['imagen' => $this->png('foto.png')], ['Accept' => 'application/json']);
        $r->assertCreated();
        $this->assertEstructura($r, true);

        $ruta = $r->json('data.imagen');
        $this->assertStringStartsWith('storage/productos/Producto_', $ruta);
        $this->assertStringEndsWith('.png', $ruta);                    // nombre seguro generado por el servidor
        $this->assertStringNotContainsString('foto', $ruta);           // no usa el nombre original
        Storage::disk('public')->assertExists($this->enDisco($ruta));
        $this->assertSame(url($ruta), $r->json('data.imagen_url'));

        $c = $this->getJson('/api/v1/productos/'.$r->json('data.id'));
        $c->assertOk()->assertJsonPath('data.imagen', $ruta)->assertJsonPath('data.imagen_url', url($ruta));
    }

    public function test_cargar_imagen_en_endpoint_independiente_y_sustituirla(): void
    {
        $p = $this->producto();

        $r1 = $this->post("/api/v1/productos/{$p->id}/imagen", ['imagen' => $this->png('uno.jpg')], ['Accept' => 'application/json']);
        $r1->assertCreated();
        $this->assertEstructura($r1, true);
        $primera = $r1->json('data.imagen');
        Storage::disk('public')->assertExists($this->enDisco($primera));

        $r2 = $this->post("/api/v1/productos/{$p->id}/imagen", ['imagen' => $this->png('dos.webp')], ['Accept' => 'application/json']);
        $r2->assertOk();
        $segunda = $r2->json('data.imagen');
        $this->assertNotSame($primera, $segunda);
        Storage::disk('public')->assertExists($this->enDisco($segunda));
        Storage::disk('public')->assertMissing($this->enDisco($primera));              // el archivo anterior se elimina
    }

    public function test_no_borra_el_archivo_anterior_si_otro_registro_lo_usa(): void
    {
        $a = $this->producto(['nombre' => 'A']);
        $this->post("/api/v1/productos/{$a->id}/imagen", ['imagen' => $this->png('x.jpg')], ['Accept' => 'application/json']);
        $ruta = $a->fresh()->imagen;
        $b = $this->producto(['nombre' => 'B', 'imagen' => $ruta]);    // comparte el mismo archivo

        $this->post("/api/v1/productos/{$a->id}/imagen", ['imagen' => $this->png('y.jpg')], ['Accept' => 'application/json'])->assertOk();
        Storage::disk('public')->assertExists($this->enDisco($ruta));                  // sigue en uso por B
    }

    public function test_sustituir_una_imagen_subida_antes_desde_la_parte_web(): void
    {
        // La parte web guarda en public/images/productos y la columna conserva "images/productos/...".
        File::ensureDirectoryExists(public_path('images/productos'));
        File::put(public_path('images/productos/Producto_test_web.png'), 'x');
        $p = $this->producto(['imagen' => 'images/productos/Producto_test_web.png']);

        $r = $this->post("/api/v1/productos/{$p->id}/imagen", ['imagen' => $this->png('nueva.png')], ['Accept' => 'application/json']);
        $r->assertOk();

        $this->assertFileDoesNotExist(public_path('images/productos/Producto_test_web.png'));
        $this->assertStringStartsWith('storage/productos/', $r->json('data.imagen'));
    }

    public function test_archivo_que_no_es_imagen_devuelve_422(): void
    {
        $p = $this->producto();
        foreach ([UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf'), UploadedFile::fake()->create('script.php', 5, 'text/x-php')] as $archivo) {
            $r = $this->post("/api/v1/productos/{$p->id}/imagen", ['imagen' => $archivo], ['Accept' => 'application/json']);
            $r->assertStatus(422);
            $this->assertEstructura($r, false);
            $r->assertJsonValidationErrorFor('imagen', 'errors');
        }
        $this->assertNull($p->fresh()->imagen);
    }

    public function test_imagen_mayor_al_maximo_devuelve_422(): void
    {
        $p = $this->producto();
        $r = $this->post("/api/v1/productos/{$p->id}/imagen", ['imagen' => $this->png('grande.jpg')->size(3000)], ['Accept' => 'application/json']);
        $r->assertStatus(422)->assertJsonPath('errors.imagen.0', 'La imagen no puede superar los 2 MB.');
        $this->assertEstructura($r, false);
    }

    public function test_crear_producto_con_imagen_invalida_no_guarda_nada(): void
    {
        $r = $this->post('/api/v1/productos', $this->validos() + ['imagen' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')], ['Accept' => 'application/json']);
        $r->assertStatus(422);
        $this->assertDatabaseCount('productos', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_imagen_sin_archivo_o_en_producto_inexistente(): void
    {
        $p = $this->producto();
        $this->postJson("/api/v1/productos/{$p->id}/imagen", [])->assertStatus(422);

        $r = $this->post('/api/v1/productos/9999/imagen', ['imagen' => $this->png('a.jpg')], ['Accept' => 'application/json']);
        $r->assertNotFound();
        $this->assertEstructura($r, false);
    }

    public function test_eliminar_imagen_asociada(): void
    {
        $p = $this->producto();
        $this->post("/api/v1/productos/{$p->id}/imagen", ['imagen' => $this->png('a.jpg')], ['Accept' => 'application/json']);
        $ruta = $p->fresh()->imagen;

        $r = $this->deleteJson("/api/v1/productos/{$p->id}/imagen");
        $r->assertOk()->assertJsonPath('data.imagen', null)->assertJsonPath('data.imagen_url', null);
        $this->assertEstructura($r, true);
        Storage::disk('public')->assertMissing($this->enDisco($ruta));

        $otra = $this->deleteJson("/api/v1/productos/{$p->id}/imagen");   // ya no tiene imagen
        $otra->assertNotFound();
        $this->assertEstructura($otra, false);

        $this->deleteJson('/api/v1/productos/9999/imagen')->assertNotFound();
    }

    // -------------------------------------------------------------- categorías

    public function test_crud_de_categorias(): void
    {
        $c = $this->postJson('/api/v1/categorias', ['nombre' => 'Calzado']);
        $c->assertCreated();
        $this->assertEstructura($c, true);
        $id = $c->json('data.id');

        $this->getJson('/api/v1/categorias')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson("/api/v1/categorias/{$id}")->assertOk()->assertJsonPath('data.nombre', 'Calzado');
        $this->patchJson("/api/v1/categorias/{$id}", ['nombre' => 'Zapatos'])->assertOk()->assertJsonPath('data.nombre', 'Zapatos');
        $this->putJson("/api/v1/categorias/{$id}", [])->assertStatus(422);
        $this->deleteJson("/api/v1/categorias/{$id}")->assertOk();
        $this->getJson("/api/v1/categorias/{$id}")->assertNotFound();
    }

    public function test_categorias_validaciones_y_404(): void
    {
        $this->postJson('/api/v1/categorias', [])->assertStatus(422);
        $r = $this->postJson('/api/v1/categorias', ['nombre' => 'Ropa']);
        $r->assertStatus(422)->assertJsonPath('errors.nombre.0', 'Ya existe una categoría con ese nombre.');
        $this->assertEstructura($r, false);

        foreach (['getJson', 'deleteJson'] as $m) {
            $x = $this->$m('/api/v1/categorias/9999');
            $x->assertNotFound();
            $this->assertEstructura($x, false);
        }
        $this->patchJson('/api/v1/categorias/9999', ['nombre' => 'Algo'])->assertNotFound();
    }

    public function test_no_se_elimina_categoria_con_productos(): void
    {
        $this->producto();
        $r = $this->deleteJson("/api/v1/categorias/{$this->categoria->id}");
        $r->assertStatus(409);
        $this->assertEstructura($r, false);
        $this->assertDatabaseHas('categorias', ['id' => $this->categoria->id]);
    }

    // ------------------------------------------------ errores globales en JSON

    public function test_metodo_no_permitido_devuelve_405_json(): void
    {
        $r = $this->call('PUT', '/api/v1/productos');
        $r->assertStatus(405);
        $this->assertEstructura($r, false);
    }

    public function test_ruta_inexistente_devuelve_404_json(): void
    {
        $r = $this->get('/api/v1/no-existe');
        $r->assertNotFound();
        $this->assertEstructura($r, false);
        $r->assertJsonPath('message', 'El recurso solicitado no existe');
    }

    public function test_solicitud_demasiado_grande_devuelve_413_json(): void
    {
        $r = $this->call('POST', '/api/v1/productos', [], [], [], ['CONTENT_TYPE' => 'application/json', 'CONTENT_LENGTH' => 999999999999], '{}');
        $r->assertStatus(413);
        $this->assertEstructura($r, false);
    }

    public function test_error_interno_devuelve_500_json_sin_detalles(): void
    {
        Producto::creating(function () {
            throw new \RuntimeException('SQLSTATE secreto en /var/www/app/Interno.php');
        });

        $r = $this->postJson('/api/v1/productos', $this->validos());
        $r->assertStatus(500)->assertJsonPath('message', 'Ocurrió un error interno al procesar la solicitud');
        $this->assertEstructura($r, false);
        $this->assertStringNotContainsString('SQLSTATE', $r->getContent());
        $this->assertStringNotContainsString('/var/www', $r->getContent());
        $this->assertStringNotContainsString('trace', $r->getContent());
    }

    public function test_responde_json_aunque_no_se_envie_accept(): void
    {
        $r = $this->call('GET', '/api/v1/productos/9999', [], [], [], ['HTTP_ACCEPT' => 'text/html']);
        $r->assertNotFound();
        $this->assertEstructura($r, false);
    }

    public function test_la_api_no_pide_autenticacion(): void
    {
        $this->assertSame(200, $this->getJson('/api/v1/productos')->status());
        $this->assertSame(200, $this->getJson('/api/v1/categorias')->status());
    }
}
