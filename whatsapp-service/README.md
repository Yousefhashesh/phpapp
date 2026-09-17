# Shipya WhatsApp Service

Bridge between Laravel and WhatsApp Web using `whatsapp-web.js`.

> Deploying to MonsterASP.net (shipyaex.runasp.net)? See
> [DEPLOY-MONSTERASP.md](DEPLOY-MONSTERASP.md) — IIS/httpPlatform specifics,
> shipping Chrome, FTP upload steps, and troubleshooting.

## Setup

```bash
cd whatsapp-service
npm install
```

## Environment

```bash
WHATSAPP_PORT=3001
WHATSAPP_HOST=127.0.0.1
WHATSAPP_API_SECRET=your-secret
WHATSAPP_GROUP_ID=120363123456789012@g.us
```

Set the same values in Laravel `.env`:

```env
WHATSAPP_ENABLED=true
WHATSAPP_SERVICE_URL=http://127.0.0.1:3001
WHATSAPP_API_SECRET=your-secret
WHATSAPP_GROUP_ID=
```

## Run on server (always on)

### Option 1: PM2 (recommended)

```bash
cd whatsapp-service
npm install
pm2 start ecosystem.config.cjs
pm2 save
pm2 startup
```

### Option 2: Supervisor

Copy `deploy/supervisor/shipya-whatsapp.conf` to `/etc/supervisor/conf.d/`, update the `directory` path, then:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start shipya-whatsapp
```

### Option 3: Manual

```bash
npm start
```

## Login & group selection

1. Start the service on the server.
2. Open **Settings → WhatsApp** in the Shipya dashboard.
3. Scan the QR code shown in the settings page.
4. After connection, click **تحديث الجروبات** and choose the target group.
5. Save settings.

## API

- `GET /status` — connection status
- `GET /qr` — QR image as data URL (when not connected)
- `GET /groups` — list WhatsApp groups
- `POST /send` — body: `{ "groupId": "...", "message": "..." }`
- Header: `X-Api-Secret: your-secret`

## Laravel scheduler

Notifications older than 5 days are pruned daily at 03:00. Ensure cron runs:

```bash
* * * * * cd /path/to/phpapp && php artisan schedule:run >> /dev/null 2>&1
```

Manual prune:

```bash
php artisan notifications:prune --days=5 --force
```
