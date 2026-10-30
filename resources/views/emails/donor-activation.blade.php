<x-mail-layout :subject="$subject" :heading="'Activate your donor account'" :action-text="$actionText" :action-url="$actionUrl">
    <p style="font-size: 15px; color: #1F1A1A; margin: 0 0 16px;">
        Hello {{ $userName }},
    </p>
    <p style="font-size: 15px; color: #403434; margin: 0 0 20px; line-height: 1.6;">
        UBTS staff created a DonorLink UG donor account for you. Use the secure link below within <strong>72 hours</strong> to verify your email, activate your account, and choose your password.
    </p>
    <p style="font-size: 13px; color: #725C5C; margin: 0; line-height: 1.5;">
        If you did not expect this account, please contact UBTS staff. Your password is never sent by email.
    </p>
</x-mail-layout>
