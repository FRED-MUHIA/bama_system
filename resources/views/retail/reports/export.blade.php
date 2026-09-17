<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} Export</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; margin: 24px; }
        h1 { font-size: 24px; margin-bottom: 6px; }
        .subtitle { color: #6b7280; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; font-size: 12px; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="subtitle">{{ $subtitle }}</div>

    <table>
        <thead>
            <tr>
                @foreach($columns as $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach(array_keys($columns) as $key)
                        <td>{{ $row[$key] ?? '-' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
