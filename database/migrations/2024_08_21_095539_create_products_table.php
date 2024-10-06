<?php

use App\Enums\ProductType;
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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->string('name');
            $table->longText('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 15, 2);
            $table->decimal('old_price', 15, 2)->nullable();
            $table->string('type');
            $table->string('commission')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('vsl_pa_link')->nullable();
            $table->string('access_link')->nullable();
            $table->string('sale_page_link')->nullable();
            $table->string('sale_challenge_link')->nullable();
            $table->string('promotional_material')->nullable();
            $table->boolean('is_partnership')->default(false)->nullable();
            $table->boolean('is_affiliated')->default(true);
            $table->string('x_link')->nullable();
            $table->string('ig_link')->nullable();
            $table->string('yt_link')->nullable();
            $table->string('fb_link')->nullable();
            $table->string('tt_link')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
