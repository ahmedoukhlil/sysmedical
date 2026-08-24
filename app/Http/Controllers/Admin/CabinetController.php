<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Infocabinet;
use App\Models\SubscriptionPlan;
use App\Models\TUser;
use App\Services\CabinetExportService;
use App\Services\StorageQuotaService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CabinetController extends Controller
{
    private SubscriptionService $subscriptions;
    private CabinetExportService $exports;

    public function __construct(SubscriptionService $subscriptions, CabinetExportService $exports)
    {
        $this->subscriptions = $subscriptions;
        $this->exports = $exports;
    }

    public function index()
    {
        $cabinets = Infocabinet::with(['users' => function ($query) {
            $query->withoutTenant();
        }])->orderBy('NomCabFr')->get();

        return view('admin.cabinets.index', compact('cabinets'));
    }

    public function create()
    {
        return view('admin.cabinets.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom_cabinet' => 'required|string|max:255',
            'owner_login' => 'required|string|max:255|unique:t_user,login',
            'owner_password' => 'required|string|min:8',
            'owner_nom' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($data) {
            $nextId = (int) Infocabinet::max('idEntete') + 1;

            $cabinet = Infocabinet::create([
                'idEntete' => $nextId,
                'NomCabFr' => $data['nom_cabinet'],
                'statut' => 'actif',
            ]);

            TUser::create([
                'login' => $data['owner_login'],
                'password' => $data['owner_password'],
                'NomComplet' => $data['owner_nom'],
                'IdClasseUser' => 3,
                'fkidcabinet' => $cabinet->idEntete,
                'ismasquer' => 0,
            ]);

            $plan = SubscriptionPlan::where('code', config('subscription.default_plan_code'))->first();
            if ($plan) {
                $this->subscriptions->createTrialSubscription($cabinet, $plan, config('subscription.trial_days'));
            }
        });

        return redirect()->route('admin.cabinets.index')->with('success', 'Cabinet créé.');
    }

    public function suspend($id)
    {
        $cabinet = Infocabinet::findOrFail($id);
        $cabinet->forceFill(['statut' => 'suspendu'])->save();

        return back()->with('success', 'Cabinet suspendu.');
    }

    public function activate($id)
    {
        $cabinet = Infocabinet::findOrFail($id);
        $cabinet->forceFill(['statut' => 'actif'])->save();

        return back()->with('success', 'Cabinet réactivé.');
    }

    public function subscription($id)
    {
        $cabinet = Infocabinet::findOrFail($id);
        $subscription = $cabinet->subscription()->with(['plan', 'payments.admin'])->first();
        $plans = SubscriptionPlan::where('actif', true)->orderBy('ordre')->get();

        $quotaService = app(StorageQuotaService::class);
        $usedBytes = $quotaService->usedBytes($cabinet->idEntete);
        $quotaBytes = $quotaService->quotaBytes($cabinet->idEntete);

        return view('admin.cabinets.subscription', compact('cabinet', 'subscription', 'plans', 'usedBytes', 'quotaBytes'));
    }

    public function export($id)
    {
        $cabinet = Infocabinet::findOrFail($id);
        $zipPath = $this->exports->export($cabinet->idEntete);

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function recordPayment(Request $request, $id)
    {
        $cabinet = Infocabinet::findOrFail($id);
        $subscription = $cabinet->subscription;

        if (!$subscription) {
            return back()->with('error', "Ce cabinet n'a pas d'abonnement.");
        }

        $data = $request->validate([
            'montant' => 'required|integer|min:1',
            'moyen' => 'required|string|max:50',
            'date_paiement' => 'required|date',
            'mois_couverts' => 'required|integer|min:1',
            'note' => 'nullable|string',
        ]);

        $this->subscriptions->recordManualPayment($subscription, $data, Auth::guard('admin')->user());

        return back()->with('success', 'Paiement enregistré.');
    }

    public function changePlan(Request $request, $id)
    {
        $cabinet = Infocabinet::findOrFail($id);
        $subscription = $cabinet->subscription;

        if (!$subscription) {
            return back()->with('error', "Ce cabinet n'a pas d'abonnement.");
        }

        $data = $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $plan = SubscriptionPlan::findOrFail($data['subscription_plan_id']);
        $this->subscriptions->changePlan($subscription, $plan);

        return back()->with('success', 'Plan mis à jour.');
    }
}
