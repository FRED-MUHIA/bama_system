<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                @foreach($columns as $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $attribute => $label)
                        <td>{{ data_get($row, $attribute) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) }}" class="text-muted">No records yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
