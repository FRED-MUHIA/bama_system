<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Support\ActiveBusiness;
use Illuminate\Support\Facades\DB;
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
        $accessibleBusinessIds = ActiveBusiness::accessibleBusinessIds();

        abort_unless($accessibleBusinessIds !== null, 403);

        $data = $request->validate([
            'business_id' => ['required', 'integer', function ($attribute, $value, $fail) use ($accessibleBusinessIds) {
                if (! in_array((int) $value, $accessibleBusinessIds, true)) {
                    $fail('You do not have access to that business.');
                }
            }],
        ]);

        $business = Business::where('is_active', true)->findOrFail($data['business_id']);

        abort_unless(
            DB::table('business_user')->where('business_id', $business->id)
                ->where('user_id', $request->user()->id)
                ->whereIn('status', ['Active', 'Pending Invitation'])
                ->exists(),
            403
        );

        ActiveBusiness::switchTo($business);

        return redirect()->route('dashboard')->with('status', 'Business switched to '.$business->name.'.');
    }
}
