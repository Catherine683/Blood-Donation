{{ $heading }}
==================================================

Hello {{ $donorName }},

Your voluntary blood donation appointment has been confirmed with Uganda Blood Transfusion Services (UBTS).

APPOINTMENT DETAILS:
Drive: {{ $driveTitle }}
Date & Time: {{ $dateTimeEat }} EAT
Venue: {{ $venue }}@if($address) ({{ $address }})@endif

LOGISTICS:
Please arrive a few minutes early and carry a valid photo ID (National ID, passport, driving permit, or student card). On-site UBTS staff handle reception and questions.

A calendar invitation file (donorlink-appointment.ics) is attached to this email.

View your appointment details:
{{ $actionUrl }}

Reschedule or cancel your session:
{{ $rescheduleUrl }}

--------------------------------------------------
Uganda Blood Transfusion Services, Nakasero Hill Road, Kampala.
