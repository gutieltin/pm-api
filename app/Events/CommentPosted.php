<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Comment;

class CommentPosted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $comment;

    /**
     * Create a new event instance.
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment->load('user:id,name'); // Load user info for broadcasting
    }


    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('project.' . $this->comment->task->project_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->comment->task_id,
            'comment' => [
                'id'         => $this->comment->id,
                'content'    => $this->comment->content,
                'created_at' => $this->comment->created_at->diffForHumans(),
                'user'       => [
                    'name' => $this->comment->user->name,
                ],
            ],
        ];
    }
    public function broadcastAs(): string
    {
        return 'comment posted';
    }
}
