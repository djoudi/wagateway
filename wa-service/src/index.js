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
const PORT = process.env.PORT || 3000;

// ─── Security middleware ──────────────────────────────────────────────────────
app.use(helmet());
app.use(express.json({ limit: '10mb' }));

// Verify internal shared secret on every request
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
app.use('/health',  healthRoutes);
app.use('/session', sessionRoutes);
app.use('/send',    sendRoutes);

// ─── Error handler ───────────────────────────────────────────────────────────
app.use((err, req, res, next) => {
  logger.error(`Unhandled error: ${err.message}`, { stack: err.stack });
  res.status(500).json({ success: false, error: 'Internal server error' });
});

// ─── Boot ─────────────────────────────────────────────────────────────────────
app.listen(PORT, () => {
  logger.info(`WA Service listening on port ${PORT}`);
  sessions.restoreAll(); // Restore persisted sessions on startup
});

module.exports = app;
