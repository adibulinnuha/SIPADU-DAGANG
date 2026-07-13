<?php

namespace App\Http\Controllers;

use App\Models\Trader;
use Illuminate\Http\Request;

class TraderController extends Controller
{
    public function index()
    {
        $traders = Trader::all();

        return view('traders.index', compact('traders'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(Trader $trader)
    {
        //
    }

    public function edit(Trader $trader)
    {
        //
    }

    public function update(Request $request, Trader $trader)
    {
        //
    }

    public function destroy(Trader $trader)
    {
        //
    }
}