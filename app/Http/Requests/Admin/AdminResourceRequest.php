<?php

namespace App\Http\Requests\Admin;

use App\Models\Producto;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validación de POST / PUT / PATCH /api/v1/admin/recursos.
 *
 * Orden de evaluación (de esta manera un error nunca "adelanta" a otro):
 *   401 (middleware auth:sanctum) -> 403 (authorize) -> 404 (recurso inexistente) -> 422 (rules)
 */
class AdminResourceRequest extends FormRequest
{
    private ?Producto $recursoCache = null;

    private bool $recursoBuscado = false;

    /** Recurso que se está editando (incluye los inactivos). Solo en PUT / PATCH. */
    public function recurso(): ?Producto
    {
        if (! $this->recursoBuscado) {
            $id = $this->route('id');
            $this->recursoCache = ctype_digit((string) $id) ? Producto::withTrashed()->find($id) : null;
            $this->recursoBuscado = true;
        }

        return $this->recursoCache;
    }

    public function authorize(): bool
    {
        $admin = $this->user();

        if ($this->isMethod('POST')) {
            return $admin->can('create', Producto::class);
        }

        if (! $admin->can('update', Producto::class)) {
            return false;
        }

        // Cambiar el estado (activo true/false) equivale a restaurar / eliminar: exige nivel 3.
        if ($this->has('activo')) {
            $habilidad = filter_var($this->input('activo'), FILTER_VALIDATE_BOOLEAN) ? 'restore' : 'delete';

            if (! $admin->can($habilidad, Producto::class)) {
                return false;
            }
        }

        abort_if($this->recurso() === null, 404, 'El recurso solicitado no existe.');

        return true;
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException('No tienes privilegios suficientes para realizar esta operación sobre los recursos.');
    }

    public function rules(): array
    {
        $recurso = $this->recurso();
        $categoria = $this->input('categoria_id', $recurso?->categoria_id);

        $reglas = [
            'nombre' => [
                'required', 'string', 'min:3', 'max:150',
                // Unicidad: no puede haber dos recursos activos con el mismo nombre en la misma categoría.
                Rule::unique('productos', 'nombre')
                    ->where(fn ($q) => $q->where('categoria_id', $categoria)->whereNull('deleted_at'))
                    ->ignore($recurso?->id),
            ],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'categoria_id' => ['required', 'integer', Rule::exists('categorias', 'id')],
            'condicion' => ['required', 'in:nuevo,usado'],
            'talla' => ['nullable', 'string', 'max:20'],
            'precio' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        // PATCH: actualización parcial. "sometimes" omite los campos ausentes, pero un campo
        // presente con null o vacío sigue fallando "required" (no se sobrescribe con nulos).
        if ($this->isMethod('PATCH')) {
            $reglas = array_map(fn ($regla) => array_merge(['sometimes'], $regla), $reglas);
        }

        // El estado es opcional en cualquier método: true = activo, false = inactivo.
        $reglas['activo'] = ['sometimes', 'boolean'];

        return $reglas;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->isMethod('PATCH') && ! $this->hasAny(['nombre', 'descripcion', 'categoria_id', 'condicion', 'talla', 'precio', 'stock', 'imagen', 'activo'])) {
                $validator->errors()->add('cuerpo', 'Envía al menos un campo para actualizar.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del recurso es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'nombre.max' => 'El nombre no debe superar los 150 caracteres.',
            'nombre.unique' => 'Ya existe un recurso activo con ese nombre en la misma categoría.',
            'categoria_id.required' => 'Selecciona una categoría.',
            'categoria_id.integer' => 'La categoría debe ser un identificador numérico.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'condicion.required' => 'Indica la condición del recurso.',
            'condicion.in' => 'La condición debe ser "nuevo" o "usado".',
            'precio.required' => 'El precio es obligatorio.',
            'precio.numeric' => 'El precio debe ser un número.',
            'precio.min' => 'El precio no puede ser negativo.',
            'precio.max' => 'El precio no puede superar 99,999.99.',
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock no puede ser negativo.',
            'stock.max' => 'El stock no puede superar 1000 unidades.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La imagen debe ser jpg, jpeg, png o webp.',
            'imagen.max' => 'La imagen no debe superar los 2 MB.',
            'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
        ];
    }
}
