const express = require('express')
const QRCode = require('qrcode')
const qrcodeTerminal = require('qrcode-terminal')
const { Client, LocalAuth } = require('whatsapp-web.js')

const PORT = Number(process.env.WHATSAPP_PORT || process.env.PORT ||3001 )
const HOST = process.env.WHATSAPP_HOST || '127.0.0.1'
const API_SECRET = process.env.WHATSAPP_API_SECRET || ''
const DEFAULT_GROUP_ID = process.env.WHATSAPP_GROUP_ID || ''

let isReady = false
let latestQr = null
let qrGeneratedAt = null
let statusText = 'starting'
let lastError = null
let lastDisconnectReason = null
let startedAt = new Date().toISOString()

const client = new Client({
  authStrategy: new LocalAuth({ dataPath: './.wwebjs_auth' }),
  puppeteer: {
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  },
})

client.on('qr', qr => {
  statusText = 'qr'
  latestQr = qr
  qrGeneratedAt = new Date().toISOString()
  lastError = null
  console.log('\nScan this QR code with WhatsApp on your phone:\n')
  qrcodeTerminal.generate(qr, { small: true })
})

client.on('ready', () => {
  statusText = 'ready'
  isReady = true
  latestQr = null
  qrGeneratedAt = null
  lastError = null
  lastDisconnectReason = null
  console.log('WhatsApp client is ready.')
})

client.on('auth_failure', message => {
  statusText = 'auth_failure'
  lastError = String(message || 'Authentication failed')
  console.error('WhatsApp auth failure:', message)
  isReady = false
})

client.on('disconnected', reason => {
  statusText = 'disconnected'
  lastDisconnectReason = String(reason || 'Disconnected')
  console.error('WhatsApp disconnected:', reason)
  isReady = false
})

client.on('loading_screen', (percent, message) => {
  statusText = `loading ${percent}%`
  console.log(`WhatsApp loading ${percent}%: ${message}`)
})

client.initialize().catch(error => {
  statusText = 'initialize_failed'
  lastError = error?.message || String(error)
  console.error('WhatsApp initialize failed:', error)
})

const app = express()
app.use(express.json({ limit: '1mb' }))

const requireSecret = (req, res, next) => {
  if (!API_SECRET) return next()
  if (req.header('X-Api-Secret') !== API_SECRET) {
    return res.status(401).json({ success: false, message: 'Unauthorized' })
  }
  return next()
}

app.get('/status', requireSecret, (_req, res) => {
  res.json({
    success: true,
    ready: isReady,
    status: statusText,
    defaultGroupId: DEFAULT_GROUP_ID || null,
    hasQr: !isReady && !!latestQr,
    qrGeneratedAt,
    lastError,
    lastDisconnectReason,
    startedAt,
    apiSecretConfigured: !!API_SECRET,
  })
})

app.post('/restart', requireSecret, async (_req, res) => {
  try {
    statusText = 'restarting'
    isReady = false
    latestQr = null
    await client.destroy().catch(() => undefined)
    await client.initialize()
    return res.json({ success: true, message: 'WhatsApp client restart requested.' })
  } catch (error) {
    lastError = error?.message || String(error)
    statusText = 'restart_failed'
    return res.status(500).json({ success: false, message: lastError })
  }
})

app.get('/qr', requireSecret, async (_req, res) => {
  if (isReady) {
    return res.json({
      success: true,
      ready: true,
      qr: null,
      message: 'WhatsApp is already connected.',
    })
  }

  if (!latestQr) {
    return res.json({
      success: true,
      ready: false,
      qr: null,
      message: 'QR code is not ready yet. Wait a few seconds and try again.',
    })
  }

  try {
    const dataUrl = await QRCode.toDataURL(latestQr, {
      margin: 1,
      width: 320,
    })

    return res.json({
      success: true,
      ready: false,
      qr: dataUrl,
      generated_at: qrGeneratedAt,
    })
  } catch (error) {
    console.error('Failed to generate QR image:', error)
    return res.status(500).json({
      success: false,
      message: error.message || 'Failed to generate QR image',
    })
  }
})

app.get('/groups', requireSecret, async (_req, res) => {
  if (!isReady) {
    return res.status(503).json({ success: false, message: 'WhatsApp client is not ready yet' })
  }

  try {
    const chats = await client.getChats()
    const groups = chats
      .filter(chat => chat.isGroup)
      .map(chat => ({
        id: chat.id._serialized,
        name: chat.name,
      }))
      .sort((a, b) => a.name.localeCompare(b.name))

    return res.json({ success: true, groups })
  } catch (error) {
    console.error('Failed to fetch groups:', error)
    return res.status(500).json({ success: false, message: error.message })
  }
})

app.post('/send', requireSecret, async (req, res) => {
  if (!isReady) {
    return res.status(503).json({ success: false, message: 'WhatsApp client is not ready yet' })
  }

  const groupId = req.body.groupId || DEFAULT_GROUP_ID
  const message = req.body.message

  if (!groupId || !message) {
    return res.status(422).json({
      success: false,
      message: 'groupId and message are required',
    })
  }

  try {
    await client.sendMessage(groupId, message)
    return res.json({ success: true })
  } catch (error) {
    console.error('Send failed:', error)
    return res.status(500).json({
      success: false,
      message: error.message || 'Failed to send message',
    })
  }
})

app.listen(PORT, HOST, () => {
  console.log(`Shipya WhatsApp service listening on http://${HOST}:${PORT}`)
})
