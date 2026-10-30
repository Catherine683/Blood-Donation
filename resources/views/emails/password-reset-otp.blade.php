<x-mail-layout subject="Your DonorLink UG password reset code" heading="Verify your password reset">
    <p style="font-size: 15px; color: #1F1A1A; margin: 0 0 16px;">
        Hello {{ $name }},
    </p>
    <p style="font-size: 15px; color: #403434; margin: 0 0 20px; line-height: 1.6;">
        Enter this verification code to continue resetting your DonorLink UG password:
    </p>
    <p style="font-size: 30px; color: #B3262E; font-weight: 700; letter-spacing: 8px; text-align: center; margin: 24px 0;">
        {{ $code }}
    </p>
    <p style="font-size: 13px; color: #725C5C; margin: 0; line-height: 1.6;">
        This code expires in 10 minutes and can only be used once. If you did not request a password reset, you can ignore this email.
    </p>
</x-mail-layout>
