<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('x_link')->nullable();
            $table->string('ig_link')->nullable();
            $table->string('yt_link')->nullable();
            $table->string('fb_link')->nullable();
            $table->string('tt_link')->nullable();
            $table->boolean('display')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['x_link', 'ig_link', 'yt_link', 'fb_link', 'tt_link', 'display']);
        });
    }
};
