<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentaDetallesModel extends Model
{
    use HasFactory;

    protected $table = 'venta_detalles';
    protected $primaryKey = 'id_venta_detalle';

    public $timestamps = false;

    protected $fillable = [
        'id_venta',
        'id_producto',
        'cantidad',
        'precio_unitario',
        'descuento',
        'subtotal',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_unitario' => 'decimal:4',
            'descuento' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function Venta()
    {
        return $this->belongsTo(VentasModel::class, 'id_venta', 'id_venta');
    }

    public function Producto()
    {
        return $this->belongsTo(ProductosModel::class, 'id_producto', 'id_producto');
    }
}
