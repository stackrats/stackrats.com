<?php

namespace App\Models;

use App\Casts\DecimalToIntCast;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'contact_id',
        'invoice_number',
        'recipient_name',
        'recipient_email',
        'recipient_address',
        'amount',
        'gst',
        'currency',
        'description',
        'line_items',
        'invoice_status_id',
        'issue_date',
        'due_date',
        'is_recurring',
        'recurring_frequency_id',
        'next_recurring_date',
        'last_sent_at',
    ];

    protected $casts = [
        'line_items' => 'array',
        'amount' => DecimalToIntCast::class,
        'gst' => DecimalToIntCast::class,
        'issue_date' => 'date',
        'due_date' => 'date',
        'next_recurring_date' => 'date',
        'last_sent_at' => 'datetime',
        'is_recurring' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function invoiceStatus(): BelongsTo
    {
        return $this->belongsTo(InvoiceStatus::class);
    }

    public function recurringFrequency(): BelongsTo
    {
        return $this->belongsTo(RecurringFrequency::class);
    }

    public function generateInvoiceNumber(): string
    {
        // Format: 381251111
        // 3 random digits + 2 year + 2 month + 2 count
        $randomPrefix = str_pad(random_int(100, 999), 3, '0', STR_PAD_LEFT);
        $year = now()->format('y');
        $month = now()->format('m');

        // Count invoices for this recipient in the current year
        $yearStart = now()->startOfYear();
        $count = static::where('recipient_email', $this->recipient_email)
            ->where('issue_date', '>=', $yearStart)
            ->count() + 1;

        $countPadded = str_pad($count, 2, '0', STR_PAD_LEFT);

        return $randomPrefix.$year.$month.$countPadded;
    }
}
