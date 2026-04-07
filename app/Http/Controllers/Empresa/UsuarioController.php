<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para gerenciar usuários.');
        }

        $usuarios = User::query()
            ->where('empresa_id', $empresa->id)
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('empresa.usuarios.index', compact('empresa', 'usuarios'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para convidar usuários.');
        }

        $usuario = new User;
        $usuario->role = 'operador';

        return view('empresa.usuarios.form', compact('empresa', 'usuario'));
    }

    public function store(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para convidar usuários.');
        }

        $data = $this->validatedUsuario($request, null);
        $data['empresa_id'] = $empresa->id;

        User::query()->create($data);

        return redirect()
            ->route('empresa.usuarios.index')
            ->with('status', 'Usuário cadastrado. Informe a senha definida ao colaborador com segurança.');
    }

    public function edit(Request $request, User $usuario): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para gerenciar usuários.');
        }

        return view('empresa.usuarios.form', compact('empresa', 'usuario'));
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para gerenciar usuários.');
        }

        $data = $this->validatedUsuario($request, $usuario);
        if (! $request->filled('password')) {
            unset($data['password']);
        }

        $usuario->update($data);

        return redirect()
            ->route('empresa.usuarios.index')
            ->with('status', 'Usuário atualizado.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para gerenciar usuários.');
        }

        if ((int) $usuario->id === (int) $request->user()->id) {
            return redirect()
                ->route('empresa.usuarios.index')
                ->with('warning', 'Você não pode remover a própria conta.');
        }

        if ($usuario->acessaPainelMaster()) {
            return redirect()
                ->route('empresa.usuarios.index')
                ->with('warning', 'Este usuário é administrador da plataforma e não pode ser removido aqui.');
        }

        $usuario->delete();

        return redirect()
            ->route('empresa.usuarios.index')
            ->with('status', 'Usuário removido.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedUsuario(Request $request, ?User $existente): array
    {
        $emailRule = Rule::unique('users', 'email');
        if ($existente) {
            $emailRule->ignore($existente->id);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $emailRule],
            'role' => ['required', 'string', Rule::in(array_keys(User::rolesEquipe()))],
        ];

        if ($existente === null) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        } else {
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        }

        return $request->validate($rules);
    }
}
