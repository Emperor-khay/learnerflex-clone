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
        Schema::create('digital_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete(null);
            $table->string('name');
            $table->string('seller_name');
            $table->integer('price');
            $table->string('commission');
            $table->string('contact_email');
            $table->string('affiliate_link')->nullable();
            $table->string('vsl_pa_link')->nullable();
            $table->string('access_link')->nullable();
            $table->string('sale_challenge_link')->nullable();
            $table->string('promotional_material')->nullable();
            $table->boolean('is_partnership')->default(false)->nullable();
            $table->string('x_link')->nullable();
            $table->string('ig_link')->nullable();
            $table->string('yt_link')->nullable();
            $table->string('fb_link')->nullable();
            $table->string('tt_link')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_products');
    }
};
