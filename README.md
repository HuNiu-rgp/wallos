# Wallos

[简体中文](README.zh-CN.md) | English

Wallos is an open-source personal subscription tracking and finance management app.

## Features

- Chinese-first interface with an English language switcher
- Authentication, profile, and password management
- Income and expense categories
- Subscription tracking with billing cycles and due dates
- Dashboard with active subscriptions, monthly/yearly subscription totals, and upcoming charges

## Screenshots

### Dashboard

![Dashboard](images/dashboard.png)

### Subscriptions

![Subscriptions](images/subscriptions.png)

### Categories

![Categories](images/categories.png)

### Calendar

![Calendar](images/calendar.png)

### System Settings

![System Settings](images/settings.png)

## Stack

- Laravel 12
- SQLite
- Inertia.js + Vue 3
- Tailwind CSS
- Pest
- Docker Compose

## Docker Installation

Docker Compose is the recommended installation method. It starts the web app and the scheduler used for subscription reminders.

```bash
mkdir wallos && cd wallos
curl -fsSL https://raw.githubusercontent.com/HuNiu-rgp/wallos/main/docker-install.sh | sh
docker compose --env-file .env.docker up -d
```

Open http://localhost:8001 after the containers become healthy.

The first startup creates the SQLite database and seeds a default administrator:

- Email: `admin@qq.com`
- Password: `123456`

Change the default password after your first login.

The SQLite database, generated application key, and storage files are persisted in Docker volumes. Restarting or upgrading containers does not reset your data or administrator password.

### Configuration

Edit `.env.docker` beside `docker-compose.yml` if you need to change the port, public URL, or timezone:

```dotenv
WALLOS_PORT=8001
APP_URL=http://localhost:8001
ASSET_URL=http://localhost:8001
TRUSTED_PROXIES=*
TZ=Asia/Shanghai
```

Running the installation script again updates `docker-compose.yml` but keeps your existing `.env.docker`.

### Upgrade

```bash
docker compose --env-file .env.docker pull
docker compose --env-file .env.docker up -d
```

### Troubleshooting

If a page still tries to load assets from port `5173`, refresh the production container:

```bash
docker compose --env-file .env.docker pull
docker compose --env-file .env.docker up -d --force-recreate
```

### Backup

```bash
docker run --rm \
  -v wallos_wallos-data:/data/database:ro \
  -v wallos_wallos-storage:/data/storage:ro \
  -v "$PWD":/backup \
  alpine tar czf /backup/wallos-backup.tar.gz -C /data .
```

## Local Development

Use Node 20+ and PHP 8.2+ if you run the app directly on your machine.

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan storage:link
touch database/database.sqlite
php artisan migrate --seed
npm install
npm run dev
php artisan serve
```

To use Docker for source development:

```bash
cp .env.example .env
docker compose -f docker-compose.dev.yml up --build
```

The app runs on http://localhost:8001 and Vite runs on http://localhost:5173.

## Docker Image

Prebuilt multi-platform images for `linux/amd64` and `linux/arm64` are published on Docker Hub:

```bash
docker pull gege188/wallos:v1.0.4
```

The production image runs Nginx on port `8000` and PHP-FPM internally.

[Docker Hub: gege188/wallos](https://hub.docker.com/r/gege188/wallos)

## Data Storage

Money is stored as integer cents (`amount_cents`) to avoid floating-point rounding errors.

## Tests

```bash
php artisan test
```
