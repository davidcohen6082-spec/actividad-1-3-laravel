<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Actividad 4.3 · Módulo administrativo (Sanctum + Policy/Gates + FormRequest + JsonResource).
 * Cuentas de prueba (seeders): admin/admin1234 (nivel 3) · nivel2/nivel2123 · nivel1/nivel1123 ·
 * inactivo/inactivo1234 · cliente1@example.com / cliente123.
 */
class AdminApiV43Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function api(string $metodo, string $uri, array $datos = [], ?string $token = null): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->json($metodo, "/api/v1/{$uri}", $datos, $token ? ['Authorization' => "Bearer {$token}"] : []);
    }

    private function login(string $usuario = 'admin', string $password = 'admin1234'): string
    {
        return $this->api('POST', 'admin/login', compact('usuario', 'password'))->assertOk()->json('data.token');
    }

    private function datosRecurso(array $extra = []): array
    {
        return array_merge([
            'nombre' => 'Chamarra de mezclilla',
            'descripcion' => 'Chamarra donada en buen estado',
            'categoria_id' => Categoria::value('id'),
            'condicion' => 'usado',
            'talla' => 'M',
            'precio' => 150.50,
            'stock' => 4,
        ], $extra);
    }

    private function crearRecurso(string $token, array $extra = []): int
    {
        return $this->api('POST', 'admin/recursos', $this->datosRecurso($extra), $token)->assertStatus(201)->json('data.id');
    }

    // ============================================================ 1. AUTENTICACIÓN (Sanctum)

    public function test_login_emite_token_bearer_con_nombre_admin_token(): void
    {
        $r = $this->api('POST', 'admin/login', ['usuario' => 'admin', 'password' => 'admin1234'])->assertOk();

        $this->assertSame('Bearer', $r->json('data.token_type'));
        $this->assertNotEmpty($r->json('data.token'));
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'admin-token', 'tokenable_type' => Administrador::class]);
        $this->assertStringNotContainsString('password', $r->getContent());
    }

    public function test_login_rechaza_credenciales_invalidas_y_datos_faltantes(): void
    {
        $this->api('POST', 'admin/login', ['usuario' => 'admin', 'password' => 'mal'])->assertStatus(401);
        $this->api('POST', 'admin/login', ['usuario' => 'noexiste', 'password' => 'x'])->assertStatus(401);
        $this->api('POST', 'admin/login', [])->assertStatus(422)->assertJsonValidationErrors(['usuario', 'password']);
    }

    public function test_cuenta_inactiva_no_obtiene_token(): void
    {
        $this->api('POST', 'admin/login', ['usuario' => 'inactivo', 'password' => 'inactivo1234'])->assertStatus(403);
    }

    public function test_un_cliente_no_puede_obtener_token_administrativo(): void
    {
        // Las credenciales de un cliente no existen en la tabla de administradores.
        $this->api('POST', 'admin/login', ['usuario' => 'cliente1@example.com', 'password' => 'cliente123'])->assertStatus(401);
    }

    public function test_rutas_admin_sin_token_devuelven_401(): void
    {
        $rutas = [
            ['GET', 'admin/recursos'], ['GET', 'admin/recursos/1'], ['POST', 'admin/recursos'],
            ['PUT', 'admin/recursos/1'], ['PATCH', 'admin/recursos/1'], ['DELETE', 'admin/recursos/1'],
            ['GET', 'admin/usuarios'], ['PATCH', 'admin/usuarios/1/estado'], ['POST', 'admin/logout'],
        ];

        foreach ($rutas as [$metodo, $uri]) {
            $this->api($metodo, $uri)->assertStatus(401)->assertJsonStructure(['message']);
        }
    }

    public function test_token_invalido_devuelve_401(): void
    {
        $this->api('GET', 'admin/recursos', [], 'token-falso-123')->assertStatus(401);
    }

    public function test_token_expirado_devuelve_401(): void
    {
        $token = $this->login();
        $this->api('GET', 'admin/recursos', [], $token)->assertOk();

        Carbon::setTestNow(now()->addMinutes(config('sanctum.expiration') + 1));
        $this->api('GET', 'admin/recursos', [], $token)->assertStatus(401);
        Carbon::setTestNow();
    }

    public function test_logout_revoca_el_token(): void
    {
        $token = $this->login();

        $this->api('GET', 'admin/recursos', [], $token)->assertOk();
        $this->api('POST', 'admin/logout', [], $token)->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->api('GET', 'admin/recursos', [], $token)->assertStatus(401);
    }

    public function test_logout_solo_revoca_el_token_actual(): void
    {
        $a = $this->login();
        $b = $this->login();

        $this->api('POST', 'admin/logout', [], $a)->assertOk();
        $this->api('GET', 'admin/recursos', [], $a)->assertStatus(401);
        $this->api('GET', 'admin/recursos', [], $b)->assertOk();
    }

    public function test_un_token_de_cliente_no_abre_el_area_admin(): void
    {
        $cliente = $this->api('POST', 'cliente/login', ['email' => 'cliente1@example.com', 'password' => 'cliente123'])->json('data.token');

        $this->api('GET', 'admin/recursos', [], $cliente)->assertStatus(403);
        $this->api('GET', 'admin/usuarios', [], $cliente)->assertStatus(403);
        $this->api('PATCH', 'admin/usuarios/1/estado', ['activo' => false], $cliente)->assertStatus(403);
    }

    // ============================================================ 2. CRUD DE RECURSOS

    public function test_crud_completo_de_recursos(): void
    {
        $token = $this->login();

        // POST -> 201
        $creado = $this->api('POST', 'admin/recursos', $this->datosRecurso(), $token)
            ->assertStatus(201)
            ->assertJsonPath('message', 'Recurso creado correctamente.')
            ->assertJsonPath('data.nombre', 'Chamarra de mezclilla')
            ->assertJsonPath('data.precio', 150.5)
            ->assertJsonPath('data.activo', true);
        $id = $creado->json('data.id');

        // GET detalle -> 200
        $this->api('GET', "admin/recursos/{$id}", [], $token)->assertOk()->assertJsonPath('data.id', $id)
            ->assertJsonStructure(['data' => ['id', 'nombre', 'descripcion', 'talla', 'condicion', 'precio', 'stock', 'imagen_url', 'activo', 'estado', 'categoria' => ['id', 'nombre'], 'creado_en']]);

        // PUT -> 200 (reemplazo completo)
        $this->api('PUT', "admin/recursos/{$id}", $this->datosRecurso(['nombre' => 'Chamarra reemplazada', 'stock' => 9]), $token)
            ->assertOk()->assertJsonPath('data.nombre', 'Chamarra reemplazada')->assertJsonPath('data.stock', 9);

        // PATCH -> 200 (parcial)
        $this->api('PATCH', "admin/recursos/{$id}", ['precio' => 99], $token)
            ->assertOk()->assertJsonPath('data.precio', 99)->assertJsonPath('data.nombre', 'Chamarra reemplazada');

        // GET listado -> 200 con paginación
        $this->api('GET', 'admin/recursos', [], $token)->assertOk()->assertJsonStructure(['data', 'links', 'meta' => ['total']]);

        // DELETE -> 200 (borrado lógico)
        $this->api('DELETE', "admin/recursos/{$id}", [], $token)->assertOk()->assertJsonPath('data.eliminacion', 'logica');
        $this->assertSoftDeleted('productos', ['id' => $id]);
    }

    public function test_la_respuesta_no_expone_columnas_internas(): void
    {
        $token = $this->login();
        $id = $this->crearRecurso($token);

        $contenido = $this->api('GET', "admin/recursos/{$id}", [], $token)->assertOk()->getContent();

        foreach (['categoria_id', 'usuario_id', 'deleted_at', 'created_at', 'password'] as $columna) {
            $this->assertStringNotContainsString("\"{$columna}\"", $contenido);
        }
    }

    public function test_el_listado_incluye_recursos_inactivos_y_se_puede_filtrar(): void
    {
        $token = $this->login();
        $id = $this->crearRecurso($token);
        $this->api('DELETE', "admin/recursos/{$id}", [], $token)->assertOk();

        $todos = $this->api('GET', 'admin/recursos?per_page=50', [], $token)->assertOk();
        $this->assertContains($id, collect($todos->json('data'))->pluck('id')->all());

        $inactivos = $this->api('GET', 'admin/recursos?estado=inactivos', [], $token)->assertOk()->json('data');
        $this->assertSame([$id], collect($inactivos)->pluck('id')->all());
        $this->assertFalse($inactivos[0]['activo']);

        $activos = $this->api('GET', 'admin/recursos?estado=activos&per_page=50', [], $token)->json('data');
        $this->assertNotContains($id, collect($activos)->pluck('id')->all());
    }

    public function test_el_estado_se_cambia_con_patch_activo(): void
    {
        $token = $this->login();
        $id = $this->crearRecurso($token);

        $this->api('PATCH', "admin/recursos/{$id}", ['activo' => false], $token)->assertOk()->assertJsonPath('data.activo', false);
        $this->assertSoftDeleted('productos', ['id' => $id]);

        $this->api('PATCH', "admin/recursos/{$id}", ['activo' => true], $token)->assertOk()->assertJsonPath('data.estado', 'activo');
        $this->assertNotSoftDeleted('productos', ['id' => $id]);
    }

    public function test_eliminacion_definitiva_y_conflictos(): void
    {
        $token = $this->login();
        $id = $this->crearRecurso($token);

        $this->api('DELETE', "admin/recursos/{$id}", [], $token)->assertOk();
        $this->api('DELETE', "admin/recursos/{$id}", [], $token)->assertStatus(409); // ya inactivo

        $this->api('DELETE', "admin/recursos/{$id}?definitivo=true", [], $token)->assertOk()->assertJsonPath('data.eliminacion', 'definitiva');
        $this->assertDatabaseMissing('productos', ['id' => $id]);

        // Un recurso referenciado en órdenes/carritos no se puede borrar físicamente.
        $referenciado = Producto::whereHas('ordenDetalles')->orWhereHas('carritoItems')->orWhereHas('listaDeseos')->first();
        if ($referenciado) {
            $this->api('DELETE', "admin/recursos/{$referenciado->id}?definitivo=true", [], $token)->assertStatus(409);
        }
    }

    public function test_recurso_inexistente_devuelve_404(): void
    {
        $token = $this->login();

        $this->api('GET', 'admin/recursos/999999', [], $token)->assertStatus(404)->assertJsonStructure(['message']);
        $this->api('PUT', 'admin/recursos/999999', $this->datosRecurso(), $token)->assertStatus(404);
        $this->api('PATCH', 'admin/recursos/999999', ['stock' => 1], $token)->assertStatus(404);
        $this->api('DELETE', 'admin/recursos/999999', [], $token)->assertStatus(404);
        $this->api('GET', 'admin/recursos/abc', [], $token)->assertStatus(404);
    }

    // ============================================================ 3. VALIDACIONES (FormRequest)

    public function test_datos_invalidos_devuelven_422_con_detalle(): void
    {
        $token = $this->login();

        $this->api('POST', 'admin/recursos', ['nombre' => 'ab', 'precio' => -5, 'stock' => 'muchos', 'condicion' => 'roto', 'categoria_id' => 99999], $token)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nombre', 'precio', 'stock', 'condicion', 'categoria_id']);

        $this->api('POST', 'admin/recursos', [], $token)->assertStatus(422)
            ->assertJsonValidationErrors(['nombre', 'categoria_id', 'condicion', 'precio', 'stock']);

        $this->api('POST', 'admin/recursos', $this->datosRecurso(['precio' => 9999999, 'stock' => 5000]), $token)
            ->assertStatus(422)->assertJsonValidationErrors(['precio', 'stock']);
    }

    public function test_unicidad_de_nombre_por_categoria(): void
    {
        $token = $this->login();
        $this->crearRecurso($token);

        $this->api('POST', 'admin/recursos', $this->datosRecurso(), $token)
            ->assertStatus(422)->assertJsonValidationErrors(['nombre']);

        // Mismo nombre en otra categoría sí se permite.
        $otra = Categoria::create(['nombre' => 'Categoria distinta']);
        $this->api('POST', 'admin/recursos', $this->datosRecurso(['categoria_id' => $otra->id]), $token)->assertStatus(201);
    }

    public function test_update_no_choca_consigo_mismo_pero_si_con_otro(): void
    {
        $token = $this->login();
        $a = $this->crearRecurso($token);
        $b = $this->crearRecurso($token, ['nombre' => 'Otro recurso']);

        $this->api('PUT', "admin/recursos/{$a}", $this->datosRecurso(['stock' => 2]), $token)->assertOk();
        $this->api('PATCH', "admin/recursos/{$b}", ['nombre' => 'Chamarra de mezclilla'], $token)
            ->assertStatus(422)->assertJsonValidationErrors(['nombre']);
    }

    public function test_put_exige_todos_los_campos(): void
    {
        $token = $this->login();
        $id = $this->crearRecurso($token);

        $this->api('PUT', "admin/recursos/{$id}", ['nombre' => 'Solo nombre'], $token)
            ->assertStatus(422)->assertJsonValidationErrors(['categoria_id', 'condicion', 'precio', 'stock']);
    }

    public function test_patch_no_sobrescribe_con_nulos_ni_acepta_cuerpo_vacio(): void
    {
        $token = $this->login();
        $id = $this->crearRecurso($token);

        $this->api('PATCH', "admin/recursos/{$id}", ['nombre' => null], $token)->assertStatus(422)->assertJsonValidationErrors(['nombre']);
        $this->api('PATCH', "admin/recursos/{$id}", ['precio' => null, 'stock' => null], $token)->assertStatus(422)->assertJsonValidationErrors(['precio', 'stock']);
        $this->api('PATCH', "admin/recursos/{$id}", [], $token)->assertStatus(422)->assertJsonValidationErrors(['cuerpo']);

        $this->assertSame('Chamarra de mezclilla', Producto::find($id)->nombre); // intacto
    }

    public function test_campos_protegidos_no_se_asignan_masivamente(): void
    {
        $token = $this->login();
        $id = $this->api('POST', 'admin/recursos', $this->datosRecurso(['usuario_id' => 99999, 'id' => 777, 'deleted_at' => now()->toDateTimeString()]), $token)
            ->assertStatus(201)->json('data.id');

        $this->assertNotSame(777, $id);
        $producto = Producto::withTrashed()->find($id);
        $this->assertNull($producto->deleted_at);
        $this->assertNotSame(99999, $producto->usuario_id);
    }

    // ============================================================ 4. AUTORIZACIÓN (Policy + Gate)

    public function test_nivel1_solo_consulta(): void
    {
        $admin = $this->login();
        $id = $this->crearRecurso($admin);
        $n1 = $this->login('nivel1', 'nivel1123');

        $this->api('GET', 'admin/recursos', [], $n1)->assertOk();
        $this->api('GET', "admin/recursos/{$id}", [], $n1)->assertOk();
        $this->api('POST', 'admin/recursos', $this->datosRecurso(['nombre' => 'Nuevo']), $n1)->assertStatus(403);
        $this->api('PUT', "admin/recursos/{$id}", $this->datosRecurso(), $n1)->assertStatus(403);
        $this->api('PATCH', "admin/recursos/{$id}", ['stock' => 1], $n1)->assertStatus(403);
        $this->api('DELETE', "admin/recursos/{$id}", [], $n1)->assertStatus(403);
    }

    public function test_la_autorizacion_se_evalua_antes_que_la_validacion(): void
    {
        $n1 = $this->login('nivel1', 'nivel1123');

        // Datos inválidos + sin privilegios: debe ser 403 (no revela reglas de validación a quien no puede crear).
        $this->api('POST', 'admin/recursos', ['nombre' => ''], $n1)->assertStatus(403);
    }

    public function test_nivel2_crea_y_edita_pero_no_elimina_ni_cambia_estado(): void
    {
        $n2 = $this->login('nivel2', 'nivel2123');
        $id = $this->crearRecurso($n2);

        $this->api('PATCH', "admin/recursos/{$id}", ['stock' => 3], $n2)->assertOk();
        $this->api('PATCH', "admin/recursos/{$id}", ['activo' => false], $n2)->assertStatus(403);
        $this->api('DELETE', "admin/recursos/{$id}", [], $n2)->assertStatus(403);
        $this->api('DELETE', "admin/recursos/{$id}?definitivo=true", [], $n2)->assertStatus(403);
        $this->assertNotSoftDeleted('productos', ['id' => $id]);
    }

    public function test_el_nivel_se_lee_de_la_base_de_datos_en_cada_peticion(): void
    {
        $token = $this->login();
        $this->api('POST', 'admin/recursos', $this->datosRecurso(), $token)->assertStatus(201);

        // Se degrada al administrador después de emitir el token.
        Administrador::where('usuario', 'admin')->update(['nivel' => 1]);

        $this->api('POST', 'admin/recursos', $this->datosRecurso(['nombre' => 'Otro']), $token)->assertStatus(403);
    }

    public function test_admin_desactivado_pierde_acceso_con_su_token(): void
    {
        $token = $this->login('nivel2', 'nivel2123');
        $this->api('GET', 'admin/recursos', [], $token)->assertOk();

        Administrador::where('usuario', 'nivel2')->update(['activo' => false]);

        $this->api('GET', 'admin/recursos', [], $token)->assertStatus(403);
    }

    // ============================================================ 5. USUARIOS

    public function test_listado_de_usuarios_incluye_clientes_y_administradores(): void
    {
        $token = $this->login();

        $r = $this->api('GET', 'admin/usuarios?per_page=50', [], $token)->assertOk();
        $tipos = collect($r->json('data'))->pluck('tipo')->unique()->sort()->values()->all();

        $this->assertSame(['administrador', 'cliente'], $tipos);
        $r->assertJsonStructure(['data' => [['id', 'tipo', 'nombre', 'identificador', 'rol', 'nivel', 'activo', 'estado']], 'meta']);
        foreach (['password', 'password_hash', 'social_id', 'remember_token'] as $secreto) {
            $this->assertStringNotContainsString($secreto, $r->getContent());
        }

        $soloAdmins = $this->api('GET', 'admin/usuarios?tipo=administrador&per_page=50', [], $token)->json('data');
        $this->assertSame(['administrador'], collect($soloAdmins)->pluck('tipo')->unique()->all());

        $inactivos = $this->api('GET', 'admin/usuarios?activo=false&per_page=50', [], $token)->json('data');
        $this->assertSame(['inactivo'], collect($inactivos)->pluck('estado')->unique()->all());
        $this->assertContains('inactivo', collect($inactivos)->pluck('identificador')->all());
    }

    public function test_desactivar_un_cliente_revoca_sus_tokens_y_le_impide_entrar(): void
    {
        $token = $this->login();
        $cliente = Usuario::where('email', 'cliente1@example.com')->firstOrFail();
        $tokenCliente = $this->api('POST', 'cliente/login', ['email' => 'cliente1@example.com', 'password' => 'cliente123'])->json('data.token');
        $this->api('GET', 'cliente/perfil', [], $tokenCliente)->assertOk();

        $this->api('PATCH', "admin/usuarios/{$cliente->id}/estado", ['activo' => false], $token)
            ->assertOk()->assertJsonPath('data.estado', 'inactivo')->assertJsonPath('data.tipo', 'cliente');

        $this->api('GET', 'cliente/perfil', [], $tokenCliente)->assertStatus(401); // token revocado
        $this->api('POST', 'cliente/login', ['email' => 'cliente1@example.com', 'password' => 'cliente123'])->assertStatus(403);

        $this->api('PATCH', "admin/usuarios/{$cliente->id}/estado", ['activo' => true], $token)->assertOk();
        $this->api('POST', 'cliente/login', ['email' => 'cliente1@example.com', 'password' => 'cliente123'])->assertOk();
    }

    public function test_cambiar_rol_de_un_cliente_y_nivel_de_un_administrador(): void
    {
        $token = $this->login();
        $cliente = Usuario::where('email', 'cliente1@example.com')->firstOrFail();
        $rol = Rol::where('nombre', '!=', 'cliente')->first();
        $n1 = Administrador::where('usuario', 'nivel1')->firstOrFail();

        if ($rol) {
            $this->api('PATCH', "admin/usuarios/{$cliente->id}/estado", ['rol_id' => $rol->id], $token)
                ->assertOk()->assertJsonPath('data.rol_id', $rol->id);
        }

        $this->api('PATCH', "admin/usuarios/{$n1->id}/estado", ['tipo' => 'administrador', 'nivel' => 2], $token)
            ->assertOk()->assertJsonPath('data.nivel', 2)->assertJsonPath('data.tipo', 'administrador');
        $this->assertSame(2, $n1->fresh()->nivel);
    }

    public function test_desactivar_administrador_revoca_sus_tokens(): void
    {
        $token = $this->login();
        $n2 = Administrador::where('usuario', 'nivel2')->firstOrFail();
        $tokenN2 = $this->login('nivel2', 'nivel2123');

        $this->api('PATCH', "admin/usuarios/{$n2->id}/estado", ['tipo' => 'administrador', 'activo' => false], $token)->assertOk();

        $this->api('GET', 'admin/recursos', [], $tokenN2)->assertStatus(401);
        $this->api('POST', 'admin/login', ['usuario' => 'nivel2', 'password' => 'nivel2123'])->assertStatus(403);
    }

    public function test_solo_nivel3_cambia_estados_y_nadie_se_modifica_a_si_mismo(): void
    {
        $n2 = $this->login('nivel2', 'nivel2123');
        $cliente = Usuario::where('email', 'cliente1@example.com')->firstOrFail();

        $this->api('PATCH', "admin/usuarios/{$cliente->id}/estado", ['activo' => false], $n2)->assertStatus(403);

        $admin = Administrador::where('usuario', 'admin')->firstOrFail();
        $token = $this->login();
        $this->api('PATCH', "admin/usuarios/{$admin->id}/estado", ['tipo' => 'administrador', 'activo' => false], $token)->assertStatus(403);
        $this->api('PATCH', "admin/usuarios/{$admin->id}/estado", ['tipo' => 'administrador', 'nivel' => 1], $token)->assertStatus(403);
        $this->assertTrue($admin->fresh()->activo);
    }

    public function test_estado_de_usuario_valida_entrada_y_existencia(): void
    {
        $token = $this->login();
        $cliente = Usuario::where('email', 'cliente1@example.com')->firstOrFail();

        $this->api('PATCH', "admin/usuarios/{$cliente->id}/estado", [], $token)->assertStatus(422)->assertJsonValidationErrors(['cuerpo']);
        $this->api('PATCH', "admin/usuarios/{$cliente->id}/estado", ['activo' => 'quizas'], $token)->assertStatus(422)->assertJsonValidationErrors(['activo']);
        $this->api('PATCH', "admin/usuarios/{$cliente->id}/estado", ['rol_id' => 99999], $token)->assertStatus(422)->assertJsonValidationErrors(['rol_id']);
        $this->api('PATCH', "admin/usuarios/{$cliente->id}/estado", ['nivel' => 2], $token)->assertStatus(422)->assertJsonValidationErrors(['nivel']);
        $this->api('PATCH', "admin/usuarios/{$cliente->id}/estado", ['tipo' => 'administrador', 'nivel' => 9], $token)->assertStatus(422)->assertJsonValidationErrors(['nivel']);
        $this->api('PATCH', "admin/usuarios/{$cliente->id}/estado", ['tipo' => 'otro', 'activo' => true], $token)->assertStatus(422)->assertJsonValidationErrors(['tipo']);
        $this->api('PATCH', 'admin/usuarios/999999/estado', ['activo' => false], $token)->assertStatus(404);
    }
}
