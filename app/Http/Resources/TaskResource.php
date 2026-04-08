<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
        'id'          => $this->id,
        'title'       => $this->title,
        'description' => $this->description,
        'urgency'     => strtoupper($this->priority), // Cleaned up
        'current_status' => $this->status,
        'due_date'    => $this->due_date ? $this->due_date->format('Y-m-d') : null,
        'assignee'    => [
            'id'   => $this->assignee_id,
            'name' => $this->assignee?->name ?? 'Unassigned',
        ],
        'created_at'  => $this->created_at->toDateTimeString(),
        'deleted_at' => $this->when($this->deleted_at, function() {
            return $this->deleted_at->toDateTimeString();
        })
    ];
    }
}
