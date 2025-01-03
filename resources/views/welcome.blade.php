<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🤖 API Only Zone</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-purple-500 to-indigo-600 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl bg-white rounded-xl shadow-2xl p-8 text-center space-y-6">
        <div class="animate-bounce text-6xl mb-4">
            🤖
        </div>
        
        <h1 class="text-4xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">
            Beep Boop! Humans Detected!
        </h1>
        
        <p class="text-xl text-gray-600">
            This is not the website you're looking for... 
            <span class="block text-sm mt-2">*waves hand in Jedi motion*</span>
        </p>
        
        <div class="bg-gray-50 rounded-lg p-6 space-y-4">
            <p class="text-lg text-gray-800">
                This domain is dedicated to our APIs only. 
                If you're looking for cool endpoints to integrate with, you're in the right place!
            </p>
            
            <div class="text-left bg-black text-green-400 p-4 rounded-lg font-mono text-sm">
                <p>$ curl -X GET https://api.example.com/v1/hello</p>
                <p class="mt-2">{"message": "Hey there! 👋 This is an API-only zone!"}</p>
            </div>
        </div>
        
        <div class="space-y-2">
            <p class="text-gray-600">Need our documentation?</p>
            <a href="/docs" class="inline-block bg-indigo-600 text-white px-6 py-2 rounded-full hover:bg-indigo-700 transition-colors">
                Check Out Our API Docs →
            </a>
        </div>
        
        <div class="border-t pt-6 mt-6">
            <p class="text-gray-500">
                💡 Tip: If you're seeing this message, you're probably looking for 
                <a href="/docs" class="text-indigo-600 hover:text-indigo-800">api.example.com/docs</a>
            </p>
        </div>
        
        <div class="text-sm text-gray-400">
            Built for machines, with ❤️ by humans
        </div>
    </div>
</body>
</html>