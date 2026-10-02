<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suppliers, purchase orders and stock transfers (plan.md §18, §30).
 *
 * stock_movements already exists from Phase 9 and is the auditable ledger;
 * this phase adds the documents that drive it.
 */
return new class extends Migration
{
    public function up(): void
    {
        // What a unit costs the facility, so margin is knowable.
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('cost', 10, 2)->default(0)->after('price');
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile', 24)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'is_active']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('reference', 32)->unique();
            $table->enum('status', ['draft', 'ordered', 'partial', 'received', 'cancelled'])
                ->default('draft')->index();

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->char('currency', 3)->default('PHP');
            $table->text('notes')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // Snapshot: a later cost change must not rewrite history.
            $table->string('product_name');
            $table->decimal('unit_cost', 10, 2);
            $table->unsignedSmallInteger('quantity_ordered')->default(1);
            $table->unsignedSmallInteger('quantity_received')->default(0);
            $table->timestamps();

            $table->index('purchase_order_id');
        });

        // Moving stock between branches is a movement out and a movement in,
        // never a silent edit of the balance.
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('to_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('reference', 32)->unique();
            $table->unsignedSmallInteger('quantity');
            $table->enum('status', ['in_transit', 'received', 'cancelled'])->default('in_transit')->index();
            $table->text('notes')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });

        Schema::dropIfExists('suppliers');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('cost');
        });
    }
};
