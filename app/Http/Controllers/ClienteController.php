<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteStoreRequest;
use App\Http\Requests\ClienteUpdateRequest;
use App\Models\Tenant\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function index(Request $request): Response
    {
        $clientes = Cliente::all();

        return view('clientes.index', [
            'clientes' => $clientes,
        ]);
    }

    public function create(Request $request): Response
    {
        return view('clientes.create');
    }

    public function store(ClienteStoreRequest $request): Response
    {
        $cliente = Cliente::create($request->validated());

        return redirect()->route('clientes.index');
    }

    public function edit(Request $request, Cliente $cliente): Response
    {
        $cliente = Cliente::find($id);

        return view('clientes.edit', [
            'cliente' => $cliente,
        ]);
    }

    public function update(ClienteUpdateRequest $request, Cliente $cliente): Response
    {
        $cliente->update($request->validated());

        return redirect()->route('clientes.index');
    }

    public function destroy(Request $request, Cliente $cliente): Response
    {
        $cliente->delete();

        return redirect()->route('clientes.index');
    }
}
