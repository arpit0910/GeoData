<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Amc;

class AmcController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => Amc::orderBy('name')->get()]);
    }
}
