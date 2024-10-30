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
        Schema::table('products', function (Blueprint $table) {
            // Add the vendor_id column and set foreign key constraint
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->onDelete('cascade'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
            $table->dropForeign(['vendor_id']); // Drop foreign key
            $table->dropColumn('vendor_id'); // Drop the column
        });
    }
};
