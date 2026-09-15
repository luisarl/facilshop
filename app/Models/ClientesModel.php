<?php

namespace App\Models;

use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientesModel extends Model
{
    use HasFactory, AuditableTrait;

    protected $table = 'clientes';
    protected $primaryKey = 'id_cliente';

    protected $auditModulo = 'VENTAS';

    protected $fillable = [
        'identificacion',
        'nombre',
        'telefono',
        'email',
        'limite_credito',
        'saldo_pendiente',
    ];

    protected function casts(): array
    {
        return [
            'limite_credito' => 'decimal:2',
            'saldo_pendiente' => 'decimal:2',
        ];
    }

    public function Ventas()
    {
        return $this->hasMany(VentasModel::class, 'id_cliente', 'id_cliente');
    }

    public function CasheaTransacciones()
    {
        return $this->hasMany(CasheaTransaccionesModel::class, 'id_cliente', 'id_cliente');
    }

    public function CreditoDisponible(): float
    {
        return (float) max(0, $this->limite_credito - $this->saldo_pendiente);
    }
}
