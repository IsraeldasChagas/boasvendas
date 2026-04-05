<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\SuporteTicket;
use App\Models\SuporteTicketMensagem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChamadoController extends Controller
{
    public function index(Request $request): RedirectResponse|View
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Sua conta precisa estar vinculada a uma empresa para ver os chamados.');
        }

        $query = SuporteTicket::query()
            ->where('empresa_id', $empresa->id);

        if ($request->filled('q')) {
            $needle = '%'.$request->string('q')->trim()->value().'%';
            $query->where(function ($sub) use ($needle) {
                $sub->where('assunto', 'like', $needle)
                    ->orWhere('descricao', 'like', $needle);
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

        $tickets = $query->orderByDesc('updated_at')->paginate(15)->withQueryString();

        return view('empresa.chamados.index', compact('empresa', 'tickets'));
    }

    public function create(Request $request): RedirectResponse|View
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Sua conta precisa estar vinculada a uma empresa para abrir chamados.');
        }

        return view('empresa.chamados.create', compact('empresa'));
    }

    public function store(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Sua conta precisa estar vinculada a uma empresa para abrir chamados.');
        }

        $data = $request->validate([
            'assunto' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:10000'],
            'prioridade' => ['required', 'string', Rule::in(array_keys(SuporteTicket::prioridades()))],
        ]);

        $data['empresa_id'] = $empresa->id;
        $data['status'] = 'aberto';

        $ticket = SuporteTicket::query()->create($data);

        return redirect()
            ->route('empresa.chamados.show', ['suporteTicket' => $ticket])
            ->with('status', 'Chamado aberto com sucesso.');
    }

    public function show(Request $request, SuporteTicket $suporteTicket): View
    {
        $suporteTicket->load(['mensagens.user']);

        return view('empresa.chamados.show', ['ticket' => $suporteTicket]);
    }

    public function storeMensagem(Request $request, SuporteTicket $suporteTicket): RedirectResponse
    {
        $validated = $request->validate([
            'corpo' => ['required', 'string', 'max:5000'],
        ]);

        SuporteTicketMensagem::query()->create([
            'suporte_ticket_id' => $suporteTicket->id,
            'user_id' => $request->user()->id,
            'corpo' => $validated['corpo'],
        ]);

        if (in_array($suporteTicket->status, ['resolvido', 'fechado'], true)) {
            $suporteTicket->update(['status' => 'aguardando']);
        }

        $suporteTicket->touch();

        return redirect()
            ->route('empresa.chamados.show', ['suporteTicket' => $suporteTicket])
            ->with('status', 'Mensagem enviada.');
    }
}
