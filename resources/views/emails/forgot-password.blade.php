<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Your Password</title>
    <style>
        body { font-family: 'Inter', Helvetica, Arial, sans-serif; background-color: #000000; color: #ffffff; margin: 0; padding: 0; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #000000; padding-bottom: 40px; }
        .content { max-width: 600px; margin: 0 auto; background-color: #0a0a0a; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; margin-top: 40px; overflow: hidden; }
        .header { padding: 40px 0; text-align: center; background: linear-gradient(to bottom, #111111, #0a0a0a); }
        .body-content { padding: 40px; }
        .footer { padding: 20px; text-align: center; color: rgba(255,255,255,0.5); font-size: 12px; }
        h1 { color: #ffffff; font-size: 24px; margin-bottom: 20px; text-align: center; }
        p { color: rgba(255,255,255,0.8); line-height: 1.6; font-size: 16px; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 14px 30px; background-color: #f59e0b; color: #ffffff !important; text-decoration: none; border-radius: 12px; font-weight: bold; font-size: 16px; text-align: center; }
        .security-note { background: rgba(239,68,68,0.05); border: 1px solid rgba(239,68,68,0.2); color: rgba(239,68,68,0.8); border-radius: 12px; padding: 20px; font-size: 14px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="content">
            <div class="header">
                <img src="{{ asset('assets/img/logo.png') }}" alt="SetuGeo Logo" style="height: 60px; width: auto;">
            </div>
            <div class="body-content">
                <h1>Reset Your Password</h1>
                <p>Hello {{ $user->name }},</p>
                <p>We received a request to reset the password for your SetuGeo account. Click the button below to choose a new password:</p>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{ $resetUrl }}" class="btn">Reset My Password</a>
                </div>
                
                <p>This password reset link will expire in 60 minutes. If you did not request a password reset, no further action is required.</p>
                
                <div class="security-note">
                    <strong>Security Note:</strong> If you did not make this request, please ignore this email or contact our support team immediately if you suspect any unauthorized access to your account.
                </div>
            </div>
            <div class="footer">
                &copy; {{ date('Y') }} SetuGeo API. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
