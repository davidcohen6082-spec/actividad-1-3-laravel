<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class LogController extends RecursoController
{
    protected function modelo(): string
    {
        return Log::class;
    }

    protected function etiqueta(): string
    {
        return 'log';
    }

    protected function relaciones(): array
    {
        return ['usuario'];
    }

    protected function buscables(): array
    {
        return ['accion'];
    }

    protected function filtros(): array
    {
        return ['usuario_id', 'critico'];
    }

    protected function ordenables(): array
    {
        return ['id', 'fecha'];
    }

    protected function ordenPredeterminado(): string
    {
        return '-fecha';
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'usuario_id' => ['required', Rule::exists('usuarios', 'id')->whereNull('deleted_at')],
            'accion' => ['required', 'string', 'min:5', 'max:150'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'critico' => ['nullable', 'boolean'],
        ];
    }

    protected function mensajes(): array
    {
        return [
            'usuario_id.required' => 'Selecciona un usuario.',
            'usuario_id.exists' => 'El usuario seleccionado no es válido.',
            'accion.required' => 'Describe la acción realizada.',
            'accion.min' => 'La descripción debe tener al menos 5 caracteres.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'Ingresa una fecha válida.',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
            'critico.boolean' => 'El campo crítico debe ser verdadero o falso.',
        ];
    }

    protected function atributos(array $validados, ?Model $registro): array
    {
        if (array_key_exists('critico', $validados)) {
            $validados['critico'] = (bool) $validados['critico'];
        }

        return $validados;
    }
}
