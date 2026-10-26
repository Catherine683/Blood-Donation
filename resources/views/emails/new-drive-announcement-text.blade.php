{{ $heading }}
==================================================

Hello {{ $donorName }},

Uganda Blood Transfusion Services (UBTS) has scheduled a new voluntary blood donation drive in your community.

DRIVE DETAILS:
Title: {{ $driveTitle }}
Dates: {{ $dateRangeEat }}
Venue: {{ $venue }}@if($address) ({{ $address }})@endif

@if ($description)
{{ $description }}
@endif

See available time slots and book:
{{ $actionUrl }}

--------------------------------------------------
To unsubscribe from new blood drive announcement emails:
{{ $unsubscribeUrl }}

Uganda Blood Transfusion Services, Nakasero Hill Road, Kampala.
