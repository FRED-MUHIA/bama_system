{{ $headline ?? $subject }}

{{ $body }}

@if(! empty($actionUrl))
{{ $actionText ?? 'Open secure link' }}: {{ $actionUrl }}
@endif

© {{ date('Y') }} {{ $appName ?? config('mail.brand.name', 'Bama') }}. All rights reserved.
