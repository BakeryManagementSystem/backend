# 🐳 Docker Deployment Guide

This guide will help you deploy the Bakery Management System backend using Docker Desktop.

## 📋 Prerequisites

- Docker Desktop installed and running
- Git (to clone the repository)
- At least 4GB of available RAM

## 🚀 Quick Start

1. **Clone the repository** (if not already done)
   ```bash
   git clone <your-repo-url>
   cd bakery-backend
   ```

2. **Run the deployment script**
   ```bash
   deploy.bat
   ```
   Select option `1` to build and start containers.

3. **Access your application**
   - **API Backend**: http://localhost:8000
   - **phpMyAdmin**: http://localhost:8080
   - **Database**: localhost:3306

## 📁 Docker Files Created

- `Dockerfile` - Defines the PHP/Laravel application container
- `docker-compose.yml` - Orchestrates multiple services (app, database, phpMyAdmin)
- `.dockerignore` - Excludes unnecessary files from Docker build
- `.env.docker` - Environment configuration for Docker
- `deploy.bat` - Windows batch script for easy deployment management

## 🏗️ Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Laravel App   │    │   MySQL 8.0     │    │   phpMyAdmin    │
│   (Port 8000)   │◄──►│   (Port 3306)   │◄──►│   (Port 8080)   │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## 🔧 Manual Commands

If you prefer using Docker commands directly:

### Build and Start
```bash
# Copy environment file
copy .env.docker laravel\.env

# Start services
docker-compose up --build -d
```

### Database Operations
```bash
# Run migrations
docker-compose exec app php artisan migrate --force

# Seed database
docker-compose exec app php artisan db:seed

# Generate application key
docker-compose exec app php artisan key:generate --force
```

### Maintenance
```bash
# View logs
docker-compose logs -f app

# Access container shell
docker-compose exec app bash

# Stop services
docker-compose down

# Stop and remove volumes
docker-compose down -v
```

## 🗄️ Database Access

### Via phpMyAdmin
- URL: http://localhost:8080
- Server: `db`
- Username: `bakery_user`
- Password: `bakery_password`

### Via MySQL Client
```bash
mysql -h localhost -P 3306 -u bakery_user -p bakery_db
# Password: bakery_password
```

## 🔒 Environment Variables

Key environment variables in `.env.docker`:

```env
APP_URL=http://localhost:8000
DB_HOST=db
DB_DATABASE=bakery_db
DB_USERNAME=bakery_user
DB_PASSWORD=bakery_password
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000
OPENAI_API_KEY=your-openai-api-key-here
```

## 🐛 Troubleshooting

### Port Conflicts
If ports 8000, 3306, or 8080 are already in use:

```yaml
# Edit docker-compose.yml
ports:
  - "8001:80"  # Change 8000 to 8001
```

### Permission Issues
```bash
# Fix Laravel permissions
docker-compose exec app chown -R www-data:www-data /var/www/html
docker-compose exec app chmod -R 755 /var/www/html/storage
```

### Database Connection Issues
```bash
# Check if database is running
docker-compose ps

# View database logs
docker-compose logs db
```

### Clear All Docker Data
```bash
# Stop and remove everything
docker-compose down -v
docker system prune -a
```

## 📊 Monitoring

### Container Status
```bash
docker-compose ps
```

### Resource Usage
```bash
docker stats
```

### Application Logs
```bash
# Application logs
docker-compose logs -f app

# Database logs
docker-compose logs -f db

# All services
docker-compose logs -f
```

## 🔄 Updates

To update the application:

```bash
# Pull latest changes
git pull

# Rebuild containers
docker-compose up --build -d

# Run migrations if needed
docker-compose exec app php artisan migrate --force
```

## 🚀 Production Deployment

For production deployment:

1. Update `.env.docker` with production values
2. Set `APP_DEBUG=false`
3. Use a strong `DB_PASSWORD`
4. Configure proper `APP_URL`
5. Add SSL certificate configuration
6. Use Docker secrets for sensitive data

## 📞 Support

If you encounter issues:

1. Check the troubleshooting section above
2. View container logs: `docker-compose logs -f`
3. Verify Docker Desktop is running
4. Ensure no port conflicts exist
5. Contact the development team

---

**Created for**: Bakery Management System  
**Course**: CSE 3104 Database Lab  
**Institution**: Ahsanullah University of Science and Technology
