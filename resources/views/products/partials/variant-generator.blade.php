@php
    $variantAttributes = collect($attributes ?? [])
        ->filter(fn ($attribute) => $attribute->values->isNotEmpty())
        ->values();
    $productVariants = $product->relationLoaded('retailVariants') ? $product->retailVariants : $product->retailVariants()->with('attributeValueLinks.value.attribute')->get();
@endphp

@if(($product->exists ?? false) && $variantAttributes->isNotEmpty())
    <div class="col-12 border-top pt-3 mt-2">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
            <div>
                <div class="fw-semibold">Variants</div>
                <div class="small text-muted">{{ $productVariants->count() }} generated for this product</div>
            </div>
            <span class="badge text-bg-light">{{ $product->productTypeLabel() }}</span>
        </div>

        <form method="post" action="{{ route('products.variants.generate', $product) }}" class="row g-2">
            @csrf
            @foreach($variantAttributes as $attribute)
                <div class="col-md-4">
                    <label class="form-label">{{ $attribute->name }}</label>
                    <select class="form-select" name="variant_values[{{ $attribute->id }}][]" multiple size="{{ min(max($attribute->values->count(), 2), 5) }}">
                        @foreach($attribute->values as $value)
                            <option value="{{ $value->id }}">{{ $value->value }}{{ $value->unit ? ' '.$value->unit : '' }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
            <div class="col-md-4">
                <label class="form-label">SKU pattern</label>
                <input class="form-control" name="sku_pattern" value="PRODUCT-SEQUENCE" placeholder="PRODUCT-COLOR-SIZE">
            </div>
            <div class="col-md-2">
                <label class="form-label">Limit</label>
                <input class="form-control" type="number" min="1" max="1000" name="max_variants" value="250">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-success btn-sm w-100">Generate</button>
            </div>
        </form>

        @if($productVariants->isNotEmpty())
            <div class="d-flex flex-wrap gap-2 mt-3">
                @foreach($productVariants->take(10) as $variant)
                    <span class="badge text-bg-light border">
                        {{ $variant->displayName() ?: $variant->sku }}
                        @if($variant->sku) · {{ $variant->sku }} @endif
                    </span>
                @endforeach
                @if($productVariants->count() > 10)
                    <span class="badge text-bg-light border">+{{ $productVariants->count() - 10 }} more</span>
                @endif
            </div>
        @endif
    </div>
@endif
