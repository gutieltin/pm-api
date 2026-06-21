@component('mail::message')
# New Comment on Your Task 💬

Hi **{{ $recipient->name }}**,

**{{ $commenter->name }}** left a comment on a task in **{{ $projectName }}**.

@component('mail::panel')
**Task:** {{ $task->title }}

**Comment:**
{{ $comment->content }}
@endcomponent

@component('mail::button', ['url' => $taskUrl, 'color' => 'green'])
View Task & Reply
@endcomponent

Thanks,
**The ProjectFlow Team**
@endcomponent