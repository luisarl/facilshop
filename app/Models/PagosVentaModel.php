<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagosVentaModel extends Model
{
    use HasFactory;

    protected $table = 'pagos_venta';
    protected $primaryKey = 'id_pago_venta';

    public $timestamps = false;

    protected $fillable = [
        'id_venta',
        'id_metodo_pago',
        'id_moneda',
        'monto',
        'tasa_cambio',
        'monto_base',
        'referencia',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'tasa_cambio' => 'decimal:4',
            'monto_base' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function Venta()
    {
        return $this->belongsTo(VentasModel::class, 'id_venta', 'id_venta');
    }

    public function MetodoPago()
    {
        return $this->belongsTo(MetodosPagoModel::class, 'id_metodo_pago', 'id_metodo_pago');
    }

    public function Moneda()
    {
        return $this->belongsTo(MonedasModel::class, 'id_moneda', 'id_moneda');
    }
}
