{{ $heading }}
==================================================

Hello {{ $donorName }},

Your blood donation appointment has been successfully rescheduled to a new time slot.

UPDATED APPOINTMENT DETAILS:
Drive: {{ $driveTitle }}
New Date & Time: {{ $dateTimeEat }} EAT
Venue: {{ $venue }}@if($address) ({{ $address }})@endif

UPDATED CALENDAR INVITATION:
An updated calendar invitation (donorlink-appointment.ics) is attached to this email. Opening it will update the event in your calendar.

View your appointment details:
{{ $actionUrl }}

Manage or cancel your session:
{{ $rescheduleUrl }}

--------------------------------------------------
Uganda Blood Transfusion Services, Nakasero Hill Road, Kampala.
