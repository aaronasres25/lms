<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeePayment extends Model
{
    protected $fillable = ['fee_invoice_id', 'amount', 'paid_on', 'method', 'reference', 'received_by'];
    protected $casts = ['amount' => 'float', 'paid_on' => 'date'];

    public function invoice() { return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id'); }
    public function receiver() { return $this->belongsTo(User::class, 'received_by'); }
}
