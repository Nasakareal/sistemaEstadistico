<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSecurityEventsTable extends Migration
{
    public function up()
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamp('occurred_at')->index();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('bucket_at')->index();
            $table->unsignedInteger('occurrences')->default(1);
            $table->string('severity', 20)->index();
            $table->string('category', 50)->index();
            $table->string('event_code', 100)->index();
            $table->string('description', 500);
            $table->string('ip_address', 45)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('method', 10)->nullable();
            $table->text('path')->nullable();
            $table->string('route_name', 191)->nullable();
            $table->unsignedSmallInteger('status_code')->nullable()->index();
            $table->string('request_id', 64)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->string('fingerprint', 64)->index();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['fingerprint', 'bucket_at']);
            $table->index(['ip_address', 'occurred_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('security_events');
    }
}
