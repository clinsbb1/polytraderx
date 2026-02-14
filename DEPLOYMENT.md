# PolyTraderX Deployment Guide — Hostinger VPS

## Prerequisites
- Hostinger VPS with Ubuntu 22.04+
- SSH access
- Domain: bot.polytraderx.sbs pointed to VPS IP

## 1. Server Setup

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2 + extensions
sudo apt install php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd php8.2-intl -y

# Install MySQL 8
sudo apt install mysql-server -y
sudo mysql_secure_installation

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js 18+ (for asset compilation)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install nodejs -y

# Install Nginx
sudo apt install nginx -y

# Install Supervisor (for queue worker)
sudo apt install supervisor -y
```

## 2. Database

```sql
sudo mysql
CREATE DATABASE polytraderx CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'polytraderx'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON polytraderx.* TO 'polytraderx'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 3. Deploy Code

```bash
cd /var/www
git clone YOUR_REPO_URL polytraderx
cd polytraderx

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# Copy and edit environment
cp .env.example .env
nano .env
# Set: APP_ENV=production, APP_DEBUG=false, APP_URL, DB creds, all API keys

# Generate key + run migrations
php artisan key:generate
php artisan migrate --seed
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Required .env Variables

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bot.polytraderx.sbs
APP_TIMEZONE=Africa/Lagos

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=polytraderx
DB_USERNAME=polytraderx
DB_PASSWORD=YOUR_STRONG_PASSWORD

QUEUE_CONNECTION=database

# Set in Platform Settings (admin panel) after first login:
# ANTHROPIC_API_KEY, TELEGRAM_BOT_TOKEN, NOWPAYMENTS_API_KEY, NOWPAYMENTS_IPN_SECRET
```

## 4. Nginx Config

```bash
sudo nano /etc/nginx/sites-available/polytraderx
```

```nginx
server {
    listen 80;
    server_name bot.polytraderx.sbs;
    root /var/www/polytraderx/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    charset utf-8;
    client_max_body_size 10M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 120;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/polytraderx /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx
```

## 5. SSL (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d bot.polytraderx.sbs
# Follow prompts, auto-redirect HTTP → HTTPS
```

## 6. Crontab

```bash
sudo crontab -e -u www-data
```

Add:
```
* * * * * cd /var/www/polytraderx && php artisan schedule:run >> /dev/null 2>&1
```

### Scheduled Commands (automatic via `routes/console.php`)

| Frequency | Command | Purpose |
|-----------|---------|---------|
| Every minute | `bot:scan-markets` | Find active 15-min markets |
| Every minute | `bot:execute-trades` | Place orders in entry window |
| Every minute | `bot:monitor-positions` | Track & resolve open positions |
| Every 5 min | `bot:ai-analyze-markets` | Haiku pre-analysis |
| Every 5 min | `bot:ai-audit-losses` | Sonnet post-loss forensics |
| Every 15 min | `bot:snapshot-balance` | Record balance snapshot |
| Daily 23:55 | `bot:daily-review` | Sonnet daily analysis |
| Weekly Sun 23:55 | `bot:weekly-report` | Sonnet weekly deep analysis |
| Daily 00:05 | `bot:daily-summary` | Compile stats + Telegram |
| Daily 02:00 | `bot:cleanup-logs` | Prune old trade logs |
| Hourly | `subscriptions:check-expired` | Deactivate expired subscriptions |

## 7. Queue Worker (Supervisor)

```bash
sudo nano /etc/supervisor/conf.d/polytraderx-worker.conf
```

```ini
[program:polytraderx-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/polytraderx/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/polytraderx/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start polytraderx-worker:*
```

## 8. Telegram Webhook Registration

After deployment, register the webhook:

```bash
curl -X POST "https://api.telegram.org/bot{YOUR_BOT_TOKEN}/setWebhook?url=https://bot.polytraderx.sbs/api/webhooks/telegram"
```

Verify:
```bash
curl "https://api.telegram.org/bot{YOUR_BOT_TOKEN}/getWebhookInfo"
```

## 9. NTP (Critical for 15-min market timing)

```bash
sudo timedatectl set-ntp true
timedatectl status
# Verify NTP synchronized: yes
# Verify timezone: Africa/Lagos
sudo timedatectl set-timezone Africa/Lagos
```

## 10. Post-Deploy Checklist

- [ ] Visit https://bot.polytraderx.sbs — landing page loads
- [ ] Register a new account — redirects to onboarding
- [ ] Login as super admin (seeded account) — admin panel loads
- [ ] Configure Platform Settings: Anthropic API key, Telegram bot token
- [ ] Check `/health` endpoint — all services green
- [ ] Verify cron: `php artisan schedule:list` shows all commands
- [ ] Verify queue: `sudo supervisorctl status` shows workers running
- [ ] Test Telegram: send `/start` to your bot
- [ ] Check bot logs: `tail -f storage/logs/bot.log`
- [ ] First user defaults to DRY_RUN=true
- [ ] Monitor for 1 week in DRY_RUN mode before going live
- [ ] Review simulated trades — are signals sensible?
- [ ] When confident: set DRY_RUN=false via Strategy page

## Maintenance Commands

```bash
# Clear all caches
php artisan optimize:clear

# Re-cache for production
php artisan optimize

# View bot logs
tail -f storage/logs/bot.log

# View Laravel logs
tail -f storage/logs/laravel.log

# Check scheduler is running
php artisan schedule:list

# Run a specific command manually
php artisan bot:scan-markets
php artisan bot:execute-trades
php artisan bot:monitor-positions

# Restart queue workers after code update
sudo supervisorctl restart polytraderx-worker:*

# Check queue status
php artisan queue:monitor database

# Deploy update
cd /var/www/polytraderx
git pull
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate
php artisan optimize
sudo supervisorctl restart polytraderx-worker:*
```

## Troubleshooting

### Bot not trading
1. Check `storage/logs/bot.log` for errors
2. Verify cron is running: `crontab -l -u www-data`
3. Check user has `is_active = true` and valid subscription
4. Verify DRY_RUN setting (Strategy page)
5. Check Polymarket API credentials (Settings > Credentials)

### Queue jobs stuck
```bash
php artisan queue:retry all
sudo supervisorctl restart polytraderx-worker:*
```

### Database issues
```bash
php artisan migrate:status
php artisan migrate
```

### SSL certificate renewal
Certbot auto-renews. To manually test:
```bash
sudo certbot renew --dry-run
```

### High memory usage
Check PHP-FPM pool config:
```bash
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
# Recommended: pm.max_children = 10, pm.start_servers = 3
sudo systemctl restart php8.2-fpm
```
