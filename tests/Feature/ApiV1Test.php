<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Categoria;
use App\Models\ListaDeseo;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Pruebas de la API v1 (áreas administrativa y cliente).
 * Cuentas de prueba creadas por los seeders:
 *   admin / admin1234 (nivel 3) · nivel2 / nivel2123 · nivel1 / nivel1123 · inactivo / inactivo1234
 *   cliente1@example.com y cliente2@example.com / cliente123
 */
class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    // ------------------------------------------------------------------ utilidades

    private function api(string $metodo, string $uri, array $datos = [], ?string $token = null): TestResponse
    {
        // Cada llamada simula una solicitud independiente (el guardia no recuerda al usuario anterior).
        $this->app['auth']->forgetGuards();

        return $this->json($metodo, "/api/v1/{$uri}", $datos, $token ? ['Authorization' => "Bearer {$token}"] : []);
    }

    private function tokenAdmin(string $usuario = 'admin', string $password = 'admin1234'): string
    {
        return $this->api('POST', 'admin/login', ['usuario' => $usuario, 'password' => $password])
            ->assertOk()->json('data.token');
    }

    private function tokenCliente(string $email = 'cliente1@example.com', string $password = 'cliente123'): string
    {
        return $this->api('POST', 'cliente/login', ['email' => $email, 'password' => $password])
            ->assertOk()->json('data.token');
    }

    private function cliente(string $email = 'cliente1@example.com'): Usuario
    {
        return Usuario::where('email', $email)->firstOrFail();
    }

    private function productoConStock(int $stock = 10): Producto
    {
        $producto = Producto::orderBy('id')->firstOrFail();
        $producto->update(['stock' => $stock]);

        return $producto->fresh();
    }

    // ============================================================ 1. SIN AUTENTICACIÓN

    public function test_usuario_no_autenticado_recibe_401_en_rutas_protegidas(): void
    {
        foreach (['admin/roles', 'admin/perfil', 'cliente/perfil', 'cliente/pedidos'] as $ruta) {
            // Sin encabezado Accept: la API igual responde JSON, nunca redirige.
            $this->app['auth']->forgetGuards();
            $respuesta = $this->call('GET', "/api/v1/{$ruta}");

            $respuesta->assertStatus(401)->assertJsonStructure(['message']);
        }
    }

    public function test_token_invalido_recibe_401(): void
    {
        $this->api('GET', 'admin/roles', [], 'token-inventado')->assertStatus(401);
    }

    // =========================================================== 2. AUTENTICACIÓN ADMIN

    public function test_admin_inicia_sesion_y_recibe_token(): void
    {
        $this->api('POST', 'admin/login', ['usuario' => 'admin', 'password' => 'admin1234'])
            ->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.administrador.nivel', 3)
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_admin_con_credenciales_incorrectas_recibe_401(): void
    {
        $this->api('POST', 'admin/login', ['usuario' => 'admin', 'password' => 'mala'])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Usuario o contraseña incorrectos.');
    }

    public function test_admin_deshabilitado_recibe_403_al_iniciar_sesion(): void
    {
        $this->api('POST', 'admin/login', ['usuario' => 'inactivo', 'password' => 'inactivo1234'])
            ->assertStatus(403);
    }

    public function test_login_sin_datos_devuelve_422(): void
    {
        $this->api('POST', 'admin/login', [])->assertStatus(422)->assertJsonValidationErrors(['usuario', 'password']);
        $this->api('POST', 'cliente/login', [])->assertStatus(422)->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_admin_consulta_perfil_y_logout_revoca_el_token(): void
    {
        $token = $this->tokenAdmin();

        $this->api('GET', 'admin/perfil', [], $token)->assertOk()->assertJsonPath('data.usuario', 'admin');
        $this->api('POST', 'admin/logout', [], $token)->assertOk();
        $this->api('GET', 'admin/perfil', [], $token)->assertStatus(401);
    }

    public function test_token_de_admin_deshabilitado_posteriormente_deja_de_servir(): void
    {
        $admin = Administrador::where('usuario', 'nivel1')->firstOrFail();
        $token = $admin->createToken('prueba', ['admin'])->plainTextToken;

        $this->api('GET', 'admin/roles', [], $token)->assertOk();

        $admin->update(['activo' => false]);

        $this->api('GET', 'admin/roles', [], $token)->assertStatus(403);
    }

    // ===================================================== 3. AUTORIZACIÓN POR NIVELES

    public function test_nivel1_solo_puede_consultar(): void
    {
        $token = $this->tokenAdmin('nivel1', 'nivel1123');
        $rol = Rol::first();

        $this->api('GET', 'admin/roles', [], $token)->assertOk();
        $this->api('GET', "admin/roles/{$rol->id}", [], $token)->assertOk();
        $this->api('POST', 'admin/roles', ['nombre' => 'Nuevo rol'], $token)->assertStatus(403);
        $this->api('PATCH', "admin/roles/{$rol->id}", ['nombre' => 'Editado'], $token)->assertStatus(403);
        $this->api('DELETE', "admin/roles/{$rol->id}", [], $token)->assertStatus(403);
        $this->api('GET', 'admin/reportes/resumen', [], $token)->assertStatus(403);
    }

    public function test_nivel2_crea_y_edita_pero_no_elimina(): void
    {
        $token = $this->tokenAdmin('nivel2', 'nivel2123');

        $id = $this->api('POST', 'admin/roles', ['nombre' => 'Rol de prueba'], $token)
            ->assertStatus(201)->json('data.id');

        $this->api('PATCH', "admin/roles/{$id}", ['nombre' => 'Rol editado'], $token)
            ->assertOk()->assertJsonPath('data.nombre', 'Rol editado');

        $this->api('DELETE', "admin/roles/{$id}", [], $token)->assertStatus(403);
        $this->api('GET', 'admin/reportes/resumen', [], $token)->assertOk()->assertJsonStructure(['data' => ['pedidos_por_estado']]);
    }

    public function test_nivel3_elimina_restaura_y_borra_definitivamente(): void
    {
        $token = $this->tokenAdmin();

        $id = $this->api('POST', 'admin/roles', ['nombre' => 'Rol temporal'], $token)->assertStatus(201)->json('data.id');

        $this->api('DELETE', "admin/roles/{$id}", [], $token)->assertStatus(204);
        $this->api('GET', "admin/roles/{$id}", [], $token)->assertStatus(404);
        $this->api('GET', 'admin/roles/papelera', [], $token)->assertOk()->assertJsonFragment(['nombre' => 'Rol temporal']);

        $this->api('PATCH', "admin/roles/papelera/{$id}", [], $token)->assertOk();
        $this->api('GET', "admin/roles/{$id}", [], $token)->assertOk();

        $this->api('DELETE', "admin/roles/{$id}", [], $token)->assertStatus(204);
        $this->api('DELETE', "admin/roles/papelera/{$id}", [], $token)->assertStatus(204);
        $this->api('GET', 'admin/roles/papelera', [], $token)->assertJsonMissing(['nombre' => 'Rol temporal']);
    }

    public function test_nivel2_no_puede_restaurar_ni_borrar_definitivamente(): void
    {
        $admin = $this->tokenAdmin();
        $id = $this->api('POST', 'admin/roles', ['nombre' => 'Rol papelera'], $admin)->json('data.id');
        $this->api('DELETE', "admin/roles/{$id}", [], $admin)->assertStatus(204);

        $nivel2 = $this->tokenAdmin('nivel2', 'nivel2123');
        $this->api('PATCH', "admin/roles/papelera/{$id}", [], $nivel2)->assertStatus(403);
        $this->api('DELETE', "admin/roles/papelera/{$id}", [], $nivel2)->assertStatus(403);
    }

    public function test_no_se_puede_borrar_definitivamente_un_rol_con_usuarios(): void
    {
        $token = $this->tokenAdmin();
        $rolId = Usuario::first()->rol_id;

        $this->api('DELETE', "admin/roles/{$rolId}", [], $token)->assertStatus(204);
        $this->api('DELETE', "admin/roles/papelera/{$rolId}", [], $token)->assertStatus(409);
    }

    public function test_categoria_con_productos_no_se_elimina_y_vacia_si(): void
    {
        $token = $this->tokenAdmin();
        $conProductos = Producto::first()->categoria_id;

        $this->api('DELETE', "admin/categorias/{$conProductos}", [], $token)->assertStatus(409);

        $id = $this->api('POST', 'admin/categorias', ['nombre' => 'Categoría vacía'], $token)->assertStatus(201)->json('data.id');
        $this->api('DELETE', "admin/categorias/{$id}", [], $token)->assertStatus(204);
    }

    public function test_pedidos_admin_cambio_de_estado_segun_nivel(): void
    {
        $pedido = $this->cliente()->ordenes()->first();       // pendiente, 2 unidades
        $producto = $pedido->detalles()->first()->producto;
        $stockAntes = $producto->stock;

        $nivel1 = $this->tokenAdmin('nivel1', 'nivel1123');
        $this->api('GET', "admin/pedidos/{$pedido->id}", [], $nivel1)->assertOk();
        $this->api('PATCH', "admin/pedidos/{$pedido->id}", ['estado' => 'completada'], $nivel1)->assertStatus(403);

        $nivel2 = $this->tokenAdmin('nivel2', 'nivel2123');
        $this->api('PATCH', "admin/pedidos/{$pedido->id}", ['estado' => 'inventado'], $nivel2)->assertStatus(422);
        $this->api('PATCH', "admin/pedidos/{$pedido->id}", ['estado' => 'cancelada'], $nivel2)
            ->assertOk()->assertJsonPath('data.estado', 'cancelada');

        $this->assertSame($stockAntes + 2, $producto->fresh()->stock, 'Cancelar devuelve el stock');
        $this->api('PATCH', "admin/pedidos/{$pedido->id}", ['estado' => 'completada'], $nivel2)->assertStatus(409);
    }

    // ================================================== 4. VALIDACIÓN, PUT/PATCH, 404, 405

    public function test_datos_invalidos_devuelven_422_con_detalle(): void
    {
        $token = $this->tokenAdmin();

        $this->api('POST', 'admin/roles', ['nombre' => 'a'], $token)
            ->assertStatus(422)->assertJsonValidationErrors(['nombre']);

        $this->api('POST', 'admin/recursos', ['nombre' => 'Camisa', 'precio' => -5, 'stock' => 'muchos', 'condicion' => 'roto'], $token)
            ->assertStatus(422)->assertJsonValidationErrors(['categoria_id', 'precio', 'stock', 'condicion']);
    }

    public function test_put_exige_todos_los_campos_y_patch_acepta_parciales(): void
    {
        $token = $this->tokenAdmin();
        $producto = Producto::first();

        $this->api('PUT', "admin/recursos/{$producto->id}", ['nombre' => 'Solo nombre'], $token)
            ->assertStatus(422)->assertJsonValidationErrors(['categoria_id', 'condicion', 'precio', 'stock']);

        $this->api('PATCH', "admin/recursos/{$producto->id}", ['stock' => 7], $token)
            ->assertOk()->assertJsonPath('data.stock', 7)->assertJsonPath('data.nombre', $producto->nombre);

        $this->api('PUT', "admin/recursos/{$producto->id}", [
            'nombre' => 'Reemplazado', 'categoria_id' => $producto->categoria_id, 'condicion' => 'nuevo', 'precio' => 99.5, 'stock' => 3,
        ], $token)->assertOk()->assertJsonPath('data.nombre', 'Reemplazado');
    }

    public function test_recursos_inexistentes_devuelven_404(): void
    {
        $admin = $this->tokenAdmin();
        $cliente = $this->tokenCliente();

        $this->api('GET', 'admin/roles/99999', [], $admin)->assertStatus(404)->assertJsonStructure(['message']);
        $this->api('GET', 'admin/recursos/99999', [], $admin)->assertStatus(404);
        $this->api('GET', 'productos/99999')->assertStatus(404);
        $this->api('GET', 'cliente/pedidos/99999', [], $cliente)->assertStatus(404);
        $this->api('GET', 'ruta/que/no/existe')->assertStatus(404);
    }

    public function test_metodo_http_no_permitido_devuelve_405(): void
    {
        $this->api('DELETE', 'productos')->assertStatus(405)->assertJsonStructure(['message']);
        $this->api('PUT', 'categorias')->assertStatus(405);
    }

    public function test_listado_admin_admite_busqueda_filtro_orden_y_paginacion(): void
    {
        $token = $this->tokenAdmin();

        $respuesta = $this->api('GET', 'admin/recursos?per_page=3&ordenar=-precio&condicion=usado', [], $token)->assertOk();
        $respuesta->assertJsonStructure(['data', 'links', 'meta']);
        $this->assertLessThanOrEqual(3, count($respuesta->json('data')));

        $precios = collect($respuesta->json('data'))->pluck('precio')->map(fn ($p) => (float) $p)->all();
        $ordenados = $precios;
        rsort($ordenados);
        $this->assertSame($ordenados, $precios);
    }

    public function test_respuestas_de_usuarios_no_exponen_la_contrasena(): void
    {
        $token = $this->tokenAdmin();

        $respuesta = $this->api('GET', 'admin/usuarios', [], $token)->assertOk();
        $this->assertStringNotContainsString('password_hash', $respuesta->getContent());
        $this->assertStringNotContainsString('social_id', $respuesta->getContent());
    }

    public function test_admin_crea_un_cliente_que_luego_puede_iniciar_sesion(): void
    {
        $token = $this->tokenAdmin();
        $rolCliente = Rol::where('nombre', 'cliente')->first();

        $this->api('POST', 'admin/usuarios', [
            'nombre' => 'Cliente Nuevo', 'email' => 'nuevo@example.com', 'rol_id' => $rolCliente->id, 'password' => 'clave12345',
        ], $token)->assertStatus(201);

        $this->api('POST', 'cliente/login', ['email' => 'nuevo@example.com', 'password' => 'clave12345'])->assertOk();
    }

    // ============================================================= 5. SEPARACIÓN DE ÁREAS

    public function test_cliente_no_puede_usar_endpoints_de_administracion(): void
    {
        $cliente = $this->tokenCliente();

        $this->api('GET', 'admin/roles', [], $cliente)->assertStatus(403);
        $this->api('POST', 'admin/recursos', ['nombre' => 'Hack'], $cliente)->assertStatus(403);
        $this->api('GET', 'admin/pedidos', [], $cliente)->assertStatus(403);
        $this->api('GET', 'admin/reportes/resumen', [], $cliente)->assertStatus(403);
    }

    public function test_admin_no_puede_usar_endpoints_de_cliente(): void
    {
        $admin = $this->tokenAdmin();

        $this->api('GET', 'cliente/perfil', [], $admin)->assertStatus(403);
        $this->api('GET', 'cliente/pedidos', [], $admin)->assertStatus(403);
        $this->api('POST', 'cliente/pedidos', ['items' => []], $admin)->assertStatus(403);
    }

    public function test_un_usuario_que_no_es_cliente_no_puede_entrar_al_area_cliente(): void
    {
        $rol = Rol::firstOrCreate(['nombre' => 'moderador']);
        Usuario::create([
            'rol_id' => $rol->id, 'nombre' => 'Moderador', 'email' => 'mod@example.com',
            'password_hash' => bcrypt('clave12345'), 'fecha_registro' => now(),
        ]);

        $this->api('POST', 'cliente/login', ['email' => 'mod@example.com', 'password' => 'clave12345'])
            ->assertStatus(401);
    }

    public function test_un_token_de_cliente_con_rol_cambiado_deja_de_servir(): void
    {
        $cliente = $this->cliente();
        $token = $this->tokenCliente();
        $this->api('GET', 'cliente/perfil', [], $token)->assertOk();

        $cliente->update(['rol_id' => Rol::firstOrCreate(['nombre' => 'invitado'])->id]);

        $this->api('GET', 'cliente/perfil', [], $token)->assertStatus(403);
    }

    // =========================================================== 6. ÁREA CLIENTE: CUENTA

    public function test_cliente_se_registra_y_siempre_queda_con_rol_cliente(): void
    {
        $rolAdmin = Rol::where('nombre', 'administrador')->first();

        $respuesta = $this->api('POST', 'cliente/registro', [
            'nombre' => 'Persona Nueva', 'email' => 'persona@example.com',
            'password' => 'clave12345', 'password_confirmation' => 'clave12345',
            'rol_id' => $rolAdmin->id,   // se ignora
        ])->assertStatus(201)->assertJsonPath('data.cliente.rol.nombre', 'cliente');

        $this->assertStringNotContainsString('password_hash', $respuesta->getContent());
        $this->api('GET', 'cliente/perfil', [], $respuesta->json('data.token'))->assertOk();
    }

    public function test_registro_invalido_devuelve_422(): void
    {
        $this->api('POST', 'cliente/registro', ['nombre' => 'ab', 'email' => 'no-es-correo', 'password' => '123'])
            ->assertStatus(422)->assertJsonValidationErrors(['nombre', 'email', 'password']);

        $this->api('POST', 'cliente/registro', [
            'nombre' => 'Repetido', 'email' => 'cliente1@example.com', 'password' => 'clave12345', 'password_confirmation' => 'clave12345',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_cliente_inicia_sesion_y_consulta_su_perfil(): void
    {
        $this->api('POST', 'cliente/login', ['email' => 'cliente1@example.com', 'password' => 'mala'])->assertStatus(401);

        $token = $this->tokenCliente();
        $respuesta = $this->api('GET', 'cliente/perfil', [], $token)->assertOk()
            ->assertJsonPath('data.email', 'cliente1@example.com');

        $this->assertStringNotContainsString('password_hash', $respuesta->getContent());

        $this->api('POST', 'cliente/logout', [], $token)->assertOk();
        $this->api('GET', 'cliente/perfil', [], $token)->assertStatus(401);
    }

    public function test_cliente_actualiza_parcialmente_su_perfil_sin_poder_cambiar_su_rol(): void
    {
        $token = $this->tokenCliente();
        $rolAdmin = Rol::where('nombre', 'administrador')->first();

        $this->api('PATCH', 'cliente/perfil', ['nombre' => 'Ana Modificada', 'rol_id' => $rolAdmin->id], $token)
            ->assertOk()->assertJsonPath('data.nombre', 'Ana Modificada')->assertJsonPath('data.rol.nombre', 'cliente');

        $this->assertSame('cliente', $this->cliente()->rol->nombre);

        // Para cambiar la contraseña se exige la contraseña actual.
        $this->api('PATCH', 'cliente/perfil', ['password' => 'nueva12345', 'password_confirmation' => 'nueva12345'], $token)
            ->assertStatus(422)->assertJsonValidationErrors(['password_actual']);
        $this->api('PATCH', 'cliente/perfil', ['password' => 'nueva12345', 'password_confirmation' => 'nueva12345', 'password_actual' => 'incorrecta'], $token)
            ->assertStatus(422);
        $this->api('PATCH', 'cliente/perfil', ['password' => 'nueva12345', 'password_confirmation' => 'nueva12345', 'password_actual' => 'cliente123'], $token)
            ->assertOk();

        $this->api('POST', 'cliente/login', ['email' => 'cliente1@example.com', 'password' => 'nueva12345'])->assertOk();
    }

    // ====================================================== 7. ÁREA CLIENTE: PEDIDOS (PROPIEDAD)

    public function test_cliente_solo_ve_sus_propios_pedidos(): void
    {
        $cliente1 = $this->cliente('cliente1@example.com');
        $token = $this->tokenCliente();

        $respuesta = $this->api('GET', 'cliente/pedidos', [], $token)->assertOk();

        $this->assertNotEmpty($respuesta->json('data'));
        foreach ($respuesta->json('data') as $pedido) {
            $this->assertSame($cliente1->id, $pedido['usuario_id']);
        }

        $propio = $cliente1->ordenes()->first();
        $this->api('GET', "cliente/pedidos/{$propio->id}", [], $token)->assertOk()->assertJsonPath('data.id', $propio->id);
    }

    public function test_cliente_no_puede_ver_ni_cancelar_pedidos_de_otro_cliente(): void
    {
        $token = $this->tokenCliente('cliente1@example.com');
        $ajeno = $this->cliente('cliente2@example.com')->ordenes()->first();

        $consulta = $this->api('GET', "cliente/pedidos/{$ajeno->id}", [], $token)->assertStatus(403);
        $this->assertArrayNotHasKey('detalles', $consulta->json());
        $this->assertArrayNotHasKey('data', $consulta->json());

        $this->api('PATCH', "cliente/pedidos/{$ajeno->id}", ['estado' => 'cancelada'], $token)->assertStatus(403);
        $this->assertSame('completada', $ajeno->fresh()->estado);
    }

    public function test_cliente_cancela_su_pedido_pendiente_y_se_devuelve_el_stock(): void
    {
        $token = $this->tokenCliente();
        $pedido = $this->cliente()->ordenes()->first();
        $producto = $pedido->detalles()->first()->producto;
        $stockAntes = $producto->stock;

        $this->api('PATCH', "cliente/pedidos/{$pedido->id}", ['estado' => 'cancelada'], $token)
            ->assertOk()->assertJsonPath('data.estado', 'cancelada');

        $this->assertSame($stockAntes + 2, $producto->fresh()->stock);

        // Ya cancelado: no se puede volver a cancelar; y un cliente no puede "completar" pedidos.
        $this->api('PATCH', "cliente/pedidos/{$pedido->id}", ['estado' => 'cancelada'], $token)->assertStatus(409);
        $this->api('PATCH', "cliente/pedidos/{$pedido->id}", ['estado' => 'completada'], $token)->assertStatus(422);
    }

    public function test_cliente_crea_pedido_y_el_servidor_calcula_el_total(): void
    {
        $token = $this->tokenCliente();
        $producto = $this->productoConStock(10);

        $respuesta = $this->api('POST', 'cliente/pedidos', [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 3, 'precio_unitario' => 0.01]],
            'total' => 0.01,            // se ignora: el precio y el total los calcula el servidor
            'usuario_id' => 999,        // se ignora: el dueño es el cliente autenticado
            'estado' => 'completada',   // se ignora
        ], $token)->assertStatus(201);

        $esperado = round($producto->precio * 3, 2);
        $this->assertEquals($esperado, $respuesta->json('data.total'));
        $this->assertSame('pendiente', $respuesta->json('data.estado'));
        $this->assertSame($this->cliente()->id, $respuesta->json('data.usuario_id'));
        $this->assertEquals($producto->precio, $respuesta->json('data.detalles.0.precio_unitario'));
        $this->assertSame(7, $producto->fresh()->stock);
    }

    public function test_pedido_con_stock_insuficiente_o_datos_invalidos_devuelve_422(): void
    {
        $token = $this->tokenCliente();
        $producto = $this->productoConStock(5);

        $this->api('POST', 'cliente/pedidos', ['items' => [['producto_id' => $producto->id, 'cantidad' => 6]]], $token)
            ->assertStatus(422)->assertJsonValidationErrors(['items.0.cantidad']);
        $this->assertSame(5, $producto->fresh()->stock, 'No se descuenta stock si el pedido falla');

        $this->api('POST', 'cliente/pedidos', ['items' => []], $token)->assertStatus(422)->assertJsonValidationErrors(['items']);
        $this->api('POST', 'cliente/pedidos', ['items' => [['producto_id' => 99999, 'cantidad' => 1]]], $token)->assertStatus(422);
        $this->api('POST', 'cliente/pedidos', ['items' => [['producto_id' => $producto->id, 'cantidad' => 0]]], $token)->assertStatus(422);
        $this->api('POST', 'cliente/pedidos', ['items' => [
            ['producto_id' => $producto->id, 'cantidad' => 1], ['producto_id' => $producto->id, 'cantidad' => 1],
        ]], $token)->assertStatus(422);
    }

    // =========================================== 8. ÁREA CLIENTE: CARRITO Y LISTA DE DESEOS

    public function test_cliente_gestiona_su_carrito(): void
    {
        $token = $this->tokenCliente();
        $producto = $this->productoConStock(10);

        $this->api('POST', 'cliente/carrito/items', ['producto_id' => $producto->id, 'cantidad' => 2], $token)
            ->assertStatus(201)->assertJsonPath('data.items.0.cantidad', 2);

        $respuesta = $this->api('POST', 'cliente/carrito/items', ['producto_id' => $producto->id, 'cantidad' => 3], $token)
            ->assertOk()->assertJsonPath('data.items.0.cantidad', 5);

        $itemId = $respuesta->json('data.items.0.id');
        $this->assertEquals(round($producto->precio * 5, 2), $respuesta->json('data.total'));

        $this->api('PATCH', "cliente/carrito/items/{$itemId}", ['cantidad' => 20], $token)->assertStatus(422);
        $this->api('PATCH', "cliente/carrito/items/{$itemId}", ['cantidad' => 1], $token)->assertOk();
        $this->api('DELETE', "cliente/carrito/items/{$itemId}", [], $token)->assertStatus(204);
        $this->api('GET', 'cliente/carrito', [], $token)->assertOk()->assertJsonPath('data.items', []);
    }

    public function test_cliente_no_puede_tocar_el_carrito_de_otro(): void
    {
        $producto = $this->productoConStock(10);
        $carritoAjeno = Carrito::firstOrCreate(['usuario_id' => $this->cliente('cliente2@example.com')->id]);
        $item = $carritoAjeno->items()->create(['producto_id' => $producto->id, 'cantidad' => 1]);

        $token = $this->tokenCliente('cliente1@example.com');

        $this->api('PATCH', "cliente/carrito/items/{$item->id}", ['cantidad' => 4], $token)->assertStatus(403);
        $this->api('DELETE', "cliente/carrito/items/{$item->id}", [], $token)->assertStatus(403);
        $this->assertSame(1, CarritoItem::find($item->id)->cantidad);
    }

    public function test_lista_de_deseos_propia_y_ajena(): void
    {
        $producto = $this->productoConStock(10);
        $token = $this->tokenCliente('cliente1@example.com');

        $id = $this->api('POST', 'cliente/lista-deseos', ['producto_id' => $producto->id], $token)->assertStatus(201)->json('data.id');
        $this->api('POST', 'cliente/lista-deseos', ['producto_id' => $producto->id], $token)->assertStatus(409);
        $this->api('POST', 'cliente/lista-deseos', ['producto_id' => 99999], $token)->assertStatus(422);
        $this->api('GET', 'cliente/lista-deseos', [], $token)->assertOk()->assertJsonCount(1, 'data');

        $otro = $this->tokenCliente('cliente2@example.com');
        $this->api('DELETE', "cliente/lista-deseos/{$id}", [], $otro)->assertStatus(403);
        $this->assertNotNull(ListaDeseo::find($id));

        $this->api('DELETE', "cliente/lista-deseos/{$id}", [], $token)->assertStatus(204);
    }

    // ================================================================= 9. CATÁLOGO PÚBLICO

    public function test_catalogo_publico_no_requiere_token_ni_expone_datos_internos(): void
    {
        $respuesta = $this->api('GET', 'productos?per_page=5')->assertOk()->assertJsonStructure(['data', 'links', 'meta']);

        $this->assertStringNotContainsString('usuario_id', $respuesta->getContent());
        $this->assertStringNotContainsString('deleted_at', $respuesta->getContent());

        $id = $respuesta->json('data.0.id');
        $this->api('GET', "productos/{$id}")->assertOk()->assertJsonPath('data.id', $id);
        $this->api('GET', 'categorias')->assertOk()->assertJsonStructure(['data' => [['id', 'nombre']]]);
    }

    public function test_catalogo_publico_valida_parametros_y_filtra(): void
    {
        $this->api('GET', 'productos?ordenar=password')->assertStatus(422);

        $nuevos = $this->api('GET', 'productos?condicion=nuevo&per_page=50')->assertOk()->json('data');
        foreach ($nuevos as $producto) {
            $this->assertSame('nuevo', $producto['condicion']);
        }

        $categoria = Categoria::first();
        $porCategoria = $this->api('GET', "productos?categoria_id={$categoria->id}&per_page=50")->assertOk()->json('data');
        foreach ($porCategoria as $producto) {
            $this->assertSame($categoria->id, $producto['categoria']['id']);
        }
    }

    public function test_un_producto_eliminado_deja_de_aparecer_en_el_catalogo(): void
    {
        $producto = Producto::first();
        $producto->delete();

        $this->api('GET', "productos/{$producto->id}")->assertStatus(404);
    }
}
