<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditoriasModel extends Model
{
    use HasFactory;

    protected $table = 'auditorias';
    protected $primaryKey = 'id_auditoria';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'modulo',
        'accion',
        'tabla_afectada',
        'id_registro_afectado',
        'valores_anteriores',
        'valores_nuevos',
        'ip_direccion',
        'user_agent',
        'url',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'id_registro_afectado' => 'integer',
            'valores_anteriores' => 'array',
            'valores_nuevos' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function Usuario()
    {
        return $this->belongsTo(UsuariosModel::class, 'id_usuario', 'id_usuario');
    }
}
