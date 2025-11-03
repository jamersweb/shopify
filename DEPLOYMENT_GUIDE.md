# 🚀 Laravel Deployment Guide - Complete File Structure

## ❌ **Common Deployment Mistake**
Many people only upload the `public` folder, but Laravel needs the **entire project structure** to work properly.

## ✅ **Correct Deployment Structure**

You need to upload **ALL** these files and folders to your server:

### **📁 Root Directory Files:**
```
ecofreight-shopify/
├── artisan                    ← Laravel command line tool
├── composer.json              ← Dependencies configuration
├── composer.lock              ← Locked dependency versions
├── .env                       ← Environment configuration (IMPORTANT!)
├── .env.example               ← Environment template
├── README.md                  ← Documentation
├── FINAL_SETUP_GUIDE.md       ← Setup guide
├── SHOPIFY_PARTNER_SETUP.md   ← Shopify configuration
└── test-credentials.php       ← Testing script
```

### **📁 Core Laravel Directories:**
```
├── app/                       ← Application code
│   ├── Console/               ← Artisan commands
│   ├── Exceptions/            ← Error handling
│   ├── Http/                  ← Controllers, Middleware
│   ├── Jobs/                  ← Background jobs
│   ├── Models/                ← Database models
│   └── Providers/             ← Service providers
├── bootstrap/                 ← Application bootstrap
│   ├── app.php                ← Main bootstrap file
│   └── cache/                 ← Bootstrap cache
├── config/                    ← Configuration files
│   ├── app.php                ← App configuration
│   ├── database.php           ← Database config
│   ├── cache.php              ← Cache config
│   ├── session.php            ← Session config
│   ├── logging.php            ← Logging config
│   └── view.php               ← View config
├── database/                  ← Database files
│   └── migrations/            ← Database migrations
├── public/                    ← Web root (what users access)
│   ├── index.php              ← Entry point
│   └── .htaccess              ← Apache configuration
├── resources/                 ← Views, assets
│   └── views/                 ← Blade templates
├── routes/                    ← Route definitions
│   ├── web.php                ← Web routes
│   ├── api.php                ← API routes
│   └── console.php            ← Console routes
├── storage/                   ← File storage
│   ├── app/                   ← App storage
│   ├── framework/             ← Framework cache
│   └── logs/                  ← Log files
└── vendor/                    ← Composer dependencies
    └── [all vendor packages]  ← Third-party libraries
```

## 🔧 **Deployment Steps:**

### **Step 1: Upload Complete Project**
Upload **ALL** files and folders to your server:
```bash
# Upload everything except .git folder
rsync -av --exclude='.git' ./ user@your-server.com:/path/to/your/app/
```

### **Step 2: Set Web Root**
Configure your web server to point to the `public` folder:

#### **Apache (.htaccess in public folder):**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

#### **Nginx Configuration:**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/your/app/public;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### **Step 3: Set Permissions**
```bash
# Set proper permissions
chmod -R 755 /path/to/your/app
chmod -R 775 /path/to/your/app/storage
chmod -R 775 /path/to/your/app/bootstrap/cache
```

### **Step 4: Configure Environment**
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Update database credentials in .env
# Update APP_URL to your domain
```

### **Step 5: Install Dependencies**
```bash
# Install Composer dependencies
composer install --optimize-autoloader --no-dev

# Run migrations
php artisan migrate --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🚨 **Critical Files You Must Upload:**

### **Essential Files (Cannot be missing):**
- ✅ `artisan` - Laravel command line tool
- ✅ `composer.json` & `composer.lock` - Dependencies
- ✅ `.env` - Environment configuration
- ✅ `app/` - All application code
- ✅ `config/` - All configuration files
- ✅ `database/` - Migrations and seeders
- ✅ `public/index.php` - Entry point
- ✅ `routes/` - All route files
- ✅ `storage/` - File storage
- ✅ `vendor/` - Composer dependencies

### **Files You Can Skip:**
- ❌ `.git/` - Version control (not needed on server)
- ❌ `node_modules/` - If using npm (not needed for PHP)
- ❌ `tests/` - Test files (optional on production)

## 🔍 **Verify Your Deployment:**

### **Check if all files are uploaded:**
```bash
# SSH into your server and check
ls -la /path/to/your/app/
# Should see: app, config, database, public, routes, storage, vendor, artisan, composer.json, .env
```

### **Test Laravel commands:**
```bash
cd /path/to/your/app
php artisan --version
# Should show: Laravel Framework 10.49.1
```

### **Test web access:**
Visit your domain - you should see your Laravel app, not a file listing.

## 🛠️ **Common Issues & Solutions:**

### **Issue: "Class not found" errors**
**Solution:** Missing `vendor/` folder or `composer install` not run

### **Issue: "Configuration not found" errors**
**Solution:** Missing `config/` folder or `.env` file

### **Issue: "Route not found" errors**
**Solution:** Missing `routes/` folder or `php artisan route:cache` not run

### **Issue: "Storage not writable" errors**
**Solution:** Wrong permissions on `storage/` folder

## 📋 **Deployment Checklist:**

- [ ] Upload **ALL** files and folders (not just public/)
- [ ] Set web server root to `public/` folder
- [ ] Set proper file permissions (755 for folders, 644 for files)
- [ ] Set writable permissions for `storage/` and `bootstrap/cache/`
- [ ] Copy `.env.example` to `.env` and configure
- [ ] Run `composer install --no-dev --optimize-autoloader`
- [ ] Run `php artisan key:generate`
- [ ] Run `php artisan migrate --force`
- [ ] Run `php artisan config:cache`
- [ ] Test your domain - should show Laravel app, not file listing

## 🎯 **Quick Fix for Current Issue:**

If you only uploaded the `public` folder, you need to:

1. **Upload the complete project** (all folders and files)
2. **Set web server root** to point to the `public` folder
3. **Run the deployment steps** above

Your Laravel app needs the complete structure to function properly! 🚀
