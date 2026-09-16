<?php

namespace App\Models;

use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductosModel extends Model
{
    use HasFactory, AuditableTrait;

    protected $table = 'productos';
    protected $primaryKey = 'id_producto';

    protected $auditModulo = 'PRODUCTOS';

    protected $fillable = [
        'sku',
        'codigo_barras',
        'nombre',
        'modelo',
        'descripcion',
        'id_marca',
        'id_categoria',
        'id_unidad',
        'id_unidad_secundaria',
        'equivalencia_unidad',
        'equivalencia_unidad_secundaria',
        'precio_costo',
        'precio_venta',
        'stock_actual',
        'stock_minimo',
        'imagen_principal',
        'fecha_vencimiento',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'equivalencia_unidad' => 'decimal:4',
            'equivalencia_unidad_secundaria' => 'decimal:4',
            'precio_costo' => 'decimal:4',
            'precio_venta' => 'decimal:4',
            'stock_actual' => 'integer',
            'stock_minimo' => 'integer',
            'fecha_vencimiento' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function Marca()
    {
        return $this->belongsTo(MarcasProductosModel::class, 'id_marca', 'id_marca');
    }

    public function Categoria()
    {
        return $this->belongsTo(ClasificacionProductosModel::class, 'id_categoria', 'id_categoria');
    }

    public function Unidad()
    {
        return $this->belongsTo(UnidadesProductosModel::class, 'id_unidad', 'id_unidad');
    }

    public function UnidadSecundaria()
    {
        return $this->belongsTo(UnidadesProductosModel::class, 'id_unidad_secundaria', 'id_unidad');
    }

    public function Imagenes()
    {
        return $this->hasMany(ProductosImagenesModel::class, 'id_producto', 'id_producto');
    }

    public function MovimientosInventario()
    {
        return $this->hasMany(MovimientosInventarioModel::class, 'id_producto', 'id_producto');
    }

    public function VentaDetalles()
    {
        return $this->hasMany(VentaDetallesModel::class, 'id_producto', 'id_producto');
    }

    public function ConteoDetalles()
    {
        return $this->hasMany(InventarioConteoDetallesModel::class, 'id_producto', 'id_producto');
    }

    public function AjusteDetalles()
    {
        return $this->hasMany(InventarioAjusteDetallesModel::class, 'id_producto', 'id_producto');
    }

    public function TieneStockBajo(): bool
    {
        return $this->stock_actual <= $this->stock_minimo;
    }

    public function getImagenPrincipalAttribute(?string $value): ?string
    {
        if (empty($value))
        {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, 'blob:') || str_starts_with($value, 'data:'))
        {
            return $value;
        }

        return asset(ltrim($value, '/'));
    }
}
