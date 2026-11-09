# Actividad 4.3 · API RESTful del módulo administrativo (Laravel 12 + Sanctum)

Base: `/api/v1/admin`. Autenticación con token Bearer (Sanctum), autorización con **Policy** (`ProductoAdminPolicy`) y **Gates** (`gestionar-usuarios`, `ver-registros`), validación con **FormRequest** y salida con **JsonResource**.

## Puesta en marcha
```
composer install
copy .env.example .env   (configura MySQL)  &&  php artisan key:generate
php artisan migrate --seed
php artisan serve
php artisan test
```
`SANCTUM_EXPIRATION=480` (minutos de vigencia del token; pasado ese tiempo responde 401).

## Cuentas de PRUEBA (seeders; no son credenciales reales)
| Usuario | Contraseña | Nivel |
|---|---|---|
| admin | admin1234 | 3 (todo) |
| nivel2 | nivel2123 | 2 (consulta, crea, edita) |
| nivel1 | nivel1123 | 1 (solo consulta) |
| inactivo | inactivo1234 | cuenta deshabilitada |

## Endpoints
| Método | Ruta | Privilegio | Éxito |
|---|---|---|---|
| POST | /admin/login | pública | 200 |
| POST | /admin/logout | token | 200 |
| GET | /admin/recursos (incluye inactivos; `?estado=activos\|inactivos`) | nivel ≥ 1 | 200 |
| GET | /admin/recursos/{id} | nivel ≥ 1 | 200 |
| POST | /admin/recursos | nivel ≥ 2 | 201 |
| PUT/PATCH | /admin/recursos/{id} (campo `activo` cambia el estado: nivel 3) | nivel ≥ 2 | 200 |
| DELETE | /admin/recursos/{id} (lógico; `?definitivo=true` físico) | nivel 3 | 200 |
| GET | /admin/usuarios (`?tipo=`, `?activo=`, `?buscar=`) | nivel ≥ 1 | 200 |
| PATCH | /admin/usuarios/{id}/estado (`tipo`, `activo`, `rol_id` o `nivel`) | nivel 3 | 200 |

Errores: 401 (sin token, inválido, revocado, expirado), 403 (sin privilegios), 404, 422, 409 (borrado definitivo de un recurso referenciado).

## Archivos principales
- `routes/api.php` · `app/Http/Controllers/Api/V1/Admin/RecursoAdminController.php`, `UsuarioController.php`, `AuthController.php`
- `app/Policies/ProductoAdminPolicy.php` · `app/Providers/AppServiceProvider.php`
- `app/Http/Requests/Admin/` (AdminResourceRequest, CambiarEstadoUsuarioRequest, AdminLoginRequest)
- `app/Http/Resources/Admin/` (RecursoAdminResource, UsuarioAdminResource)
- `database/migrations/2026_11_08_120000_add_activo_to_usuarios_table.php`
- `tests/Feature/AdminApiV43Test.php` · `docs/Bancos_de_Ropa_API_Admin_4_3.postman_collection.json`
