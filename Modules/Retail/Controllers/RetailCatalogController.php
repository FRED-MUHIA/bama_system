<?php

namespace Modules\Retail\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\SecuritySetting;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Retail\Repositories\RetailRepository;
use Modules\Retail\Services\RetailCatalogService;
use Modules\Retail\Services\RetailValidationRules;

class RetailCatalogController extends Controller
{
    public function index(Request $request, RetailRepository $retail)
    {
        return view('retail.module', [
            'title' => 'Product Catalog',
            'section' => 'products',
            'records' => $retail->productSearch($request->query('q'))->paginate(20),
            'products' => Product::with(
                'retailProfile',
                'brand',
                'retailVariants.product',
                'retailVariants.attributeValueLinks.value.attribute'
            )->orderBy('name')->get(),
            'product' => new Product(['is_active' => true, 'stock_unit' => 'pcs']),
            'categories' => ProductCategory::with('parent')->orderBy('sort_order')->orderBy('name')->get(),
            'brands' => ProductBrand::orderBy('name')->get(),
            'attributes' => ProductAttribute::with('values')->orderBy('sort_order')->orderBy('name')->get(),
            'stockUnits' => Product::STOCK_UNITS,
            'productTypes' => Product::PRODUCT_TYPES,
        ]);
    }

    public function storeProfile(Request $request, RetailCatalogService $catalog)
    {
        $pinData = $request->validate(['authorization_pin' => ['required', 'digits_between:4,12']]);
        $setting = SecuritySetting::where('business_id', ActiveBusiness::id())->first();
        if (! $setting?->verifiesPosEditPin($pinData['authorization_pin'])) {
            throw ValidationException::withMessages(['authorization_pin' => 'The product and stock authorization PIN is incorrect or has not been configured. Contact an administrator.']);
        }
        $data = $request->validate(RetailValidationRules::productProfile());
        $product = Product::findOrFail($data['product_id']);
        abort_unless((int) $product->business_id === (int) ActiveBusiness::id(), 404);
        unset($data['product_id']);
        $catalog->upsertProfile($product, $data);

        return back()->with('status', 'Retail product profile saved.');
    }
}
