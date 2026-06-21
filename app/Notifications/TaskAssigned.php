<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task)
    {
        // Ensure relationships are loaded to prevent N+1 queries
        if (! $this->task->relationLoaded('project')) {
            $this->task->load('project');
        }
        if (! $this->task->relationLoaded('creator')) {
            $this->task->load('creator');
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->greeting("Hello {$notifiable->name}!")
            ->line("You have been assigned a new task: **{$this->task->title}**")
            ->line("**Project**: {$this->task->project->name}")
            ->line('**Priority**: '.ucfirst($this->task->priority))
            ->when($this->task->due_at, fn ($mail) => $mail->line("**Due Date**: {$this->task->due_at->format('M d, Y')}"))
            ->when($this->task->description, fn ($mail) => $mail->line("**Description**: {$this->task->description}"))
            ->line("Assigned by: {$this->task->creator->name}")
            ->action('View Task', url('/tasks/'.$this->task->id))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'project_name' => $this->task->project->name,
            'priority' => $this->task->priority,
            'due_at' => $this->task->due_at?->toDateString(),
            'assigned_by' => $this->task->creator->name,
        ];
    }
}
