<!DOCTYPE html>
<html>
<head>
    <title>Skipped Affiliate Registrations</title>
</head>
<body>
    <h1>Skipped Affiliate Registrations</h1>
    <p>The following emails could not be registered as affiliates during bulk upload because they already exist:</p>
    <ul>
        @foreach ($skippedEmails as $email)
            <li>{{ $email }}</li>
        @endforeach
    </ul>
</body>
</html>
