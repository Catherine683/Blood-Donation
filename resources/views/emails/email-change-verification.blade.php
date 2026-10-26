<x-mail-layout :subject="$subject" :heading="$heading" :action-text="$actionText" :action-url="$actionUrl">
    <p style="font-size: 15px; color: #1F1A1A; margin: 0 0 16px;">
        Hello {{ $donorName }},
    </p>

    <p style="font-size: 15px; color: #403434; margin: 0 0 20px; line-height: 1.6;">
        A request was made to update your DonorLink UG account email address to <strong>{{ $newEmail }}</strong>.
    </p>

    <div style="background-color: #FBF8F6; border: 1px solid #E5DFDB; border-radius: 12px; padding: 20px; margin: 0 0 24px;">
        <p style="font-size: 13px; color: #524343; margin: 0 0 10px; line-height: 1.5;">
            To complete this change and swap your email address, please click the button below within <strong>24 hours</strong>.
        </p>
        <p style="font-size: 13px; color: #725C5C; margin: 0; line-height: 1.5;">
            Until you confirm this address, your current email address remains active for logging in and receiving appointment reminders.
        </p>
    </div>
</x-mail-layout>
