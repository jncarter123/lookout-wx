<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only change log; the auto-increment id is the cursor for GET /api/alerts/changes.
        Schema::create('nws_alert_changes', function (Blueprint $table) {
            $table->id();

            // No FK: change rows must outlive pruned alerts so consumers still see the removal.
            $table->string('alert_id', 512);
            $table->string('type', 16); // upserted | removed

            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nws_alert_changes');
    }
};
