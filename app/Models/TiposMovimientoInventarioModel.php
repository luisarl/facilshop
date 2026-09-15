<?php

namespace App\Models;

use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TiposMovimientoInventarioModel extends Model
{
    use HasFactory, AuditableTrait;

    protected $table = 'tipos_movimiento_inventario';
    protected $primaryKey = 'id_tipo_movimiento';

    protected $auditModulo = 'INVENTARIO';

    protected $fillable = [
        'codigo',
        'nombre',
        'naturaleza',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function Movimientos()
    {
        return $this->hasMany(MovimientosInventarioModel::class, 'id_tipo_movimiento', 'id_tipo_movimiento');
    }

    public function Ajustes()
    {
        return $this->hasMany(InventarioAjustesModel::class, 'id_tipo_movimiento', 'id_tipo_movimiento');
    }

    public function EsEntrada(): bool
    {
        return $this->naturaleza === 'ENTRADA';
    }

    public function EsSalida(): bool
    {
        return $this->naturaleza === 'SALIDA';
    }
}
