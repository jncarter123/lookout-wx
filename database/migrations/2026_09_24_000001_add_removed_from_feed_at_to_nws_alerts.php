<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nws_alerts', function (Blueprint $table) {
            // Set when the alert no longer appears in the NWS active feed (cancelled, superseded, or expired early).
            $table->timestamp('removed_from_feed_at')->nullable()->after('nws_updated_at');
            $table->index('removed_from_feed_at');
        });
    }

    public function down(): void
    {
        Schema::table('nws_alerts', function (Blueprint $table) {
            $table->dropIndex(['removed_from_feed_at']);
            $table->dropColumn('removed_from_feed_at');
        });
    }
};
