@component('emails.layout', [
    'subject' => 'Confirm your email for DonorLink UG',
    'heading' => 'Confirm your email address',
    'actionText' => 'Confirm Email Address',
    'actionUrl' => $url,
])
    <p style="margin: 0 0 16px;">Hello {{ $user->name }},</p>
    <p style="margin: 0 0 16px;">Thank you for registering as a voluntary blood donor with DonorLink UG. Please confirm your email address to complete your registration and begin booking blood donation appointments with UBTS.</p>
    <p style="margin: 0;">This link will expire in 60 minutes.</p>
@endcomponent
