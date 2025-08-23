# 🍞 Bakery Management System - Backend API

A robust Laravel-based REST API backend for the Bakery Management System, providing secure data management, business logic, and AI-powered features for bakery operations.

## 📌 Project Overview

This is the server-side application of the Bakery Management System, built with Laravel and MySQL. It provides a comprehensive REST API for managing products, inventory, orders, customers, expenses, and generating business analytics.

## 👥 Team Members

<div align="center">

| Member | Student ID | Role | WakaTime Stats |
|--------|------------|------|----------------|
| **Adel Mohammad Zahid** | 20220204057 | Project Setup & Dashboard | [![wakatime](https://wakatime.com/badge/user/arany-hasan-uuid.svg)](https://wakatime.com/@arany-hasan) |
| **Saleh Mahmud Sami** | 20220204061 | Product & Inventory UI | [![wakatime](https://wakatime.com/badge/user/adel-zahid-uuid.svg)](https://wakatime.com/@adel-zahid) |
| **Md. Rubayet Islam** | 20220204069 | Orders & Analytics UI | [![wakatime](https://wakatime.com/badge/user/rehnuma-tarannum-uuid.svg)](https://wakatime.com/@rehnuma-tarannum) |
| **Abhishek Sarker** | 20220204104 | Expense & AI Interface | [![wakatime](https://wakatime.com/badge/user/rubayet-islam-uuid.svg)](https://wakatime.com/@rubayet-islam) |

</div>

### Team Contributions Dashboard
[![Team WakaTime Stats](https://github-readme-stats.vercel.app/api/wakatime?username=your-team-username&layout=compact&theme=radical)](https://wakatime.com/@your-team-username)

## 🎯 Core Features

### 🔐 Authentication & Authorization
- JWT-based authentication using Laravel Sanctum
- Role-based access control (Admin, Staff)
- Secure password hashing and validation

### 🧁 Product Management
- Complete CRUD operations for products and categories
- Product-ingredient relationship management
- Image upload and storage

### 📦 Inventory Management
- Real-time stock level tracking
- Automatic stock deduction on sales
- Low stock alerts and notifications

### 🛒 Order Processing
- Customer management system
- Order creation and status tracking
- Sales recording and analytics

### 💰 Financial Management
- Expense tracking and categorization
- Cost analysis and profit calculations
- Revenue and expense reporting

### 📊 Reporting & Analytics
- Comprehensive business reports
- Sales analytics and trends
- Customer behavior analytics

### 🤖 AI Integration
- OpenAI API integration for business insights
- Natural language query processing
- Intelligent recommendations

## 🛠 Technology Stack

- **Framework**: Laravel 10.x
- **Database**: MySQL 8.0
- **Authentication**: Laravel Sanctum
- **ORM**: Eloquent
- **File Storage**: Laravel Storage
- **API Documentation**: Laravel Swagger/OpenAPI
- **Testing**: PHPUnit

## 🚀 Getting Started

### Prerequisites
- PHP >= 8.1
- Composer
- MySQL >= 8.0

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/bakery-backend.git
   cd bakery-backend
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure Environment Variables**
   ```env
   APP_NAME="Bakery Management System API"
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=bakery_db
   DB_USERNAME=root
   DB_PASSWORD=

   SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000
   OPENAI_API_KEY=your-openai-api-key
   ```

5. **Database Setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Start Development Server**
   ```bash
   php artisan serve
   ```

## 📁 Project Structure

```
app/
├── Http/
│   ├── Controllers/        # API Controllers
│   ├── Middleware/         # Custom middleware
│   ├── Requests/           # Form request validation
│   └── Resources/          # API resources
├── Models/                 # Eloquent models
├── Services/               # Business logic services
└── Observers/              # Model observers
database/
├── migrations/             # Database migrations
└── seeders/               # Database seeders
routes/
└── api.php                # API routes
```

## 🗄️ Database Schema

### Core Tables
- **Users**: Authentication and role management
- **Products**: Product catalog with categories
- **Ingredients**: Inventory items and stock levels
- **Orders**: Customer orders and order items
- **Customers**: Customer information
- **Expenses**: Business expense tracking

## 🔌 API Endpoints

### Authentication
```
POST   /api/auth/login           # User login
POST   /api/auth/logout          # User logout
GET    /api/auth/user            # Get authenticated user
```

### Products
```
GET    /api/products             # List all products
POST   /api/products             # Create new product
GET    /api/products/{id}        # Get single product
PUT    /api/products/{id}        # Update product
DELETE /api/products/{id}        # Delete product
```

### Inventory
```
GET    /api/ingredients          # List all ingredients
POST   /api/ingredients          # Add new ingredient
GET    /api/inventory/alerts     # Get low stock alerts
POST   /api/inventory/adjust     # Adjust stock levels
```

### Orders
```
GET    /api/orders               # List orders
POST   /api/orders               # Create new order
GET    /api/customers            # List customers
POST   /api/customers            # Create customer
```

### Reports & Analytics
```
GET    /api/reports/sales        # Sales reports
GET    /api/reports/inventory    # Inventory reports
GET    /api/analytics/dashboard  # Dashboard analytics
```

### AI Assistant
```
POST   /api/ai/query             # Process AI query
GET    /api/ai/suggestions       # Get AI suggestions
```

## 📋 Development Checkpoints

### Checkpoint 1: Foundation ✅
- [ ] Database schema design and migration
- [ ] User authentication system
- [ ] Basic API structure

### Checkpoint 2: Core Features 🔄
- [ ] Product management CRUD API
- [ ] Inventory management system
- [ ] Order processing and sales management
- [ ] Expense tracking

### Checkpoint 3: Advanced Features ⏳
- [ ] Reporting and analytics API
- [ ] AI assistant integration
- [ ] Performance optimization

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/ProductTest.php
```

## 🚀 Deployment

### Production Setup
```bash
composer install --optimize-autoloader --no-dev
php artisan key:generate
php artisan migrate --force
php artisan config:cache
```

## 🔗 Related Repositories

- **Frontend React App**: [bakery-frontend](https://github.com/BakeryManagementSystem/frontend)

## 📄 License

This project is developed for **CSE 3104 Database Lab** at Ahsanullah University of Science and Technology and is intended for academic purposes.

---

**Course**: CSE 3104 Database Lab  
**Institution**: Ahsanullah University of Science and Technology  
**Semester**: Fall 2024
