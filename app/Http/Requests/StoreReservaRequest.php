<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservaRequest extends FormRequest
{
    /**
     * Determine se o usuário está autorizado a fazer este request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtém as regras de validação que se aplicam ao request.
     */
    public function rules(): array
    {
        return [
            // Campos de Horário
            'date'          => ['required', 'date', 'after_or_equal:today'],
            'start_time'    => ['required', 'date_format:H:i'],
            'end_time'      => ['nullable', 'date_format:H:i', 'after:start_time'],
            'price'         => ['required', 'numeric', 'min:0'],

            // Campos do Cliente
            'client_name'   => ['required', 'string', 'max:255'],

            // CORREÇÃO: Apenas verifica se é obrigatório e string, max 50 caracteres (mais que suficiente para WhatsApp)
            'client_contact'=> ['required', 'string', 'max:50'],
        ];
    }

    /**
     * Personaliza as mensagens de erro.
     */
    public function messages(): array
    {
        return [
            'date.required' => 'A data da reserva é obrigatória.',
            'date.after_or_equal' => 'Não é possível agendar em datas passadas.',
            'start_time.required' => 'O horário de início é obrigatório.',
            'end_time.after' => 'O horário de término deve ser após o horário de início.',
            'price.required' => 'O valor é obrigatório.',
            'client_name.required' => 'O seu nome é obrigatório.',
            'client_contact.required' => 'O número do WhatsApp é obrigatório para contato.',
        ];
    }
}
