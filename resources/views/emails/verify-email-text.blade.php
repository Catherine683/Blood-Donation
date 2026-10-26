@component('emails.layout-text', [
    'subject' => 'Confirm your email for DonorLink UG',
    'heading' => 'Confirm your email address',
    'actionText' => 'Confirm Email Address',
    'actionUrl' => $url,
])
Hello {{ $user->name }},

Thank you for registering as a voluntary blood donor with DonorLink UG. Please confirm your email address to complete your registration and begin booking blood donation appointments with UBTS.

This link will expire in 60 minutes.
@endcomponent
