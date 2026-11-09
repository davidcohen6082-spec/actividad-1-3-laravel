# Actividad 4.4 · API RESTful del área cliente (Laravel 12 + Sanctum)

Base: `/api/v1/client`. Tokens Bearer (Sanctum, `client-token`), propiedad de datos con **OrdenPolicy** + **Eloquent Scope** (`Orden::propios`), validación con **FormRequest** y salida con **JsonResource**. El «registro» del cliente es su pedido (`ordenes`; propietario = `usuario_id`).

## Puesta en marcha
```
php artisan migrate      # agrega ordenes.notas (además de usuarios.activo de la 4.3)
php artisan serve
php artisan test         # 101 pruebas
```

## Endpoints
| Método | Ruta | Acceso | Éxito |
|---|---|---|---|
| POST | /client/register | pública | 201 |
| POST | /client/login | pública | 200 |
| POST | /client/logout | token | 200 |
| GET | /client/catalogo · /client/catalogo/{id} | pública | 200 |
| GET, PUT/PATCH | /client/perfil | token | 200 |
| GET, POST | /client/mis-registros | token (lista filtrada por scope) | 200 / 201 |
| GET | /client/mis-registros/{id} | Policy `view` (dueño) | 200 |
| PUT/PATCH | /client/mis-registros/{id} | Policy `update` (dueño) | 200 |
| DELETE | /client/mis-registros/{id} | Policy `delete` (dueño + cancelable) | 200 |

Errores: 401 (sin token/inválido/expirado/revocado), 403 (registro ajeno o no cancelable), 404, 409 (modificar un registro no pendiente), 422.

## Archivos principales
- `routes/api.php` · `app/Http/Controllers/Api/V1/Client/` (AuthController, PerfilController, RegistroController)
- `app/Policies/OrdenPolicy.php` · `app/Models/Orden.php` (`scopePropios`, `registrarPara`)
- `app/Http/Requests/Client/` · `app/Http/Resources/Client/`
- `database/migrations/2026_11_08_220000_add_notas_to_ordenes_table.php`
- `tests/Feature/ClientApiV44Test.php` · `docs/Bancos_de_Ropa_API_Client_4_4.postman_collection.json`
