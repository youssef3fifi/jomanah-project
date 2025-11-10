# 🏥 PharmaCare Management System

A professional, production-ready Pharmacy Management System built with PHP backend and modern frontend. Designed for easy deployment on AWS infrastructure with complete inventory, sales, and customer management features.

## 📋 Table of Contents

- [Features](#features)
- [Technology Stack](#technology-stack)
- [Project Structure](#project-structure)
- [Local Development Setup](#local-development-setup)
- [AWS EC2 Deployment](#aws-ec2-deployment)
- [AWS RDS Database Setup](#aws-rds-database-setup)
- [API Documentation](#api-documentation)
- [Security Features](#security-features)
- [Troubleshooting](#troubleshooting)

## ✨ Features

### Comprehensive Functionality
- **Dashboard**: Real-time statistics and quick actions
- **Inventory Management**: Add, edit, delete medicines with stock tracking
- **Sales Processing**: Point-of-sale system with cart functionality
- **Customer Management**: Customer records and purchase history
- **Reports & Analytics**: 
  - Inventory reports with low stock alerts
  - Sales reports with payment method breakdown
  - Expiring medicines tracking

### Professional Features
- ✅ RESTful API architecture
- ✅ Responsive design for mobile and desktop
- ✅ Real-time stock updates
- ✅ Search and filter functionality
- ✅ Form validations
- ✅ Alert notifications
- ✅ Pagination for large datasets
- ✅ CORS-enabled for frontend-backend separation

## 🛠 Technology Stack

### Backend
- **Language**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Architecture**: RESTful API with PDO
- **Server**: Apache with mod_rewrite

### Frontend
- **Core**: HTML5, CSS3, Vanilla JavaScript
- **Design**: Modern, responsive UI with pharmacy theme
- **Features**: Real-time updates, modals, notifications

## 📁 Project Structure

```
pharmacy-management-system/
├── backend/
│   ├── api/
│   │   ├── medicines.php      # Medicines CRUD operations
│   │   ├── customers.php      # Customers management
│   │   ├── sales.php          # Sales transactions
│   │   └── reports.php        # Reports and analytics
│   ├── config/
│   │   ├── database.php       # Database connection
│   │   └── cors.php           # CORS configuration
│   ├── includes/
│   │   └── functions.php      # Helper functions
│   ├── .htaccess              # URL rewriting rules
│   └── index.php              # API entry point
├── frontend/
│   ├── index.html             # Dashboard
│   ├── inventory.html         # Inventory management
│   ├── sales.html             # Sales processing
│   ├── customers.html         # Customer management
│   ├── reports.html           # Reports & analytics
│   ├── css/
│   │   └── style.css          # Main stylesheet
│   └── js/
│       ├── config.js          # API URL configuration
│       ├── main.js            # Common utilities
│       ├── inventory.js       # Inventory logic
│       ├── sales.js           # Sales logic
│       └── customers.js       # Customer logic
├── database/
│   └── schema.sql             # Database schema
├── .gitignore
└── README.md
```

## 🚀 Local Development Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache web server with mod_rewrite enabled
- Web browser (Chrome, Firefox, Safari, Edge)

### Step-by-Step Installation

1. **Clone the Repository**
   ```bash
   git clone https://github.com/youssef3fifi/jomanah-project.git
   cd jomanah-project
   ```

2. **Set Up Database**
   ```bash
   # Log into MySQL
   mysql -u root -p
   
   # Import the schema
   source database/schema.sql
   ```

3. **Configure Backend**
   
   The system uses environment-based configuration. For local development, it uses default values:
   - Host: `localhost`
   - Database: `pharmacy_db`
   - Username: `root`
   - Password: `` (empty)
   
   To customize, set environment variables:
   ```bash
   export DB_HOST="localhost"
   export DB_NAME="pharmacy_db"
   export DB_USER="root"
   export DB_PASSWORD="your_password"
   export DB_PORT="3306"
   ```

4. **Configure Frontend**
   
   Edit `frontend/js/config.js`:
   ```javascript
   const API_BASE_URL = 'http://localhost/backend';
   ```

5. **Set Up Apache Virtual Host** (Optional but recommended)
   
   Create a virtual host configuration:
   ```apache
   <VirtualHost *:80>
       ServerName pharmacy.local
       DocumentRoot /path/to/jomanah-project
       
       <Directory /path/to/jomanah-project>
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
   
   Add to your hosts file:
   ```
   127.0.0.1 pharmacy.local
   ```

6. **Start Development Server**
   
   Using PHP built-in server (for testing):
   ```bash
   # Backend
   cd backend
   php -S localhost:8000
   
   # Frontend (in another terminal)
   cd frontend
   php -S localhost:8080
   ```
   
   Then update `frontend/js/config.js`:
   ```javascript
   const API_BASE_URL = 'http://localhost:8000';
   ```

7. **Access the Application**
   
   Open your browser and navigate to:
   - Frontend: `http://localhost:8080` (or your virtual host)
   - Backend API: `http://localhost:8000` (or your backend URL)

### Default Credentials
- **Admin Username**: `admin`
- **Admin Password**: `admin123`

## ☁️ AWS EC2 Deployment

### Prerequisites
- AWS account
- EC2 instance (Ubuntu 20.04 or Amazon Linux 2)
- Security group with HTTP (80) and SSH (22) open

### Deployment Steps

1. **Launch EC2 Instance**
   ```bash
   # Connect to your instance
   ssh -i your-key.pem ubuntu@your-ec2-ip
   ```

2. **Install LAMP Stack**
   
   **For Ubuntu:**
   ```bash
   sudo apt update
   sudo apt install -y apache2 mysql-server php libapache2-mod-php php-mysql
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```
   
   **For Amazon Linux:**
   ```bash
   sudo yum update -y
   sudo amazon-linux-extras install -y php7.4 lamp-mariadb10.2-php7.4
   sudo yum install -y httpd mariadb-server
   sudo systemctl start httpd
   sudo systemctl enable httpd
   sudo systemctl start mariadb
   sudo systemctl enable mariadb
   ```

3. **Deploy Application Files**
   ```bash
   cd /var/www/html
   sudo git clone https://github.com/youssef3fifi/jomanah-project.git
   sudo mv jomanah-project/* .
   sudo chown -R www-data:www-data /var/www/html
   sudo chmod -R 755 /var/www/html
   ```

4. **Configure Apache**
   
   Edit `/etc/apache2/sites-available/000-default.conf` (Ubuntu):
   ```apache
   <VirtualHost *:80>
       ServerAdmin admin@pharmacy.local
       DocumentRoot /var/www/html
       
       <Directory /var/www/html>
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
       
       ErrorLog ${APACHE_LOG_DIR}/error.log
       CustomLog ${APACHE_LOG_DIR}/access.log combined
   </VirtualHost>
   ```
   
   Restart Apache:
   ```bash
   sudo systemctl restart apache2
   ```

5. **Set Up Database**
   ```bash
   sudo mysql -u root -p
   ```
   
   Then run:
   ```sql
   source /var/www/html/database/schema.sql
   ```

6. **Configure Environment Variables**
   
   Create a `.env` file or set in Apache configuration:
   ```bash
   export DB_HOST="localhost"
   export DB_NAME="pharmacy_db"
   export DB_USER="pharmacy_user"
   export DB_PASSWORD="secure_password"
   ```

7. **Update Frontend Configuration**
   
   Edit `frontend/js/config.js`:
   ```javascript
   const API_BASE_URL = 'http://YOUR_EC2_PUBLIC_IP/backend';
   ```

8. **Set Permissions**
   ```bash
   sudo chown -R www-data:www-data /var/www/html
   sudo chmod -R 755 /var/www/html
   ```

9. **Test the Application**
   
   Visit: `http://YOUR_EC2_PUBLIC_IP`

## 🗄 AWS RDS Database Setup

### Create RDS Instance

1. **Launch RDS Instance**
   - Database engine: MySQL 5.7 or MariaDB
   - Instance class: db.t2.micro (for testing) or larger for production
   - Storage: 20 GB (minimum)
   - Enable public accessibility (or use VPC peering)

2. **Configure Security Group**
   - Add inbound rule for MySQL (port 3306)
   - Source: Your EC2 instance security group

3. **Get Connection Details**
   - Endpoint: `your-rds-instance.region.rds.amazonaws.com`
   - Port: `3306`
   - Username: Set during creation
   - Password: Set during creation

4. **Import Database Schema**
   ```bash
   mysql -h your-rds-endpoint.rds.amazonaws.com \
         -u your-username \
         -p \
         pharmacy_db < database/schema.sql
   ```

5. **Update Backend Configuration**
   
   Set environment variables on EC2:
   ```bash
   export DB_HOST="your-rds-endpoint.rds.amazonaws.com"
   export DB_NAME="pharmacy_db"
   export DB_USER="your-username"
   export DB_PASSWORD="your-password"
   export DB_PORT="3306"
   ```

## 📚 API Documentation

### Base URL
```
http://YOUR_SERVER_IP/backend/api
```

### Authentication
Currently, the API is open. For production, implement authentication middleware.

### Endpoints

#### Medicines

**List all medicines**
```
GET /medicines
Query Parameters:
  - page: Page number (default: 1)
  - limit: Items per page (default: 10, max: 100)
  - search: Search term
  - category: Filter by category
  - low_stock: true/false (show items with stock < 50)
```

**Get medicine details**
```
GET /medicines/{id}
```

**Create medicine**
```
POST /medicines
Body: {
  "name": "Medicine Name",
  "category": "Category",
  "price": 10.99,
  "stock_quantity": 100,
  "expiry_date": "2025-12-31",
  "supplier": "Supplier Name",
  "description": "Description"
}
```

**Update medicine**
```
PUT /medicines/{id}
Body: (same as create, all fields optional)
```

**Delete medicine**
```
DELETE /medicines/{id}
```

#### Customers

**List all customers**
```
GET /customers
Query Parameters:
  - page: Page number
  - limit: Items per page
  - search: Search term
```

**Get customer details**
```
GET /customers/{id}
```

**Create customer**
```
POST /customers
Body: {
  "name": "Customer Name",
  "phone": "+1234567890",
  "email": "email@example.com",
  "address": "Address"
}
```

**Update customer**
```
PUT /customers/{id}
Body: (same as create, all fields optional)
```

**Delete customer**
```
DELETE /customers/{id}
```

#### Sales

**List all sales**
```
GET /sales
Query Parameters:
  - page: Page number
  - limit: Items per page
  - customer_id: Filter by customer
  - payment_method: cash/card/insurance
  - date_from: YYYY-MM-DD
  - date_to: YYYY-MM-DD
```

**Get sale details**
```
GET /sales/{id}
```

**Create sale**
```
POST /sales
Body: {
  "customer_id": 1 (optional),
  "payment_method": "cash",
  "items": [
    {
      "medicine_id": 1,
      "quantity": 2
    }
  ]
}
```

#### Reports

**Inventory report**
```
GET /reports/inventory
```

**Sales report**
```
GET /reports/sales
Query Parameters:
  - date_from: YYYY-MM-DD
  - date_to: YYYY-MM-DD
```

**Expiring medicines**
```
GET /reports/expiring
Query Parameters:
  - days: Number of days (default: 90)
```

**Dashboard statistics**
```
GET /reports/dashboard
```

## 🔒 Security Features

### Implemented Security Measures

1. **SQL Injection Prevention**
   - All queries use prepared statements with PDO
   - Input sanitization and validation

2. **XSS Protection**
   - HTML special characters encoding
   - Content Security Policy headers

3. **CORS Configuration**
   - Configurable allowed origins
   - Proper handling of preflight requests

4. **Input Validation**
   - Server-side validation for all inputs
   - Type checking and range validation

5. **Password Security**
   - BCrypt password hashing
   - Secure password storage

### Additional Security Recommendations

For production deployment:

1. **Enable HTTPS**
   ```bash
   sudo apt install certbot python3-certbot-apache
   sudo certbot --apache
   ```

2. **Implement Authentication**
   - Add JWT or session-based authentication
   - Protect API endpoints with middleware

3. **Database Security**
   - Use strong database passwords
   - Restrict database user privileges
   - Enable SSL for database connections

4. **Server Hardening**
   - Keep software updated
   - Configure firewall rules
   - Disable directory listing
   - Hide PHP version

5. **Regular Backups**
   - Set up automated database backups
   - Store backups securely

## 🐛 Troubleshooting

### Common Issues

**1. Database Connection Failed**
```
Error: Database connection failed
```
**Solution**: 
- Check database credentials in `backend/config/database.php`
- Ensure MySQL service is running: `sudo systemctl status mysql`
- Verify database exists: `SHOW DATABASES;`

**2. 404 Not Found for API Endpoints**
```
Error: 404 Not Found
```
**Solution**:
- Ensure mod_rewrite is enabled: `sudo a2enmod rewrite`
- Check `.htaccess` file exists in backend directory
- Verify Apache configuration allows `.htaccess` overrides

**3. CORS Errors**
```
Error: CORS policy blocked
```
**Solution**:
- Update `backend/config/cors.php` with correct origins
- Ensure CORS headers are being sent
- Check browser console for specific CORS errors

**4. Frontend Can't Connect to Backend**
```
Error: Failed to fetch
```
**Solution**:
- Verify API_BASE_URL in `frontend/js/config.js`
- Check if backend is accessible from browser
- Look for network errors in browser developer tools

**5. Permission Denied Errors**
```
Error: Permission denied
```
**Solution**:
```bash
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
```

### Debug Mode

To enable error display for debugging (disable in production):

Edit `php.ini`:
```ini
display_errors = On
error_reporting = E_ALL
```

Or add to the top of PHP files:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Logs

Check server logs for errors:
```bash
# Apache error log
sudo tail -f /var/log/apache2/error.log

# MySQL error log
sudo tail -f /var/log/mysql/error.log

# PHP error log
sudo tail -f /var/log/php/error.log
```

## 📞 Support

For issues, questions, or contributions:
- GitHub Issues: [Create an issue](https://github.com/youssef3fifi/jomanah-project/issues)
- Email: support@pharmacare.com

## 📄 License

This project is licensed under the MIT License.

## 🙏 Acknowledgments

Built with ❤️ for efficient pharmacy management.

---

**Note**: This is a demonstration system. For production use, implement additional security measures, authentication, and regular security audits.