<?php

use App\Models\Invoice;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('invoices.{invoiceId}', function ($user, $invoiceId) {
    $invoice = Invoice::find($invoiceId);

    return $invoice && $user->id === $invoice->user_id;
});
