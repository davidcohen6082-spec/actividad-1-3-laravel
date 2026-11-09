<?php

namespace Tests\Feature;

use App\Models\Orden;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Actividad 4.4 · Área cliente (Sanctum + propiedad de datos con Policy y Eloquent Scope).
 */
class ClientApiV44Test extends TestCase
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

        return $this->json($metodo, "/api/v1/client/{$uri}", $datos, $token ? ['Authorization' => "Bearer {$token}"] : []);
    }

    /** Registra un cliente nuevo y devuelve su token. */
    private function registrar(string $letra): string
    {
        return $this->api('POST', 'register', [
            'nombre' => "Cliente {$letra}", 'email' => "cliente.{$letra}@example.com",
            'password' => 'clave12345', 'password_confirmation' => 'clave12345',
        ])->assertStatus(201)->json('data.token');
    }

    private function login(string $letra): string
    {
        return $this->api('POST', 'login', ['email' => "cliente.{$letra}@example.com", 'password' => 'clave12345'])->assertOk()->json('data.token');
    }

    private function producto(int $stock = 10): Producto
    {
        return Producto::where('stock', '>=', $stock)->firstOrFail();
    }

    private function crearRegistro(string $token, array $extra = []): int
    {
        $p = $this->producto();

        return $this->api('POST', 'mis-registros', array_merge(['items' => [['producto_id' => $p->id, 'cantidad' => 2]], 'notas' => 'Entrega en la tarde'], $extra), $token)
            ->assertStatus(201)->json('data.id');
    }

    // ========================================================= 1. REGISTRO / LOGIN / LOGOUT

    public function test_registro_asigna_rol_cliente_y_emite_client_token(): void
    {
        $r = $this->api('POST', 'register', [
            'nombre' => 'Cliente A', 'email' => 'a@example.com', 'password' => 'clave12345', 'password_confirmation' => 'clave12345',
            'rol_id' => 1, 'rol' => 'administrador', // intento de elevarse: se ignora
        ])->assertStatus(201);

        $this->assertSame('Bearer', $r->json('data.token_type'));
        $this->assertSame('cliente', $r->json('data.cliente.rol'));
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'client-token', 'tokenable_type' => Usuario::class]);
        $this->assertTrue(Usuario::where('email', 'a@example.com')->first()->esCliente());
        foreach (['password', 'password_hash', 'social_id'] as $secreto) {
            $this->assertStringNotContainsString($secreto, $r->getContent());
        }
    }

    public function test_registro_invalido_devuelve_422(): void
    {
        $this->registrar('a');

        $this->api('POST', 'register', [])->assertStatus(422)->assertJsonValidationErrors(['nombre', 'email', 'password']);
        $this->api('POST', 'register', ['nombre' => 'Ab', 'email' => 'no-es-correo', 'password' => '123', 'password_confirmation' => '999'])
            ->assertStatus(422)->assertJsonValidationErrors(['nombre', 'email', 'password']);
        $this->api('POST', 'register', ['nombre' => 'Otro A', 'email' => 'cliente.a@example.com', 'password' => 'clave12345', 'password_confirmation' => 'clave12345'])
            ->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_login_de_dos_clientes_y_rechazos(): void
    {
        $this->registrar('a');
        $this->registrar('b');

        $this->assertNotSame($this->login('a'), $this->login('b'));
        $this->api('POST', 'login', ['email' => 'cliente.a@example.com', 'password' => 'mala'])->assertStatus(401);
        $this->api('POST', 'login', ['email' => 'nadie@example.com', 'password' => 'clave12345'])->assertStatus(401);
        $this->api('POST', 'login', [])->assertStatus(422)->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_un_administrador_no_inicia_sesion_como_cliente_ni_usa_su_token(): void
    {
        $this->api('POST', 'login', ['email' => 'admin', 'password' => 'admin1234'])->assertStatus(422);

        $admin = $this->json('POST', '/api/v1/admin/login', ['usuario' => 'admin', 'password' => 'admin1234'])->json('data.token');
        $this->api('GET', 'perfil', [], $admin)->assertStatus(403);
        $this->api('GET', 'mis-registros', [], $admin)->assertStatus(403);
    }

    public function test_cliente_desactivado_no_inicia_sesion(): void
    {
        $this->registrar('a');
        Usuario::where('email', 'cliente.a@example.com')->update(['activo' => false]);

        $this->api('POST', 'login', ['email' => 'cliente.a@example.com', 'password' => 'clave12345'])->assertStatus(403);
    }

    public function test_rutas_privadas_sin_token_devuelven_401(): void
    {
        foreach ([['GET', 'perfil'], ['PUT', 'perfil'], ['PATCH', 'perfil'], ['GET', 'mis-registros'], ['POST', 'mis-registros'],
                  ['GET', 'mis-registros/1'], ['PUT', 'mis-registros/1'], ['DELETE', 'mis-registros/1'], ['POST', 'logout']] as [$m, $u]) {
            $this->api($m, $u)->assertStatus(401)->assertJsonStructure(['message']);
        }

        $this->api('GET', 'perfil', [], 'token-falso')->assertStatus(401);
    }

    public function test_token_expirado_devuelve_401(): void
    {
        $token = $this->registrar('a');
        $this->api('GET', 'perfil', [], $token)->assertOk();

        Carbon::setTestNow(now()->addMinutes(config('sanctum.expiration') + 1));
        $this->api('GET', 'perfil', [], $token)->assertStatus(401);
        Carbon::setTestNow();
    }

    public function test_logout_revoca_el_token(): void
    {
        $token = $this->registrar('a');

        $this->api('POST', 'logout', [], $token)->assertOk();
        $this->api('GET', 'perfil', [], $token)->assertStatus(401);
        $this->api('GET', 'mis-registros', [], $token)->assertStatus(401);
    }

    // ========================================================= 2. CATÁLOGO PÚBLICO

    public function test_catalogo_es_publico(): void
    {
        $r = $this->api('GET', 'catalogo?per_page=3')->assertOk()->assertJsonStructure(['data', 'links', 'meta']);
        $id = $r->json('data.0.id');

        $this->api('GET', "catalogo/{$id}")->assertOk()->assertJsonPath('data.id', $id);
        $this->api('GET', 'catalogo/999999')->assertStatus(404);
        $this->assertStringNotContainsString('usuario_id', $r->getContent());
    }

    // ========================================================= 3. PERFIL

    public function test_perfil_consulta_y_actualizacion_put_patch(): void
    {
        $token = $this->registrar('a');

        $this->api('GET', 'perfil', [], $token)->assertOk()->assertJsonPath('data.email', 'cliente.a@example.com')->assertJsonPath('data.rol', 'cliente');
        $this->api('PATCH', 'perfil', ['nombre' => 'Nombre Nuevo'], $token)->assertOk()->assertJsonPath('data.nombre', 'Nombre Nuevo')->assertJsonPath('data.email', 'cliente.a@example.com');
        $this->api('PUT', 'perfil', ['nombre' => 'Nombre Total', 'email' => 'nuevo.a@example.com'], $token)->assertOk()->assertJsonPath('data.email', 'nuevo.a@example.com');
    }

    public function test_perfil_validaciones_y_campos_protegidos(): void
    {
        $token = $this->registrar('a');
        $this->registrar('b');

        $this->api('PUT', 'perfil', ['nombre' => 'Solo nombre'], $token)->assertStatus(422)->assertJsonValidationErrors(['email']);
        $this->api('PATCH', 'perfil', ['email' => 'cliente.b@example.com'], $token)->assertStatus(422)->assertJsonValidationErrors(['email']);
        $this->api('PATCH', 'perfil', ['email' => 'no-valido'], $token)->assertStatus(422)->assertJsonValidationErrors(['email']);
        $this->api('PATCH', 'perfil', ['nombre' => null], $token)->assertStatus(422)->assertJsonValidationErrors(['nombre']);

        // Intento de cambiarse el rol: se ignora.
        $this->api('PATCH', 'perfil', ['nombre' => 'Sigo cliente', 'rol_id' => 1], $token)->assertOk()->assertJsonPath('data.rol', 'cliente');
    }

    public function test_cambio_de_contrasena_exige_la_actual(): void
    {
        $token = $this->registrar('a');
        $nueva = ['password' => 'otraclave99', 'password_confirmation' => 'otraclave99'];

        $this->api('PATCH', 'perfil', $nueva, $token)->assertStatus(422)->assertJsonValidationErrors(['password_actual']);
        $this->api('PATCH', 'perfil', $nueva + ['password_actual' => 'incorrecta'], $token)->assertStatus(422)->assertJsonValidationErrors(['password_actual']);
        $this->api('PATCH', 'perfil', $nueva + ['password_actual' => 'clave12345'], $token)->assertOk();
        $this->api('POST', 'login', ['email' => 'cliente.a@example.com', 'password' => 'otraclave99'])->assertOk();
    }

    // ========================================================= 4. MIS REGISTROS (CRUD PROPIO)

    public function test_crear_registro_propio_y_calculo_en_servidor(): void
    {
        $token = $this->registrar('a');
        $p = $this->producto();
        $stock = $p->stock;

        $r = $this->api('POST', 'mis-registros', ['items' => [['producto_id' => $p->id, 'cantidad' => 3]], 'notas' => 'Nota'], $token)
            ->assertStatus(201)->assertJsonPath('data.estado', 'pendiente')->assertJsonPath('data.cancelable', true)
            ->assertJsonPath('data.items.0.cantidad', 3);

        $this->assertEquals(round($p->precio * 3, 2), $r->json('data.total'));
        $this->assertSame($stock - 3, $p->fresh()->stock);
    }

    public function test_el_propietario_lo_asigna_el_servidor_aunque_el_cliente_lo_inyecte(): void
    {
        $tokenA = $this->registrar('a');
        $b = Usuario::where('email', '!=', 'cliente.a@example.com')->first();
        $a = Usuario::where('email', 'cliente.a@example.com')->first();
        $p = $this->producto();

        $id = $this->api('POST', 'mis-registros', [
            'items' => [['producto_id' => $p->id, 'cantidad' => 1]],
            'usuario_id' => $b->id, 'user_id' => $b->id, 'precio_unitario' => 0.01, 'total' => 0.01, 'estado' => 'completada',
        ], $tokenA)->assertStatus(201)->json('data.id');

        $orden = Orden::find($id);
        $this->assertSame($a->id, $orden->usuario_id);        // dueño = usuario autenticado
        $this->assertSame('pendiente', $orden->estado);       // estado no inyectable
        $this->assertGreaterThan(0.01, (float) $orden->total); // precio sale de la base de datos
    }

    public function test_la_lista_solo_contiene_registros_propios(): void
    {
        $a = $this->registrar('a');
        $b = $this->registrar('b');
        $idA = $this->crearRegistro($a);

        $lista = $this->api('GET', 'mis-registros', [], $a)->assertOk();
        $this->assertSame([$idA], collect($lista->json('data'))->pluck('id')->all());
        $this->assertSame(1, $lista->json('meta.total'));

        $this->assertSame([], $this->api('GET', 'mis-registros', [], $b)->assertOk()->json('data')); // B no ve nada de A

        // Los pedidos de otros clientes sembrados no aparecen para A.
        $this->assertGreaterThan(1, Orden::count());
    }

    public function test_cliente_b_no_puede_ver_ni_modificar_ni_cancelar_el_registro_de_a_403(): void
    {
        $a = $this->registrar('a');
        $b = $this->registrar('b');
        $id = $this->crearRegistro($a);

        $this->api('GET', "mis-registros/{$id}", [], $b)->assertStatus(403)->assertJsonStructure(['message']);
        $this->api('PUT', "mis-registros/{$id}", ['notas' => 'robado'], $b)->assertStatus(403);
        $this->api('PATCH', "mis-registros/{$id}", ['notas' => 'robado'], $b)->assertStatus(403);
        $this->api('DELETE', "mis-registros/{$id}", [], $b)->assertStatus(403);

        $orden = Orden::find($id);
        $this->assertSame('Entrega en la tarde', $orden->notas);
        $this->assertSame('pendiente', $orden->estado);
    }

    public function test_403_se_evalua_antes_que_422_en_registros_ajenos(): void
    {
        $a = $this->registrar('a');
        $b = $this->registrar('b');
        $id = $this->crearRegistro($a);

        $this->api('PUT', "mis-registros/{$id}", ['items' => 'x', 'notas' => str_repeat('x', 999)], $b)->assertStatus(403);
    }

    public function test_los_registros_sembrados_de_otros_clientes_tambien_dan_403(): void
    {
        $a = $this->registrar('a');
        $ajeno = Orden::firstOrFail();

        $this->api('GET', "mis-registros/{$ajeno->id}", [], $a)->assertStatus(403);
        $this->api('DELETE', "mis-registros/{$ajeno->id}", [], $a)->assertStatus(403);
        $this->assertNotSame('cancelada', $ajeno->fresh()->estado);
    }

    public function test_propietario_consulta_modifica_y_cancela(): void
    {
        $a = $this->registrar('a');
        $id = $this->crearRegistro($a);
        $p = Orden::find($id)->detalles->first()->producto;
        $stockTrasCrear = $p->fresh()->stock;

        $this->api('GET', "mis-registros/{$id}", [], $a)->assertOk()->assertJsonPath('data.id', $id);
        $this->api('PUT', "mis-registros/{$id}", ['notas' => 'Cambio de horario'], $a)->assertOk()->assertJsonPath('data.notas', 'Cambio de horario');
        $this->api('PATCH', "mis-registros/{$id}", ['notas' => null], $a)->assertOk()->assertJsonPath('data.notas', null);

        $this->api('DELETE', "mis-registros/{$id}", [], $a)->assertOk()->assertJsonPath('data.estado', 'cancelada')->assertJsonPath('data.cancelable', false);
        $this->assertSame($stockTrasCrear + 2, $p->fresh()->stock); // el stock se devuelve
    }

    public function test_registro_cancelado_ya_no_se_modifica_ni_se_vuelve_a_cancelar(): void
    {
        $a = $this->registrar('a');
        $id = $this->crearRegistro($a);
        $this->api('DELETE', "mis-registros/{$id}", [], $a)->assertOk();

        $this->api('DELETE', "mis-registros/{$id}", [], $a)->assertStatus(403)->assertJsonPath('message', 'Este registro ya no es cancelable (estado actual: cancelada).');
        $this->api('PATCH', "mis-registros/{$id}", ['notas' => 'tarde'], $a)->assertStatus(409);
        $this->api('GET', "mis-registros/{$id}", [], $a)->assertOk()->assertJsonPath('data.estado', 'cancelada');
    }

    public function test_registro_inexistente_devuelve_404(): void
    {
        $a = $this->registrar('a');

        foreach ([['GET'], ['PUT', ['notas' => 'x']], ['PATCH', ['notas' => 'x']], ['DELETE']] as $p) {
            $this->api($p[0], 'mis-registros/999999', $p[1] ?? [], $a)->assertStatus(404)->assertJsonStructure(['message']);
        }
        $this->api('GET', 'mis-registros/abc', [], $a)->assertStatus(404);
    }

    public function test_validaciones_al_crear_registro_422(): void
    {
        $a = $this->registrar('a');
        $p = $this->producto();

        $this->api('POST', 'mis-registros', [], $a)->assertStatus(422)->assertJsonValidationErrors(['items']);
        $this->api('POST', 'mis-registros', ['items' => []], $a)->assertStatus(422)->assertJsonValidationErrors(['items']);
        $this->api('POST', 'mis-registros', ['items' => [['producto_id' => 999999, 'cantidad' => 1]]], $a)->assertStatus(422)->assertJsonValidationErrors(['items.0.producto_id']);
        $this->api('POST', 'mis-registros', ['items' => [['producto_id' => $p->id, 'cantidad' => 0]]], $a)->assertStatus(422)->assertJsonValidationErrors(['items.0.cantidad']);
        $this->api('POST', 'mis-registros', ['items' => [['producto_id' => $p->id, 'cantidad' => -3]]], $a)->assertStatus(422)->assertJsonValidationErrors(['items.0.cantidad']);
        $this->api('POST', 'mis-registros', ['items' => [['producto_id' => $p->id, 'cantidad' => 'muchos']]], $a)->assertStatus(422)->assertJsonValidationErrors(['items.0.cantidad']);
        $this->api('POST', 'mis-registros', ['items' => [['producto_id' => $p->id, 'cantidad' => 1], ['producto_id' => $p->id, 'cantidad' => 1]]], $a)->assertStatus(422);
        $this->api('POST', 'mis-registros', ['items' => [['producto_id' => $p->id, 'cantidad' => $p->stock + 500]]], $a)->assertStatus(422);
        $this->api('POST', 'mis-registros', ['items' => [['producto_id' => $p->id, 'cantidad' => 1]], 'notas' => str_repeat('x', 300)], $a)->assertStatus(422)->assertJsonValidationErrors(['notas']);
    }

    public function test_validaciones_al_modificar_registro_422(): void
    {
        $a = $this->registrar('a');
        $id = $this->crearRegistro($a);

        $this->api('PUT', "mis-registros/{$id}", [], $a)->assertStatus(422)->assertJsonValidationErrors(['notas']);
        $this->api('PATCH', "mis-registros/{$id}", [], $a)->assertStatus(422)->assertJsonValidationErrors(['cuerpo']);
        $this->api('PATCH', "mis-registros/{$id}", ['items' => [['producto_id' => 1, 'cantidad' => 1]]], $a)->assertStatus(422)->assertJsonValidationErrors(['items']);
        $this->api('PATCH', "mis-registros/{$id}", ['notas' => str_repeat('x', 300)], $a)->assertStatus(422)->assertJsonValidationErrors(['notas']);
    }

    public function test_las_respuestas_no_exponen_campos_internos(): void
    {
        $a = $this->registrar('a');
        $id = $this->crearRegistro($a);

        $contenido = $this->api('GET', "mis-registros/{$id}", [], $a)->assertOk()->getContent()
            .$this->api('GET', 'mis-registros', [], $a)->getContent();

        foreach (['usuario_id', 'user_id', 'created_at', 'updated_at', 'deleted_at', 'password'] as $campo) {
            $this->assertStringNotContainsString("\"{$campo}\"", $contenido);
        }
    }

    public function test_flujo_completo_a_y_b_de_la_actividad(): void
    {
        $a = $this->registrar('a');
        $b = $this->registrar('b');
        $a = $this->login('a');
        $b = $this->login('b');

        $id = $this->crearRegistro($a);
        $this->assertCount(1, $this->api('GET', 'mis-registros', [], $a)->json('data'));

        $this->api('GET', "mis-registros/{$id}", [], $b)->assertStatus(403);
        $this->api('PUT', "mis-registros/{$id}", ['notas' => 'x'], $b)->assertStatus(403);

        $this->api('PUT', "mis-registros/{$id}", ['notas' => 'ok'], $a)->assertOk();
        $this->api('DELETE', "mis-registros/{$id}", [], $a)->assertOk();

        $this->api('POST', 'logout', [], $a)->assertOk();
        $this->api('GET', 'mis-registros', [], $a)->assertStatus(401);
    }
}
