<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;
    protected $fillable = [
        'task_id',
        'user_id',
        'content',
    ];
    public function task()
{
    return $this->belongsTo(Task::class);
}

public function user()
{
    return $this->belongsTo(User::class);
}

public function getCreatedAtForHumansAttribute(): string
{
    return $this->created_at->diffForHumans();
}
}
