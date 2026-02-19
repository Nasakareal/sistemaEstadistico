<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWazeAlertsTable extends Migration
{
    public function up()
    {
        Schema::create('waze_alerts', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();
            $table->string('waze_id')->nullable();
            $table->string('type')->nullable();
            $table->string('subtype')->nullable();
            $table->string('country', 8)->nullable();
            $table->string('city')->nullable();
            $table->string('street')->nullable();

            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            $table->unsignedBigInteger('pub_millis')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->boolean('notified')->default(false);
            $table->json('raw')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('waze_alerts');
    }
}
