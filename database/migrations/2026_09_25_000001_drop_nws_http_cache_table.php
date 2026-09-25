<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Never used: HTTP validators are stored in the cache (HttpValidatorCache).
        // New installs never create it (see 2026_01_04_090800), hence IfExists.
        Schema::dropIfExists('nws_http_cache');
    }

    public function down(): void
    {
        Schema::create('nws_http_cache', function (Blueprint $table) {
            $table->id();
            $table->string('url', 768)->unique(); // 768 x 4 bytes fits MySQL's 3072-byte key limit
            $table->string('etag', 255)->nullable();
            $table->string('last_modified', 255)->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }
};
