<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoreLocation;
use Illuminate\Http\Request;

class StoreLocationController extends Controller
{
    public function index(Request $request)
    {
        $query = StoreLocation::with([
            'availableSatellites' => function ($query) {
                $query
                    ->select('store_location_id', 'id', 'name', 'address', 'city', 'state', 'zip')
                    ->orderBy('name');
            },
        ])
            ->where('is_enabled', '=', 1)
            ->orderBy('state')
            ->orderBy('city');

        $mealPlanStoresOnly = filter_var(
            $request->input('meal-plan-stores-only'),
            FILTER_VALIDATE_BOOLEAN,
        );

        if ($mealPlanStoresOnly === true) {
            $query->where('is_meal_plan_ordering_enabled', '=', 1);
        }

        return $query->get();
    }

    public function show($code)
    {
        return StoreLocation::where('code', '=', $code)->firstOrFail();
    }
}
