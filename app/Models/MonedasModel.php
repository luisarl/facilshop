<?php

namespace App\Models;

use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonedasModel extends Model
{
    use HasFactory, AuditableTrait;

    protected $table = 'monedas';
    protected $primaryKey = 'id_moneda';

    protected $auditModulo = 'MONEDAS';

    protected $fillable = [
        'codigo',
        'nombre',
        'simbolo',
        'tasa_cambio',
        'es_principal',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'tasa_cambio' => 'decimal:4',
            'es_principal' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function HistoricoTasas()
    {
        return $this->hasMany(HistoricoTasasCambioModel::class, 'id_moneda', 'id_moneda');
    }

    public function MetodosPago()
    {
        return $this->hasMany(MetodosPagoModel::class, 'id_moneda', 'id_moneda');
    }

    public function Ventas()
    {
        return $this->hasMany(VentasModel::class, 'id_moneda', 'id_moneda');
    }

    public function PagosVenta()
    {
        return $this->hasMany(PagosVentaModel::class, 'id_moneda', 'id_moneda');
    }
}
