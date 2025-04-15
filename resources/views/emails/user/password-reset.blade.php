<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4; line-height: 1.6;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px; border-radius: 8px; margin-top: 40px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        
        <h1 style="color: #333333; font-size: 24px; margin-bottom: 20px; text-align: center;">Password Reset Request</h1>
        
        <p style="color: #666666; font-size: 16px; margin-bottom: 30px; text-align: center;">
            Hello {{$name}}, you are receiving this email because we received a password reset request for your account.
        </p>
        
        <div style="text-align: center; margin-bottom: 30px;">
            <a href="{{$resetUrl}}" style="background-color: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
                Reset Password
            </a>
        </div>
        
        <p style="color: #666666; font-size: 14px; margin-bottom: 20px; text-align: center;">
            This password reset link will expire in <span style="color: #e53e3e; font-weight: bold;">60 minutes</span>
        </p>
        
        <p style="color: #666666; font-size: 14px; margin-bottom: 20px; text-align: center;">
            If you did not request a password reset, no further action is required.
        </p>
        
        <div style="border-top: 1px solid #eee; margin-top: 30px; padding-top: 20px;">
            <p style="color: #999999; font-size: 12px; text-align: center;">
                If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:
            </p>
            <p style="color: #999999; font-size: 12px; text-align: center; word-break: break-all;">
                {{$resetUrl}}
            </p>
        </div>
    </div>
</body>
</html> 