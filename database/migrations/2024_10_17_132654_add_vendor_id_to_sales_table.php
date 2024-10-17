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
            $table->enum('status', ['approved', 'disapproved', 'pending'])->default('pending');
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade')->after('affiliate_id');
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
        $table->dropColumn('vendor_id');
        $table->string('status')->change();
            //
        });
    }
};
