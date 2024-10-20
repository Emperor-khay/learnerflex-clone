<!DOCTYPE html>
<html>
<head>
    <title>Vendor Request</title>
</head>
<body>
    <h1>{{ $user->name }} has requested a vendor account</h1>

    <div style="padding: 10px; background-color: #f0f0f0; border-radius: 5px;">
        They have provided a URL link to their sales page. Please review it by clicking the button below!
    </div>

    <p>You can contact the user at: {{ $user->email }}</p>

    <a href="{{ $saleurl }}" style="background-color: #3490dc; color: white; padding: 10px 20px; text-decoration: none; display: inline-block;">Open Sales Page</a>

    <br><br>

    <!-- Add dynamic verification link for upgrading the user to a vendor -->
    <a href="learnerflex.com"
       style="background-color: #38c172; color: white; padding: 10px 20px; text-decoration: none; display: inline-block;">
       Login and Upgrade to Vendor
    </a>

    <p>From,<br>{{ config('app.name') }}</p>
</body>
</html>
