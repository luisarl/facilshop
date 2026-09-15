<?php

namespace App\Models;

use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioAjustesModel extends Model
{
    use HasFactory, AuditableTrait;

    protected $table = 'inventario_ajustes';
    protected $primaryKey = 'id_ajuste';

    protected $auditModulo = 'AJUSTES';

    protected $fillable = [
        'codigo_ajuste',
        'id_usuario',
        'id_tipo_movimiento',
        'motivo',
        'documento_referencia',
        'estado',
        'fecha_ajuste',
        'total_items',
        'total_costo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ajuste' => 'datetime',
            'total_items' => 'integer',
            'total_costo' => 'decimal:2',
        ];
    }

    public function Usuario()
    {
        return $this->belongsTo(UsuariosModel::class, 'id_usuario', 'id_usuario');
    }

    public function TipoMovimiento()
    {
        return $this->belongsTo(TiposMovimientoInventarioModel::class, 'id_tipo_movimiento', 'id_tipo_movimiento');
    }

    public function Detalles()
    {
        return $this->hasMany(InventarioAjusteDetallesModel::class, 'id_ajuste', 'id_ajuste');
    }

    public function EstaAplicado(): bool
    {
        return $this->estado === 'APLICADO';
    }

    public function EstaAnulado(): bool
    {
        return $this->estado === 'ANULADO';
    }
}
