@component('emails.layout-text', [
    'subject' => 'Reset your password for DonorLink UG',
    'heading' => 'Reset your password',
    'actionText' => 'Reset Password',
    'actionUrl' => $url,
])
Hello {{ $user->name }},

We received a request to reset the password for your DonorLink UG account. Click the link below to choose a new password.

This password reset link will expire in 60 minutes.

If you did not request a password reset, no further action is required.
@endcomponent
