<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\Faq;

class HomeController extends Controller
{
    public function index()
    {
        return view('website.home');
    }

    public function about()
    {
        return view('website.about');
    }

    public function contact()
    {
        return view('website.contact');
    }

    public function sendContact(Request $request)
    {
        return back()->with('success', 'Thank you for your message! Our team will get back to you shortly.');
    }

    public function pricing()
    {
        $plans = Plan::where('status', 1)->orderBy('amount', 'asc')->get();
        return view('website.pricing', compact('plans'));
    }

    public function docs()
    {
        return view('website.docs');
    }

    public function status()
    {
        return view('website.status');
    }

    public function privacy()
    {
        return view('website.privacy');
    }

    public function terms()
    {
        return view('website.terms');
    }

    public function faq()
    {
        $faqs = Faq::where('visibility', 'website')->where('status', 1)->orderBy('order')->get();
        return view('website.faq', compact('faqs'));
    }
}
