<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Products and point-of-sale sales (plan.md §17, §30).
 *
 * Line items snapshot the name and the peso price at the moment of sale, so a
 * later price change or product deletion can never rewrite what a customer
 * actually paid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->enum('category', [
                'paddle',
                'ball',
                'grip',
                'bag',
                'apparel',
                'drink',
                'snack',
                'accessory',
                'rental',
            ])->index();

            // Barcode is optional: apparel and food are often scanned by name.
            $table->string('sku', 64)->nullable();
            $table->string('barcode', 64)->nullable();

            $table->decimal('price', 10, 2);
            $table->char('currency', 3)->default('PHP');
            $table->unsignedSmallInteger('stock')->default(0);
            $table->unsignedSmallInteger('reorder_level')->default(0);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Stock that never goes below this, whatever is sold. Set high
            // enough for consumables, 0 for equipment.
            $table->unsignedSmallInteger('track_stock')->default(1);
            $table->decimal('tax_rate', 5, 2)->default(0);

            // A SKU and a barcode are each unique within a facility only.
            $table->unique(['organization_id', 'sku'], 'products_org_sku_unique');
            $table->unique(['organization_id', 'barcode'], 'products_org_barcode_unique');
            $table->index(['organization_id', 'is_active', 'category']);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('reference', 32)->unique();
            $table->enum('status', ['open', 'paid', 'refunded', 'void'])->default('open')->index();

            // Money in pesos, matching the rest of the product.
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('amount_tendered', 10, 2)->default(0);
            $table->decimal('change_due', 10, 2)->default(0);
            $table->char('currency', 3)->default('PHP');

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status', 'created_at']);
            $table->index(['branch_id', 'created_at']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            // Snapshots, so history survives a rename or a price change.
            $table->string('product_name');
            $table->string('sku', 64)->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('line_total', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->timestamps();

            $table->index('sale_id');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Signed: stock in and stock out are the same fact, so a movement
            // is never silently lost.
            $table->integer('quantity');
            $table->enum('reason', ['sale', 'refund', 'restock', 'adjustment', 'damage', 'return'])
                ->default('adjustment')->index();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('products');
    }
};
