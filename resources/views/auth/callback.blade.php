<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Authentication Callback</title>
    <script>
        try {
            // Use @json to safely encode the PHP $data array into a JS object literal
            const dataObject = @json($data);

            // IMPORTANT: Replace '*' with your actual React app origin for security!
            // Get this from your .env file or configuration
            const targetOrigin = "{{ env('FRONTEND_URL', 'https://bhuorder.com') }}"; // Default for local dev

            if (window.opener) {
                // Send the JavaScript object to the opener window
                window.opener.postMessage(dataObject, targetOrigin);
            } else {
                // Handle case where popup wasn't opened correctly or opener is gone
                console.error("Cannot find opener window to post message.");
                // You might want to display a message to the user here
                document.body.innerHTML = '<p>Error: Could not communicate back to the application. Please close this window and try again.</p>';
            }

        } catch (e) {
            console.error("Error processing authentication data:", e);
            // Optionally try to inform the opener window about the error
            try {
                const targetOrigin = "{{ env('FRONTEND_URL', 'https://bhuorder.com') }}";
                if (window.opener) {
                    window.opener.postMessage({ status: 'error', message: 'Callback processing error.' }, targetOrigin);
                }
            } catch (postError) {
                console.error("Failed to post error message:", postError);
            }
            document.body.innerHTML = '<p>An error occurred during authentication. Please close this window and try again.</p>';
        } finally {
            // Attempt to close the popup window, even if errors occurred
            // Use a small delay in case postMessage needs a moment
            setTimeout(() => {
                window.close();
            }, 100); // 100ms delay
        }
    </script>
</head>

<body>
    <p>Authenticating, please wait...</p>
    {{-- Optional: Display error message if $data indicates an error from server-side --}}
    @if(isset($data['status']) && $data['status'] === 'error')
        <p style="color: red;">Error: {{ $data['message'] ?? 'An unknown error occurred.' }}</p>
        <p style="color: red;">Please close this window.</p>
    @else
        <p>You can close this window if it doesn't close automatically.</p>
    @endif
</body>

</html>