<?php

namespace App\Models;

use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioConteosModel extends Model
{
    use HasFactory, AuditableTrait;

    protected $table = 'inventario_conteos';
    protected $primaryKey = 'id_conteo';

    protected $auditModulo = 'CONTEOS';

    protected $fillable = [
        'codigo_conteo',
        'id_usuario',
        'descripcion',
        'estado',
        'fecha_inicio',
        'fecha_cierre',
        'total_items_contados',
        'total_diferencia_unidades',
        'total_diferencia_costo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_cierre' => 'datetime',
            'total_items_contados' => 'integer',
            'total_diferencia_unidades' => 'integer',
            'total_diferencia_costo' => 'decimal:2',
        ];
    }

    public function Usuario()
    {
        return $this->belongsTo(UsuariosModel::class, 'id_usuario', 'id_usuario');
    }

    public function Detalles()
    {
        return $this->hasMany(InventarioConteoDetallesModel::class, 'id_conteo', 'id_conteo');
    }

    public function EstaBorrador(): bool
    {
        return $this->estado === 'BORRADOR';
    }

    public function EstaEnProceso(): bool
    {
        return $this->estado === 'EN_PROCESO';
    }

    public function EstaAplicado(): bool
    {
        return $this->estado === 'APLICADO';
    }
}
