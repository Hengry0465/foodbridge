<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        tr:nth-child(even) { background: #fafafa; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">Generated at: {{ $generatedAt }}</p>

    @if (! empty($filters))
        <p class="meta">
            Filters:
            @foreach ($filters as $key => $value)
                {{ $key }}={{ is_bool($value) ? ($value ? 'true' : 'false') : $value }}@if (! $loop->last), @endif
            @endforeach
        </p>
    @endif

    <table>
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($columns as $column)
                        <td>
                            @php
                                $value = data_get($row, $column);
                                if (is_array($value)) {
                                    $value = json_encode($value);
                                } elseif (is_object($value) && enum_exists($value::class)) {
                                    $value = $value->value;
                                } elseif (is_object($value) && method_exists($value, '__toString')) {
                                    $value = (string) $value;
                                }
                            @endphp
                            {{ $value }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
