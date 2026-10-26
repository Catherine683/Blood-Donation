<x-mail-layout :subject="$subject" :heading="$heading" :action-text="$actionText" :action-url="$actionUrl">
    <p style="font-size: 15px; color: #1F1A1A; margin: 0 0 16px;">
        Hello {{ $donorName }},
    </p>

    <p style="font-size: 15px; color: #403434; margin: 0 0 20px; line-height: 1.6;">
        Your voluntary blood donation appointment for <strong>{{ $driveTitle }}</strong> on <strong>{{ $dateTimeEat }} EAT</strong> has been cancelled.
    </p>
    <div style="background-color: #FBF8F6; border: 1px solid #E5DFDB; border-radius: 12px; padding: 20px; margin: 0 0 24px;">
        <p style="font-size: 13px; color: #524343; margin: 0 0 10px; line-height: 1.5;">
            Your reserved place has been released immediately. You can browse upcoming blood donation drives across Uganda and schedule a session whenever you are ready.
        </p>
        <p style="font-size: 13px; color: #725C5C; margin: 0; line-height: 1.5;">
            Remember to remove this appointment from your calendar.
        </p>
    </div>
</x-mail-layout>
