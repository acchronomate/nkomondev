<div class="p-4">
    <h3 class="text-lg font-semibold mb-4">Historique des taux de change pour {{ $currency->name }} ({{ $currency->code }})</h3>

    @if($history->isEmpty())
        <p class="text-gray-500">Aucun historique disponible.</p>
    @else
        <div class="space-y-2">
            <div class="grid grid-cols-3 gap-4 font-semibold text-sm text-gray-700 border-b pb-2">
                <div>Date</div>
                <div>Taux</div>
                <div>Modifié par</div>
            </div>

            @foreach($history as $entry)
                <div class="grid grid-cols-3 gap-4 text-sm py-2 border-b border-gray-100">
                    <div class="text-gray-600">
                        {{ $entry->created_at->format('d/m/Y H:i') }}
                    </div>
                    <div class="font-mono">
                        {{ number_format($entry->rate, 6) }} / XOF
                    </div>
                    <div class="text-gray-600">
                        {{ $entry->changedBy?->name ?? 'Système' }}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 pt-4 border-t">
            <p class="text-sm text-gray-500">
                Taux actuel : <span class="font-semibold">{{ number_format($currency->exchange_rate, 6) }} / XOF</span>
            </p>
        </div>
    @endif
</div>
