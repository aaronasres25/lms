<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** lms.viewer - reading progress of one user in one e-book. */
class Viewer extends Model
{
    protected $fillable = ['user_id', 'book_id', 'current_page', 'last_read_at'];
    protected $casts = ['last_read_at' => 'datetime'];

    public function book() { return $this->belongsTo(Book::class); }
    public function user() { return $this->belongsTo(User::class); }

    public static function progressFor(int $userId, int $bookId): int
    {
        return (int) (self::where('user_id', $userId)->where('book_id', $bookId)->value('current_page') ?: 1);
    }

    public static function saveProgress(int $userId, int $bookId, int $page): void
    {
        if ($page < 1) return;
        self::updateOrCreate(
            ['user_id' => $userId, 'book_id' => $bookId],
            ['current_page' => $page, 'last_read_at' => now()]
        );
    }
}
