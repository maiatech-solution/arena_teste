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

    /**
     * Busca o status de reputação e VIP de um usuário pelo número de contato.
     * Utilizado pelo modal de Agendamento Rápido no Dashboard.
     *
     * @param string $contact O número de WhatsApp (11 dígitos, limpo no frontend).
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReputation(string $contact)
    {
        // 1. Limpa o contato (garantia de segurança, embora o frontend já faça isso)
        $cleanedContact = preg_replace('/\D/', '', $contact);

        // 2. Busca o usuário pelo contato
        // O CAMPO É 'whatsapp_contact', baseado no seu método searchClients
        $user = User::where('whatsapp_contact', $cleanedContact)->first(); 

        if (!$user) {
            // Cliente não encontrado (ou novo)
            return response()->json([
                'is_vip' => false,
                'status_tag' => null, 
            ]);
        }
        
        // 3. Lógica de Reputação e VIP
        // 🛑 ATENÇÃO: Seus campos 'is_vip' e 'no_show_count' (ou similar) devem existir no User Model.
        $isVip = $user->is_vip ?? false;
        $noShowCount = $user->no_show_count ?? 0; 
        $statusTag = '';

        if ($isVip) {
            // Cliente VIP: Prioridade máxima na tag
            $statusTag = '<p class="font-bold text-lg text-indigo-700">⭐ Cliente VIP</p>';
        } elseif ($noShowCount > 2) {
            // Mais de 2 faltas: Alto Risco
            $statusTag = '<p class="font-bold text-lg text-red-700">⛔ Alto Risco de Falta (' . $noShowCount . ' Faltas)</p>';
        } elseif ($noShowCount > 0) {
            // 1 ou 2 faltas: Histórico de Alerta
            $statusTag = '<p class="font-medium text-sm text-orange-700">⚠️ Histórico de Falta (' . $noShowCount . ')</p>';
        } else {
            // Sem faltas e não VIP: Confiável
            $statusTag = '<p class="font-medium text-sm text-green-700">🟢 Cliente Confiável</p>';
        }

        // 4. Retorna o status de volta para o JavaScript
        return response()->json([
            'is_vip' => $isVip,
            'status_tag' => $statusTag, // Retorna o HTML que será injetado
        ]);
    }
}