<?php

namespace App\Models;

use App\Traits\AuditableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class UsuariosModel extends Authenticatable
{
    use HasFactory, Notifiable, AuditableTrait;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';

    protected $auditModulo = 'AUTH';

    protected $fillable = [
        'nombre',
        'name',
        'email',
        'email_verified_at',
        'password',
        'rol',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function CajaTurnos()
    {
        return $this->hasMany(CajaTurnosModel::class, 'id_usuario', 'id_usuario');
    }

    public function MovimientosInventario()
    {
        return $this->hasMany(MovimientosInventarioModel::class, 'id_usuario', 'id_usuario');
    }

    public function Auditorias()
    {
        return $this->hasMany(AuditoriasModel::class, 'id_usuario', 'id_usuario');
    }

    public function getIdAttribute(): ?int
    {
        return $this->attributes['id_usuario'] ?? $this->getKey();
    }

    public function getNameAttribute(): ?string
    {
        return $this->nombre;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['nombre'] = $value;
    }

    public function EsSuperAdmin(): bool
    {
        return $this->rol === 'superadmin';
    }

    public function EsAdmin(): bool
    {
        return in_array($this->rol, ['superadmin', 'admin']);
    }

    public function EsCajero(): bool
    {
        return $this->rol === 'cajero';
    }
}
