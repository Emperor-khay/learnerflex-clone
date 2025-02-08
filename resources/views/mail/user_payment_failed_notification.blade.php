<!DOCTYPE html>
<html>
<head>
    <title>Payment Failed Notification</title>
</head>
<body>
    <h2>Payment Failed</h2>
    <p>Dear {{ $transaction->user->name ?? 'Customer' }},</p>
    <p>We regret to inform you that your payment of <strong>₦{{ number_format($amount, 2) }}</strong> for Transaction ID <strong>{{ $transaction->id }}</strong> has failed.</p>

    <p><strong>Message:</strong> {{ $message }}</p>

    <p>Please try again or contact our support team for assistance.</p>

    <p>Best regards,</p>
    <p><strong>LearnerFlex Team</strong></p>
</body>
</html>
