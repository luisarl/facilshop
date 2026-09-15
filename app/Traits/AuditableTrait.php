<?php

namespace App\Traits;

use App\Models\AuditoriasModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait AuditableTrait
{
    public static function bootAuditableTrait(): void
    {
        static::created(function ($model)
        {
            $model->RegistrarAuditoria('CREAR', null, $model->getAttributes());
        });

        static::updated(function ($model)
        {
            $ValoresAnteriores = array_intersect_key($model->getOriginal(), $model->getDirty());
            $ValoresNuevos = $model->getDirty();

            $model->RegistrarAuditoria('ACTUALIZAR', $ValoresAnteriores, $ValoresNuevos);
        });

        static::deleted(function ($model)
        {
            $model->RegistrarAuditoria('ELIMINAR', $model->getOriginal(), null);
        });
    }

    public function RegistrarAuditoria(string $accion, ?array $ValoresAnteriores, ?array $ValoresNuevos): void
    {
        $idUsuario = Auth::id();
        $modulo = property_exists($this, 'auditModulo') ? $this->auditModulo : strtoupper($this->getTable());

        $CamposOcultos = ['password', 'remember_token', 'token', 'api_secret'];
        if ($ValoresAnteriores !== null)
        {
            foreach ($CamposOcultos as $campo)
            {
                unset($ValoresAnteriores[$campo]);
            }
        }

        if ($ValoresNuevos !== null)
        {
            foreach ($CamposOcultos as $campo)
            {
                unset($ValoresNuevos[$campo]);
            }
        }

        AuditoriasModel::create([
            'id_usuario' => $idUsuario,
            'modulo' => $modulo,
            'accion' => $accion,
            'tabla_afectada' => $this->getTable(),
            'id_registro_afectado' => $this->getKey(),
            'valores_anteriores' => $ValoresAnteriores,
            'valores_nuevos' => $ValoresNuevos,
            'ip_direccion' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
        ]);
    }
}
