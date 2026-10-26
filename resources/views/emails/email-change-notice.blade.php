<x-mail-layout :subject="$subject" :heading="$heading" :action-text="$actionText" :action-url="$actionUrl">
    <p style="font-size: 15px; color: #1F1A1A; margin: 0 0 16px;">
        Hello {{ $donorName }},
    </p>

    <p style="font-size: 15px; color: #403434; margin: 0 0 20px; line-height: 1.6;">
        A request was recently submitted to change the email address for your DonorLink UG account to <strong>{{ $maskedEmail }}</strong>.
    </p>

    <div style="background-color: #FBF8F6; border: 1px solid #E5DFDB; border-radius: 12px; padding: 20px; margin: 0 0 24px;">
        <p style="font-size: 13px; color: #524343; margin: 0 0 10px; line-height: 1.5;">
            A confirmation link was sent to that address. Your current email address remains active until the change is verified.
        </p>
        <p style="font-size: 13px; color: #725C5C; margin: 0; line-height: 1.5;">
            <strong>If you did not request this change:</strong> Please sign in to your DonorLink account immediately to cancel the pending change and update your password.
        </p>
    </div>
</x-mail-layout>
