<?php

namespace App\Models;

use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentasModel extends Model
{
    use HasFactory, AuditableTrait;

    protected $table = 'ventas';
    protected $primaryKey = 'id_venta';

    protected $auditModulo = 'VENTAS';

    protected $fillable = [
        'id_caja_turno',
        'id_cliente',
        'id_moneda',
        'tasa_cambio',
        'numero_comprobante',
        'tipo_comprobante',
        'subtotal',
        'descuento_total',
        'impuesto',
        'total',
        'total_moneda_base',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'tasa_cambio' => 'decimal:4',
            'subtotal' => 'decimal:2',
            'descuento_total' => 'decimal:2',
            'impuesto' => 'decimal:2',
            'total' => 'decimal:2',
            'total_moneda_base' => 'decimal:2',
        ];
    }

    public function CajaTurno()
    {
        return $this->belongsTo(CajaTurnosModel::class, 'id_caja_turno', 'id_caja_turno');
    }

    public function Cliente()
    {
        return $this->belongsTo(ClientesModel::class, 'id_cliente', 'id_cliente');
    }

    public function Moneda()
    {
        return $this->belongsTo(MonedasModel::class, 'id_moneda', 'id_moneda');
    }

    public function Detalles()
    {
        return $this->hasMany(VentaDetallesModel::class, 'id_venta', 'id_venta');
    }

    public function Pagos()
    {
        return $this->hasMany(PagosVentaModel::class, 'id_venta', 'id_venta');
    }

    public function CasheaTransaccion()
    {
        return $this->hasOne(CasheaTransaccionesModel::class, 'id_venta', 'id_venta');
    }

    public function EstaCompletada(): bool
    {
        return $this->estado === 'COMPLETADA';
    }

    public function EstaAnulada(): bool
    {
        return $this->estado === 'ANULADA';
    }
}
