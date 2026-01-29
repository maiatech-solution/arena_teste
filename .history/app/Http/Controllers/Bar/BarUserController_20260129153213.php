{{-- Procure a parte das Ações (td) e deixe apenas o EDITAR e EXCLUIR --}}
<td class="px-8 py-6 whitespace-nowrap text-center">
    <div class="flex justify-center items-center gap-3">

        {{-- BOTÃO EDITAR --}}
        <a href="{{ route('bar.users.edit', $user) }}" class="bg-gray-700 hover:bg-orange-600 p-2 rounded-xl transition-all border border-gray-600 text-white" title="Editar">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
            </svg>
        </a>

        {{-- BOTÃO EXCLUIR (Se não for o próprio usuário logado) --}}
        @if (Auth::id() !== $user->id)
        <form action="{{ route('bar.users.destroy', $user) }}" method="POST" onsubmit="return false;" class="inline">
            @csrf @method('DELETE')
            <button type="button" onclick="showCustomConfirmation(this)"
                class="bg-gray-700 hover:bg-red-600 p-2 rounded-xl transition-all border border-gray-600 text-white"
                data-username="{{ $user->name }}" data-userid="{{ $user->id }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </form>
        @endif
    </div>
</td>
