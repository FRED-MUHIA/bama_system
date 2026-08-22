<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\IamService;
use App\Support\ActiveBusiness;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);

        $business = Business::create([
            'name' => $data['name'],
            'slug' => ActiveBusiness::slug($data['name']),
        ]);

        ActiveBusiness::switchTo($business);
        app(IamService::class)->bootstrapBusinessDefaults($request->user());

        return back()->with('status', 'Business added and selected.');
    }

    public function switch(Request $request)
    {
        $data = $request->validate(['business_id' => ['required', 'exists:businesses,id']]);
        $accessibleBusinessIds = ActiveBusiness::accessibleBusinessIds();

        abort_if($accessibleBusinessIds !== null && ! in_array((int) $data['business_id'], $accessibleBusinessIds, true), 403);

        $business = Business::where('is_active', true)->findOrFail($data['business_id']);
        ActiveBusiness::switchTo($business);

        return redirect()->route('dashboard')->with('status', 'Business switched to ' . $business->name . '.');
    }
}
