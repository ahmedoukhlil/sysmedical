@extends('layouts.admin')

@section('title', 'Abonnement — ' . $cabinet->NomCabFr)

@section('content')
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Abonnement — {{ $cabinet->NomCabFr }}</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (!$subscription)
        <div class="bg-white rounded-lg shadow p-6 text-gray-500">
            Ce cabinet n'a pas d'abonnement enregistré.
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center gap-3 mb-4">
                <span class="text-lg font-semibold">{{ $subscription->plan->nom ?? '—' }}</span>
                @php
                    $badges = [
                        'essai' => 'bg-blue-100 text-blue-800',
                        'actif' => 'bg-green-100 text-green-800',
                        'impaye' => 'bg-yellow-100 text-yellow-800',
                        'suspendu' => 'bg-red-100 text-red-800',
                        'resilie' => 'bg-gray-100 text-gray-600',
                    ];
                @endphp
                <span class="px-2 py-1 rounded text-xs font-semibold {{ $badges[$subscription->statut] ?? 'bg-gray-100 text-gray-600' }}">
                    {{ ucfirst($subscription->statut) }}
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                <div>
                    <div class="text-gray-500">Fin d'essai</div>
                    <div>{{ $subscription->trial_ends_at?->format('d/m/Y') ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Fin de délai de grâce</div>
                    <div>{{ $subscription->grace_ends_at?->format('d/m/Y') ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Fin de période payée</div>
                    <div>{{ $subscription->current_period_ends_at?->format('d/m/Y') ?? '—' }}</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Changer de plan</h2>
                <form method="POST" action="{{ route('admin.cabinets.subscription.change-plan', $cabinet->idEntete) }}" class="space-y-3">
                    @csrf
                    <select name="subscription_plan_id" class="w-full border rounded px-3 py-2" required>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected($subscription->subscription_plan_id === $plan->id)>
                                {{ $plan->nom }} — {{ number_format($plan->prix_mensuel, 0, ',', ' ') }} {{ $plan->devise }}/mois
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="bg-blue-800 text-white px-4 py-2 rounded hover:bg-blue-900">Changer de plan</button>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Enregistrer un paiement</h2>
                <form method="POST" action="{{ route('admin.cabinets.subscription.payment', $cabinet->idEntete) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Montant (MRU)</label>
                        <input type="number" name="montant" min="1" class="w-full border rounded px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Moyen de paiement</label>
                        <select name="moyen" class="w-full border rounded px-3 py-2" required>
                            <option value="especes">Espèces</option>
                            <option value="virement">Virement</option>
                            <option value="cheque">Chèque</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Date du paiement</label>
                        <input type="date" name="date_paiement" value="{{ now()->format('Y-m-d') }}" class="w-full border rounded px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Mois couverts</label>
                        <input type="number" name="mois_couverts" min="1" value="1" class="w-full border rounded px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Note (optionnel)</label>
                        <textarea name="note" class="w-full border rounded px-3 py-2" rows="2"></textarea>
                    </div>
                    <button type="submit" class="bg-green-700 text-white px-4 py-2 rounded hover:bg-green-800">Enregistrer le paiement</button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <h2 class="font-semibold text-gray-800 px-6 pt-6">Historique des paiements</h2>
            <table class="min-w-full text-sm mt-4">
                <thead class="bg-gray-100 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Montant</th>
                        <th class="px-4 py-3">Moyen</th>
                        <th class="px-4 py-3">Mois couverts</th>
                        <th class="px-4 py-3">Enregistré par</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($subscription->payments->sortByDesc('date_paiement') as $payment)
                        <tr>
                            <td class="px-4 py-3">{{ $payment->date_paiement->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ number_format($payment->montant, 0, ',', ' ') }} {{ $payment->devise }}</td>
                            <td class="px-4 py-3">{{ ucfirst($payment->moyen) }}</td>
                            <td class="px-4 py-3">{{ $payment->mois_couverts }}</td>
                            <td class="px-4 py-3">{{ $payment->admin->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">Aucun paiement enregistré.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
@endsection
