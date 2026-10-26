<x-mail-layout :subject="$subject" :heading="$heading" :action-text="$actionText" :action-url="$actionUrl">
    <p style="font-size: 15px; color: #1F1A1A; margin: 0 0 16px;">
        Hello {{ $donorName }},
    </p>

    <p style="font-size: 15px; color: #403434; margin: 0 0 20px; line-height: 1.6;">
        Your voluntary blood donation appointment has been confirmed with Uganda Blood Transfusion Services (UBTS).
    </p>

    <div style="background-color: #FBF8F6; border: 1px solid #E5DFDB; border-radius: 12px; padding: 20px; margin: 0 0 24px;">
        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #B3262E; margin-bottom: 6px;">
            Appointment Details
        </div>
        <div style="font-size: 17px; font-weight: 700; color: #1F1A1A; margin-bottom: 4px;">
            {{ $driveTitle }}
        </div>
        <div style="font-size: 14px; font-weight: 600; color: #403434; margin-bottom: 8px;">
            Date &amp; Time: {{ $dateTimeEat }} EAT
        </div>
        <div style="font-size: 13px; color: #524343;">
            Venue: {{ $venue }}@if($address) &bull; {{ $address }}@endif
        </div>
    </div>

    <div style="background-color: #FFFFFF; border-left: 3px solid #B3262E; padding: 12px 16px; margin: 0 0 24px;">
        <p style="font-size: 13px; color: #524343; margin: 0; line-height: 1.5;">
            <strong>Important Logistics:</strong> Please arrive a few minutes early and carry a valid photo ID (National ID, passport, driving permit, or student card). On-site UBTS staff handle reception and questions.
        </p>
    </div>

    <p style="font-size: 14px; color: #524343; margin: 0 0 16px;">
        A calendar invitation (<code>donorlink-appointment.ics</code>) is attached to this email so you can add this session to your calendar.
    </p>

    <p style="font-size: 13px; color: #736262; margin: 0;">
        Need to change your time or cancel? <a href="{{ $rescheduleUrl }}" style="color: #B3262E; text-decoration: underline;">Reschedule or cancel your session</a>.
    </p>
</x-mail-layout>
