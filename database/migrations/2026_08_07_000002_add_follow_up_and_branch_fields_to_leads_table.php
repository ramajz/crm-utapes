<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('handler_id')->constrained()->nullOnDelete();
            $table->boolean('follow_up_required')->default(false)->after('last_update_at');
            $table->string('follow_up_status', 10)->nullable()->after('follow_up_required');
            $table->timestamp('follow_up_completed_at')->nullable()->after('follow_up_status');

            $table->index('branch_id');
            $table->index('follow_up_required');
            $table->index('follow_up_status');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['follow_up_status']);
            $table->dropIndex(['follow_up_required']);
            $table->dropIndex(['branch_id']);
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn('follow_up_completed_at');
            $table->dropColumn('follow_up_status');
            $table->dropColumn('follow_up_required');
        });
    }
};
