<?php

namespace App\Services\Mesas;

use App\Enums\Mesas\ComandaItemStatus;
use App\Enums\Mesas\ComandaSetorDestino;
use App\Enums\Mesas\ComandaStatus;
use App\Models\ComandaItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CozinhaService
{
    public function __construct(
        private readonly MesaService $mesaService,
    ) {}

    /**
     * Itens que já saíram da comanda (enviados) e ainda não entregues na mesa.
     *
     * @return Collection<int, ComandaItem>
     */
    public function itensPainel(int $empresaId, ?ComandaSetorDestino $setor = null): Collection
    {
        $q = ComandaItem::query()
            // produto.fichaTecnica alimenta o modo de preparo exibido no painel.
            ->with(['comanda.mesa', 'produto.fichaTecnica.insumo'])
            ->whereHas('comanda', function (Builder $b) use ($empresaId) {
                $b->where('empresa_id', $empresaId)
                    ->whereIn('status', ComandaStatus::abertasValues());
            })
            ->whereIn('status', [
                ComandaItemStatus::Enviado,
                ComandaItemStatus::Recebido,
                ComandaItemStatus::EmPreparo,
                ComandaItemStatus::Pronto,
            ])
            ->orderBy('enviado_cozinha_em')
            ->orderBy('id');

        if ($setor !== null) {
            $q->where('setor_destino', $setor);
        }

        return $q->get();
    }

    public function atualizarStatusItem(ComandaItem $item, ComandaItemStatus $novo): void
    {
        $atual = $item->status;

        if (! $this->transicaoPermitida($atual, $novo)) {
            throw new \InvalidArgumentException('Transição de status inválida para o item.');
        }

        if ($novo === ComandaItemStatus::Pronto) {
            $item->pronto_em = now();
        }
        if ($novo === ComandaItemStatus::Entregue) {
            $item->entregue_em = now();
        }

        $item->status = $novo;
        $item->save();

        if ($item->comanda) {
            $this->mesaService->sincronizarStatusMesa($item->comanda->mesa);
        }
    }

    private function transicaoPermitida(ComandaItemStatus $de, ComandaItemStatus $para): bool
    {
        $map = [
            ComandaItemStatus::Enviado->value => [
                ComandaItemStatus::Recebido,
                ComandaItemStatus::EmPreparo,
            ],
            ComandaItemStatus::Recebido->value => [ComandaItemStatus::EmPreparo],
            ComandaItemStatus::EmPreparo->value => [ComandaItemStatus::Pronto],
            ComandaItemStatus::Pronto->value => [ComandaItemStatus::Entregue],
        ];

        $allowed = $map[$de->value] ?? [];

        return in_array($para, $allowed, true);
    }
}
