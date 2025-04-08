<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Borrow extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_copy_id',
        'user_id',
        'borrowed_from',
        'due_date',
        'returned_at',
    ];

    public function bookCopy()
    {
        return $this->belongsTo(BookCopy::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
