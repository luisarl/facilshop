<?php

namespace App\Models;

use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClasificacionProductosModel extends Model
{
    use HasFactory, AuditableTrait;

    protected $table = 'clasificacion_productos';
    protected $primaryKey = 'id_categoria';

    protected $auditModulo = 'PRODUCTOS';

    protected $fillable = [
        'nombre',
        'descripcion',
        'id_categoria_padre',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function Padre()
    {
        return $this->belongsTo(ClasificacionProductosModel::class, 'id_categoria_padre', 'id_categoria');
    }

    public function Hijos()
    {
        return $this->hasMany(ClasificacionProductosModel::class, 'id_categoria_padre', 'id_categoria');
    }

    public function Productos()
    {
        return $this->hasMany(ProductosModel::class, 'id_categoria', 'id_categoria');
    }
}
