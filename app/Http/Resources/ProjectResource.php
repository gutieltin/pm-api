<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
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
            'workspace_id' => $this->workspace_id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'stats' => [
                'total_tasks' => $this->whenCounted('tasks'),
            ],
            'workspace_name' => $this->whenLoaded('workspace', function () {
                return $this->workspace->name;
            }),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'is_deleted' => $this->trashed(),
        ];
    }
}
