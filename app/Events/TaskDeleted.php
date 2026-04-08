<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $taskId;
    public $projectId;

    public function __construct($taskId, $projectId)
    {
        $this->taskId = $taskId;
        $this->projectId = $projectId;
    }

    public function broadcastOn(): array
    {
        // We still broadcast to the project channel so everyone on that project sees the removal
        return [
            new PrivateChannel('project.' . $this->projectId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->taskId,
            'message' => 'Task has been removed'
        ];
    }
}
