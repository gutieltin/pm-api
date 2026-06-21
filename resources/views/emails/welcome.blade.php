@component('mail::message')
# Welcome to ProjectFlow! 🎉

Hi **{{ $user->name }}**,

Your account has been created. You've been added to the **{{ $workspaceName }}** workspace as a **{{ ucfirst($role) }}**.

Here are your login credentials:

@component('mail::panel')
**Email:** {{ $user->email }}

**Temporary Password:** `{{ $tempPassword }}`
@endcomponent

@component('mail::button', ['url' => $loginUrl, 'color' => 'success'])
Log In to ProjectFlow
@endcomponent

> ⚠️ **Important:** You will be asked to set a new password on your first login. Please keep this email safe.

If you have any issues logging in, contact your manager.

Thanks,
**The ProjectFlow Team**
@endcomponent