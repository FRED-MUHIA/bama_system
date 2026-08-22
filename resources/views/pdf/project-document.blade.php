<!doctype html>
<html>
<head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:12px;color:#111827} h1{font-size:20px} .meta{color:#6b7280;margin-bottom:20px} .content{white-space:pre-wrap;line-height:1.5}</style></head>
<body>
<h1>{{ $document->title }}</h1>
<div class="meta">{{ $document->document_type }} · {{ $document->project?->project_name }} · {{ $document->project?->client?->name }}</div>
<div class="content">{{ $document->content }}</div>
</body>
</html>
