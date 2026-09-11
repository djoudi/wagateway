'use strict';

const express     = require('express');
const helmet      = require('helmet');
const rateLimit   = require('express-rate-limit');
const { logger }  = require('./utils/logger');
const sessions    = require('./sessions/manager');
const sendRoutes  = require('./routes/send');
const sessionRoutes = require('./routes/session');
const healthRoutes  = require('./routes/health');

const app  = express();
function resolveListenPort() {
  const dedicated = Number.parseInt(process.env.WA_SERVICE_PORT || '', 10);
  if (Number.isInteger(dedicated) && dedicated > 0 && dedicated < 65536) {
    return dedicated;
  }
  const inherited = Number.parseInt(process.env.PORT || '3000', 10);
  // EasyPanel/Coolify inject PORT=80 for the public web process. Never steal
  // it, and never collide with nginx (80/443) or Reverb (8080) in this image.
  if (!Number.isInteger(inherited) || inherited <= 0 || inherited === 80 || inherited === 443 || inherited === 8080) {
    return 3000;
  }
  return inherited;
}
const PORT = resolveListenPort();

// ─── Security middleware ──────────────────────────────────────────────────────
app.use(helmet());
app.use(express.json({ limit: '10mb' }));

// Liveness must be public — Laravel ping and process supervisors do not
// always send X-WG-Secret. Everything else stays behind the shared secret.
app.use('/health', healthRoutes);

app.use((req, res, next) => {
  const secret = req.headers['x-wg-secret'];
  if (secret !== process.env.LARAVEL_SECRET) {
    return res.status(401).json({ success: false, error: 'Unauthorized' });
  }
  next();
});

// Rate limiting — safety net (Laravel enforces plan limits)
app.use(rateLimit({ windowMs: 60_000, max: 500, standardHeaders: true }));

// ─── Routes ──────────────────────────────────────────────────────────────────
app.use('/session', sessionRoutes);
app.use('/send',    sendRoutes);

// ─── Error handler ───────────────────────────────────────────────────────────
app.use((err, req, res, next) => {
  logger.error(`Unhandled error: ${err.message}`, { stack: err.stack });
  res.status(500).json({ success: false, error: 'Internal server error' });
});

// ─── Boot ─────────────────────────────────────────────────────────────────────
const server = app.listen(PORT, '0.0.0.0', () => {
  logger.info(`WA Service listening on port ${PORT}`);
  sessions.restoreAll();
});
server.on('error', (err) => {
  logger.error(`WA Service failed to bind port ${PORT}: ${err.message}`);
  process.exit(1);
});

module.exports = app;
