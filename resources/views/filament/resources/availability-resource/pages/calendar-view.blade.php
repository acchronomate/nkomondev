<div>
    <div class="mb-4">
        <div class="bg-white rounded-lg shadow p-4">
            <h2 class="text-lg font-semibold mb-4">Calendrier des disponibilités</h2>

            <div class="mb-4 flex gap-4">
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-green-500 rounded mr-2"></div>
                    <span class="text-sm">Disponible</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-yellow-500 rounded mr-2"></div>
                    <span class="text-sm">Partiellement disponible</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-red-500 rounded mr-2"></div>
                    <span class="text-sm">Complet</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-gray-500 rounded mr-2"></div>
                    <span class="text-sm">Bloqué</span>
                </div>
            </div>

            <div class="text-center py-8 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <p class="mt-2">Vue calendrier en cours de développement</p>
                <p class="text-sm mt-1">Cette fonctionnalité sera bientôt disponible</p>
            </div>
        </div>
    </div>

    <div class="text-center">
        <a href="{{ \App\Filament\Admin\Resources\AvailabilityResource::getUrl('index') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
            Retour à la liste
        </a>
    </div>
</div>
