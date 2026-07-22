<?php

namespace App\Http\Controllers;

use App\Models\Bendel;
use App\Services\BendelGenerator;
use Illuminate\Http\Request;

class BendelController extends Controller
{
    public function index()
    {
        $bendels = Bendel::latest()->paginate(10);

        return view('bendel.index', compact('bendels'));
    }

    public function store(Request $request, BendelGenerator $generator)
    {
        $request->validate([
            'tanggal_pendapatan' => 'required|date',
            'tanggal_setor' => 'required|date',
        ]);

        $generator->generate(
            $request->tanggal_pendapatan,
            $request->tanggal_setor
        );

        return redirect()
            ->route('bendel.index')
            ->with('success', 'Bendel berhasil dibuat.');
    }

    public function generate(Request $request, BendelGenerator $generator)
    {
        $request->validate([
            'tanggal_pendapatan' => 'required|date',
            'tanggal_setor' => 'required|date',
        ]);

        $generator->generate(
            $request->tanggal_pendapatan,
            $request->tanggal_setor
        );

        return redirect()
            ->route('bendel.index')
            ->with('success', 'Bendel berhasil dibuat.');
    }
}
