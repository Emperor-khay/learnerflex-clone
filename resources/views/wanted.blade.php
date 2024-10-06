<!DOCTYPE html>
<html>
<head>
    <title>Vendor Request</title>
</head>
<body>
    <h1>{{ $user->name }} has requested for a vendor account</h1>

    <div style="padding: 10px; background-color: #f0f0f0; border-radius: 5px;">
        He/She has provided a URL link to their sales page. Please take a look at their sales page and review it. The button below, once clicked, will take you to it!
    </div>

    <p>Here's the user email for a reply back if needed: {{ $user->email }}</p>

    <a href="{{ $saleurl }}" style="background-color: #3490dc; color: white; padding: 10px 20px; text-decoration: none; display: inline-block;">Open Sales Page</a>

    <br><br>
<a href="{{ url('https://learnerflex.com/api/user/accept-vendor-request', ['name' => 'LF ADmiN', 'user_id' => 1]) }}" style="background-color: #3490dc; color: white; padding: 10px 20px; text-decoration: none; display: inline-block;">Verify Vendor</a>

    <p>From,<br>{{ config('app.name') }}</p>
</body>
</html>
