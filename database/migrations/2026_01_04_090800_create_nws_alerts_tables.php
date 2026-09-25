<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nws_alerts', function (Blueprint $table) {
            // NWS alert "id" is typically a URL; store as string PK.
            $table->string('id', 512)->primary();

            $table->string('event')->nullable();
            $table->string('headline')->nullable();
            $table->string('severity')->nullable();
            $table->string('certainty')->nullable();
            $table->string('urgency')->nullable();
            $table->string('status')->nullable();        // Actual/Exercise/System/Test
            $table->string('message_type')->nullable();  // Alert/Update/Cancel
            $table->string('category')->nullable();      // Met/Geo/Safety/etc.

            $table->timestamp('sent')->nullable();
            $table->timestamp('effective')->nullable();
            $table->timestamp('onset')->nullable();
            $table->timestamp('expires')->nullable();
            $table->timestamp('ends')->nullable();

            $table->json('raw')->nullable();             // full properties + geometry etc.
            $table->timestamp('nws_updated_at')->nullable(); // from feed entry <updated> or alert properties.updated

            $table->timestamps();
        });

        Schema::create('nws_alert_counties', function (Blueprint $table) {
            $table->id();
            $table->string('alert_id', 512);
            $table->string('county_ugc', 6); // e.g. TXC121

            $table->timestamps();

            $table->unique(['alert_id', 'county_ugc']);
            $table->index(['county_ugc']);

            $table->foreign('alert_id')
                ->references('id')->on('nws_alerts')
                ->cascadeOnDelete();
        });

        // nws_http_cache used to be created here. It was never used and is dropped by
        // 2026_09_25_000001; its unique index on a 1024-char utf8mb4 column also
        // exceeded MySQL 8's 3072-byte key limit and broke fresh installs.
    }

    public function down(): void
    {
        Schema::dropIfExists('nws_http_cache');
        Schema::dropIfExists('nws_alert_counties');
        Schema::dropIfExists('nws_alerts');
    }
};
