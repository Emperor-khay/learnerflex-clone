<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropForeignKeyConstraintOnUserIdInTransactionsTable extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Drop the foreign key constraint on the user_id column
            $table->dropForeign(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Restore the foreign key constraint on user_id if rolling back
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
}
