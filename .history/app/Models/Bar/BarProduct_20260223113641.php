<?php

namespace App\Models\Bar;

use Illuminate\Database\Eloquent\Model;

class BarProduct extends Model
{
    protected $table = 'bar_products';

    protected $fillable = [
        'bar_category_id',
        'barcode',
        'name',
        'purchase_price',
        'sale_price',
        'stock_quantity',
        'min_stock',
        'is_active',
        'unit_type',
        'content_quantity',
        'manage_stock',
        'is_combo'
    ];

    /**
     * Relacionamento: Um produto (combo) tem várias composições.
     */
    public function compositions()
    {
        return $this->hasMany(BarProductComposition::class, 'parent_id');
    }

    /**
     * Relacionamento: Um produto pertence a uma categoria.
     */
    public function category()
    {
        return $this->belongsTo(BarCategory::class, 'bar_category_id');
    }

    /**
     * 🚀 MÉTODO: BAIXA DE ESTOQUE (Vendas PDV e Mesas)
     */
    public function baixarEstoque($quantidadeVendida, $referencia = null)
    {
        // 1. Se for um COMBO, processa os itens filhos
        if ($this->is_combo) {
            $itensCombo = $this->compositions()->get();

            foreach ($itensCombo as $item) {
                $produtoFilho = BarProduct::find($item->child_id);

                if ($produtoFilho) {
                    $totalParaAbater = $item->quantity * $quantidadeVendida;

                    // 🛡️ SÓ diminui o número se a gestão estiver ativa (Ex: Refri sim, Coxinha não)
                    if ($produtoFilho->manage_stock) {
                        $produtoFilho->decrement('stock_quantity', $totalParaAbater);
                    }

                    // 🚀 SEMPRE gera o log de movimentação para auditoria
                    BarStockMovement::create([
                        'bar_product_id' => $produtoFilho->id,
                        'user_id'        => auth()->id() ?? 1,
                        'quantity'       => -$totalParaAbater,
                        'type'           => 'saida',
                        'description'    => "BAIXA AUTOMÁTICA COMBO: [{$this->name}]" . ($referencia ? " | Ref: {$referencia}" : ""),
                    ]);
                }
            }
        }

        // 2. Se for produto SIMPLES (venda direta de um item que não é combo)
        if (!$this->is_combo && $this->manage_stock) {
            $this->decrement('stock_quantity', $quantidadeVendida);
        }

        // Registro da venda do item principal (o que sai no cupom/financeiro)
        BarStockMovement::create([
            'bar_product_id' => $this->id,
            'user_id'        => auth()->id() ?? 1,
            'quantity'       => -$quantidadeVendida,
            'type'           => 'saida',
            'description'    => "Venda Realizada" . ($referencia ? " | Ref: {$referencia}" : ""),
        ]);
    }

    /**
     * 🔄 MÉTODO: ESTORNO DE ESTOQUE (Cancelamento de Vendas)
     */
    public function devolverEstoque($quantidadeEstornada, $referencia = null)
    {
        // 1. Se for um COMBO, devolve individualmente os itens ao estoque
        if ($this->is_combo) {
            $itensCombo = $this->compositions()->get();

            foreach ($itensCombo as $item) {
                $produtoFilho = BarProduct::find($item->child_id);

                if ($produtoFilho && $produtoFilho->manage_stock) {
                    $totalParaDevolver = $item->quantity * $quantidadeEstornada;

                    $produtoFilho->increment('stock_quantity', $totalParaDevolver);

                    BarStockMovement::create([
                        'bar_product_id' => $produtoFilho->id,
                        'user_id'        => auth()->id() ?? 1,
                        'quantity'       => $totalParaDevolver,
                        'type'           => 'entrada',
                        'description'    => "ESTORNO COMBO: [{$this->name}]" . ($referencia ? " | Ref: {$referencia}" : ""),
                    ]);
                }
            }
        }

        // 2. Se for produto SIMPLES, devolve ao próprio estoque
        if (!$this->is_combo && $this->manage_stock) {
            $this->increment('stock_quantity', $quantidadeEstornada);
        }

        // Registro da movimentação do item principal (estorno da venda)
        BarStockMovement::create([
            'bar_product_id' => $this->id,
            'user_id'        => auth()->id() ?? 1,
            'quantity'       => $quantidadeEstornada,
            'type'           => 'entrada',
            'description'    => "Venda Cancelada/Estornada" . ($referencia ? " | Ref: {$referencia}" : ""),
        ]);
    }
}
