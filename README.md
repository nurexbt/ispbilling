# KHAN ERP - ISP Billing System

## Installation Instructions

1.  **Database Setup**:
    *   Open your web browser.
    *   Navigate to `http://localhost/khanerp/install.php`.
    *   This will create the `khanerp` database and the `routers` table.
    *   You should see "Database and table setup completed successfully."

2.  **Usage**:
    *   Go to `http://localhost/khanerp/`.
    *   Click "Add Host" to add a Mikrotik router.
    *   Enter the details:
        *   **Device Name**: Must match the router's System Identity exactly.
        *   **Host IP**: The IP address of the router.
        *   **API Port**: Default is 8728. Ensure this port is open on the router (`/ip service print`).
        *   **User/Pass**: Router credentials.
    *   Click "Connect & Save".
    *   The system will verify the connection and the identity. If they match, the router will be saved.

## Requirements
*   PHP 7.4 or higher
*   MySQL/MariaDB
*   Mikrotik RouterOS with API service enabled.
