<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\SuporteTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SuporteController extends Controller
{
    public function index(Request $request): View
    {
        $query = SuporteTicket::query()->with('empresa')->orderByDesc('updated_at');

        if ($request->filled('q')) {
            $q = $request->string('q')->trim();
            $query->where(function ($sub) use ($q) {
                $sub->where('assunto', 'like', '%'.$q.'%')
                    ->orWhere('descricao', 'like', '%'.$q.'%')
                    ->orWhereHas('empresa', fn ($e) => $e->where('nome', 'like', '%'.$q.'%'));
            });
        }

        $st = $request->input('status');
        if (is_string($st) && array_key_exists($st, SuporteTicket::statusRotulos())) {
            $query->where('status', $st);
        }

        $pr = $request->input('prioridade');
        if (is_string($pr) && array_key_exists($pr, SuporteTicket::prioridades())) {
            $query->where('prioridade', $pr);
        }

        $tickets = $query->get();

        $abertos = SuporteTicket::query()->whereIn('status', ['aberto', 'aguardando', 'em_andamento'])->count();
        $resolvidos7d = SuporteTicket::query()
            ->where('status', 'resolvido')
            ->where('updated_at', '>=', now()->subDays(7))
            ->count();

        return view('admin.suporte.index', compact('tickets', 'abertos', 'resolvidos7d'));
    }

    public function create(): View
    {
        $empresas = Empresa::query()->orderBy('nome')->get();

        return view('admin.suporte.create', compact('empresas'));
    }

    public function store(Request $request): RedirectResponse
    {
        SuporteTicket::query()->create($this->validated($request));

        return redirect()
            ->route('admin.suporte.index')
            ->with('status', 'Ticket criado.');
    }

    public function show(SuporteTicket $ticket): View
    {
        $ticket->load(['empresa', 'mensagens.user']);

        return view('admin.suporte.show', compact('ticket'));
    }

    public function edit(SuporteTicket $ticket): View
    {
        $empresas = Empresa::query()->orderBy('nome')->get();

        return view('admin.suporte.edit', compact('ticket', 'empresas'));
    }

    public function update(Request $request, SuporteTicket $ticket): RedirectResponse
    {
        $ticket->update($this->validated($request));

        return redirect()
            ->route('admin.suporte.show', $ticket)
            ->with('status', 'Ticket atualizado.');
    }

    public function destroy(SuporteTicket $ticket): RedirectResponse
    {
        $ticket->delete();

        return redirect()
            ->route('admin.suporte.index')
            ->with('status', 'Ticket removido.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'empresa_id' => ['nullable', 'integer', 'exists:empresas,id'],
            'assunto' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:10000'],
            'prioridade' => ['required', 'string', Rule::in(array_keys(SuporteTicket::prioridades()))],
            'status' => ['required', 'string', Rule::in(array_keys(SuporteTicket::statusRotulos()))],
        ]);
    }
}
