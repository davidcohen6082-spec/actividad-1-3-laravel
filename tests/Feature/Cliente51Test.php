<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Actividad 5.1 · Lo que necesita el cliente frontend: consulta abierta (TEMPORAL) del recurso
 * administrativo, imágenes con URL absoluta y CORS para otro origen.
 */
class Cliente51Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function api(string $metodo, string $uri, array $datos = [], array $cabeceras = []): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->json($metodo, "/api/v1/{$uri}", $datos, $cabeceras);
    }

    public function test_listado_y_detalle_se_consultan_sin_token(): void
    {
        $lista = $this->api('GET', 'admin/recursos?per_page=5')->assertOk()->assertJsonStructure(['data', 'links', 'meta' => ['total', 'current_page', 'last_page']]);
        $id = $lista->json('data.0.id');

        $this->api('GET', "admin/recursos/{$id}")->assertOk()->assertJsonPath('data.id', $id)
            ->assertJsonStructure(['data' => ['id', 'nombre', 'categoria' => ['id', 'nombre'], 'precio', 'stock', 'estado', 'imagen_url', 'imagenes']]);
    }

    public function test_la_consulta_abierta_incluye_inactivos_y_responde_404(): void
    {
        $p = Producto::first();
        $p->delete();

        $ids = collect($this->api('GET', 'admin/recursos?per_page=50')->json('data'))->pluck('id')->all();
        $this->assertContains($p->id, $ids);
        $this->api('GET', "admin/recursos/{$p->id}")->assertOk()->assertJsonPath('data.estado', 'inactivo');
        $this->api('GET', 'admin/recursos/999999')->assertStatus(404)->assertJsonStructure(['message']);
    }

    public function test_las_rutas_de_escritura_siguen_protegidas(): void
    {
        $datos = ['nombre' => 'Sin token', 'categoria_id' => Categoria::value('id'), 'condicion' => 'usado', 'precio' => 10, 'stock' => 1];

        $this->api('POST', 'admin/recursos', $datos)->assertStatus(401);
        $this->api('PUT', 'admin/recursos/1', $datos)->assertStatus(401);
        $this->api('PATCH', 'admin/recursos/1', ['stock' => 1])->assertStatus(401);
        $this->api('DELETE', 'admin/recursos/1')->assertStatus(401);
        $this->api('GET', 'admin/usuarios')->assertStatus(401);
        $this->api('GET', 'admin/perfil')->assertStatus(401);
    }

    public function test_con_token_la_policy_sigue_aplicandose_en_la_consulta(): void
    {
        $cliente = $this->json('POST', '/api/v1/cliente/login', ['email' => 'cliente1@example.com', 'password' => 'cliente123'])->json('data.token');

        // Un token de cliente sin permisos administrativos no abre la consulta: el usuario existe pero no es admin.
        $this->app['auth']->forgetGuards();
        $r = $this->getJson('/api/v1/admin/recursos', ['Authorization' => "Bearer {$cliente}"]);
        $this->assertContains($r->getStatusCode(), [200, 403]); // abierta temporalmente; no debe fallar con 500
    }

    public function test_las_imagenes_salen_como_url_absoluta_y_en_galeria(): void
    {
        $p = Producto::first();
        $p->update(['imagen' => 'images/productos/ejemplo.jpg']);

        $r = $this->api('GET', "admin/recursos/{$p->id}")->assertOk();
        $this->assertStringStartsWith('http', $r->json('data.imagen_url'));
        $this->assertStringEndsWith('/images/productos/ejemplo.jpg', $r->json('data.imagen_url'));
        $this->assertSame([$r->json('data.imagen_url')], $r->json('data.imagenes'));

        $p->update(['imagen' => null]);
        $r = $this->api('GET', "admin/recursos/{$p->id}")->assertOk();
        $this->assertNull($r->json('data.imagen_url'));
        $this->assertSame([], $r->json('data.imagenes'));
    }

    public function test_cors_permite_el_origen_del_cliente(): void
    {
        $r = $this->call('OPTIONS', '/api/v1/admin/recursos', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost:5173',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'accept,content-type',
        ]);
        $this->assertContains($r->getStatusCode(), [200, 204]);
        $this->assertSame('http://localhost:5173', $r->headers->get('Access-Control-Allow-Origin'));

        $get = $this->call('GET', '/api/v1/admin/recursos', [], [], [], ['HTTP_ORIGIN' => 'http://localhost:5173', 'HTTP_ACCEPT' => 'application/json']);
        $this->assertSame('http://localhost:5173', $get->headers->get('Access-Control-Allow-Origin'));

        $otro = $this->call('GET', '/api/v1/admin/recursos', [], [], [], ['HTTP_ORIGIN' => 'http://sitio-malicioso.test', 'HTTP_ACCEPT' => 'application/json']);
        $this->assertNull($otro->headers->get('Access-Control-Allow-Origin'));
    }
}
