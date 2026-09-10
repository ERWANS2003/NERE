<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body style="background: #1a1a1a; color: white; font-family: sans-serif; padding: 20px;">
    <h1>Dashboard Works!</h1>
    <p>If you see this, Blade rendering is working.</p>
    <p>User: {{ auth()->user()->name }}</p>
</body>
</html>
