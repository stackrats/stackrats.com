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
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('recipient_name');
            $table->string('recipient_email');
            $table->text('recipient_address')->nullable();
            $table->unsignedInteger('amount')->default(0);
            $table->unsignedInteger('gst')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->text('description')->nullable();
            $table->text('line_items')->nullable(); // JSON
            $table->foreignUuid('invoice_status_id')->constrained('invoice_statuses')->onDelete('restrict');
            $table->date('issue_date');
            $table->date('due_date');
            $table->boolean('is_recurring')->default(false);
            $table->foreignUuid('recurring_frequency_id')->nullable()->constrained('recurring_frequencies')->onDelete('restrict');
            $table->timestamp('next_recurring_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });
    }
};
