<?php

namespace App\Http\Controllers;

use App\Models\CurrencyConversion;
use Illuminate\Http\Request;

class CurrencyConversionController extends Controller
{
    public function lookup($currency)
    {
        $rate = CurrencyConversion::where('currency', strtoupper($currency))->first();

        if (!$rate) {
            return response()->json([
                'success' => false,
                'message' => 'Currency conversion rates not found for ' . strtoupper($currency)
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'currency' => $rate->currency,
                'usd_conversion_rate' => $rate->usd_conversion_rate,
                'inr_conversion_rate' => $rate->inr_conversion_rate,
                'last_updated' => $rate->updated_at->toDateTimeString()
            ]
        ]);
    }
}
