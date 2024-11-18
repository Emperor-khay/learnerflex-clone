<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue with User Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
        }
        .container {
            margin: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 20px;
            font-size: 0.9em;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Issue with User Registration</h2>
        <p>Dear Admin,</p>
        <p>An issue occurred during the registration process:</p>
        <ul>
            <li><strong>Order ID:</strong> {{ $orderID }}</li>
            <li><strong>Email:</strong> {{ $email }}</li>
        </ul>
        <p>Please investigate this issue at your earliest convenience. Note: this means the transaction was successful but the user couldn't complete registration- user details is not saved in database</p>
        <p>Thank you,</p>
        <p><strong>Your Application Team</strong></p>
    </div>
    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>
