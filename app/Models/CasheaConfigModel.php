<?php

namespace App\Models;

use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CasheaConfigModel extends Model
{
    use HasFactory, AuditableTrait;

    protected $table = 'cashea_config';
    protected $primaryKey = 'id_cashea_config';

    protected $auditModulo = 'VENTAS';

    protected $fillable = [
        'modo_operacion',
        'api_key',
        'api_secret',
        'merchant_id',
        'porcentaje_inicial_defecto',
        'cuotas_defecto',
        'activo',
    ];

    protected $hidden = [
        'api_secret',
    ];

    protected function casts(): array
    {
        return [
            'porcentaje_inicial_defecto' => 'decimal:2',
            'cuotas_defecto' => 'integer',
            'activo' => 'boolean',
        ];
    }
}
