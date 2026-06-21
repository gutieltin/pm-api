<?php

namespace App\Mail;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class TaskProgressMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Task $task)
    {
        // Ensure relationships are loaded to prevent N+1 queries
        if (! $this->task->relationLoaded('assignee')) {
            $this->task->load('assignee');
        }
        if (! $this->task->relationLoaded('project')) {
            $this->task->load('project');
        }
    }

    public function envelope(): Envelope
    {
        // Verify assignee exists before accessing email
        if (! $this->task->assignee || ! $this->task->assignee->email) {
            throw new \RuntimeException('Task must have an assigned user with an email address.');
        }

        return new Envelope(
            subject: 'Task Progress Update: '.$this->task->title,
            to: [$this->task->assignee->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.task-progress',
            with: [
                'taskTitle' => $this->task->title,
                'taskStatus' => $this->task->status,
                'assigneeName' => $this->task->assignee->name ?? 'Assignee',
                'projectName' => $this->task->project->name ?? 'Project',
                'dueDate' => $this->task->due_at,
                'priority' => $this->task->priority,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
