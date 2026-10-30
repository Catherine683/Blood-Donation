<x-mail-layout :subject="'Your DonorLink UG verification code'" :heading="'Verify your email address'">
    <p style="font-size: 15px; color: #1F1A1A; margin: 0 0 16px;">
        Hello {{ $name }},
    </p>
    <p style="font-size: 15px; color: #403434; margin: 0 0 20px; line-height: 1.6;">
        Enter this six-digit code to verify your email and activate your donor account:
    </p>
    <p style="font-size: 30px; color: #B3262E; font-weight: 700; letter-spacing: 8px; margin: 0 0 20px;">
        {{ $code }}
    </p>
    <p style="font-size: 13px; color: #725C5C; margin: 0; line-height: 1.5;">
        This code expires in 10 minutes. If you did not create a DonorLink UG account, you can ignore this email.
    </p>
</x-mail-layout>
