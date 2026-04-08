<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model
{
    use HasFactory , SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'owner_id', // The User ID of the person who created it
    ];

    // --- Relationships ---

    /**
     * The creator/owner of the workspace.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Many-to-Many relationship with Users.
     * This uses the 'workspace_user' pivot table to store the 'role'.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->withPivot('role') // Crucial for our Admin/Employee logic
            ->withTimestamps();
    }

    /**
     * All projects assigned to this workspace.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    // --- Helpers ---

    /**
     * Get all tasks across all projects in this workspace.
     * This is useful for a "Global Task List" view.
     */
    public function tasks()
    {
        return $this->hasManyThrough(Task::class, Project::class);
    }

    protected static function booted()
{
    static::deleted(function ($workspace) {
        // When workspace is soft-deleted, soft-delete its projects
        $workspace->projects()->delete();
    });

    static::restoring(function ($workspace) {
        // When restored, bring back the projects too
        $workspace->projects()->restore();
    });
}
}
