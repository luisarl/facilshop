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
            $accion = 'CREAR';
            if (isset($model->accionAuditoriaPersonalizada))
            {
                $accion = $model->accionAuditoriaPersonalizada;
            }

            $model->RegistrarAuditoria($accion, null, $model->getAttributes());
        });

        static::updated(function ($model)
        {
            $ValoresAnteriores = array_intersect_key($model->getOriginal(), $model->getDirty());
            $ValoresNuevos = $model->getDirty();

            $accion = 'ACTUALIZAR';
            if (isset($model->accionAuditoriaPersonalizada))
            {
                $accion = $model->accionAuditoriaPersonalizada;
            }
            elseif ($model->isDirty('estado'))
            {
                $EstadoNuevo = strtoupper((string) $model->estado);
                if (in_array($EstadoNuevo, ['ANULADA', 'ANULADO']))
                {
                    $accion = 'ANULAR';
                }
                elseif (in_array($EstadoNuevo, ['APLICADO', 'APLICADA']))
                {
                    $accion = 'APLICAR';
                }
            }

            $model->RegistrarAuditoria($accion, $ValoresAnteriores, $ValoresNuevos);
        });

        static::deleted(function ($model)
        {
            $accion = 'ELIMINAR';
            if (isset($model->accionAuditoriaPersonalizada))
            {
                $accion = $model->accionAuditoriaPersonalizada;
            }

            $model->RegistrarAuditoria($accion, $model->getOriginal(), null);
        });
    }

    public function RegistrarAuditoria(string $accion, ?array $ValoresAnteriores, ?array $ValoresNuevos): void
    {
        $IdUsuario = Auth::id();

        // Si se elimina un usuario o si el usuario autenticado es el registro que se elimina, usar null para evitar violación de clave foránea
        if ($this->getTable() === 'usuarios' && $accion === 'ELIMINAR')
        {
            $IdUsuario = null;
        }
        elseif ($IdUsuario === null && isset($this->id_usuario) && $this->getTable() !== 'usuarios')
        {
            $IdUsuario = $this->id_usuario;
        }

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
            'id_usuario' => $IdUsuario,
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
