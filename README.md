# 🏥 Pharmacy Management System

A professional, production-ready Pharmacy Management System built with **Node.js/Express.js backend** and **Vanilla JavaScript frontend**. Features complete inventory, sales, and customer management with **in-memory storage** - perfect for development, testing, and quick deployment without database setup.

## ✨ Features

### Comprehensive Functionality
- **📊 Dashboard**: Real-time statistics, revenue tracking, and quick actions
- **💊 Inventory Management**: Full CRUD operations for medicines with stock tracking
- **🛒 Sales/POS System**: Point-of-sale with cart functionality and automatic stock updates
- **👥 Customer Management**: Customer records with purchase history tracking
- **📈 Reports & Analytics**: 
  - Inventory reports with low stock alerts
  - Sales reports with payment method breakdown
  - Expiring medicines tracking (within 3 months)
  - Top-selling medicines analysis
  - Dashboard statistics

### Professional Features
- ✅ RESTful API architecture with Express.js
- ✅ In-memory storage using JavaScript arrays
- ✅ CORS-enabled for frontend-backend separation
- ✅ Responsive design for mobile, tablet, and desktop
- ✅ Real-time stock updates on sales
- ✅ Search and filter functionality
- ✅ Form validations with error messages
- ✅ Toast notifications for user feedback
- ✅ XSS protection with HTML escaping
- ✅ AWS EC2 deployment ready

## 🛠 Technology Stack

### Backend
- **Runtime**: Node.js (v14+)
- **Framework**: Express.js v4.18
- **Storage**: In-Memory JavaScript Arrays
- **Architecture**: RESTful API
- **CORS**: Configured for cross-origin requests

### Frontend
- **Core**: HTML5, CSS3, Vanilla JavaScript (No frameworks)
- **Design**: Modern, responsive UI with pharmacy theme
- **Features**: Real-time updates, modals, notifications, form validation

### Data Storage
- **Type**: In-Memory Storage using JavaScript Arrays
- **Persistence**: Data persists only while server is running
- **Reset Behavior**: Data resets to initial sample data on server restart
- **Perfect For**: Development, testing, demos, and quick deployment

## 📁 Project Structure

```
jomanah-project/
├── backend/
│   ├── server.js                 # Main Express server
│   ├── package.json              # Node dependencies
│   ├── .env.example              # Environment variables template
│   ├── routes/
│   │   ├── medicines.js          # Medicines CRUD API
│   │   ├── customers.js          # Customers CRUD API
│   │   ├── sales.js              # Sales API with stock updates
│   │   └── reports.js            # Reports and analytics API
│   ├── data/
│   │   └── storage.js            # In-memory data storage
│   └── middleware/
│       └── cors.js               # CORS middleware
├── frontend/
│   ├── index.html                # Dashboard page
│   ├── inventory.html            # Inventory management
│   ├── sales.html                # Sales/POS page
│   ├── customers.html            # Customer management
│   ├── reports.html              # Reports page
│   ├── css/
│   │   └── style.css             # Main stylesheet
│   └── js/
│       ├── config.js             # API configuration
│       ├── main.js               # Common utilities
│       ├── inventory.js          # Inventory page logic
│       ├── sales.js              # Sales page logic
│       └── customers.js          # Customers page logic
├── .gitignore
└── README.md
```

## 🚀 Quick Start

### Prerequisites
- **Node.js** v14.0.0 or higher
- **npm** (comes with Node.js)
- Modern web browser (Chrome, Firefox, Safari, Edge)

### Installation & Setup

1. **Clone the Repository**
   ```bash
   git clone https://github.com/youssef3fifi/jomanah-project.git
   cd jomanah-project
   ```

2. **Install Backend Dependencies**
   ```bash
   cd backend
   npm install
   ```

3. **Start the Backend Server**
   ```bash
   npm start
   ```
   
   Server will start on `http://localhost:3000`
   
   You should see:
   ```
   ═══════════════════════════════════════════════════════
   🏥 Pharmacy Management System API
   ═══════════════════════════════════════════════════════
   🚀 Server running on http://0.0.0.0:3000
   📊 Environment: development
   💾 Storage: In-Memory (resets on server restart)
   ═══════════════════════════════════════════════════════
   ```

4. **Open the Frontend**
   
   Open `frontend/index.html` directly in your browser, OR use a local server:
   
   ```bash
   # Option 1: Using Python
   cd frontend
   python -m http.server 8080
   
   # Option 2: Using Node.js http-server
   npm install -g http-server
   cd frontend
   http-server -p 8080
   
   # Option 3: Use VS Code Live Server extension
   ```
   
   Access the application at `http://localhost:8080`

### Initial Data

The system comes pre-loaded with sample data:
- **6 Medicines**: Various categories (Pain Relief, Antibiotics, etc.)
- **3 Customers**: Sample customer records
- **2 Sales**: Example transactions

## 📚 API Documentation

### Base URL
```
http://localhost:3000/api
```

### Endpoints

#### Medicines API

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/medicines` | Get all medicines (supports filtering) |
| GET | `/api/medicines/:id` | Get single medicine by ID |
| GET | `/api/medicines/low-stock` | Get medicines with low stock |
| POST | `/api/medicines` | Create new medicine |
| PUT | `/api/medicines/:id` | Update medicine |
| DELETE | `/api/medicines/:id` | Delete medicine |

**Query Parameters for GET /api/medicines:**
- `category`: Filter by category
- `search`: Search in name/description
- `low_stock=true`: Show items with stock < 50

**Example - Create Medicine:**
```bash
curl -X POST http://localhost:3000/api/medicines \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Aspirin 500mg",
    "category": "Pain Relief",
    "price": 25,
    "stock": 100,
    "expiryDate": "2026-12-31",
    "supplier": "PharmaCorp",
    "description": "Pain reliever"
  }'
```

#### Customers API

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/customers` | Get all customers |
| GET | `/api/customers/:id` | Get single customer |
| GET | `/api/customers/:id/history` | Get customer purchase history |
| POST | `/api/customers` | Create new customer |
| PUT | `/api/customers/:id` | Update customer |
| DELETE | `/api/customers/:id` | Delete customer |

**Example - Create Customer:**
```bash
curl -X POST http://localhost:3000/api/customers \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "phone": "01234567890",
    "email": "john@example.com",
    "address": "123 Main St"
  }'
```

#### Sales API

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/sales` | Get all sales (supports filtering) |
| GET | `/api/sales/:id` | Get single sale |
| POST | `/api/sales` | Create new sale (updates stock automatically) |
| DELETE | `/api/sales/:id` | Delete sale |

**Query Parameters for GET /api/sales:**
- `customerId`: Filter by customer
- `paymentMethod`: Filter by payment method
- `dateFrom`: Filter from date (YYYY-MM-DD)
- `dateTo`: Filter to date (YYYY-MM-DD)

**Example - Create Sale:**
```bash
curl -X POST http://localhost:3000/api/sales \
  -H "Content-Type: application/json" \
  -d '{
    "customerId": 1,
    "paymentMethod": "Cash",
    "items": [
      {
        "medicineId": 1,
        "quantity": 2
      },
      {
        "medicineId": 3,
        "quantity": 1
      }
    ]
  }'
```

**Valid Payment Methods:**
- Cash
- Credit Card
- Debit Card
- Insurance

#### Reports API

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/reports/dashboard` | Dashboard statistics |
| GET | `/api/reports/inventory` | Inventory report by category |
| GET | `/api/reports/sales` | Sales report with filters |
| GET | `/api/reports/expiring` | Medicines expiring soon |
| GET | `/api/reports/top-selling` | Top selling medicines |

**Example - Dashboard Statistics:**
```bash
curl http://localhost:3000/api/reports/dashboard
```

**Response:**
```json
{
  "success": true,
  "data": {
    "totalMedicines": 6,
    "totalCustomers": 3,
    "totalSales": 2,
    "totalRevenue": 610,
    "todaySales": 0,
    "todayRevenue": 0,
    "lowStockCount": 0,
    "expiringCount": 1
  }
}
```

## ☁️ AWS EC2 Deployment

### Prerequisites
- AWS account
- EC2 instance (Ubuntu 20.04 or Amazon Linux 2)
- Security group with ports 22 (SSH) and 3000 (API) open

### Step-by-Step Deployment

1. **Launch EC2 Instance**
   - Choose Ubuntu Server 20.04 LTS or Amazon Linux 2
   - Instance type: t2.micro (free tier eligible)
   - Configure security group:
     - SSH (22): Your IP
     - Custom TCP (3000): 0.0.0.0/0 (or your specific IPs)

2. **Connect to EC2 Instance**
   ```bash
   ssh -i your-key.pem ubuntu@your-ec2-public-ip
   ```

3. **Install Node.js**
   
   **For Ubuntu:**
   ```bash
   curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
   sudo apt-get install -y nodejs
   ```
   
   **For Amazon Linux:**
   ```bash
   curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
   source ~/.bashrc
   nvm install 18
   ```

4. **Install Git and Clone Repository**
   ```bash
   sudo apt-get update
   sudo apt-get install -y git
   git clone https://github.com/youssef3fifi/jomanah-project.git
   cd jomanah-project
   ```

5. **Install Backend Dependencies**
   ```bash
   cd backend
   npm install
   ```

6. **Configure Environment (Optional)**
   ```bash
   cp .env.example .env
   nano .env
   # Edit PORT and other settings if needed
   ```

7. **Start Server with PM2 (Production)**
   ```bash
   sudo npm install -g pm2
   pm2 start server.js --name pharmacy-api
   pm2 startup
   pm2 save
   ```
   
   Or use nohup for simple deployment:
   ```bash
   nohup npm start > server.log 2>&1 &
   ```

8. **Update Frontend Configuration**
   
   Edit `frontend/js/config.js`:
   ```javascript
   const API_BASE_URL = 'http://YOUR_EC2_PUBLIC_IP:3000';
   ```

9. **Serve Frontend Files**
   
   **Option 1: Using nginx**
   ```bash
   sudo apt-get install -y nginx
   sudo cp -r frontend/* /var/www/html/
   sudo systemctl restart nginx
   ```
   
   **Option 2: Direct file access**
   Upload frontend files to your server and access via file:// or use a simple HTTP server

10. **Access Your Application**
    - Frontend: `http://YOUR_EC2_PUBLIC_IP` (if using nginx)
    - API: `http://YOUR_EC2_PUBLIC_IP:3000`

### Production Recommendations

1. **Use HTTPS**
   ```bash
   sudo apt-get install certbot python3-certbot-nginx
   sudo certbot --nginx
   ```

2. **Set up nginx as reverse proxy**
   ```nginx
   location /api {
       proxy_pass http://localhost:3000;
       proxy_http_version 1.1;
       proxy_set_header Upgrade $http_upgrade;
       proxy_set_header Connection 'upgrade';
       proxy_set_header Host $host;
       proxy_cache_bypass $http_upgrade;
   }
   ```

3. **Configure firewall**
   ```bash
   sudo ufw allow 22
   sudo ufw allow 80
   sudo ufw allow 443
   sudo ufw enable
   ```

4. **Monitor with PM2**
   ```bash
   pm2 status
   pm2 logs pharmacy-api
   pm2 monit
   ```

## 💾 Data Persistence

### Important Notes

- **In-Memory Storage**: All data is stored in JavaScript arrays in RAM
- **Data Lifetime**: Data persists only while the Node.js server process is running
- **Reset Behavior**: When the server restarts, all data resets to initial sample data
- **No Database Required**: Perfect for development, testing, and demos

### When Server Restarts, You Lose:
- New medicines added
- New customers created
- Sales transactions made
- Stock updates

### Why In-Memory Storage?

**Advantages:**
- ✅ Zero database setup
- ✅ Instant deployment
- ✅ Perfect for development and testing
- ✅ Fast data access
- ✅ No database maintenance
- ✅ Easy to reset to clean state

**Best For:**
- Development and testing
- Demonstrations and prototypes
- Educational purposes
- Proof of concepts
- Quick deployments

### Migrating to Persistent Storage

To add permanent storage, you can easily integrate a database:

**Option 1: MongoDB**
```bash
npm install mongoose
```

**Option 2: PostgreSQL**
```bash
npm install pg
```

**Option 3: MySQL**
```bash
npm install mysql2
```

The modular structure makes it easy to replace the storage layer without changing the API routes.

## 🔒 Security Features

### Implemented Security
- ✅ Input validation on all endpoints
- ✅ XSS protection with HTML escaping
- ✅ CORS configuration
- ✅ Error handling without sensitive info exposure
- ✅ Type checking and sanitization

### Additional Recommendations for Production
1. Add authentication (JWT, OAuth)
2. Implement rate limiting
3. Use HTTPS/TLS encryption
4. Add request logging and monitoring
5. Implement API key authentication
6. Add input sanitization middleware
7. Set secure HTTP headers

## 🐛 Troubleshooting

### Server Won't Start

**Error: Port 3000 already in use**
```bash
# Find and kill process using port 3000
lsof -ti:3000 | xargs kill -9

# Or use a different port
PORT=3001 npm start
```

### CORS Errors

**Error: Access-Control-Allow-Origin**
- Ensure backend server is running
- Check frontend config.js has correct API URL
- Verify CORS middleware is loaded in server.js

### Frontend Can't Connect to Backend

**Check the following:**
1. Backend server is running: `curl http://localhost:3000/api/health`
2. Correct API URL in `frontend/js/config.js`
3. No firewall blocking port 3000
4. Browser console for specific error messages

### Data Disappeared

**Reason:** Server restarted, data reset to initial state
**Solution:** This is expected behavior with in-memory storage. For persistent data, integrate a database.

### Module Not Found Error

```bash
# Reinstall dependencies
cd backend
rm -rf node_modules package-lock.json
npm install
```

## 📊 Testing the API

### Health Check
```bash
curl http://localhost:3000/api/health
```

### Get All Medicines
```bash
curl http://localhost:3000/api/medicines
```

### Create a Sale
```bash
curl -X POST http://localhost:3000/api/sales \
  -H "Content-Type: application/json" \
  -d '{
    "customerId": 1,
    "paymentMethod": "Cash",
    "items": [{"medicineId": 1, "quantity": 2}]
  }'
```

### Check Dashboard Stats
```bash
curl http://localhost:3000/api/reports/dashboard
```

## 🎨 Frontend Pages

1. **Dashboard (index.html)** - Overview with statistics and quick actions
2. **Inventory (inventory.html)** - Manage medicines with full CRUD operations
3. **Sales (sales.html)** - Point-of-sale system with cart functionality
4. **Customers (customers.html)** - Customer management with purchase history
5. **Reports (reports.html)** - Analytics and reports

## 📝 Future Enhancements

- [ ] Add user authentication and authorization
- [ ] Implement persistent database storage
- [ ] Add export functionality (PDF, Excel)
- [ ] Implement barcode scanning for medicines
- [ ] Add prescription management
- [ ] Email notifications for low stock and expiring medicines
- [ ] Multi-pharmacy branch support
- [ ] Advanced analytics and charts
- [ ] Mobile app (React Native)

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📞 Support

For issues, questions, or contributions:
- GitHub Issues: [Create an issue](https://github.com/youssef3fifi/jomanah-project/issues)
- Email: support@pharmacy.com

## 🙏 Acknowledgments

Built with ❤️ for efficient pharmacy management.

---

**Note**: This system uses in-memory storage for simplicity. For production use with permanent data storage, integrate a database (MongoDB, PostgreSQL, MySQL) following the same API structure.
