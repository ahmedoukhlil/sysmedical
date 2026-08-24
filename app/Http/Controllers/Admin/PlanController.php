<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;

class PlanController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::orderBy('ordre')->get();

        return view('admin.plans.index', compact('plans'));
    }
}
