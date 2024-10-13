Transactions API Documentation
Endpoint: GET /transactions
This endpoint returns a paginated list of transactions. You can apply optional filters using query parameters.

Query Parameters:
tx_ref (optional): Filter by transaction reference (e.g., abc123).
status (optional): Filter by transaction status (e.g., completed, pending).
user_id (optional): Filter by the user who made the transaction (e.g., 10).
per_page (optional): Number of results per page (default is 15).