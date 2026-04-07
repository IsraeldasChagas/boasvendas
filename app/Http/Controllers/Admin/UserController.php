<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->with('empresa')->orderByDesc('id');

        if ($request->filled('q')) {
            $q = $request->string('q')->trim();
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%');
            });
        }

        $usuarios = $query->paginate(24)->withQueryString();

        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function create(): View
    {
        $empresas = Empresa::query()->orderBy('nome')->get();

        return view('admin.usuarios.create', compact('empresas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);
        unset($data['password_confirmation']);

        User::query()->create($data);

        return redirect()
            ->route('admin.usuarios.index')
            ->with('status', 'Usuário cadastrado.');
    }

    public function edit(User $user): View
    {
        $empresas = Empresa::query()->orderBy('nome')->get();

        return view('admin.usuarios.edit', compact('user', 'empresas'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);
        unset($data['password_confirmation']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()
            ->route('admin.usuarios.index')
            ->with('status', 'Usuário atualizado.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ((int) $user->id === (int) auth()->id()) {
            return redirect()
                ->route('admin.usuarios.index')
                ->with('warning', 'Você não pode remover a própria conta.');
        }

        $user->delete();

        return redirect()
            ->route('admin.usuarios.index')
            ->with('status', 'Usuário removido.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $existente = null): array
    {
        $emailRule = Rule::unique('users', 'email');
        if ($existente) {
            $emailRule->ignore($existente->id);
        }

        $passwordRules = $existente
            ? ['nullable', 'string', Password::defaults(), 'confirmed']
            : ['required', 'string', Password::defaults(), 'confirmed'];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', $emailRule],
            'password' => $passwordRules,
            'empresa_id' => ['nullable', 'integer', 'exists:empresas,id'],
            'role' => ['required', 'string', Rule::in(array_keys(User::rolesEquipe()))],
        ]);
    }
}
