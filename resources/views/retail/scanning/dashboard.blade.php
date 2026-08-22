@extends('layouts.app')
@section('title', 'Smart Product Scanning')

@section('content')
@include('retail.partials.nav')
<style>
    .scan-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .scan-metric{background:#fff;border:1px solid #d9dee8;border-radius:8px;padding:14px}
    .scan-metric .label{color:#667085;font-size:.72rem;font-weight:800;text-transform:uppercase}
    .scan-metric .value{font-size:1.5rem;font-weight:900;color:#075985}
    .scan-board{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
    .camera-drop{border:1px dashed #98a2b3;border-radius:8px;padding:12px;background:#f8fafc}
    @media(max-width:1000px){.scan-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.scan-board{grid-template-columns:1fr}}
    @media(max-width:640px){.scan-grid{grid-template-columns:1fr}}
</style>

<div class="d-flex justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h1 class="h3 mb-1">Smart Product Scanning & Verification</h1>
        <div class="text-muted">Scan QR, 1D, 2D, Data Matrix, camera, and POS scanner inputs into the Retail POS flow.</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-dark" href="{{ route('retail.scanning.devices') }}"><i class="bi bi-upc-scan me-1"></i>Devices</a>
        <a class="btn btn-outline-dark" href="{{ route('retail.scanning.batches') }}"><i class="bi bi-boxes me-1"></i>Batches</a>
    </div>
</div>

<div class="card p-3 mb-3">
    <form method="POST" action="{{ route('retail.scanning.scan') }}" class="row g-2" enctype="multipart/form-data" id="smartScanForm">
        @csrf
        <input type="hidden" name="decoded_text" id="decodedText">
        <input type="hidden" name="confidence" id="cameraConfidence">
        <div class="col-md-4"><input class="form-control" name="raw_value" id="scanValue" placeholder="SKU, barcode, QR payload, GTIN, UPC, EAN"></div>
        <div class="col-md-2"><select class="form-select" name="input_type"><option>Scanner Device Input</option><option>Mobile Device Camera</option><option>Camera Feed</option><option>Barcode Image</option><option>QR Code Image</option></select></div>
        <div class="col-md-2"><input class="form-control" name="device_code" placeholder="Device code"></div>
        <div class="col-md-1"><input class="form-control" name="quantity" type="number" step="0.001" value="1"></div>
        <div class="col-md-2 form-check d-flex align-items-center ps-4"><input class="form-check-input me-2" type="checkbox" name="update_inventory" value="1" id="updateInventory"><label class="form-check-label" for="updateInventory">Update inventory</label></div>
        <div class="col-md-1"><button class="btn btn-success w-100"><i class="bi bi-search"></i></button></div>
        <div class="col-12">
            <div class="camera-drop d-flex flex-wrap align-items-center gap-2">
                <label class="btn btn-outline-dark mb-0" for="barcodeImage"><i class="bi bi-camera me-1"></i>Camera Scan</label>
                <input class="d-none" id="barcodeImage" name="barcode_image" type="file" accept="image/*,.svg,.txt,.csv,.json,.xml" capture="environment">
                <input class="form-control flex-grow-1" name="manual_code" placeholder="Barcode number or text from image">
                <select class="form-select" name="camera_type" style="max-width:220px"><option>POS Camera</option><option>Mobile Camera</option><option>Self Checkout Camera</option></select>
                <span class="small text-muted" id="cameraScanStatus">Upload/capture a product code image, or enter the printed barcode number.</span>
            </div>
        </div>
    </form>
    @if(session('scanResult'))
        @php($result = session('scanResult'))
        <div class="alert {{ $result['success'] ? 'alert-success' : 'alert-danger' }} mt-3 mb-0">
            <strong>{{ $result['message'] }}</strong>
            @if($result['product'])
                <span class="ms-2">{{ $result['product']['name'] }} · {{ number_format($result['pricing']['final_price'], 2) }}</span>
            @endif
        </div>
    @endif
</div>

<div class="scan-grid mb-3">
    @foreach($metrics as $label => $value)
        <div class="scan-metric"><div class="label">{{ $label }}</div><div class="value">{{ number_format($value) }}</div></div>
    @endforeach
</div>

<div class="scan-board">
    <div class="card p-3">
        <h2 class="h5 mb-2">Recent Scan Events</h2>
        @forelse($recentScans as $scan)
            <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                <div>
                    <strong>{{ $scan->product?->name ?: $scan->identifier_value }}</strong>
                    <div class="small text-muted">{{ $scan->symbology }} · {{ $scan->device?->name ?: 'Unregistered device' }}</div>
                </div>
                <span class="status-pill">{{ $scan->result }}</span>
            </div>
        @empty
            <div class="text-muted">No scan events yet.</div>
        @endforelse
    </div>

    <div class="card p-3">
        <h2 class="h5 mb-2">Expiry Alerts</h2>
        @forelse($expiryAlerts as $batch)
            <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                <div>
                    <strong>{{ $batch->product?->name }}</strong>
                    <div class="small text-muted">{{ $batch->batch_number }} · {{ $batch->expiry_date?->format('d M Y') }}</div>
                </div>
                <span class="status-pill">{{ $batch->status }}</span>
            </div>
        @empty
            <div class="text-muted">No batches expiring in the next 30 days.</div>
        @endforelse
    </div>

    <div class="card p-3">
        <h2 class="h5 mb-2">Top Scanned Products</h2>
        @forelse($topProducts as $product)
            <div class="d-flex justify-content-between border-bottom py-2"><strong>{{ $product->name }}</strong><span>{{ $product->scans }}</span></div>
        @empty
            <div class="text-muted">Product scan frequency appears after scanning begins.</div>
        @endforelse
    </div>

    <div class="card p-3">
        <h2 class="h5 mb-2">Scan Devices</h2>
        @forelse($devices as $device)
            <div class="d-flex justify-content-between border-bottom py-2"><strong>{{ $device->name }}</strong><span class="status-pill">{{ $device->status }}</span></div>
        @empty
            <div class="text-muted">Register POS scanners, cameras, and self-checkout stations.</div>
        @endforelse
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('barcodeImage');
    const scanValue = document.getElementById('scanValue');
    const decodedText = document.getElementById('decodedText');
    const confidence = document.getElementById('cameraConfidence');
    const status = document.getElementById('cameraScanStatus');

    if (!fileInput) return;

    fileInput.addEventListener('change', async () => {
        const file = fileInput.files && fileInput.files[0];
        if (!file) return;

        status.textContent = `Reading ${file.name}...`;

        if (!('BarcodeDetector' in window) || !file.type.startsWith('image/')) {
            status.textContent = 'Image attached. Server will decode QR/text payloads, or use the entered barcode number.';
            return;
        }

        try {
            const formats = await BarcodeDetector.getSupportedFormats();
            const detector = new BarcodeDetector({ formats });
            const image = await createImageBitmap(file);
            const codes = await detector.detect(image);

            if (codes.length) {
                const value = codes[0].rawValue || '';
                scanValue.value = scanValue.value || value;
                decodedText.value = value;
                confidence.value = 98;
                status.textContent = `Decoded ${codes.length} code${codes.length === 1 ? '' : 's'} from image.`;
            } else {
                status.textContent = 'No barcode found in browser. Server will still check the uploaded image.';
            }
        } catch (error) {
            status.textContent = 'Browser decode unavailable. Server will check QR/text payloads.';
        }
    });
});
</script>
@endsection
