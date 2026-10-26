<x-mail-layout :message="$message ?? null" :subject="$subject" :heading="$heading" :action-text="$actionText" :action-url="$actionUrl">
    <p style="font-size: 15px; color: #1F1A1A; margin: 0 0 16px;">
        Hello,
    </p>

    <p style="font-size: 15px; color: #403434; margin: 0 0 20px; line-height: 1.6;">
        This diagnostic email confirms that outbound email delivery is fully operational and authenticated via <strong>Brevo SMTP</strong> for <strong>DonorLink UG</strong>.
    </p>

    <div style="background-color: #FBF8F6; border: 1px solid #E5DFDB; border-radius: 12px; padding: 20px; margin: 0 0 24px;">
        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #B3262E; margin-bottom: 8px;">
            Configuration &amp; Telemetry
        </div>
        <div style="font-size: 14px; color: #403434; margin-bottom: 6px;">
            <strong>Mailer Transport:</strong> {{ $mailerName }} ({{ $host }}:{{ $port }})
        </div>
        <div style="font-size: 14px; color: #403434; margin-bottom: 6px;">
            <strong>Sender Identity:</strong> {{ $fromAddress }}
        </div>
        <div style="font-size: 14px; color: #403434; margin-bottom: 6px;">
            <strong>Recipient:</strong> {{ $recipient }}
        </div>
        <div style="font-size: 14px; color: #403434;">
            <strong>Dispatched:</strong> {{ $timestamp }} EAT
        </div>
    </div>

    <div style="background-color: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 12px; padding: 16px; margin: 0 0 24px;">
        <p style="font-size: 14px; color: #065F46; margin: 0; line-height: 1.5;">
            ✓ <strong>Branding Active:</strong> This email was rendered with the Uganda Blood Transfusion Services (UBTS) droplet logo and official styling.
        </p>
    </div>

    <p style="font-size: 13px; color: #736262; margin: 0;">
        All appointment confirmations, 24h reminders, reschedule notices, and staff invitation emails share this branded template.
    </p>
</x-mail-layout>
