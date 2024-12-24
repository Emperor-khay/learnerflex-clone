<!DOCTYPE html>
<html>
<head>
    <title>Withdrawal Processing</title>
</head>
<body>
    <p>Dear {{ $withdrawal->email }},</p>

    <p>We are pleased to inform you that your {{ $type }} withdrawal request of {{ $withdrawal->amount }} has been approved and is now being processed.</p>

    <p>Bank Details:</p>
    <ul>
        <li>Bank Name: {{ $withdrawal->bank_name }}</li>
        <li>Bank Account: {{ $withdrawal->bank_account }}</li>
    </ul>

    <p>Thank you for using our platform.</p>

    <p>Best regards,<br>Your Team</p>
</body>
</html>
