<?php

namespace App\Models;

use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnidadesProductosModel extends Model
{
    use HasFactory, AuditableTrait;

    protected $table = 'unidades_productos';
    protected $primaryKey = 'id_unidad';

    protected $auditModulo = 'PRODUCTOS';

    protected $fillable = [
        'nombre',
        'abreviatura',
        'permite_decimales',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'permite_decimales' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function ProductosPrincipales()
    {
        return $this->hasMany(ProductosModel::class, 'id_unidad', 'id_unidad');
    }

    public function ProductosSecundarios()
    {
        return $this->hasMany(ProductosModel::class, 'id_unidad_secundaria', 'id_unidad');
    }

    public function AjusteDetalles()
    {
        return $this->hasMany(InventarioAjusteDetallesModel::class, 'id_unidad', 'id_unidad');
    }
}
