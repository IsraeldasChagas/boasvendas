<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para gerenciar clientes.');
        }

        $query = Cliente::query()
            ->where('empresa_id', $empresa->id)
            ->orderBy('nome');

        if ($request->filled('q')) {
            $q = $request->string('q')->trim();
            $query->where(function ($sub) use ($q) {
                $sub->where('nome', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%')
                    ->orWhere('telefone', 'like', '%'.$q.'%')
                    ->orWhere('documento', 'like', '%'.$q.'%');
            });
        }

        if ($request->filled('ativo')) {
            $ativo = $request->input('ativo');
            if ($ativo === '1') {
                $query->where('ativo', true);
            }
            if ($ativo === '0') {
                $query->where('ativo', false);
            }
        }

        $clientes = $query->get();

        return view('empresa.clientes.index', compact('empresa', 'clientes'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        return view('empresa.clientes.create', compact('empresa'));
    }

    public function store(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $data = $this->validated($request);
        $data['empresa_id'] = $empresa->id;

        Cliente::query()->create($data);

        return redirect()
            ->route('empresa.clientes.index')
            ->with('status', 'Cliente cadastrado.');
    }

    public function edit(Request $request, Cliente $cliente): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $cliente->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        return view('empresa.clientes.edit', compact('empresa', 'cliente'));
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $cliente->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $cliente->update($this->validated($request));

        return redirect()
            ->route('empresa.clientes.index')
            ->with('status', 'Cliente atualizado.');
    }

    public function destroy(Request $request, Cliente $cliente): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $cliente->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $cliente->delete();

        return redirect()
            ->route('empresa.clientes.index')
            ->with('status', 'Cliente removido.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:32'],
            'documento' => ['nullable', 'string', 'max:32'],
            'observacoes' => ['nullable', 'string', 'max:10000'],
        ]);

        $data['ativo'] = $request->boolean('ativo');

        return $data;
    }
}
