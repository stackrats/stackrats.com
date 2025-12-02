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
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('recurring_completed_at')->nullable()->after('next_recurring_at');
            $table->foreignUuid('parent_invoice_id')->nullable()->after('recurring_completed_at')->constrained('invoices')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
        });
    }
};
