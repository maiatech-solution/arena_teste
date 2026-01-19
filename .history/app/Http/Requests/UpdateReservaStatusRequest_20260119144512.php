<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReservaStatusRequest extends FormRequest
{
    /**
     * Determine se o usuário está autorizado a fazer este request.
     * * ATENÇÃO: Se este recurso for apenas para administradores/gestores, você
     * DEVE incluir uma checagem de permissão aqui (ex: $this->user()->isAdmin()).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtém as regras de validação que se aplicam ao request.
     * * Garante que o campo 'status' seja obrigatório e um dos valores permitidos.
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                // ADICIONADO: 'free' e 'maintenance' para permitir a reativação inteligente
                Rule::in(['confirmed', 'cancelled', 'rejected', 'free', 'maintenance']),
            ],
            // Adicionamos a action como opcional para não quebrar a validação
            'action' => ['nullable', 'string'],
        ];
    }

    /**
     * Personaliza as mensagens de erro.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'O campo status é obrigatório para atualização.',
            'status.in' => 'O status fornecido não é válido.',
        ];
    }
}
