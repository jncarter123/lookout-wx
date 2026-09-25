<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nws_alerts', function (Blueprint $table) {
            $table->index('expires');
            $table->index('ends');
            $table->index('nws_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('nws_alerts', function (Blueprint $table) {
            $table->dropIndex(['expires']);
            $table->dropIndex(['ends']);
            $table->dropIndex(['nws_updated_at']);
        });
    }
};
