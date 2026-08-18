<?php

namespace Database\Seeders;

use App\Models\Comunicacion;
use App\Models\ComunicacionDestinatario;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComunicacionesPruebaSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {

            Comunicacion::query()
                ->where('asunto', 'like', '[PRUEBA]%')
                ->delete();

            $remitente = User::role('Superadmin')
                ->where('estado', 'Activo')
                ->first();

            if (!$remitente) {
                $remitente = User::query()
                    ->where('estado', 'Activo')
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'like', '%Coordinador%');
                    })
                    ->first();
            }

            if (!$remitente) {
                throw new \RuntimeException(
                    'No existe un Superadmin o Coordinador activo para generar las comunicaciones de prueba.'
                );
            }

            $usuarios = User::query()
                ->where('estado', 'Activo')
                ->where('id', '!=', $remitente->id)
                ->get();

            if ($usuarios->isEmpty()) {
                throw new \RuntimeException(
                    'No existen usuarios activos suficientes para generar comunicaciones de prueba.'
                );
            }

            $ordenGlobal = Comunicacion::create([
                'remitente_user_id' => $remitente->id,
                'tipo' => 'orden',
                'asunto' => '[PRUEBA] Orden general',
                'contenido' => 'Todo el personal deberá confirmar de enterado esta orden de prueba.',
                'alcance' => 'todos',
                'unidad_id' => null,
                'turno_id' => null,
                'role_id' => null,
                'destinatario_user_id' => null,
                'requiere_enterado' => true,
                'enviado_at' => now()->subMinutes(30),
            ]);

            foreach ($usuarios as $index => $usuario) {

                $leidoAt = null;
                $enteradoAt = null;

                if ($index % 3 === 0) {
                    $leidoAt = now()->subMinutes(20);
                    $enteradoAt = now()->subMinutes(18);
                } elseif ($index % 3 === 1) {
                    $leidoAt = now()->subMinutes(15);
                }

                ComunicacionDestinatario::create([
                    'comunicacion_id' => $ordenGlobal->id,
                    'user_id' => $usuario->id,
                    'leido_at' => $leidoAt,
                    'enterado_at' => $enteradoAt,
                ]);
            }

            $usuarioConUnidad = User::query()
                ->where('estado', 'Activo')
                ->where('id', '!=', $remitente->id)
                ->whereNotNull('unidad_id')
                ->first();

            if ($usuarioConUnidad) {

                $usuariosUnidad = User::query()
                    ->where('estado', 'Activo')
                    ->where('unidad_id', $usuarioConUnidad->unidad_id)
                    ->where('id', '!=', $remitente->id)
                    ->get();

                if ($usuariosUnidad->isNotEmpty()) {

                    $avisoUnidad = Comunicacion::create([
                        'remitente_user_id' => $remitente->id,
                        'tipo' => 'aviso',
                        'asunto' => '[PRUEBA] Aviso de unidad',
                        'contenido' => 'Este es un aviso de prueba dirigido únicamente al personal de esta unidad.',
                        'alcance' => 'unidad',
                        'unidad_id' => $usuarioConUnidad->unidad_id,
                        'turno_id' => null,
                        'role_id' => null,
                        'destinatario_user_id' => null,
                        'requiere_enterado' => false,
                        'enviado_at' => now()->subMinutes(20),
                    ]);

                    foreach ($usuariosUnidad as $index => $usuario) {
                        ComunicacionDestinatario::create([
                            'comunicacion_id' => $avisoUnidad->id,
                            'user_id' => $usuario->id,
                            'leido_at' => $index === 0
                                ? now()->subMinutes(10)
                                : null,
                            'enterado_at' => null,
                        ]);
                    }
                }
            }

            $destinatarioMensaje = $usuarios->first();

            $mensajeUno = Comunicacion::create([
                'remitente_user_id' => $remitente->id,
                'tipo' => 'mensaje',
                'asunto' => '[PRUEBA] Mensaje directo',
                'contenido' => 'Hola, este es el primer mensaje de prueba del chat.',
                'alcance' => 'usuario',
                'unidad_id' => null,
                'turno_id' => null,
                'role_id' => null,
                'destinatario_user_id' => $destinatarioMensaje->id,
                'requiere_enterado' => false,
                'enviado_at' => now()->subMinutes(10),
            ]);

            ComunicacionDestinatario::create([
                'comunicacion_id' => $mensajeUno->id,
                'user_id' => $destinatarioMensaje->id,
                'leido_at' => now()->subMinutes(8),
                'enterado_at' => null,
            ]);

            $mensajeDos = Comunicacion::create([
                'remitente_user_id' => $destinatarioMensaje->id,
                'tipo' => 'mensaje',
                'asunto' => '[PRUEBA] Respuesta directa',
                'contenido' => 'Recibido. Esta es una respuesta de prueba.',
                'alcance' => 'usuario',
                'unidad_id' => null,
                'turno_id' => null,
                'role_id' => null,
                'destinatario_user_id' => $remitente->id,
                'requiere_enterado' => false,
                'enviado_at' => now()->subMinutes(7),
            ]);

            ComunicacionDestinatario::create([
                'comunicacion_id' => $mensajeDos->id,
                'user_id' => $remitente->id,
                'leido_at' => null,
                'enterado_at' => null,
            ]);

            $mensajeTres = Comunicacion::create([
                'remitente_user_id' => $destinatarioMensaje->id,
                'tipo' => 'mensaje',
                'asunto' => '[PRUEBA] Segundo mensaje',
                'contenido' => 'Este mensaje queda sin leer para probar el contador de pendientes.',
                'alcance' => 'usuario',
                'unidad_id' => null,
                'turno_id' => null,
                'role_id' => null,
                'destinatario_user_id' => $remitente->id,
                'requiere_enterado' => false,
                'enviado_at' => now()->subMinutes(3),
            ]);

            ComunicacionDestinatario::create([
                'comunicacion_id' => $mensajeTres->id,
                'user_id' => $remitente->id,
                'leido_at' => null,
                'enterado_at' => null,
            ]);
        });
    }
}
