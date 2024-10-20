<!-- verify-vendor.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Verify Vendor</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h1>Click below to verify and upgrade the user to a vendor</h1>
    
    <form id="vendor-upgrade-form" method="PATCH action="{{ url('/api/admin/accept-vendor-request/' . $id) }}">
        @csrf
        <button type="submit" style="background-color: #38c172; color: white; padding: 10px 20px; border: none;">Verify and Upgrade to Vendor</button>
    </form>

    <script>
        // Optionally, automatically submit the form when the page loads
        document.getElementById('vendor-upgrade-form').submit();
    </script>
</body>
</html>
