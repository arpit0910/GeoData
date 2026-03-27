<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $countries = Country::orderBy('name')->get();
        $subscription = Subscription::where('user_id', $user->id)
            ->where('status', 'paid')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        return view('profile.index', compact('user', 'countries', 'subscription'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'company_website' => 'nullable|url|max:255',
            'gst_number' => ['nullable', 'string', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'phone' => ['required', 'string', 'regex:/^(?:\+?91[\-\s]?)?[6-9]\d{9}$/'],
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'pincode' => ['required', 'string', 'regex:/^[1-9][0-9]{5}$/'],
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
        ], [
            'gst_number.regex' => 'Please enter a valid 15-character Indian GSTIN.',
            'phone.regex' => 'Please enter a valid 10-digit Indian mobile number.',
            'pincode.regex' => 'Please enter a valid 6-digit Indian PIN code.'
        ]);

        $user = Auth::user();
        $user->update($request->only([
            'name', 'company_name', 'company_website', 'gst_number',
            'phone', 'address_line_1', 'address_line_2', 'country_id', 'pincode', 'state_id', 'city_id'
        ]));

        return redirect()->route('profile.index')->with('success', 'Profile updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required', 
                'string', 
                'min:8', 
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/'
            ],
        ], [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number and one special character.'
        ]);

        $user = Auth::user();
        $user->forceFill([
            'password' => Hash::make($request->password)
        ])->save();

        return back()->with('success', 'Password updated successfully!');
    }

    public function apiKeys()
    {
        $user = Auth::user();
        return view('api-keys.index', compact('user'));
    }
}
