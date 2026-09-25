<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nws_alerts', function (Blueprint $table) {
            // NWS headlines can exceed 255 characters; MySQL strict mode rejects the insert.
            $table->text('headline')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('nws_alerts', function (Blueprint $table) {
            $table->string('headline')->nullable()->change();
        });
    }
};
