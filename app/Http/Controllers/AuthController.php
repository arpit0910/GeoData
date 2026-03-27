<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended('dashboard');
        }

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    /**
     * Show the registration form.
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle user registration.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
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

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 1,
            'available_credits' => 0,
        ]);

        Auth::login($user);

        return redirect()->route('profile.complete');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function completeProfile()
    {
        $countries = Country::orderBy('name')->get();
        return view('auth.complete-profile', compact('countries'));
    }

    public function saveProfile(Request $request)
    {
        $request->validate([
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
        $user->update([
            'company_name' => $request->company_name,
            'company_website' => $request->company_website,
            'gst_number' => $request->gst_number,
            'phone' => $request->phone,
            'address_line_1' => $request->address_line_1,
            'address_line_2' => $request->address_line_2,
            'country_id' => $request->country_id,
            'pincode' => $request->pincode,
            'state_id' => $request->state_id,
            'city_id' => $request->city_id,
        ]);

        return redirect()->route('dashboard')->with('success', 'Profile completed successfully!');
    }
}
