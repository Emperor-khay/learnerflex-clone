<?php

use App\Enums\OtherProductType;
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
        Schema::create('other_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete(null);
            $table->string('name');
            $table->string('image');
            $table->string('access_link')->nullable();
            $table->string('description');
            $table->string('seller_name')->nullable();
            $table->decimal('rating', 5, 2)->default(0); // Added precision and scale
            $table->decimal('price', 15, 2); // Added precision and scale
            $table->decimal('old_price', 15, 2)->nullable(); // Added precision and scale
            $table->enum('type', array_map(fn($case) => $case->value, OtherProductType::cases())); // Converting enum values to array of strings
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('other_products');
    }
};
