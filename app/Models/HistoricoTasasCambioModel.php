<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoTasasCambioModel extends Model
{
    use HasFactory;

    protected $table = 'historico_tasas_cambio';
    protected $primaryKey = 'id_historico_tasa';

    public $timestamps = false;

    protected $fillable = [
        'id_moneda',
        'id_usuario',
        'tasa_anterior',
        'tasa_nueva',
        'observaciones',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'tasa_anterior' => 'decimal:4',
            'tasa_nueva' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    public function Moneda()
    {
        return $this->belongsTo(MonedasModel::class, 'id_moneda', 'id_moneda');
    }

    public function Usuario()
    {
        return $this->belongsTo(UsuariosModel::class, 'id_usuario', 'id_usuario');
    }
}
