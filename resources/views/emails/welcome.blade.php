<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to SetuGeo</title>
    <style>
        body { font-family: 'Inter', Helvetica, Arial, sans-serif; background-color: #000000; color: #ffffff; margin: 0; padding: 0; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #000000; padding-bottom: 40px; }
        .content { max-width: 600px; margin: 0 auto; background-color: #0a0a0a; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; margin-top: 40px; overflow: hidden; }
        .header { padding: 40px 0; text-align: center; background: linear-gradient(to bottom, #111111, #0a0a0a); }
        .body-content { padding: 40px; }
        .footer { padding: 20px; text-align: center; color: rgba(255,255,255,0.5); font-size: 12px; }
        h1 { color: #ffffff; font-size: 24px; margin-bottom: 20px; text-align: center; }
        p { color: rgba(255,255,255,0.8); line-height: 1.6; font-size: 16px; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 14px 30px; background-color: #f59e0b; color: #ffffff !important; text-decoration: none; border-radius: 12px; font-weight: bold; font-size: 16px; text-align: center; transition: all 0.3s ease; }
        .btn:hover { background-color: #d97706; transform: translateY(-2px); }
        .feature-box { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .feature-title { color: #f59e0b; font-weight: bold; margin-bottom: 5px; }
        .feature-desc { color: rgba(255,255,255,0.6); font-size: 14px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="content">
            <div class="header">
                <img src="{{ asset('assets/img/logo.png') }}" alt="SetuGeo Logo" style="height: 60px; width: auto;">
            </div>
            <div class="body-content">
                <h1>Welcome aboard, {{ $user->name }}!</h1>
                <p>We're thrilled to have you join the SetuGeo community. You've just unlocked access to one of the most comprehensive geographic data platforms available.</p>
                
                <div class="feature-box">
                    <div class="feature-title">Fast & Reliable APIs</div>
                    <div class="feature-desc">Access worldwide country, state, city, and pincode data with extreme low latency.</div>
                </div>
                
                <div class="feature-box">
                    <div class="feature-title">Real-time Currency Conversion</div>
                    <div class="feature-desc">Get the latest exchange rates for over 100+ global currencies.</div>
                </div>

                <p>To get started, please complete your profile and explore our API documentation to integrate geo-intelligence into your applications.</p>
                
                <div style="text-align: center; margin-top: 30px;">
                    <a href="{{ route('dashboard') }}" class="btn">Go to My Dashboard</a>
                </div>
                
                <p style="margin-top: 40px; font-size: 14px; color: rgba(255,255,255,0.5); border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                    If you have any questions, feel free to reply to this email or visit our <a href="{{ route('support.index') }}" style="color: #f59e0b; text-decoration: none;">Help & Support</a> center.
                </p>
            </div>
            <div class="footer">
                &copy; {{ date('Y') }} SetuGeo API. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
