<!DOCTYPE html>
<html>
<head>
    <title>Your Account has been Upgraded to Vendor</title>
</head>
<body>
    <h1>Congratulations, {{ $user->name }}!</h1>

    <p>Your account has been successfully upgraded to a vendor status. You can now start listing your products and selling them on our platform.</p>

    <div style="padding: 10px; background-color: #f0f0f0; border-radius: 5px;">
        To manage your vendor account, visit your vendor dashboard by clicking the button below.
    </div>

    <a href="{{ url('/vendor') }}" style="background-color: #3490dc; color: white; padding: 10px 20px; text-decoration: none; display: inline-block;">Go to Vendor Dashboard</a>

    <br><br>
    <p>If you have any questions, feel free to contact us at support@example.com.</p>

    <p>Thanks,<br>{{ config('app.name') }} Team</p>
</body>
</html>
