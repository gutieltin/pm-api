<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'project_id',
        'creator_id',
        'assignee_id',
        'title',
        'description',
        'priority',
        'status',
        'due_at',
    ];


    protected $casts = [
        'due_at' => 'date',
        "created_at" => 'datetime',
    ];



    /**
     * The project this task belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * The admin/manager who created the task.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * The employee assigned to do the work.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id')->withDefault([
            'name' => 'Unassigned',
        ]);
    }

    /**
     * All comments left on this specific task.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest();
    }

    // --- Helpers / Scopes ---

    /**
     * Scope a query to only include "High Priority" tasks.
     * Usage: Task::highPriority()->get();
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

}
