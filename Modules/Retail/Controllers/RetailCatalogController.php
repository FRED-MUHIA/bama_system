<?php

namespace Modules\Retail\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
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
            'products' => Product::with('retailProfile')->orderBy('name')->get(),
            'product' => new Product(['is_active' => true, 'stock_unit' => 'pcs']),
            'categories' => ProductCategory::orderBy('name')->get(),
            'stockUnits' => Product::STOCK_UNITS,
        ]);
    }

    public function storeProfile(Request $request, RetailCatalogService $catalog)
    {
        $data = $request->validate(RetailValidationRules::productProfile());
        $product = Product::findOrFail($data['product_id']);
        unset($data['product_id']);
        $catalog->upsertProfile($product, $data);

        return back()->with('status', 'Retail product profile saved.');
    }
}
