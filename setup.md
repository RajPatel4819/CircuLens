# CircuLens Setup Guide

## Requirements

- PHP 8.0+ with extensions: `pdo`, `pdo_mysql`, `json`, `fileinfo`
- MySQL 5.7+ or MariaDB 10.3+
- Python 3.9+ (for scraper)
- Apache / Nginx with mod_rewrite

---

## 1. Database Setup

```bash
mysql -u root -p < database.sql
```

Or import via phpMyAdmin.

**Sample credentials (for testing only):**
| Role  | Username/Email | Password |
|-------|----------------|----------|
| Admin | `admin`        | `admin123` |
| User  | `user@test.com`| `user123`  |

> ⚠️ Change passwords in production!

---

## 2. PHP Configuration

Edit `/config/config.php`:

```php
define('APP_URL',  'http://localhost/CircuLens');  // Change to your domain
define('DB_HOST',  'localhost');
define('DB_NAME',  'circulens');
define('DB_USER',  'root');
define('DB_PASS',  '');

// Email settings (for password reset & notifications)
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_PORT',     587);
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-app-password');
```

---

## 3. File Permissions

```bash
chmod 755 assets/uploads/
chmod +x scraper/cron_runner.sh
```

---

## 4. Python Scraper Setup

```bash
cd scraper/

# Create virtual environment
python3 -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate

# Install dependencies
pip install -r requirements.txt

# Download spaCy model (optional, for better classification)
python -m spacy download en_core_web_sm

# Configure environment
cp .env.example .env
# Edit .env with your database and email credentials

# Test the scraper
python scraper.py

# Test NLP classifier
python nlp_analyzer.py
```

---

## 5. Set Up Cron Job (Optional)

Run the scraper every 6 hours:

```bash
crontab -e
```

Add:
```
0 */6 * * * /path/to/CircuLens/scraper/cron_runner.sh
```

---

## 6. Web Server Configuration

### Apache (.htaccess)

Place in project root:
```apache
Options -Indexes
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /CircuLens/
</IfModule>
```

### Nginx

```nginx
location /CircuLens/ {
    try_files $uri $uri/ /CircuLens/index.php;
}
```

---

## Project Structure

```
CircuLens/
├── index.php           # Public homepage with circular listing
├── database.sql        # Database schema + sample data
├── setup.md            # This file
├── .gitignore
├── admin/              # Admin panel (requires login)
│   ├── login.php
│   ├── index.php       # Dashboard
│   ├── circulars.php   # Manage circulars
│   ├── add_circular.php
│   └── logout.php
├── user/               # User panel (requires login)
│   ├── login.php
│   ├── register.php
│   ├── forgot_password.php
│   ├── dashboard.php
│   ├── profile.php
│   ├── preferences.php
│   └── logout.php
├── api/                # JSON API endpoints
│   ├── circulars.php
│   ├── preferences.php
│   └── notifications.php
├── scraper/            # Python scraper + NLP
│   ├── scraper.py
│   ├── nlp_analyzer.py
│   ├── matcher.py
│   ├── notifier.py
│   ├── requirements.txt
│   ├── cron_runner.sh
│   └── .env.example
├── config/             # PHP configuration
│   ├── config.php
│   ├── database.php
│   └── auth.php
├── includes/           # Shared PHP templates
│   ├── header.php
│   ├── footer.php
│   ├── admin_header.php
│   ├── admin_footer.php
│   ├── user_header.php
│   └── user_footer.php
└── assets/
    ├── css/style.css
    ├── js/main.js
    └── uploads/        # PDF uploads
```

---

## API Reference

### Public Endpoints

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/api/circulars.php` | List circulars (paginated) |
| GET | `/api/circulars.php?id=N` | Get single circular |
| GET | `/api/circulars.php?type=examination` | Filter by type |
| GET | `/api/circulars.php?search=exam` | Search circulars |

### Authenticated Endpoints (requires session)

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/api/preferences.php` | Get user preferences |
| POST | `/api/preferences.php` | Save user preferences |
| GET | `/api/notifications.php` | Get user notifications |
| POST | `/api/notifications.php?mark_read=all` | Mark all as read |

---

## Troubleshooting

**"Database not connected" error:**
- Verify MySQL is running
- Check credentials in `config/config.php`
- Ensure the `circulens` database exists

**PDF uploads not working:**
- Check `assets/uploads/` exists and is writable
- Verify `upload_max_filesize` in php.ini (should be ≥ 10M)

**Scraper not working:**
- Ensure all Python dependencies are installed
- Check `scraper/.env` is configured
- GTU website may have changed structure — check selectors in `scraper.py`

**Emails not sending:**
- For Gmail: enable 2FA and use an App Password
- Check SMTP credentials in `config/config.php` and `scraper/.env`
