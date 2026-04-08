<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
        'id' => $this->id,
        'name' => $this->name,
        'slug' => $this->slug,
        'description' => $this->description,
        
        // Stats for the Admin Dashboard
        'stats' => [
            'projects_count' => $this->whenCounted('projects'),
            'members_count'  => $this->whenCounted('users'),
        ],

        'owner' => [
            'id' => $this->owner_id,
            'name' => $this->owner?->name,
        ],

        'dates' => [
            'created_at' => $this->created_at->format('M d, Y'),
            'is_archived' => $this->trashed(), // Returns true if in Trash
        ],
    ];
    }

}
