<x-mail-layout :subject="$subject" :heading="$heading" :action-text="$actionText" :action-url="$actionUrl">
    <p style="font-size: 15px; color: #1F1A1A; margin: 0 0 16px;">
        Hello {{ $donorName }},
    </p>

    <p style="font-size: 15px; color: #403434; margin: 0 0 20px; line-height: 1.6;">
        Uganda Blood Transfusion Services (UBTS) has scheduled a new voluntary blood donation drive in your community.
    </p>

    <div style="background-color: #FBF8F6; border: 1px solid #E5DFDB; border-radius: 12px; padding: 20px; margin: 0 0 24px;">
        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #B3262E; margin-bottom: 6px;">
            Blood Donation Drive
        </div>
        <div style="font-size: 18px; font-weight: 700; color: #1F1A1A; margin-bottom: 6px;">
            {{ $driveTitle }}
        </div>
        <div style="font-size: 14px; font-weight: 600; color: #403434; margin-bottom: 6px;">
            Dates: {{ $dateRangeEat }}
        </div>
        <div style="font-size: 13px; color: #524343;">
            Venue: {{ $venue }}@if($address) &bull; {{ $address }}@endif
        </div>
    </div>

    @if ($description)
        <p style="font-size: 14px; color: #524343; margin: 0 0 20px; line-height: 1.5;">
            {!! nl2br(e($description)) !!}
        </p>
    @endif

    <p style="font-size: 13px; color: #736262; margin: 24px 0 0; border-top: 1px solid #F3EFEA; pt-4;">
        You received this announcement because you opted in to receive updates about new blood collection drives. You can <a href="{{ $unsubscribeUrl }}" style="color: #736262; text-decoration: underline;">unsubscribe from new drive announcements</a> at any time.
    </p>
</x-mail-layout>
