@component('emails.layout', [
    'subject' => 'Reset your password for DonorLink UG',
    'heading' => 'Reset your password',
    'actionText' => 'Reset Password',
    'actionUrl' => $url,
])
    <p style="margin: 0 0 16px;">Hello {{ $user->name }},</p>
    <p style="margin: 0 0 16px;">We received a request to reset the password for your DonorLink UG account. Click the button below to choose a new password.</p>
    <p style="margin: 0 0 16px;">This password reset link will expire in 60 minutes.</p>
    <p style="margin: 0;">If you did not request a password reset, no further action is required.</p>
@endcomponent
