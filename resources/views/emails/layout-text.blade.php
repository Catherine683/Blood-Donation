{{ $heading ?? $subject }}
==================================================

{{ $slot ?? '' }}

@if (!empty($actionText) && !empty($actionUrl))
{{ $actionText }}:
{{ $actionUrl }}
@endif

--------------------------------------------------
You received this email because someone registered with this address on DonorLink UG. If that wasn't you, you can ignore this message.

Uganda Blood Transfusion Services, Nakasero Hill Road, Kampala.
