@php
    $appName = $appName ?? config('mail.brand.name', 'BAMA');
    $logoPath = $logoPath ?? config('mail.brand.logo_path', 'images/bama-logo.png');
    $logoUrl = $logoUrl ?? ($logoPath ? asset($logoPath) : null);
    $headline = $headline ?? $subject;
    $paragraphs = collect(preg_split('/\R{2,}/', trim($body ?? '')))
        ->map(fn ($paragraph) => trim($paragraph))
        ->filter();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f3f2;color:#4b5563;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">{{ $preheader ?? $headline }}</div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#f1f3f2;margin:0;padding:0;">
        <tr>
            <td align="center" style="padding:16px 12px 0;">
                <table role="presentation" width="570" cellspacing="0" cellpadding="0" style="width:570px;max-width:100%;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden;">
                    <tr>
                        <td style="background:#00A651;background-image:linear-gradient(135deg,#00BF63 0%,#00A651 52%,#007A3D 100%);padding:24px 32px;">
                            <a href="{{ config('app.url') }}" style="color:#ffffff;text-decoration:none;font-size:22px;font-weight:800;letter-spacing:0;display:inline-block;">
                                @if($logoUrl)
                                    <span style="display:inline-block;">
                                        <img src="{{ $logoUrl }}" alt="{{ $appName }}" width="210" style="display:block;width:210px;max-width:210px;height:auto;border:0;outline:none;text-decoration:none;">
                                    </span>
                                @else
                                    {{ $appName }}
                                @endif
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 32px 32px;">
                            <h1 style="margin:0 0 20px;color:#00A651;font-size:25px;line-height:1.08;font-weight:800;">{{ $headline }}</h1>

                            @foreach($paragraphs as $paragraph)
                                <p style="margin:0 0 16px;color:#4b5563;font-size:15px;line-height:1.5;">{!! nl2br(e($paragraph)) !!}</p>
                            @endforeach

                            @if(! empty($actionUrl))
                                <table role="presentation" cellspacing="0" cellpadding="0" style="margin:26px 0;">
                                    <tr>
                                        <td style="border-radius:8px;background:#00A651;">
                                            <a href="{{ $actionUrl }}" target="_blank" rel="noopener" style="display:inline-block;padding:13px 24px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;border-radius:8px;">{{ $actionText ?? 'Open secure link' }}</a>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @if(! empty($footerNote))
                                <p style="margin:26px 0 0;color:#6b7280;font-size:13px;line-height:1.5;">{{ $footerNote }}</p>
                            @endif
                        </td>
                    </tr>
                </table>
                <table role="presentation" width="570" cellspacing="0" cellpadding="0" style="width:570px;max-width:100%;margin:0 auto;">
                    <tr>
                        <td style="padding:28px 32px 34px;color:#9ca3af;font-size:12px;line-height:1.5;text-align:left;">
                            <p style="margin:0 0 18px;color:#9ca3af;">{{ $appName }} is a business management workspace for teams, clients, billing, documents and operations.</p>
                            <p style="margin:0;color:#9ca3af;">© {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
