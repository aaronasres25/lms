<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReadHistory extends Model
{
    protected $fillable = ['user_id', 'book_id', 'category_id', 'department_id'];

    public function book() { return $this->belongsTo(Book::class); }
}
