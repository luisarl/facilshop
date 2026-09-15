<?php

namespace App\Models;

use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductosImagenesModel extends Model
{
    use HasFactory, AuditableTrait;

    protected $table = 'productos_imagenes';
    protected $primaryKey = 'id_producto_imagen';

    protected $auditModulo = 'PRODUCTOS';

    protected $fillable = [
        'id_producto',
        'ruta_imagen',
        'es_principal',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'es_principal' => 'boolean',
            'orden' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function Producto()
    {
        return $this->belongsTo(ProductosModel::class, 'id_producto', 'id_producto');
    }
}
