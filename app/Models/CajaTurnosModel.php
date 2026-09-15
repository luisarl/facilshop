<?php

namespace App\Models;

use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CajaTurnosModel extends Model
{
    use HasFactory, AuditableTrait;

    protected $table = 'caja_turnos';
    protected $primaryKey = 'id_caja_turno';

    protected $auditModulo = 'CAJA';

    protected $fillable = [
        'id_usuario',
        'monto_inicial',
        'monto_final_teorico',
        'monto_final_declarado',
        'diferencia',
        'estado',
        'fecha_apertura',
        'fecha_cierre',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'monto_inicial' => 'decimal:2',
            'monto_final_teorico' => 'decimal:2',
            'monto_final_declarado' => 'decimal:2',
            'diferencia' => 'decimal:2',
            'fecha_apertura' => 'datetime',
            'fecha_cierre' => 'datetime',
        ];
    }

    public function Usuario()
    {
        return $this->belongsTo(UsuariosModel::class, 'id_usuario', 'id_usuario');
    }

    public function Ventas()
    {
        return $this->hasMany(VentasModel::class, 'id_caja_turno', 'id_caja_turno');
    }

    public function EstaAbierta(): bool
    {
        return $this->estado === 'ABIERTA';
    }
}
