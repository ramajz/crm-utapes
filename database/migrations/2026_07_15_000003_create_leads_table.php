<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 50)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('handler_id')->nullable()->constrained()->nullOnDelete();

            // Financial
            $table->string('financial_status', 20)->default('unpaid'); // unpaid, paid, lunas
            $table->decimal('total_value', 15, 0)->default(0);

            // Funnel & Follow-up
            $table->string('funnel_stage', 10)->default('cold'); // hot, warm, cold
            $table->string('status_fu', 30)->default('new');
            $table->text('notes')->nullable();
            $table->string('size', 5)->nullable();

            // Marketing
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('utm_content', 100)->nullable();
            $table->string('traffic_type', 20)->nullable(); // ads, organik, direct

            // Classification
            $table->string('lead_type', 10)->default('new'); // new, repeat

            // Response Time
            $table->timestamp('first_replied_at')->nullable();

            // Timestamps
            $table->dateTime('timestamp'); // Original order timestamp
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('financial_status');
            $table->index('funnel_stage');
            $table->index('status_fu');
            $table->index('timestamp');
            $table->index('handler_id');
            $table->index('customer_id');
            $table->index('traffic_type');
            $table->index('lead_type');
            $table->index('first_replied_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
