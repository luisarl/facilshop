<?php

namespace App\Models;

use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarcasProductosModel extends Model
{
    use HasFactory, AuditableTrait;

    protected $table = 'marcas_productos';
    protected $primaryKey = 'id_marca';

    protected $auditModulo = 'PRODUCTOS';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function Productos()
    {
        return $this->hasMany(ProductosModel::class, 'id_marca', 'id_marca');
    }
}
