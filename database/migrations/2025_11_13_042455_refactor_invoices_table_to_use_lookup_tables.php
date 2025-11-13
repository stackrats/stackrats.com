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
            // Drop the old enum columns
            $table->dropColumn('status');
            $table->dropColumn('recurring_frequency');
        });

        Schema::table('invoices', function (Blueprint $table) {
            // Add new UUID foreign key columns
            $table->foreignUuid('invoice_status_id')->nullable()->constrained('invoice_statuses')->onDelete('restrict');
            $table->foreignUuid('recurring_frequency_id')->nullable()->constrained('recurring_frequencies')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop the new foreign key columns
            $table->dropForeign(['invoice_status_id']);
            $table->dropForeign(['recurring_frequency_id']);
            $table->dropColumn('invoice_status_id');
            $table->dropColumn('recurring_frequency_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            // Restore the old enum columns
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->enum('recurring_frequency', ['weekly', 'monthly', 'quarterly', 'yearly'])->nullable();
        });
    }
};
