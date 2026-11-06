<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeInvoiceItem extends Model
{
    protected $fillable = ['fee_invoice_id', 'fee_type_id', 'description', 'amount'];
    protected $casts = ['amount' => 'float'];
}
