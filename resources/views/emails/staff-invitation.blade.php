<x-mail-layout :subject="$subject" :heading="$heading" :action-text="$actionText" :action-url="$actionUrl">
    <p style="font-size: 15px; color: #1F1A1A; margin: 0 0 16px;">
        Hello {{ $userName }},
    </p>

    <p style="font-size: 15px; color: #403434; margin: 0 0 20px; line-height: 1.6;">
        An account has been provisioned for you on the Uganda Blood Transfusion Services (UBTS) DonorLink portal with the role of <strong>{{ $roleName }}</strong>.
    </p>

    <div style="background-color: #FBF8F6; border: 1px solid #E5DFDB; border-radius: 12px; padding: 20px; margin: 0 0 24px;">
        <p style="font-size: 13px; color: #524343; margin: 0 0 10px; line-height: 1.5;">
            To activate your account and configure your secure password, please click the link below within <strong>72 hours</strong>.
        </p>
        <p style="font-size: 13px; color: #725C5C; margin: 0; line-height: 1.5;">
            For your security, passwords are never sent by email. If you did not anticipate this invitation, please contact your UBTS system administrator.
        </p>
    </div>
</x-mail-layout>
