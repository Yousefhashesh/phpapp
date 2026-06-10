module.exports = {
  apps: [
    {
      name: 'shipya-whatsapp',
      cwd: __dirname,
      script: 'index.js',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '512M',
      env: {
        NODE_ENV: 'production',
        WHATSAPP_PORT: 3001,
        WHATSAPP_HOST: '127.0.0.1',
      },
    },
  ],
}
