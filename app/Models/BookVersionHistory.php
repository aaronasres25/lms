<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookVersionHistory extends Model
{
    protected $fillable = ['book_id', 'version', 'change_reason', 'user_id'];

    public function user() { return $this->belongsTo(User::class); }
    public function book() { return $this->belongsTo(Book::class); }
}
