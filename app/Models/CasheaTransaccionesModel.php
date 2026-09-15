<?php

namespace App\Models;

use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CasheaTransaccionesModel extends Model
{
    use HasFactory, AuditableTrait;

    protected $table = 'cashea_transacciones';
    protected $primaryKey = 'id_cashea_transaccion';

    protected $auditModulo = 'VENTAS';

    protected $fillable = [
        'id_venta',
        'id_cliente',
        'cedula_cliente',
        'telefono_cliente',
        'referencia_cashea',
        'monto_total',
        'porcentaje_inicial',
        'monto_inicial',
        'monto_financiado',
        'numero_cuotas',
        'monto_cuota',
        'estado',
        'modo',
        'codigo_autorizacion',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'monto_total' => 'decimal:2',
            'porcentaje_inicial' => 'decimal:2',
            'monto_inicial' => 'decimal:2',
            'monto_financiado' => 'decimal:2',
            'numero_cuotas' => 'integer',
            'monto_cuota' => 'decimal:2',
            'payload' => 'array',
        ];
    }

    public function Venta()
    {
        return $this->belongsTo(VentasModel::class, 'id_venta', 'id_venta');
    }

    public function Cliente()
    {
        return $this->belongsTo(ClientesModel::class, 'id_cliente', 'id_cliente');
    }

    public function EstaAprobada(): bool
    {
        return $this->estado === 'APROBADA';
    }
}
