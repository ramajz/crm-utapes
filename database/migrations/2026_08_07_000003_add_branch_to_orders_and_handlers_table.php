<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('handler_id')->constrained()->nullOnDelete();
            $table->index('branch_id');
        });

        Schema::table('handlers', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('handlers', function (Blueprint $table) {
            $table->dropIndex(['branch_id']);
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['branch_id']);
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
