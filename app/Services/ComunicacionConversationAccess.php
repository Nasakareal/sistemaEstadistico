<?php

namespace App\Services;

use App\Models\User;

class ComunicacionConversationAccess
{
    /**
     * Users the actor may discover to start a new direct conversation.
     */
    public static function discoverableRecipients(User $actor, bool $global)
    {
        $query = User::query()
            ->visibleFor($actor)
            ->where('estado', 'Activo')
            ->where('users.id', '!=', $actor->id);

        if (!$global) {
            $actor->unidad_id
                ? $query->where('unidad_id', $actor->unidad_id)
                : $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Users the actor may message. Existing direct conversations remain
     * replyable even when the other participant is outside the directory.
     * A received order or notice also authorizes a private reply to its
     * sender, because those communications store their recipients in the
     * comunicacion_destinatarios table instead of destinatario_user_id.
     */
    public static function messageableRecipients(User $actor, bool $global)
    {
        $discoverable = self::discoverableRecipients($actor, $global)
            ->select('users.id');

        return User::query()
            ->where(function ($status) {
                $status->where('estado', 'Activo')
                    ->orWhereNull('estado');
            })
            ->where('users.id', '!=', $actor->id)
            ->where(function ($query) use ($discoverable, $actor) {
                $query->whereIn('users.id', $discoverable)
                    ->orWhereExists(function ($conversation) use ($actor) {
                        $conversation->selectRaw('1')
                            ->from('comunicaciones')
                            ->where('comunicaciones.tipo', 'mensaje')
                            ->where('comunicaciones.alcance', 'usuario')
                            ->where(function ($participants) use ($actor) {
                                $participants
                                    ->where(function ($incoming) use ($actor) {
                                        $incoming
                                            ->whereColumn('comunicaciones.remitente_user_id', 'users.id')
                                            ->where('comunicaciones.destinatario_user_id', $actor->id);
                                    })
                                    ->orWhere(function ($outgoing) use ($actor) {
                                        $outgoing
                                            ->where('comunicaciones.remitente_user_id', $actor->id)
                                            ->whereColumn('comunicaciones.destinatario_user_id', 'users.id');
                                    });
                            });
                    })
                    ->orWhereExists(function ($received) use ($actor) {
                        $received->selectRaw('1')
                            ->from('comunicaciones')
                            ->join(
                                'comunicacion_destinatarios',
                                'comunicacion_destinatarios.comunicacion_id',
                                '=',
                                'comunicaciones.id'
                            )
                            ->whereColumn(
                                'comunicaciones.remitente_user_id',
                                'users.id'
                            )
                            ->where(
                                'comunicacion_destinatarios.user_id',
                                $actor->id
                            );
                    });
            });
    }
}
