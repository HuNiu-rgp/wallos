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

## Docker

```bash
cp .env.example .env
docker compose up --build
```

打开 http://localhost:8001。

Vite 运行在 http://localhost:5173。

首次创建数据库时会导入默认管理员：

- 邮箱：`admin@qq.com`
- 密码：`123456`

## 定时通知

Docker Compose 会启动 `scheduler` 服务。系统每天 12:00 检查即将到期的订阅，并根据系统设置发送邮件、Telegram 或 Webhook 通知。

## 数据说明

金额使用整数分（`amount_cents`）存储，避免浮点数计算误差。

## 测试

```bash
php artisan test
```
