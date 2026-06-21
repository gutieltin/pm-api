@component('mail::message')
# Task Status Updated 🔄

Hi **{{ $assignee->name }}**,

The status of your task has been updated.

@component('mail::panel')
**Task:** {{ $task->title }}

**Project:** {{ $projectName }}

**Previous Status:** {{ ucfirst(str_replace('_', ' ', $oldStatus)) }}

**New Status:** {{ ucfirst(str_replace('_', ' ', $newStatus)) }}
@endcomponent

@component('mail::button', ['url' => $taskUrl, 'color' => 'green'])
View Task
@endcomponent

Thanks,
**The ProjectFlow Team**
@endcomponent