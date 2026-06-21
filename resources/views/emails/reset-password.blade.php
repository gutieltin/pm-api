@component('mail::message')
# Reset Your Password 🔒

Hi **{{ $user->name }}**,

We received a request to reset your ProjectFlow password. Click the button below to choose a new one.

@component('mail::button', ['url' => $resetUrl, 'color' => 'success'])
Reset Password
@endcomponent

This link will expire in 60 minutes. If you didn't request this, you can safely ignore this email.

Thanks,
**The ProjectFlow Team**
@endcomponent