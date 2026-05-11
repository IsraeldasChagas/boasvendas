<?php

namespace App\Models;

use App\Support\WhatsAppPedidoCliente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmpresaEntregador extends Model
{
    protected $table = 'empresa_entregadores';

    protected $fillable = [
        'empresa_id',
        'nome',
        'whatsapp',
        'foto',
        'moto_modelo',
        'moto_cor',
        'moto_placa',
        'ordem',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'ordem' => 'integer',
            'ativo' => 'boolean',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function rotuloMotoCurto(): string
    {
        $parts = array_filter([
            $this->moto_modelo ? trim((string) $this->moto_modelo) : null,
            $this->moto_cor ? trim((string) $this->moto_cor) : null,
            $this->moto_placa ? strtoupper(trim((string) $this->moto_placa)) : null,
        ]);

        return $parts === [] ? '—' : implode(' · ', $parts);
    }

    /**
     * Caminho absoluto da foto em public/uploads (ou legado storage).
     */
    public function resolveFotoAbsolutePath(): ?string
    {
        if ($this->foto === null || $this->foto === '') {
            return null;
        }

        $rel = ltrim(str_replace('\\', '/', (string) $this->foto), '/');
        if ($rel === '' || Str::contains($rel, '..')) {
            return null;
        }

        $candidates = [
            public_path('uploads/'.$rel),
            public_path($rel),
        ];

        if (Str::startsWith($rel, 'uploads/')) {
            $candidates[] = public_path($rel);
            $candidates[] = public_path('uploads/'.ltrim(Str::after($rel, 'uploads/'), '/'));
        }

        foreach (array_unique(array_filter($candidates)) as $full) {
            if (@is_file($full)) {
                return $full;
            }
        }

        $storage = storage_path('app/public/'.$rel);
        if (@is_file($storage)) {
            return $storage;
        }

        return null;
    }

    public function urlFoto(): ?string
    {
        if ($this->resolveFotoAbsolutePath() === null) {
            return null;
        }

        $v = $this->updated_at?->getTimestamp() ?? time();

        return route('empresa.entregadores.foto', ['entregador' => $this->getKey()], absolute: false).'?v='.$v;
    }

    /**
     * Abre só o contato do entregador no WhatsApp, sem texto automático do pedido
     * (disponibilidade e detalhes da entrega ficam na conversa com ele).
     *
     * @param  \App\Models\Pedido  $pedido  Mantido na assinatura por compatibilidade com a view; não é usado na URL.
     * @param  \App\Models\Empresa  $empresa  Idem.
     */
    public function urlWhatsAppPedido(Pedido $pedido, Empresa $empresa): ?string
    {
        $digits = WhatsAppPedidoCliente::normalizarTelefoneBr($this->whatsapp);
        if ($digits === null) {
            return null;
        }

        return 'https://wa.me/'.$digits;
    }
}
