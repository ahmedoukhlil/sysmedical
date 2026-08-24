<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Infocabinet;
use App\Models\TUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CabinetController extends Controller
{
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
}
