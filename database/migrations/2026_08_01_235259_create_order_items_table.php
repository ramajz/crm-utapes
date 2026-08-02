<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->integer('position')->default(0);
            $table->string('product_name', 255)->nullable();
            $table->string('variant_unique_id', 100)->nullable();
            $table->string('variant_sku', 100)->nullable();
            $table->integer('quantity')->default(1);
            $table->integer('weight')->default(0);
            $table->decimal('product_price', 15, 0)->default(0);
            $table->decimal('variant_price', 15, 0)->default(0);
            $table->decimal('variant_cogs', 15, 0)->default(0);
            $table->decimal('discount', 15, 0)->default(0);
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
