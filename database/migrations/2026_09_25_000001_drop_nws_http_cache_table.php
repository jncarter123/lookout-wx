<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Never used: HTTP validators are stored in the cache (HttpValidatorCache).
        Schema::dropIfExists('nws_http_cache');
    }

    public function down(): void
    {
        Schema::create('nws_http_cache', function (Blueprint $table) {
            $table->id();
            $table->string('url', 1024)->unique();
            $table->string('etag', 255)->nullable();
            $table->string('last_modified', 255)->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }
};
