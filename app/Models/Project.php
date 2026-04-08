<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projects';

    protected $fillable = [
        'workspace_id',
        'name',
        'description',
        'owner_id',
        'status',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
        ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
    
    //whoever is the lead of this project
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getProgressAttribute(): int
    {
        $total = $this->tasks()->count();
        if ($total === 0) return 0;

        $completed = $this->tasks()->where('status', 'done')->count();
        
        return (int) (($completed / $total) * 100);
    }

    protected static function booted()
    {
        static::deleted(fn ($project) => $project->tasks()->delete());
        static::restoring(fn ($project) => $project->tasks()->restore());
    }
}
