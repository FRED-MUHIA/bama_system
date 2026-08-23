@if(($paymentMethods ?? collect())->count())
    <div class="card mt-3">
        <div class="card-body">
            <h2 class="h5">Payment Methods</h2>
            <div class="row g-3">
                @foreach($paymentMethods as $method)
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex justify-content-between gap-2">
                                <strong>{{ $method->name }}</strong>
                                <span class="badge text-bg-light">{{ ucfirst($method->type) }}</span>
                            </div>
                            @if($method->details)
                                <div class="small text-muted mt-2" style="white-space:pre-wrap">{{ $method->details }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
