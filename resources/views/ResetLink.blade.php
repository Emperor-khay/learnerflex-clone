<!DOCTYPE html>
<html>
<head>
    <title>Password Reset Request</title>
</head>
<body>
    <h2>Hello,</h2>
    <p>You are receiving this email because we received a password reset request for your account.</p>

    <p>
        Here is your token: <b>{{$token}}</b>
        Click the button below to reset your password:
    </p>

    <p>
        <a href="" style="background-color: #4CAF50; color: white; padding: 10px 20px; text-align: center; text-decoration: none; display: inline-block; border-radius: 4px;">
            Reset Password
        </a>
    </p>

    <p>
        If you did not request a password reset, no further action is required.
    </p>

    <p>Thank you,<br/>
    {{ config('app.name') }} Team</p>
</body>
</html>
