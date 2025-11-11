/**
 * API Configuration
 * Update the API_BASE_URL to match your backend server IP/domain
 */

// For local development with Node.js backend
// For AWS EC2 deployment, replace with your EC2 instance IP address
// Example: const API_BASE_URL = 'http://54.123.45.67:3000';

const API_BASE_URL = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
    ? 'http://localhost:3000'
    : 'http://YOUR_EC2_IP:3000';  // Replace with actual EC2 IP

// API Endpoints
const API = {
    medicines: `${API_BASE_URL}/api/medicines`,
    customers: `${API_BASE_URL}/api/customers`,
    sales: `${API_BASE_URL}/api/sales`,
    reports: {
        inventory: `${API_BASE_URL}/api/reports/inventory`,
        sales: `${API_BASE_URL}/api/reports/sales`,
        expiring: `${API_BASE_URL}/api/reports/expiring`,
        dashboard: `${API_BASE_URL}/api/reports/dashboard`,
        topSelling: `${API_BASE_URL}/api/reports/top-selling`
    }
};
