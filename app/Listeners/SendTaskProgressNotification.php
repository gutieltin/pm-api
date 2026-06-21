<?php

namespace App\Listeners;

use App\Events\TaskUpdated;
use App\Mail\TaskProgressMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendTaskProgressNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TaskUpdated $event): void
    {
        Mail::queue(new TaskProgressMail($event->task));
    }
}
