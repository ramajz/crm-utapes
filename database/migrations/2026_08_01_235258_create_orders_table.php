<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 50)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('handler_id')->nullable()->constrained()->nullOnDelete();

            // Order lifecycle (Scalev)
            $table->string('status', 30)->default('draft');
            $table->string('payment_status', 20)->default('unpaid');
            $table->boolean('is_probably_spam')->default(false);
            $table->string('source', 20)->default('scalev');

            // Timeline order & pembayaran
            $table->dateTime('created_time')->nullable();
            $table->dateTime('draft_time')->nullable();
            $table->dateTime('pending_time')->nullable();
            $table->dateTime('confirmed_time')->nullable();
            $table->dateTime('in_process_time')->nullable();
            $table->dateTime('ready_time')->nullable();
            $table->dateTime('shipped_time')->nullable();
            $table->dateTime('completed_time')->nullable();
            $table->dateTime('rts_time')->nullable();
            $table->dateTime('canceled_time')->nullable();
            $table->dateTime('closed_time')->nullable();
            $table->dateTime('unpaid_time')->nullable();
            $table->dateTime('paid_time')->nullable();
            $table->dateTime('conflict_time')->nullable();
            $table->dateTime('settled_time')->nullable();
            $table->dateTime('transfer_time')->nullable();

            // Payment detail
            $table->string('payment_method', 100)->nullable();
            $table->string('epayment_provider', 100)->nullable();
            $table->string('financial_entity', 100)->nullable();
            $table->string('payment_account_holder', 100)->nullable();
            $table->string('payment_account_number', 100)->nullable();
            $table->string('transferproof_url')->nullable();
            $table->string('pg_reference_id')->nullable();
            $table->json('pg_payment_info')->nullable();

            // Financial
            $table->decimal('gross_revenue', 15, 0)->default(0);
            $table->decimal('scalev_fee', 15, 0)->default(0);
            $table->decimal('payment_fee', 15, 0)->default(0);
            $table->decimal('net_payment_revenue', 15, 0)->default(0);
            $table->decimal('unique_code_discount', 15, 0)->default(0);
            $table->decimal('discount_code_discount', 15, 0)->default(0);
            $table->decimal('net_revenue', 15, 0)->default(0);
            $table->decimal('product_price', 15, 0)->default(0);
            $table->decimal('product_discount', 15, 0)->default(0);
            $table->decimal('other_income', 15, 0)->default(0);
            $table->decimal('cogs', 15, 0)->default(0);
            $table->decimal('shipping_cost', 15, 0)->default(0);
            $table->decimal('shipping_discount', 15, 0)->default(0);
            $table->decimal('discount_rate', 8, 2)->default(0);
            $table->integer('total_quantity')->default(0);
            $table->integer('total_weight')->default(0);

            // Attribution
            $table->string('advertiser', 100)->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('utm_content', 100)->nullable();
            $table->string('utm_id', 100)->nullable();
            $table->string('traffic_type', 20)->nullable();
            $table->string('store', 100)->nullable();

            // Destination & catatan
            $table->json('destination')->nullable();
            $table->text('notes')->nullable();

            // Payload mentah event terakhir yang dipakai
            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->index('customer_id');
            $table->index('handler_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('paid_time');
            $table->index('created_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
