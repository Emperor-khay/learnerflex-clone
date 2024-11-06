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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('affiliate_id')->nullable()->constrained('users')->onDelete('set null'); // Nullable if no affiliate
            $table->decimal('amount', 15, 2); // Sale amount
            $table->string('status')->default('pending');
            $table->decimal('commission', 15, 2)->nullable(); // Commission given to affiliate
            $table->string('currency', 10)->nullable(); // Currency used in the sale
            $table->string('email')->nullable();
            $table->integer('org_vendor')->nullable();
            $table->integer('org_aff')->nullable();
            $table->integer('org_company')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
