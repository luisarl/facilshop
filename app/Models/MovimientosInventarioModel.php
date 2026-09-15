<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientosInventarioModel extends Model
{
    use HasFactory;

    protected $table = 'movimientos_inventario';
    protected $primaryKey = 'id_movimiento_inventario';

    public $timestamps = false;

    protected $fillable = [
        'id_producto',
        'id_usuario',
        'id_tipo_movimiento',
        'cantidad',
        'stock_anterior',
        'nuevo_stock',
        'motivo',
        'documento_referencia',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'stock_anterior' => 'integer',
            'nuevo_stock' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function Producto()
    {
        return $this->belongsTo(ProductosModel::class, 'id_producto', 'id_producto');
    }

    public function Usuario()
    {
        return $this->belongsTo(UsuariosModel::class, 'id_usuario', 'id_usuario');
    }

    public function TipoMovimiento()
    {
        return $this->belongsTo(TiposMovimientoInventarioModel::class, 'id_tipo_movimiento', 'id_tipo_movimiento');
    }
}
