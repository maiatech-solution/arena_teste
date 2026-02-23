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

    public function category()
    {
        return $this->belongsTo(BarCategory::class, 'bar_category_id');
    }

    /**
     * 🚀 MÉTODO INTELIGENTE DE BAIXA DE ESTOQUE
     * Trata produtos simples e combos automaticamente.
     */
    public function baixarEstoque($quantidadeVendida, $vendaId = null)
    {
        // 1. Se for um COMBO, baixa os itens da receita
        if ($this->is_combo) {
            // Carregamos as composições desse combo
            $itensCombo = $this->compositions()->get();

            foreach ($itensCombo as $item) {
                // Buscamos o produto real (filho)
                $produtoFilho = BarProduct::find($item->child_id);

                if ($produtoFilho && $produtoFilho->manage_stock) {
                    $totalParaAbater = $item->quantity * $quantidadeVendida;

                    // Abate o estoque do item real
                    $produtoFilho->decrement('stock_quantity', $totalParaAbater);

                    // Registra no histórico de movimentação
                    BarStockMovement::create([
                        'bar_product_id' => $produtoFilho->id,
                        'user_id'        => auth()->id() ?? 1, // 1 como fallback para sistema
                        'quantity'       => -$totalParaAbater,
                        'type'           => 'saida',
                        'description'    => "BAIXA COMBO: [{$this->name}]" . ($vendaId ? " | Venda #{$vendaId}" : ""),
                    ]);
                }
            }
        }

        // 2. Independente de ser combo ou não, registramos a saída do item vendido
        // Se for combo, ele registra a saída mas não mexe no stock_quantity (que já é 0)
        // Se for produto simples, ele abate normalmente.
        if (!$this->is_combo && $this->manage_stock) {
            $this->decrement('stock_quantity', $quantidadeVendida);
        }

        // Registro da movimentação do item principal (o que aparece no cupom)
        BarStockMovement::create([
            'bar_product_id' => $this->id,
            'user_id'        => auth()->id() ?? 1,
            'quantity'       => -$quantidadeVendida,
            'type'           => 'saida',
            'description'    => "Venda Realizada" . ($vendaId ? " | Venda #{$vendaId}" : ""),
        ]);
    }
}
