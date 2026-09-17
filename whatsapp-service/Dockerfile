# Render.com image for the Shipya WhatsApp bridge (whatsapp-web.js).
# Chrome comes from Debian's chromium package (puppeteer's own download is
# skipped); whatsapp-web.js finds it via PUPPETEER_EXECUTABLE_PATH.
FROM node:20-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        chromium \
        ca-certificates \
        fonts-liberation \
        libasound2 \
        libatk-bridge2.0-0 \
        libatk1.0-0 \
        libcairo2 \
        libcups2 \
        libdbus-1-3 \
        libexpat1 \
        libfontconfig1 \
        libgbm1 \
        libglib2.0-0 \
        libgtk-3-0 \
        libnspr4 \
        libnss3 \
        libpango-1.0-0 \
        libpangocairo-1.0-0 \
        libx11-6 \
        libx11-xcb1 \
        libxcb1 \
        libxcomposite1 \
        libxdamage1 \
        libxext6 \
        libxfixes3 \
        libxrandr2 \
        libxrender1 \
        libxss1 \
        libxtst6 \
        xdg-utils \
    && rm -rf /var/lib/apt/lists/*

ENV PUPPETEER_SKIP_DOWNLOAD=true \
    PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium \
    NODE_ENV=production

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --omit=dev

COPY index.js ./

# Render injects PORT; index.js binds WHATSAPP_HOST (0.0.0.0 via render.yaml).
CMD ["node", "index.js"]
