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
            $table->integer('product_id');
            $table->integer('affiliate_id');
            $table->integer('user_id');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10);
            $table->string('status')->default('pending');
            $table->boolean('is_onboard')->default(0);
            $table->string('email');
            $table->integer('org_vendor')->nullable();
            $table->integer('org_aff')->nullable();
            $table->integer('org_company')->nullable();
            
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
