<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function store(Request $request)
    {
        return back()->withErrors([
            'business' => 'Adding more business profiles is coming soon. Each profile manages one business for now.',
        ]);
    }

    public function switch(Request $request)
    {
        $data = $request->validate(['business_id' => ['required', 'exists:businesses,id']]);
        $accessibleBusinessIds = ActiveBusiness::accessibleBusinessIds();

        abort_if($accessibleBusinessIds !== null && ! in_array((int) $data['business_id'], $accessibleBusinessIds, true), 403);

        $business = Business::where('is_active', true)->findOrFail($data['business_id']);
        ActiveBusiness::switchTo($business);

        return redirect()->route('dashboard')->with('status', 'Business switched to '.$business->name.'.');
    }
}
