<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expenses, so revenue is meaningful (plan.md §20, §30).
 *
 * Revenue itself is not duplicated into a ledger table: it is derived from
 * payments and sales, which are already authoritative. A copied revenue
 * figure could silently disagree with the money actually collected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('suppliers')->nullOnDelete();

            $table->string('reference', 32)->nullable();
            $table->string('category', 64)->index();
            $table->string('description');

            // Pesos, always positive. A negative amount would be a receipt or
            // a refund, which have their own tables.
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('PHP');
            $table->date('incurred_on')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'incurred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
