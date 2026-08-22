<div class="card"><div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle" id="items-table">
            <thead><tr><th style="width:220px">Title</th><th>Description</th><th style="width:110px">Qty</th><th style="width:150px">Unit</th><th style="width:140px">Discount</th><th style="width:120px">Tax %</th><th style="width:60px"></th></tr></thead>
            <tbody>
            @foreach($items as $i => $item)
                <tr>
                    <td><input class="form-control" name="items[{{ $i }}][title]" value="{{ $item['title'] ?? '' }}" placeholder="Service title"></td>
                    <td><textarea class="form-control" name="items[{{ $i }}][description]" rows="2" required>{{ $item['description'] ?? '' }}</textarea></td>
                    <td><input class="form-control" name="items[{{ $i }}][quantity]" type="number" step="0.01" value="{{ $item['quantity'] ?? 1 }}" required></td>
                    <td><input class="form-control" name="items[{{ $i }}][unit_price]" type="number" step="0.01" value="{{ $item['unit_price'] ?? 0 }}" required></td>
                    <td><input class="form-control" name="items[{{ $i }}][discount]" type="number" step="0.01" value="{{ $item['discount'] ?? 0 }}"></td>
                    <td><input class="form-control" name="items[{{ $i }}][tax_rate]" type="number" step="0.01" value="{{ $item['tax_rate'] ?? '' }}" placeholder="Optional"></td>
                    <td><button type="button" class="btn btn-outline-danger btn-sm remove-row"><i class="bi bi-x"></i></button></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <button type="button" class="btn btn-outline-warning btn-sm" id="add-row"><i class="bi bi-plus"></i> Add item</button>
</div></div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    let index = document.querySelectorAll('#items-table tbody tr').length;
    document.getElementById('add-row').addEventListener('click', () => {
        const tbody = document.querySelector('#items-table tbody');
        tbody.insertAdjacentHTML('beforeend', `<tr>
            <td><input class="form-control" name="items[${index}][title]" placeholder="Service title"></td>
            <td><textarea class="form-control" name="items[${index}][description]" rows="2" required></textarea></td>
            <td><input class="form-control" name="items[${index}][quantity]" type="number" step="0.01" value="1" required></td>
            <td><input class="form-control" name="items[${index}][unit_price]" type="number" step="0.01" value="0" required></td>
            <td><input class="form-control" name="items[${index}][discount]" type="number" step="0.01" value="0"></td>
            <td><input class="form-control" name="items[${index}][tax_rate]" type="number" step="0.01" placeholder="Optional"></td>
            <td><button type="button" class="btn btn-outline-danger btn-sm remove-row"><i class="bi bi-x"></i></button></td>
        </tr>`);
        index++;
    });
    document.addEventListener('click', (event) => {
        if (event.target.closest('.remove-row') && document.querySelectorAll('#items-table tbody tr').length > 1) {
            event.target.closest('tr').remove();
        }
    });
});
</script>
@endpush
