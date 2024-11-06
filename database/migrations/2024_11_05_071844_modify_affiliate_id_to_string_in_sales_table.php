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
        Schema::table('sales', function (Blueprint $table) {
            // Drop the foreign key constraint and column first
            $table->dropForeign(['affiliate_id']);
            $table->dropColumn('affiliate_id');

            // Recreate the column as a string
            $table->string('affiliate_id')->nullable()->after('vendor_id');
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Drop the string column
            $table->dropColumn('affiliate_id');

            // Recreate it as an integer with a foreign key constraint
            $table->foreignId('affiliate_id')->nullable()->constrained('users')->onDelete('set null');
       
            //
        });
    }
};
