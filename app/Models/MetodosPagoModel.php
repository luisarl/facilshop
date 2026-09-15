<?php

namespace App\Models;

use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetodosPagoModel extends Model
{
    use HasFactory, AuditableTrait;

    protected $table = 'metodos_pago';
    protected $primaryKey = 'id_metodo_pago';

    protected $auditModulo = 'MONEDAS';

    protected $fillable = [
        'nombre',
        'codigo',
        'id_moneda',
        'tipo',
        'requiere_referencia',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'requiere_referencia' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function Moneda()
    {
        return $this->belongsTo(MonedasModel::class, 'id_moneda', 'id_moneda');
    }

    public function PagosVenta()
    {
        return $this->hasMany(PagosVentaModel::class, 'id_metodo_pago', 'id_metodo_pago');
    }

    public function EsCredito(): bool
    {
        return $this->tipo === 'CREDITO';
    }

    public function EsEfectivo(): bool
    {
        return $this->tipo === 'EFECTIVO';
    }

    public function EsFinanciamiento(): bool
    {
        return $this->tipo === 'FINANCIAMIENTO';
    }
}
