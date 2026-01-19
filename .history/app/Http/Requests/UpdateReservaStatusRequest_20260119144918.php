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
                // Adicionamos 'free' e 'maintenance' à lista permitida
                \Illuminate\Validation\Rule::in(['confirmed', 'cancelled', 'rejected', 'free', 'maintenance']),
            ],
            // Permitimos o campo 'action' que o formulário envia
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
