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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('tx_ref')->unique()->nullable();
            $table->string('transaction_id')->nullable();
            $table->integer('product_id')->nullable();
            $table->string('affiliate_id')->nullable();
            $table->string('user_id')->nullable();//buyer
            $table->foreignId('vendor_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->nullable();
            $table->string('status')->default('pending');
            $table->string('email')->nullable();
            $table->bigInteger('org_vendor')->nullable();
            $table->bigInteger('org_aff')->nullable();
            $table->bigInteger('org_company')->nullable();
            $table->string('description')->nullable();

            
            $table->json('meta')->nullable();  // To store any additional data
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
