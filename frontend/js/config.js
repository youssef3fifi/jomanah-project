/**
 * API Configuration
 * Update the API_BASE_URL to match your backend server IP/domain
 */

// For local development, use localhost
// For AWS EC2 deployment, replace with your EC2 instance IP address
// Example: const API_BASE_URL = 'http://54.123.45.67/backend';

const API_BASE_URL = 'http://localhost/backend';

// API Endpoints
const API = {
    medicines: `${API_BASE_URL}/api/medicines`,
    customers: `${API_BASE_URL}/api/customers`,
    sales: `${API_BASE_URL}/api/sales`,
    reports: {
        inventory: `${API_BASE_URL}/api/reports/inventory`,
        sales: `${API_BASE_URL}/api/reports/sales`,
        expiring: `${API_BASE_URL}/api/reports/expiring`,
        dashboard: `${API_BASE_URL}/api/reports/dashboard`
    }
};
