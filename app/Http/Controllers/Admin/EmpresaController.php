<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Plano;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class EmpresaController extends Controller
{
    public function index(Request $request): View
    {
        $query = Empresa::query()->with('plano')->orderBy('nome');

        if ($request->filled('q')) {
            $q = $request->string('q')->trim();
            $query->where(function ($sub) use ($q) {
                $sub->where('nome', 'like', '%'.$q.'%')
                    ->orWhere('email_contato', 'like', '%'.$q.'%')
                    ->orWhere('cnpj', 'like', '%'.$q.'%');
            });
        }

        $statusFiltro = $request->input('status');
        if (is_string($statusFiltro) && array_key_exists($statusFiltro, Empresa::statusRotulos())) {
            $query->where('status', $statusFiltro);
        }

        $empresas = $query->get();

        return view('admin.empresas.index', compact('empresas'));
    }

    public function create(): View
    {
        $planos = Plano::query()->orderBy('ordem')->orderBy('nome')->get();

        return view('admin.empresas.create', compact('planos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $admin = $this->validatedAdminUser($request);

        DB::transaction(function () use ($data, $admin) {
            $empresa = Empresa::query()->create($data);

            User::query()->create([
                'name' => $admin['admin_name'],
                'email' => $admin['admin_email'],
                'password' => $admin['admin_password'],
                'empresa_id' => $empresa->id,
                'role' => 'gestor',
            ]);
        });

        return redirect()
            ->route('admin.empresas.index')
            ->with('status', 'Empresa cadastrada e usuário administrador criado.');
    }

    public function show(Empresa $empresa): View
    {
        $empresa->load('plano');

        return view('admin.empresas.show', compact('empresa'));
    }

    public function edit(Empresa $empresa): View
    {
        $planos = Plano::query()->orderBy('ordem')->orderBy('nome')->get();

        return view('admin.empresas.edit', compact('empresa', 'planos'));
    }

    public function update(Request $request, Empresa $empresa): RedirectResponse
    {
        $empresa->update($this->validated($request));

        return redirect()
            ->route('admin.empresas.show', $empresa)
            ->with('status', 'Empresa atualizada.');
    }

    public function destroy(Empresa $empresa): RedirectResponse
    {
        $empresa->delete();

        return redirect()
            ->route('admin.empresas.index')
            ->with('status', 'Empresa removida.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'email_contato' => ['nullable', 'email', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:32'],
            'plano_id' => ['nullable', 'integer', 'exists:planos,id'],
            'status' => ['required', 'string', Rule::in(array_keys(Empresa::statusRotulos()))],
            'modulos_resumo' => ['nullable', 'string', 'max:255'],
            'cliente_desde' => ['nullable', 'date'],
        ]);
    }

    /**
     * @return array{admin_name:string,admin_email:string,admin_password:string}
     */
    private function validatedAdminUser(Request $request): array
    {
        $validated = $request->validate([
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'admin_password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        return [
            'admin_name' => $validated['admin_name'],
            'admin_email' => $validated['admin_email'],
            'admin_password' => $validated['admin_password'],
        ];
    }
}
