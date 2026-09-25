<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nws_alert_zones', function (Blueprint $table) {
            $table->id();

            $table->string('alert_id', 512);
            $table->string('zone_id', 16);          // e.g. GMZ330
            $table->string('zone_kind', 32)->nullable(); // e.g. forecast (optional)

            $table->timestamps();

            $table->unique(['alert_id', 'zone_id']);
            $table->index(['zone_id']);
            $table->index(['alert_id']);

            $table->foreign('alert_id')
                ->references('id')->on('nws_alerts')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nws_alert_zones');
    }
};
