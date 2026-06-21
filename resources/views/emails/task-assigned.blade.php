@component('mail::message')
# New Task Assigned to You 📋

Hi **{{ $assignee->name }}**,

You have been assigned a new task in **{{ $projectName }}**.

@component('mail::panel')
**Task:** {{ $task->title }}

**Project:** {{ $projectName }}

**Urgency:** {{ ucfirst($task->urgency ?? 'Normal') }}

@if($task->due_date)
**Due Date:** {{ \Carbon\Carbon::parse($task->due_date)->format('d M Y') }}
@endif

@if($task->description)
**Description:** {{ $task->description }}
@endif
@endcomponent

@component('mail::button', ['url' => $taskUrl, 'color' => 'green'])
View Task
@endcomponent

Thanks,
**The ProjectFlow Team**
@endcomponent