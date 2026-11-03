<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Amc;
use Illuminate\Http\JsonResponse;

class AmcController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Amc::orderBy('name')->get()]);
    }
}
