<?php

namespace Modules\Retail\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Retail\Models\RetailPromotion;
use Modules\Retail\Repositories\RetailRepository;
use Modules\Retail\Services\RetailValidationRules;

class RetailPromotionController extends Controller
{
    public function index(RetailRepository $retail)
    {
        return view('retail.module', [
            'title' => 'Promotions',
            'section' => 'promotions',
            'records' => $retail->promotions()->paginate(20),
            'promotionTypes' => RetailPromotion::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        RetailPromotion::create($request->validate(RetailValidationRules::promotion()));

        return back()->with('status', 'Promotion saved.');
    }
}
