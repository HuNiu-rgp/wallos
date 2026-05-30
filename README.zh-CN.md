# Wallos

简体中文 | [English](README.md)

Wallos 是一个开源的个人订阅追踪和财务管理工具。

## 功能

- 默认中文界面，支持切换英文
- 登录、注册、个人资料和密码管理
- 普通用户和管理员权限
- 分类管理，支持自定义分类颜色
- 订阅管理，支持账单周期、下次支付时间、续费链接和 Logo
- 订阅 JSON 导入和导出
- 仪表盘，显示有效订阅、月付、年付和即将扣费项目
- 日历视图，按日期查看即将到期的订阅
- 邮件、Telegram 和 Webhook 到期通知
- 深色和浅色主题
- 系统设置和用户管理

## 截图

### 仪表盘

![仪表盘](images/dashboard.png)

### 订阅

![订阅](images/subscriptions.png)

### 分类

![分类](images/categories.png)

### 日历

![日历](images/calendar.png)

### 系统设置

![系统设置](images/settings.png)

## 技术栈

- Laravel 12
- SQLite
- Inertia.js + Vue 3
- Tailwind CSS
- Ant Design Vue
- Pest
- Docker Compose

## Docker 安装

推荐使用 Docker Compose 安装。它会同时启动 Web 服务和订阅到期通知所需的定时任务。

```bash
mkdir wallos && cd wallos
curl -fsSL https://raw.githubusercontent.com/HuNiu-rgp/wallos/main/docker-install.sh | sh
docker compose --env-file .env.docker up -d
```

容器健康检查通过后，打开 http://localhost:8001。

首次启动会创建 SQLite 数据库并导入默认管理员：

- 邮箱：`admin@qq.com`
- 密码：`123456`

首次登录后请修改默认密码。

SQLite 数据库、自动生成的应用密钥和存储文件都会保存在 Docker 数据卷中。重启或升级容器不会重置数据，也不会恢复管理员默认密码。

### 配置

如需修改端口、公开访问地址或时区，请编辑 `docker-compose.yml` 旁边的 `.env.docker`：

```dotenv
WALLOS_PORT=8001
APP_URL=http://localhost:8001
ASSET_URL=http://localhost:8001
TRUSTED_PROXIES=*
TZ=Asia/Shanghai
```

再次运行安装脚本会更新 `docker-compose.yml`，但不会覆盖已经存在的 `.env.docker`。

### 升级

```bash
docker compose --env-file .env.docker pull
docker compose --env-file .env.docker up -d
```

### 故障排查

如果页面仍然尝试从 `5173` 端口加载资源，请刷新生产容器：

```bash
docker compose --env-file .env.docker pull
docker compose --env-file .env.docker up -d --force-recreate
```

### 备份

```bash
docker run --rm \
  -v wallos_wallos-data:/data/database:ro \
  -v wallos_wallos-storage:/data/storage:ro \
  -v "$PWD":/backup \
  alpine tar czf /backup/wallos-backup.tar.gz -C /data .
```

## 本地开发

如果直接在本机运行，请准备 Node.js 20+ 和 PHP 8.2+。

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

使用 Docker 进行源码开发：

```bash
cp .env.example .env
docker compose -f docker-compose.dev.yml up --build
```

应用运行在 http://localhost:8001，Vite 运行在 http://localhost:5173。

## Docker 镜像

Docker Hub 提供适用于 `linux/amd64` 和 `linux/arm64` 的多平台镜像：

```bash
docker pull gege188/wallos:v1.0.4
```

生产镜像内置 Nginx 和 PHP-FPM，容器对外监听 `8000` 端口。

[Docker Hub：gege188/wallos](https://hub.docker.com/r/gege188/wallos)

## 定时通知

Docker Compose 会启动 `scheduler` 服务。系统每天 12:00 检查即将到期的订阅，并根据系统设置发送邮件、Telegram 或 Webhook 通知。

## 数据说明

金额使用整数分（`amount_cents`）存储，避免浮点数计算误差。

## 测试

```bash
php artisan test
```
