{{-- Guia de ajuda: fiscal no produto (progressivo) --}}
<details class="vf-fiscal-ajuda border rounded-3 mb-3 bg-primary-subtle bg-opacity-25">
    <summary class="px-3 py-2 fw-semibold user-select-none d-flex align-items-center gap-2" style="cursor:pointer">
        <i class="bi bi-question-circle text-primary"></i>
        <span>Guia de ajuda — como preencher o fiscal?</span>
    </summary>
    <div class="px-3 pb-3 small">
        <p class="mb-2 mt-1">
            <strong>Regra do {{ config('app.name') }}:</strong> você pode vender sem preencher nada disso.
            Fiscal só importa se a loja <strong>emite nota</strong>. Vendedor de rua: foque em nome e preço.
        </p>

        <h4 class="h6 fw-bold mb-2">Na hora de criar o produto</h4>
        <ol class="mb-3 ps-3">
            <li class="mb-1"><strong>Tipo do produto</strong> — você escolhe:
                <ul class="mb-1 mt-1">
                    <li><em>Produção própria</em> = você faz (lanche, açaí, trufa)</li>
                    <li><em>Revenda</em> = comprou pronto (refri, água, doce industrializado)</li>
                    <li><em>Insumo</em> = não vende ao cliente (só usa na cozinha)</li>
                </ul>
            </li>
            <li class="mb-1"><strong>Usar padrões da empresa</strong> — deixe marcado. O sistema puxa NCM, CFOP e CSOSN já configurados.</li>
            <li class="mb-1"><strong>Avançado</strong> — só abra se o contador pediu valores diferentes neste item.</li>
        </ol>

        <h4 class="h6 fw-bold mb-2">De onde vêm as informações?</h4>
        <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered bg-white mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Campo</th>
                        <th>Quem informa</th>
                        <th>De onde vem</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Nome, preço, estoque</td>
                        <td><span class="badge text-bg-success">Você</span></td>
                        <td>Do seu negócio</td>
                    </tr>
                    <tr>
                        <td>Tipo (próprio / revenda)</td>
                        <td><span class="badge text-bg-success">Você</span></td>
                        <td>Senso comum do produto</td>
                    </tr>
                    <tr>
                        <td>NCM, CSOSN/CST, CFOP padrão</td>
                        <td><span class="badge text-bg-primary">Contador</span> ou suporte</td>
                        <td>Tabela da Receita / regra do Simples — cadastrados uma vez em Fiscal → Configurações</td>
                    </tr>
                    <tr>
                        <td>GTIN / código de barras</td>
                        <td><span class="badge text-bg-success">Você</span> (se tiver)</td>
                        <td>Embalagem do produto; se não tiver, deixe vazio</td>
                    </tr>
                    <tr>
                        <td>CEST / alíquotas especiais</td>
                        <td><span class="badge text-bg-primary">Contador</span></td>
                        <td>Só em casos específicos (substituição tributária etc.)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h4 class="h6 fw-bold mb-2">Exemplo rápido</h4>
        <ul class="mb-2 ps-3">
            <li><strong>X-Burger</strong> → tipo Produção própria → herdar padrão → Salvar</li>
            <li><strong>Coca 350ml</strong> → tipo Revenda → herdar padrão → Salvar</li>
        </ul>
        <p class="mb-0 text-muted">
            Não sabe o NCM padrão da loja? Pergunte ao contador ou ao suporte do {{ config('app.name') }} —
            isso se configura <strong>uma vez</strong> na empresa, não em todo produto.
            @isset($linkConfigFiscal)
                <a href="{{ $linkConfigFiscal }}">Abrir padrões fiscais</a>
            @endisset
        </p>
    </div>
</details>
