<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Request Received</title>
    <style type="text/css">
        /* Base styles */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #333333;
            background-color: #f9f9f9;
        }

        /* Container */
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }

        /* Header */
        .header {
            padding: 25px;
            text-align: center;
            background-color: #4F46E5;
            /* Indigo color - you can use your brand color */
            color: #ffffff;
        }

        .header img {
            max-height: 60px;
            width: auto;
        }

        /* Content */
        .content {
            padding: 30px 25px;
        }

        /* Footer */
        .footer {
            padding: 20px 25px;
            text-align: center;
            background-color: #f5f5f5;
            color: #666666;
            font-size: 14px;
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4F46E5;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            margin: 15px 0;
        }

        /* Utility classes */
        .text-center {
            text-align: center;
        }

        .text-highlight {
            color: #4F46E5;
            font-weight: bold;
        }

        /* Make it responsive */
        @media screen and (max-width: 600px) {
            .container {
                width: 100% !important;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header with logo -->
       

        <!-- Main content -->
        <div class="content">
            <h2>Thank You for Contacting Us!</h2>

            <p>Hello {{ $name }},</p>

            <p>We've received your message and want to thank you for reaching out to us. Our team will review your
                inquiry and get back to you as soon as possible.</p>

            <p>Here's a summary of the information you provided:</p>

            <ul>
                <li><strong>Name:</strong> {{ $name }}</li>
                <li><strong>Subject:</strong> {{ $subject }}</li>
            </ul>

            <p>We typically respond within 24-48 business hours. If your matter is urgent, please don't hesitate to call
                us directly at <span class="text-highlight">(234) 9032497799</span>.</p>

           

            <p>Thank you for your patience,<br>
                The {{ config('app.name') }} Team</p>
        </div>

        <!-- Footer with company info and links -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p>
                <a href=" " style="color: #4F46E5; text-decoration: none;">Privacy Policy</a> |
                <a href="   " style="color: #4F46E5; text-decoration: none;">Terms of Service</a> |
                <a href=""
                    style="color: #4F46E5; text-decoration: none;">Unsubscribe</a>
            </p>
            <p style="font-size: 12px; color: #999999;">
                This is an automated message, please do not reply directly to this email.
            </p>
        </div>
    </div>
</body>

</html>