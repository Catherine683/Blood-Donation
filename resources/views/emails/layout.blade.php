<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'DonorLink UG' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #FBF8F6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1F1A1A; line-height: 1.6;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #FBF8F6; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 560px; background-color: #FFFFFF; border-radius: 16px; border: 1px solid #E5DFDB; overflow: hidden; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
                    <!-- Header with Logo -->
                    <tr>
                        <td align="center" style="padding: 28px 32px 22px; border-bottom: 1px solid #F3EFEA; background-color: #FFFFFF;">
                            @php
                                $activeMsg = (isset($message) && method_exists($message, 'embed'))
                                    ? $message
                                    : (Illuminate\Support\Facades\View::shared('__active_mail_message') ?? null);

                                $logoPath = public_path('images/logo.png');
                                $logoSrc = app()->environment('testing')
                                    ? asset('images/logo.png')
                                    : (($activeMsg && method_exists($activeMsg, 'embed') && file_exists($logoPath))
                                        ? $activeMsg->embed($logoPath)
                                        : asset('images/logo.png'));
                            @endphp
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="vertical-align: middle; padding-right: 12px;">
                                        <img src="{{ $logoSrc }}" alt="DonorLink UG droplet logo" width="42" height="42" style="display: block; width: 42px; height: 42px; border: 0;" />
                                    </td>
                                    <td style="vertical-align: middle; text-align: left;">
                                        <table role="presentation" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="vertical-align: middle;">
                                                    <span style="font-size: 21px; font-weight: 800; color: #1F1A1A; letter-spacing: -0.4px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">DonorLink</span>
                                                </td>
                                                <td style="vertical-align: middle; padding-left: 6px;">
                                                    <span style="background-color: #FEE2E2; color: #991B1B; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 6px; letter-spacing: 0.6px; display: inline-block; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">UG</span>
                                                </td>
                                            </tr>
                                        </table>
                                        <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: #736262; margin-top: 2px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                            Uganda Blood Transfusion Services
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 32px 32px 28px;">
                            <h1 style="font-size: 20px; font-weight: 700; color: #1F1A1A; margin: 0 0 16px; line-height: 1.3;">
                                {{ $heading ?? $subject }}
                            </h1>

                            <div style="font-size: 15px; color: #403434; margin: 0 0 24px;">
                                {{ $slot ?? '' }}
                            </div>

                            @if (!empty($actionText) && !empty($actionUrl))
                                <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 28px 0;">
                                    <tr>
                                        <td align="center" style="border-radius: 10px; background-color: #B3262E;">
                                            <a href="{{ $actionUrl }}" target="_blank" style="font-size: 15px; font-weight: 600; color: #FFFFFF; text-decoration: none; padding: 14px 28px; border-radius: 10px; display: inline-block; background-color: #B3262E; border: 1px solid #B3262E;">
                                                {{ $actionText }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                                <p style="font-size: 12px; color: #736262; margin: 24px 0 0; word-break: break-all;">
                                    If you are having trouble clicking the button, copy and paste this link into your web browser:<br>
                                    <a href="{{ $actionUrl }}" style="color: #B3262E; text-decoration: underline;">{{ $actionUrl }}</a>
                                </p>
                            @endif
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 32px; background-color: #FBF8F6; border-top: 1px solid #F3EFEA; text-align: center;">
                            <p style="font-size: 12px; color: #736262; margin: 0 0 8px; line-height: 1.5;">
                                You received this email because someone registered with this address on DonorLink UG. If that wasn't you, you can ignore this message.
                            </p>
                            <p style="font-size: 11px; color: #8F7E7E; margin: 0;">
                                Uganda Blood Transfusion Services &bull; Nakasero Hill Road, Kampala
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
