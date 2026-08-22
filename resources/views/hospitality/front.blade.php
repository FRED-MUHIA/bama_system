@extends('layouts.marketing')

@section('body')
<style>
    .front-shell{min-height:100vh;background:#fffdfa;color:#101010}
    .front-hero{background:#000;color:#fff;padding:34px 18px 28px}
    .front-wrap{max-width:1180px;margin:0 auto}
    .front-top{display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}
    .brand-mark{display:flex;align-items:center;gap:12px;font-weight:900;font-size:1.25rem}
    .brand-dot{width:18px;height:18px;border-radius:50%;background:#00A651;box-shadow:0 0 0 6px rgba(0,166,81,.18)}
    .hero-grid{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(280px,.9fr);gap:26px;align-items:end;margin-top:30px}
    .front-hero h1{font-size:clamp(2.2rem,6vw,5.4rem);line-height:.95;margin:0 0 14px;font-weight:900}
    .front-hero p{max-width:620px;color:#d6d6d6;font-size:1.05rem;margin:0}
    .hero-panel{background:#111;border:1px solid #2b2b2b;border-radius:14px;padding:18px}
    .hero-panel strong{display:block;color:#00A651;font-size:2rem}
    .front-content{padding:24px 18px 50px}
    .front-grid{display:grid;grid-template-columns:minmax(0,1fr) 380px;gap:20px;align-items:start}
    .menu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px}
    .menu-card{background:#fff;border:1px solid #dedbd5;border-radius:12px;padding:14px;display:grid;gap:10px;min-height:172px}
    .menu-card h2{font-size:1.05rem;margin:0}
    .menu-card p{margin:0;color:#6f6b66;font-size:.92rem}
    .menu-meta{display:flex;justify-content:space-between;gap:10px;align-items:center;margin-top:auto}
    .price{color:#00A651;font-weight:900;font-size:1.15rem}
    .reserve-card{background:#fff;border:1px solid #dedbd5;border-radius:12px;padding:16px;position:sticky;top:14px}
    .reserve-card h2{font-size:1.25rem;margin:0 0 12px}
    .field-grid{display:grid;gap:10px}
    .front-input,.front-select{width:100%;border:1px solid #d8d4cc;border-radius:9px;padding:12px 13px;background:#fff;color:#101010}
    .qty{width:76px;border:1px solid #d8d4cc;border-radius:9px;padding:9px 10px}
    .front-button{border:0;border-radius:9px;background:#00A651;color:#fff;font-weight:900;padding:13px 16px;width:100%;cursor:pointer}
    .front-alert{background:#eaf8f0;border:1px solid #bce7ce;color:#105a31;border-radius:10px;padding:12px;margin-bottom:14px}
    .front-empty{background:#fff3cd;border:1px solid #ffe69c;color:#664d03;border-radius:10px;padding:14px}
    .total-line{display:flex;justify-content:space-between;gap:12px;font-weight:900;border-top:1px solid #ece8df;margin-top:12px;padding-top:12px}
    @media(max-width:900px){.hero-grid,.front-grid{grid-template-columns:1fr}.reserve-card{position:static}}
</style>

<div class="front-shell">
    <section class="front-hero">
        <div class="front-wrap">
            <div class="front-top">
                <div class="brand-mark"><span class="brand-dot"></span><span>Hospitality Restaurant</span></div>
                <a href="{{ route('login') }}" style="color:#fff;text-decoration:none;font-weight:800">Staff Login</a>
            </div>
            <div class="hero-grid">
                <div>
                    <h1>Menu & Table Reservations</h1>
                    <p>Reserve a table, choose food ahead of arrival, and let the restaurant team prepare the order through the same POS-backed Hospitality workflow.</p>
                </div>
                <div class="hero-panel">
                    <div class="text-muted">Today’s Menu</div>
                    <strong>{{ $menuItems->count() }}</strong>
                    <div>{{ $restaurantTables->where('status', 'Available')->count() }} tables currently available</div>
                </div>
            </div>
        </div>
    </section>

    <main class="front-content">
        <div class="front-wrap">
            @if(session('status'))
                <div class="front-alert">{{ session('status') }}</div>
            @endif

            <form method="post" action="{{ route('public.hospitality.reserve') }}" class="front-grid" id="front-menu-form">
                @csrf
                <div>
                    <div class="menu-grid">
                        @forelse($menuItems as $index => $item)
                            <article class="menu-card">
                                <div>
                                    <h2>{{ $item->name }}</h2>
                                    <p>{{ $item->category?->name ?? 'Restaurant Menu' }}</p>
                                    @if($item->description)<p>{{ $item->description }}</p>@endif
                                </div>
                                <div class="menu-meta">
                                    <span class="price">{{ number_format($item->price, 2) }}</span>
                                    <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->id }}">
                                    <input class="qty front-qty" name="items[{{ $index }}][quantity]" type="number" min="0" step="1" value="0" data-price="{{ $item->price }}" aria-label="Quantity for {{ $item->name }}">
                                </div>
                            </article>
                        @empty
                            <div class="front-empty">The restaurant menu is not published yet.</div>
                        @endforelse
                    </div>
                </div>

                <aside class="reserve-card">
                    <h2>Reserve</h2>
                    <div class="field-grid">
                        <input class="front-input" name="full_name" placeholder="Full name" required>
                        <input class="front-input" name="phone" placeholder="Phone">
                        <input class="front-input" name="email" type="email" placeholder="Email">
                        <select class="front-select" name="order_type" required>
                            <option>Table Reservation</option>
                            <option>Dine In</option>
                            <option>Room Service</option>
                            <option>Takeaway</option>
                        </select>
                        <select class="front-select" name="restaurant_table_id">
                            <option value="">Choose table</option>
                            @foreach($restaurantTables as $table)
                                <option value="{{ $table->id }}">{{ $table->table_number }} · {{ $table->section ?: 'Main floor' }} · {{ $table->capacity }} seats</option>
                            @endforeach
                        </select>
                        <input class="front-input" name="reserved_for" type="datetime-local" required>
                        <input class="front-input" name="party_size" type="number" min="1" value="2" placeholder="Guests" required>
                        <select class="front-select" name="payment_method_id">
                            <option value="">Pay at restaurant</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                        <select class="front-select" name="shipping_method">
                            <option value="">No delivery</option>
                            @foreach($shippingMethods as $method)
                                <option>{{ $method }}</option>
                            @endforeach
                        </select>
                        <input class="front-input" name="notes" placeholder="Special request">
                    </div>
                    <div class="total-line"><span>Total</span><span id="front-menu-total">0.00</span></div>
                    <button class="front-button" @disabled($menuItems->isEmpty())>Send Reservation</button>
                </aside>
            </form>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const total = document.getElementById('front-menu-total');
    const inputs = document.querySelectorAll('.front-qty');
    const render = () => {
        const amount = Array.from(inputs).reduce((sum, input) => sum + Number(input.value || 0) * Number(input.dataset.price || 0), 0);
        total.textContent = amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };
    inputs.forEach((input) => input.addEventListener('input', render));
    render();
});
</script>
@endsection
