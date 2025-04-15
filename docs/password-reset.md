# Password Reset API Documentation

## Overview
This documentation describes the password reset functionality for the Order API. The system allows users to request a password reset link via email and then reset their password using a secure token.

## Endpoints

### 1. Request Password Reset

**Endpoint:** `POST /api/forgot-password`

**Description:** Initiates the password reset process by sending a reset link to the user's email.

**Request Body:**
```json
{
    "email": "user@example.com"
}
```

**Response:**
- Success (200 OK):
```json
{
    "message": "Password reset link sent to your email"
}
```

- Error (400 Bad Request):
```json
{
    "message": "The email field is required",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

**Notes:**
- The reset link in the email will expire after 60 minutes
- The link will direct users to the frontend application's reset password page
- The token is included as a query parameter in the URL

### 2. Reset Password

**Endpoint:** `POST /api/reset-password`

**Description:** Allows users to set a new password using the token received in their email.

**Request Body:**
```json
{
    "token": "reset-token-from-email",
    "password": "new-password",
    "password_confirmation": "new-password"
}
```

**Response:**
- Success (200 OK):
```json
{
    "message": "Password has been reset successfully"
}
```

- Error (400 Bad Request):
```json
{
    "message": "Invalid or expired reset token"
}
```

**Notes:**
- The token must be valid and not expired (60 minutes)
- The password must be at least 8 characters long
- The password confirmation must match the password

## Frontend Implementation Guide

### 1. Password Reset Request Page
Create a form that collects the user's email and makes a POST request to `/api/forgot-password`.

Example:
```javascript
const requestPasswordReset = async (email) => {
    try {
        const response = await fetch('/api/forgot-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email })
        });
        
        const data = await response.json();
        if (response.ok) {
            // Show success message
        } else {
            // Handle error
        }
    } catch (error) {
        // Handle network error
    }
};
```

### 2. Password Reset Page
Create a page that handles the reset token from the URL and provides a form for the new password.

Example:
```javascript
// Get token from URL
const queryParams = new URLSearchParams(window.location.search);
const token = queryParams.get('token');

const resetPassword = async (newPassword) => {
    try {
        const response = await fetch('/api/reset-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                token,
                password: newPassword,
                password_confirmation: newPassword
            })
        });
        
        const data = await response.json();
        if (response.ok) {
            // Show success message and redirect to login
        } else {
            // Handle error
        }
    } catch (error) {
        // Handle network error
    }
};
```

## Security Considerations

1. **Token Security:**
   - Tokens are randomly generated using 64 characters
   - Tokens expire after 60 minutes
   - Tokens are single-use and deleted after successful password reset

2. **Password Requirements:**
   - Minimum 8 characters
   - Must include password confirmation
   - Passwords are hashed before storage

3. **Rate Limiting:**
   - Implement rate limiting on the forgot-password endpoint to prevent abuse
   - Consider implementing CAPTCHA for multiple failed attempts

## Error Handling

The API returns appropriate HTTP status codes and error messages:

- 200: Success
- 400: Bad Request (validation errors, invalid token)
- 401: Unauthorized
- 500: Server Error

## Email Template

The system sends a professionally designed HTML email containing:
- Personalized greeting
- Reset password button
- Token expiration notice
- Manual URL copy option
- Security notice for non-requested resets

## Environment Variables

Required environment variables:
```
FRONTEND_URL=http://yourfrontend.com
```

## Testing

To test the password reset flow:

1. Request a password reset:
```bash
curl -X POST http://your-api.com/api/forgot-password \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com"}'
```

2. Reset password using the token:
```bash
curl -X POST http://your-api.com/api/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "token": "reset-token",
    "password": "new-password",
    "password_confirmation": "new-password"
  }'
```

## Support

For any issues or questions regarding the password reset functionality, please contact the support team at support@bhuorder.com.ng. 