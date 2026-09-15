<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioConteoDetallesModel extends Model
{
    use HasFactory;

    protected $table = 'inventario_conteo_detalles';
    protected $primaryKey = 'id_conteo_detalle';

    public $timestamps = false;

    protected $fillable = [
        'id_conteo',
        'id_producto',
        'stock_teorico',
        'stock_fisico',
        'diferencia',
        'costo_unitario',
        'valor_diferencia',
        'observaciones',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'stock_teorico' => 'integer',
            'stock_fisico' => 'integer',
            'diferencia' => 'integer',
            'costo_unitario' => 'decimal:4',
            'valor_diferencia' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function Conteo()
    {
        return $this->belongsTo(InventarioConteosModel::class, 'id_conteo', 'id_conteo');
    }

    public function Producto()
    {
        return $this->belongsTo(ProductosModel::class, 'id_producto', 'id_producto');
    }
}
