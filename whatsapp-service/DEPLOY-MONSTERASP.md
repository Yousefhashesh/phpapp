# Deploy to MonsterASP.net (site91992 — shipyaex.runasp.net)

MonsterASP runs Node.js behind IIS via `httpPlatformHandler`. Two hard rules:

1. The app **must listen on `process.env.PORT`** — `index.js` already does this.
2. There is **no npm on the server** — you upload `node_modules` *and* the Chrome
   binary yourself via FTP.

## 1. Build the deployment folder locally (Windows)

```bat
cd E:\work\phpapp\whatsapp-service
npm install

:: Copy Chrome from the local puppeteer cache into .\chrome
:: (adjust the win64-... folder to the version listed in your cache)
xcopy "%USERPROFILE%\.cache\puppeteer\chrome\win64-152.0.7977.75\chrome-win64" chrome\ /E /I
```

Check the cache version first: `dir "%USERPROFILE%\.cache\puppeteer\chrome"`.
The final layout must contain `chrome\chrome.exe`.

`index.js` also honours `PUPPETEER_EXECUTABLE_PATH` and falls back to puppeteer's
default lookup if `chrome\chrome.exe` is missing.

## 2. Set the shared secret

Generate one secret and put the **same value** in both places:

- `web.config` → `<environmentVariable name="WHATSAPP_API_SECRET" value="...">`
- Shipya `.env` (shipyaex.com) → `WHATSAPP_API_SECRET=...`

Example: `python -c "import secrets; print(secrets.token_urlsafe(32))"`

## 3. Upload via FTP

From the MonsterASP panel: site **site91992**, FTP login `site91992`, website
root `\wwwroot`.

1. **Delete** the Next.js placeholder files currently in `\wwwroot`.
2. Upload the whole `whatsapp-service` folder contents **except** `.wwebjs_auth`
   and `.wwebjs_cache` (they will be created on the server):
   - `index.js`, `package.json`, `web.config`, `node_modules\`, `chrome\`
3. Create an empty `logs` folder in `\wwwroot` (for `whatsapp-*.log` stdout).

The upload is ~700 MB (node_modules + Chrome). Use FTP passive mode (FileZilla);
expect it to take a while — it is one-time. Updates later are single files.

## 4. Verify the service

Open <https://shipyaex.runasp.net/status> (add header `X-Api-Secret: <secret>`
if set, e.g. with curl or the browser is fine when the secret is empty — it is
not, so use curl):

```bash
curl -H "X-Api-Secret: <secret>" https://shipyaex.runasp.net/status
```

Expect `{"success":true,"ready":false,"status":"qr", ...}` after ~1–2 minutes
(cold start launches Chrome). If you get 502.3, the process is still starting —
`startupTimeLimit` in `web.config` is set to 180 s for this reason.

## 5. Point Shipya at the service

In the Shipya dashboard (shipyaex.com) → **Settings → WhatsApp**:

1. Set the service URL to `https://shipyaex.runasp.net` and save.
2. Scan the QR code shown on the settings page with the dedicated WhatsApp phone.
3. Click **تحديث الجروبات**, pick the default group and per-client group mappings.
4. Save, then create/edit a test order and confirm the message lands in the group.

The provider is chosen automatically by URL: `*.ultramsg.com` → UltraMsg API,
anything else → this service. **Rollback = set the service URL back to the
UltraMsg URL in settings.** Nothing else to change.

## 6. Keep-alive

The free plan idle-kills the Node process (~20 min without requests), dropping
the WhatsApp session. The Shipya scheduler pings `/status` every 5 minutes
(`whatsapp-service-keepalive`, added in `bootstrap/app.php`) — make sure the
every-minute cron for `php artisan schedule:run` is running on shipyaex.com.

## Troubleshooting

| Symptom | Check | Fix |
| --- | --- | --- |
| 502.3 / site down | `\wwwroot\logs\whatsapp-*.log` | Cold start still running; retry after 2 min |
| Process exits with heap/OOM errors | same log | Free plan is 256 MB RAM; Chrome needs more. Upgrade MonsterASP (~$2.50/mo) or host `whatsapp-service` on a small VPS (PM2: `ecosystem.config.cjs`, supervisor conf in `deploy/supervisor/`) |
| `status: "qr"` again after days | — | Normal after host recycles; scan QR once from Settings → WhatsApp (session files persist in `.wwebjs_auth`) |
| `initialize_failed` | log mentions Chrome path | Ensure `chrome\chrome.exe` was uploaded; or set `PUPPETEER_EXECUTABLE_PATH` in `web.config` |

## Risks

- **Unofficial API** (whatsapp-web.js): use a dedicated WhatsApp number and keep
  the send rate moderate — ban risk.
- **Free-plan RAM (256 MB)** is below what Chrome usually wants; the extra
  `--disable-gpu`/`--disable-dev-shm-usage` flags help, but if it crash-loops,
  move to a paid plan or a VPS.
