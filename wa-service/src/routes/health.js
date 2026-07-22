'use strict';
const express  = require('express');
const router   = express.Router();
const { sessions } = require('../sessions/manager');
router.get('/', (req, res) => {
  res.json({ status: 'ok', active_sessions: sessions.size, uptime_seconds: Math.floor(process.uptime()), memory_mb: Math.round(process.memoryUsage().rss / 1024 / 1024), timestamp: new Date().toISOString() });
});
module.exports = router;
