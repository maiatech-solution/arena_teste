<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

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
        // Define a data mínima como "hoje"
        $minDate = Carbon::now()->format('Y-m-d');

        return [
            // Nomes de input SINCRONIZADOS com create.blade.php

            // Campos do Cliente
            'nome_cliente'    => ['required', 'string', 'max:255'],
            'contato_cliente' => ['required', 'string', 'max:50'],

            // Campos de Horário
            // Agora usa 'data_reserva'
            'data_reserva'    => ['required', 'date', "after_or_equal:{$minDate}"],
            // Agora usa 'hora_inicio'
            'hora_inicio'     => ['required', 'date_format:H:i'],
            // Agora usa 'hora_fim'
            'hora_fim'        => ['required', 'date_format:H:i', 'after:hora_inicio'],

            // O campo 'price' foi removido da validação, pois não está no formulário.
            // Se ele for um campo obrigatório no seu Controller, certifique-se de adicioná-lo ao Blade.
        ];
    }

    /**
     * Personaliza as mensagens de erro.
     */
    public function messages(): array
    {
        return [
            // Mensagens sincronizadas com os novos nomes de campo
            'data_reserva.required' => 'A data da reserva é obrigatória.',
            'data_reserva.after_or_equal' => 'Não é possível agendar em datas passadas.',
            'hora_inicio.required' => 'O horário de início é obrigatório.',
            'hora_fim.required' => 'O horário de fim é obrigatório.',
            'hora_fim.after' => 'O horário de término deve ser após o horário de início.',

            'nome_cliente.required' => 'O nome completo do cliente é obrigatório.',
            'contato_cliente.required' => 'O contato (telefone ou e-mail) é obrigatório.',
        ];
    }
}
