<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ReclassifyAuthenticatedRateLimitSecurityEvents extends Migration
{
    public function up()
    {
        DB::table('security_events')
            ->where('event_code', 'rate_limit_exceeded')
            ->whereNotNull('user_id')
            ->update([
                'event_code' => 'authenticated_rate_limit_reached',
                'description' => 'Un usuario autenticado alcanzó el límite compartido de solicitudes API.',
                'severity' => 'warning',
                'category' => 'operational',
            ]);
    }

    public function down()
    {
        DB::table('security_events')
            ->where('event_code', 'authenticated_rate_limit_reached')
            ->update([
                'event_code' => 'rate_limit_exceeded',
                'description' => 'La IP excedió el límite de solicitudes permitido.',
                'severity' => 'high',
                'category' => 'rate_limit',
            ]);
    }
}
