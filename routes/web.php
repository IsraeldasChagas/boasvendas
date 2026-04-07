<?php

use App\Http\Controllers\Admin\AssinaturaController as AdminAssinaturaController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EmpresaController as AdminEmpresaController;
use App\Http\Controllers\Admin\ModuloController as AdminModuloController;
use App\Http\Controllers\Admin\PlanoController as AdminPlanoController;
use App\Http\Controllers\Admin\SuporteController as AdminSuporteController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Empresa\CaixaController;
use App\Http\Controllers\Empresa\CategoriaController;
use App\Http\Controllers\Empresa\ChamadoController as EmpresaChamadoController;
use App\Http\Controllers\Empresa\ClienteController;
use App\Http\Controllers\Empresa\ConfiguracaoController;
use App\Http\Controllers\Empresa\DashboardController as EmpresaDashboardController;
use App\Http\Controllers\Empresa\EntregaController;
use App\Http\Controllers\Empresa\FidelidadeController;
use App\Http\Controllers\Empresa\FinanceiroController;
use App\Http\Controllers\Empresa\PedidoController;
use App\Http\Controllers\Empresa\ProdutoController;
use App\Http\Controllers\Empresa\RelatorioController;
use App\Http\Controllers\Empresa\UsuarioController;
use App\Http\Controllers\Empresa\VendaExternaController;
use App\Http\Controllers\Publico\FidelidadePublicController;
use App\Http\Controllers\Publico\PublicoController;
use App\Http\Controllers\Site\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('site.home');
Route::get('/planos', [SiteController::class, 'planos'])->name('site.planos');
Route::get('/sobre', [SiteController::class, 'sobre'])->name('site.sobre');
Route::get('/contato', [SiteController::class, 'contato'])->name('site.contato');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])
        ->middleware('throttle:10,1')
        ->name('login.authenticate');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/cadastro-empresa', [AuthController::class, 'cadastroEmpresa'])->name('auth.cadastro-empresa');
Route::get('/cadastro-usuario', [AuthController::class, 'cadastroUsuario'])->name('auth.cadastro-usuario');
Route::get('/esqueci-senha', [AuthController::class, 'esqueciSenha'])->name('auth.esqueci-senha');
Route::get('/redefinir-senha', [AuthController::class, 'redefinirSenha'])->name('auth.redefinir-senha');

Route::get('/loja/{slug}/fidelidade', [FidelidadePublicController::class, 'show'])->name('publico.fidelidade');
Route::post('/loja/{slug}/fidelidade', [FidelidadePublicController::class, 'consultar'])
    ->middleware('throttle:30,1')
    ->name('publico.fidelidade.consultar');
Route::get('/loja/{slug}/produto/{produto_id}', [PublicoController::class, 'produto'])
    ->whereNumber('produto_id')
    ->name('publico.produto');
Route::post('/loja/{slug}/carrinho/adicionar', [PublicoController::class, 'carrinhoAdicionar'])
    ->middleware('throttle:60,1')
    ->name('publico.carrinho.adicionar');
Route::get('/loja/{slug}/carrinho', [PublicoController::class, 'carrinho'])->name('publico.carrinho');
Route::post('/loja/{slug}/carrinho/atualizar', [PublicoController::class, 'carrinhoAtualizar'])->name('publico.carrinho.atualizar');
Route::post('/loja/{slug}/carrinho/remover', [PublicoController::class, 'carrinhoRemover'])->name('publico.carrinho.remover');
Route::get('/loja/{slug}/checkout', [PublicoController::class, 'checkout'])->name('publico.checkout');
Route::post('/loja/{slug}/checkout', [PublicoController::class, 'checkoutFinalizar'])
    ->middleware('throttle:20,1')
    ->name('publico.checkout.finalizar');
Route::get('/loja/{slug}/acompanhar', [PublicoController::class, 'acompanhar'])->name('publico.acompanhar');
Route::post('/loja/{slug}/acompanhar', [PublicoController::class, 'acompanharBuscar'])
    ->middleware('throttle:30,1')
    ->name('publico.acompanhar.buscar');
Route::get('/loja/{slug}/pedido/{codigo}', [PublicoController::class, 'pedidoPublico'])
    ->where('codigo', '[A-Za-z0-9\-]+')
    ->name('publico.pedido.show');
Route::get('/loja/{slug}', [PublicoController::class, 'loja'])->name('publico.loja');

Route::get('/produto/{id}', [PublicoController::class, 'legadoProduto'])->name('publico.produto.legado');
Route::get('/carrinho', [PublicoController::class, 'legadoCarrinho'])->name('publico.carrinho.legado');
Route::get('/checkout', [PublicoController::class, 'legadoCheckout'])->name('publico.checkout.legado');
Route::get('/acompanhar-pedido', [PublicoController::class, 'legadoAcompanhar'])->name('publico.acompanhar-pedido.legado');

Route::middleware(['auth', 'empresa.painel'])->prefix('empresa')->name('empresa.')->group(function () {
    Route::get('/dashboard', [EmpresaDashboardController::class, 'index'])->name('dashboard');

    Route::get('/suporte/chamados', [EmpresaChamadoController::class, 'index'])->name('chamados.index');
    Route::get('/suporte/chamados/novo', [EmpresaChamadoController::class, 'create'])->name('chamados.create');
    Route::post('/suporte/chamados', [EmpresaChamadoController::class, 'store'])
        ->middleware('throttle:15,1')
        ->name('chamados.store');
    Route::post('/suporte/chamados/{suporteTicket}/mensagens', [EmpresaChamadoController::class, 'storeMensagem'])
        ->middleware('throttle:30,1')
        ->name('chamados.mensagens.store');
    Route::get('/suporte/chamados/{suporteTicket}', [EmpresaChamadoController::class, 'show'])->name('chamados.show');

    Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/{pedido}', [PedidoController::class, 'show'])->name('pedidos.show');
    Route::put('/pedidos/{pedido}/status', [PedidoController::class, 'updateStatus'])->name('pedidos.status');

    Route::get('/produtos', [ProdutoController::class, 'index'])->name('produtos.index');
    Route::get('/produtos/novo', [ProdutoController::class, 'create'])->name('produtos.create');
    Route::post('/produtos', [ProdutoController::class, 'store'])->name('produtos.store');
    Route::get('/produtos/{produto}/editar', [ProdutoController::class, 'edit'])->name('produtos.edit');
    Route::put('/produtos/{produto}', [ProdutoController::class, 'update'])->name('produtos.update');
    Route::delete('/produtos/{produto}', [ProdutoController::class, 'destroy'])->name('produtos.destroy');

    Route::get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index');
    Route::get('/categorias/nova', [CategoriaController::class, 'create'])->name('categorias.create');
    Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store');
    Route::get('/categorias/{categoria}/editar', [CategoriaController::class, 'edit'])->name('categorias.edit');
    Route::put('/categorias/{categoria}', [CategoriaController::class, 'update'])->name('categorias.update');
    Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');
    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::get('/clientes/novo', [ClienteController::class, 'create'])->name('clientes.create');
    Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
    Route::get('/clientes/{cliente}/editar', [ClienteController::class, 'edit'])->name('clientes.edit');
    Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])->name('clientes.update');
    Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');

    Route::get('/fidelidade', [FidelidadeController::class, 'programa'])->name('fidelidade.programa');
    Route::put('/fidelidade', [FidelidadeController::class, 'programaUpdate'])->name('fidelidade.programa.update');
    Route::get('/fidelidade/cartoes', [FidelidadeController::class, 'cartoes'])->name('fidelidade.cartoes');
    Route::post('/fidelidade/cartoes/selo', [FidelidadeController::class, 'adicionarSelo'])->name('fidelidade.cartoes.selo');
    Route::post('/fidelidade/cartoes/{fidelidadeCartao}/resgatar', [FidelidadeController::class, 'resgatar'])->name('fidelidade.cartoes.resgatar');

    Route::get('/entregas', [EntregaController::class, 'index'])->name('entregas.index');

    Route::get('/financeiro', [FinanceiroController::class, 'index'])->name('financeiro.index');

    Route::get('/contas-receber', [FinanceiroController::class, 'receberIndex'])->name('financeiro.contas-receber');
    Route::get('/contas-receber/novo', [FinanceiroController::class, 'receberCreate'])->name('financeiro.contas-receber.create');
    Route::post('/contas-receber', [FinanceiroController::class, 'receberStore'])->name('financeiro.contas-receber.store');
    Route::get('/contas-receber/{financeiroTitulo}/editar', [FinanceiroController::class, 'receberEdit'])->name('financeiro.contas-receber.edit');
    Route::put('/contas-receber/{financeiroTitulo}', [FinanceiroController::class, 'receberUpdate'])->name('financeiro.contas-receber.update');
    Route::delete('/contas-receber/{financeiroTitulo}', [FinanceiroController::class, 'receberDestroy'])->name('financeiro.contas-receber.destroy');
    Route::post('/contas-receber/{financeiroTitulo}/baixar', [FinanceiroController::class, 'receberBaixar'])->name('financeiro.contas-receber.baixar');

    Route::get('/contas-pagar', [FinanceiroController::class, 'pagarIndex'])->name('financeiro.contas-pagar');
    Route::get('/contas-pagar/novo', [FinanceiroController::class, 'pagarCreate'])->name('financeiro.contas-pagar.create');
    Route::post('/contas-pagar', [FinanceiroController::class, 'pagarStore'])->name('financeiro.contas-pagar.store');
    Route::get('/contas-pagar/{financeiroTitulo}/editar', [FinanceiroController::class, 'pagarEdit'])->name('financeiro.contas-pagar.edit');
    Route::put('/contas-pagar/{financeiroTitulo}', [FinanceiroController::class, 'pagarUpdate'])->name('financeiro.contas-pagar.update');
    Route::delete('/contas-pagar/{financeiroTitulo}', [FinanceiroController::class, 'pagarDestroy'])->name('financeiro.contas-pagar.destroy');
    Route::post('/contas-pagar/{financeiroTitulo}/baixar', [FinanceiroController::class, 'pagarBaixar'])->name('financeiro.contas-pagar.baixar');

    Route::get('/caixa', [CaixaController::class, 'index'])->name('caixa.index');
    Route::post('/caixa/abrir', [CaixaController::class, 'abrir'])->name('caixa.abrir');
    Route::post('/caixa/movimento', [CaixaController::class, 'movimento'])->name('caixa.movimento');
    Route::post('/caixa/fechar', [CaixaController::class, 'fechar'])->name('caixa.fechar');
    Route::get('/caixa/conferencia', [CaixaController::class, 'conferencia'])->name('caixa.conferencia');
    Route::get('/relatorios', [RelatorioController::class, 'index'])->name('relatorios.index');
    Route::get('/configuracoes', [ConfiguracaoController::class, 'index'])->name('configuracoes.index');
    Route::put('/configuracoes', [ConfiguracaoController::class, 'update'])->name('configuracoes.update');
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/novo', [UsuarioController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios', [UsuarioController::class, 'store'])
        ->middleware('throttle:15,1')
        ->name('usuarios.store');
    Route::get('/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
    Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{usuario}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');

    Route::prefix('venda-externa')->name('venda-externa.')->group(function () {
        Route::get('/dashboard', [VendaExternaController::class, 'dashboard'])->name('dashboard');
        Route::get('/pontos', [VendaExternaController::class, 'pontosIndex'])->name('pontos');
        Route::get('/pontos/novo', [VendaExternaController::class, 'pontosCreate'])->name('pontos.create');
        Route::post('/pontos', [VendaExternaController::class, 'pontosStore'])->name('pontos.store');
        Route::get('/pontos/{vePonto}/editar', [VendaExternaController::class, 'pontosEdit'])->name('pontos.edit');
        Route::put('/pontos/{vePonto}', [VendaExternaController::class, 'pontosUpdate'])->name('pontos.update');
        Route::delete('/pontos/{vePonto}', [VendaExternaController::class, 'pontosDestroy'])->name('pontos.destroy');
        Route::get('/remessas', [VendaExternaController::class, 'remessasIndex'])->name('remessas.index');
        Route::get('/remessas/novo', [VendaExternaController::class, 'remessasCreate'])->name('remessas.create');
        Route::post('/remessas', [VendaExternaController::class, 'remessasStore'])->name('remessas.store');
        Route::get('/remessas/{veRemessa}/editar', [VendaExternaController::class, 'remessasEdit'])->name('remessas.edit');
        Route::put('/remessas/{veRemessa}', [VendaExternaController::class, 'remessasUpdate'])->name('remessas.update');
        Route::delete('/remessas/{veRemessa}', [VendaExternaController::class, 'remessasDestroy'])->name('remessas.destroy');
        Route::get('/remessas/{veRemessa}', [VendaExternaController::class, 'remessasShow'])->name('remessas.show');
        Route::get('/acertos', [VendaExternaController::class, 'acertosIndex'])->name('acertos');
        Route::get('/acertos/novo', [VendaExternaController::class, 'acertosCreate'])->name('acertos.create');
        Route::post('/acertos', [VendaExternaController::class, 'acertosStore'])->name('acertos.store');
        Route::get('/acertos/{veAcerto}/editar', [VendaExternaController::class, 'acertosEdit'])->name('acertos.edit');
        Route::put('/acertos/{veAcerto}', [VendaExternaController::class, 'acertosUpdate'])->name('acertos.update');
        Route::delete('/acertos/{veAcerto}', [VendaExternaController::class, 'acertosDestroy'])->name('acertos.destroy');
        Route::get('/acertos/{veAcerto}', [VendaExternaController::class, 'acertosShow'])->name('acertos.show');
        Route::get('/fiados', [VendaExternaController::class, 'fiadosIndex'])->name('fiados');
        Route::get('/fiados/novo', [VendaExternaController::class, 'fiadosCreate'])->name('fiados.create');
        Route::post('/fiados', [VendaExternaController::class, 'fiadosStore'])->name('fiados.store');
        Route::get('/fiados/{veFiado}/editar', [VendaExternaController::class, 'fiadosEdit'])->name('fiados.edit');
        Route::put('/fiados/{veFiado}', [VendaExternaController::class, 'fiadosUpdate'])->name('fiados.update');
        Route::delete('/fiados/{veFiado}', [VendaExternaController::class, 'fiadosDestroy'])->name('fiados.destroy');
        Route::post('/fiados/{veFiado}/baixar', [VendaExternaController::class, 'fiadosBaixar'])->name('fiados.baixar');
        Route::get('/fiados/{veFiado}', [VendaExternaController::class, 'fiadosShow'])->name('fiados.show');
        Route::get('/relatorios/export/acertos', [VendaExternaController::class, 'relatoriosExportAcertos'])->name('relatorios.export.acertos');
        Route::get('/relatorios/export/fiados', [VendaExternaController::class, 'relatoriosExportFiados'])->name('relatorios.export.fiados');
        Route::get('/relatorios/export/remessas', [VendaExternaController::class, 'relatoriosExportRemessas'])->name('relatorios.export.remessas');
        Route::get('/relatorios', [VendaExternaController::class, 'relatorios'])->name('relatorios');
    });
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/usuarios', [AdminUserController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/criar', [AdminUserController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios', [AdminUserController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('usuarios.store');
    Route::get('/usuarios/{user}/editar', [AdminUserController::class, 'edit'])->name('usuarios.edit');
    Route::put('/usuarios/{user}', [AdminUserController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('usuarios.update');
    Route::delete('/usuarios/{user}', [AdminUserController::class, 'destroy'])->name('usuarios.destroy');
    Route::get('/empresas', [AdminEmpresaController::class, 'index'])->name('empresas.index');
    Route::get('/empresas/criar', [AdminEmpresaController::class, 'create'])->name('empresas.create');
    Route::post('/empresas', [AdminEmpresaController::class, 'store'])->name('empresas.store');
    Route::get('/empresas/{empresa}/editar', [AdminEmpresaController::class, 'edit'])->name('empresas.edit');
    Route::get('/empresas/{empresa}', [AdminEmpresaController::class, 'show'])->name('empresas.show');
    Route::put('/empresas/{empresa}', [AdminEmpresaController::class, 'update'])->name('empresas.update');
    Route::delete('/empresas/{empresa}', [AdminEmpresaController::class, 'destroy'])->name('empresas.destroy');
    Route::get('/planos', [AdminPlanoController::class, 'index'])->name('planos.index');
    Route::get('/planos/criar', [AdminPlanoController::class, 'create'])->name('planos.create');
    Route::post('/planos', [AdminPlanoController::class, 'store'])->name('planos.store');
    Route::get('/planos/{plano}/editar', [AdminPlanoController::class, 'edit'])->name('planos.edit');
    Route::put('/planos/{plano}', [AdminPlanoController::class, 'update'])->name('planos.update');
    Route::delete('/planos/{plano}', [AdminPlanoController::class, 'destroy'])->name('planos.destroy');
    Route::get('/modulos', [AdminModuloController::class, 'index'])->name('modulos.index');
    Route::get('/modulos/criar', [AdminModuloController::class, 'create'])->name('modulos.create');
    Route::post('/modulos', [AdminModuloController::class, 'store'])->name('modulos.store');
    Route::get('/modulos/{modulo}/editar', [AdminModuloController::class, 'edit'])->name('modulos.edit');
    Route::put('/modulos/{modulo}', [AdminModuloController::class, 'update'])->name('modulos.update');
    Route::delete('/modulos/{modulo}', [AdminModuloController::class, 'destroy'])->name('modulos.destroy');
    Route::get('/assinaturas', [AdminAssinaturaController::class, 'index'])->name('assinaturas.index');
    Route::get('/assinaturas/criar', [AdminAssinaturaController::class, 'create'])->name('assinaturas.create');
    Route::post('/assinaturas', [AdminAssinaturaController::class, 'store'])->name('assinaturas.store');
    Route::get('/assinaturas/{assinatura}/editar', [AdminAssinaturaController::class, 'edit'])->name('assinaturas.edit');
    Route::put('/assinaturas/{assinatura}', [AdminAssinaturaController::class, 'update'])->name('assinaturas.update');
    Route::delete('/assinaturas/{assinatura}', [AdminAssinaturaController::class, 'destroy'])->name('assinaturas.destroy');
    Route::get('/suporte', [AdminSuporteController::class, 'index'])->name('suporte.index');
    Route::get('/suporte/criar', [AdminSuporteController::class, 'create'])->name('suporte.create');
    Route::post('/suporte', [AdminSuporteController::class, 'store'])->name('suporte.store');
    Route::get('/suporte/{ticket}/editar', [AdminSuporteController::class, 'edit'])->name('suporte.edit');
    Route::get('/suporte/{ticket}', [AdminSuporteController::class, 'show'])->name('suporte.show');
    Route::put('/suporte/{ticket}', [AdminSuporteController::class, 'update'])->name('suporte.update');
    Route::delete('/suporte/{ticket}', [AdminSuporteController::class, 'destroy'])->name('suporte.destroy');
});
