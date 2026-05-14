<?php

namespace App\Services\Mesas;

use App\Enums\Mesas\ComandaItemStatus;
use App\Enums\Mesas\ComandaStatus;
use App\Enums\Mesas\MesaStatus;
use App\Models\Comanda;
use App\Models\Mesa;
use Illuminate\Support\Collection;

class MesaService
{
    public function comandaAbertaNaMesa(Mesa $mesa): ?Comanda
    {
        return Comanda::query()
            ->where('mesa_id', $mesa->id)
            ->whereIn('status', ComandaStatus::abertasValues())
            ->orderByDesc('id')
            ->first();
    }

    public function garantirPodeAbrir(Mesa $mesa): void
    {
        if (! $mesa->ativo) {
            throw new \RuntimeException('Mesa inativa.');
        }
        if ($this->comandaAbertaNaMesa($mesa)) {
            throw new \RuntimeException('Já existe comanda aberta nesta mesa.');
        }
    }

    public function solicitarConta(Mesa $mesa): Comanda
    {
        $c = $this->comandaAbertaNaMesa($mesa);
        if (! $c) {
            throw new \RuntimeException('Nenhuma comanda aberta nesta mesa.');
        }
        if ($c->status === ComandaStatus::ContaSolicitada) {
            $mesa->status = MesaStatus::ContaSolicitada;
            $mesa->save();

            return $c;
        }

        $c->status = ComandaStatus::ContaSolicitada;
        $c->save();

        $mesa->status = MesaStatus::ContaSolicitada;
        $mesa->save();

        return $c;
    }

    /** Atualiza status visual da mesa conforme comanda e itens atuais. */
    public function sincronizarStatusMesa(Mesa $mesa): void
    {
        $mesa->refresh();
        $comanda = $this->comandaAbertaNaMesa($mesa);

        if (! $comanda) {
            $mesa->status = MesaStatus::Livre;
            $mesa->save();

            return;
        }

        if ($comanda->status === ComandaStatus::ContaSolicitada) {
            $mesa->status = MesaStatus::ContaSolicitada;
            $mesa->save();

            return;
        }

        /** @var Collection<int, \App\Models\ComandaItem> $itens */
        $itens = $comanda->itens()->get();
        $ativos = $itens->filter(fn ($i) => $i->status !== ComandaItemStatus::Cancelado);

        if ($ativos->isEmpty()) {
            $mesa->status = MesaStatus::Ocupada;
            $mesa->save();

            return;
        }

        $vals = $ativos->map(fn ($i) => $i->status->value);

        if ($vals->every(fn (string $v) => $v === ComandaItemStatus::Pendente->value)) {
            $mesa->status = MesaStatus::AguardandoPedido;
        } elseif ($vals->contains(fn (string $v) => in_array($v, [
            ComandaItemStatus::EmPreparo->value,
            ComandaItemStatus::Recebido->value,
            ComandaItemStatus::Pronto->value,
        ], true))) {
            $mesa->status = MesaStatus::EmPreparo;
        } elseif ($vals->contains(fn (string $v) => $v === ComandaItemStatus::Enviado->value)) {
            $mesa->status = MesaStatus::PedidoEnviado;
        } else {
            $mesa->status = MesaStatus::Ocupada;
        }

        $mesa->save();
    }

    public function liberarMesaAposFechamento(Comanda $comanda): void
    {
        $mesa = $comanda->mesa;
        $mesa->status = MesaStatus::Livre;
        $mesa->save();
    }
}
