<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Authentication Callback</title>
    <script>
        // Send the data to the opener window
        window.opener.postMessage(data , '*')

        // Close the popup
        window.close()
    </script>
</head>
<body>
    <p>Authenticating...</p>
    @isset($error)
        <p style="color: red;">An error occurred. Please close this window and try again.</p>
    @endisset
</body>
</html>