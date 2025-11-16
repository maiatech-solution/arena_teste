<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User; // Assumindo que seu modelo de cliente/usuário é 'User'

class UserController extends Controller
{
    /**
     * Busca clientes (usuários) por nome, email ou contato de WhatsApp.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchClients(Request $request)
    {
        // Certifique-se de que o usuário está autenticado e tem permissão de administrador
        // (Você deve ter isso configurado no middleware da rota, mas é bom verificar)
        if (!auth()->check() || !auth()->user()->is_admin) {
            return response()->json(['error' => 'Não autorizado.'], 403);
        }

        $query = $request->input('query');

        if (empty($query) || strlen($query) < 2) {
            // Retorna vazio se a query for muito curta ou nula
            return response()->json([]);
        }

        // Tokeniza a query para pesquisa flexível (ex: "joão silva" encontra "joão" e "silva")
        $keywords = explode(' ', $query);

        $clients = User::where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $q->orWhere('name', 'like', '%' . $keyword . '%')
                  ->orWhere('email', 'like', '%' . $keyword . '%')
                  // Assumindo que você tem um campo 'whatsapp_contact' na tabela users
                  ->orWhere('whatsapp_contact', 'like', '%' . $keyword . '%');
            }
        })
        // Opcional: Filtra para não listar o próprio administrador ou usuários inativos
        // ->where('id', '!=', auth()->id())
        // ->where('is_active', true)
        ->limit(10) // Limita os resultados para performance
        ->get(['id', 'name', 'email', 'whatsapp_contact']); // Seleciona apenas os campos necessários

        // Mapeia para garantir que o formato do JSON é o esperado pelo JS
        return response()->json($clients->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'whatsapp_contact' => $client->whatsapp_contact,
            ];
        }));
    }
}
