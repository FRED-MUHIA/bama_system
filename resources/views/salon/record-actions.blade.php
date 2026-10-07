@php
    $recordDefinition = \Modules\Salon\Services\SalonRecords::definition($recordType);
    $recordLocked = ($recordType === 'appointments' && ($record->status === 'Completed' || $record->invoice_id || $record->pos_order_id || $record->payment_status === 'Paid'))
        || ($recordType === 'commissions' && ($record->status === 'Paid' || $record->payment_id));
@endphp
@if(auth()->user()->hasPermission('salon.'.$recordDefinition['permission'].'.manage') && !$recordLocked)
    <div class="salon-actions">
        <a class="btn btn-sm btn-outline-primary" href="{{ route('salon.records.edit', [$recordType, $record->id]) }}">{{ $recordType === 'appointments' ? 'Edit / reschedule' : 'Edit' }}</a>
        @if(!in_array($recordType, ['loyalty', 'gift-cards']))
            <form method="post" action="{{ route('salon.records.destroy', [$recordType, $record->id]) }}" onsubmit="return confirm(this.dataset.confirm)" data-confirm="{{ $recordType === 'consumptions' ? 'Delete this product usage and restore its quantity to stock?' : 'Delete this '.strtolower($recordDefinition['label']).'? This cannot be undone. Records with linked history will be kept.' }}">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
        @endif
    </div>
@endif
