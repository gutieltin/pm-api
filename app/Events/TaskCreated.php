<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// "ShouldBroadcastNow" means send it immediately, don't wait for a queue worker.
class TaskCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $task;

    public function __construct(Task $task)
    {
        // We pass the entire Task model so the frontend gets the ID, Title, etc.
        $this->task = $task;
    }

    // This defines the "Radio Station" (Channel) we are broadcasting on.
    public function broadcastOn(): array
    {
        // We broadcast to a specific project channel.
        // Only people with access to Project #1 can listen to 'project.1'
        return [
            new PrivateChannel('project.' . $this->task->project_id),
        ];
    }
    
    // Optional: Customize the data name (e.g., 'task')
    public function broadcastWith(): array
    {
        return ['task' => $this->task];
    }
}