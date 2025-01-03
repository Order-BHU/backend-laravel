<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your OTP Code</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4; line-height: 1.6;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px; border-radius: 8px; margin-top: 40px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="text-align: center; margin-bottom: 30px;">
            <img src="/api/placeholder/120/40" alt="Company Logo" style="max-width: 120px; height: auto;"/>
        </div>
        
        <h1 style="color: #333333; font-size: 24px; margin-bottom: 20px; text-align: center;">Hey {{$name}}! Verify Your Identity</h1>
        
        <p style="color: #666666; font-size: 16px; margin-bottom: 30px; text-align: center;">
            Use the following OTP code to complete your verification process.
        </p>
        
        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 6px; text-align: center; margin-bottom: 30px;">
            <span style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #2d3748;">{{$otp}}</span>
        </div>
        
        <p style="color: #666666; font-size: 14px; margin-bottom: 20px; text-align: center;">
            This code will expire in <span style="color: #e53e3e; font-weight: bold;">24 hours</span>
        </p>
        
        <div style="border-top: 1px solid #eee; margin-top: 30px; padding-top: 20px;">
            <p style="color: #999999; font-size: 12px; text-align: center;">
                If you didn't request this code, please ignore this email or contact support if you have concerns.
            </p>
            
            <p style="color: #999999; font-size: 12px; text-align: center; margin-bottom: 0;">
                © 2024 Order. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>