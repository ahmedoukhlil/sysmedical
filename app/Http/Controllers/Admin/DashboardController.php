<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CabinetSubscription;
use App\Models\Infocabinet;
use App\Models\TUser;

class DashboardController extends Controller
{
    public function index()
    {
        $totalCabinets = Infocabinet::count();
        $totalActifs = Infocabinet::where('statut', 'actif')->count();
        $totalUsers = TUser::withoutTenant()->where('ismasquer', 0)->count();
        $essaisEnCours = CabinetSubscription::where('statut', CabinetSubscription::STATUT_ESSAI)->count();
        $abonnementsImpayes = CabinetSubscription::whereIn('statut', [
            CabinetSubscription::STATUT_IMPAYE,
            CabinetSubscription::STATUT_SUSPENDU,
        ])->count();

        return view('admin.dashboard', compact(
            'totalCabinets',
            'totalActifs',
            'totalUsers',
            'essaisEnCours',
            'abonnementsImpayes'
        ));
    }
}
