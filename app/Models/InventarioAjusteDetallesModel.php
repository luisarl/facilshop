<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioAjusteDetallesModel extends Model
{
    use HasFactory;

    protected $table = 'inventario_ajuste_detalles';
    protected $primaryKey = 'id_ajuste_detalle';

    public $timestamps = false;

    protected $fillable = [
        'id_ajuste',
        'id_producto',
        'id_unidad',
        'cantidad',
        'cantidad_base',
        'costo_unitario',
        'costo_total',
        'stock_anterior',
        'nuevo_stock',
        'observaciones',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:4',
            'cantidad_base' => 'integer',
            'costo_unitario' => 'decimal:4',
            'costo_total' => 'decimal:2',
            'stock_anterior' => 'integer',
            'nuevo_stock' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function Ajuste()
    {
        return $this->belongsTo(InventarioAjustesModel::class, 'id_ajuste', 'id_ajuste');
    }

    public function Producto()
    {
        return $this->belongsTo(ProductosModel::class, 'id_producto', 'id_producto');
    }

    public function Unidad()
    {
        return $this->belongsTo(UnidadesProductosModel::class, 'id_unidad', 'id_unidad');
    }
}
