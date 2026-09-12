'use strict';
const express = require('express');
const router  = express.Router();
const manager = require('../sessions/manager');
const { logger } = require('../utils/logger');

router.post('/start', async (req, res) => {
  const { session_id } = req.body;
  if (!session_id) return res.status(400).json({ success: false, error: 'session_id required' });
  try {
    manager.createSession(session_id).catch((err) => logger.error(err.message));
    res.json({ success: true });
  }
  catch (err) { logger.error(err.message); res.status(500).json({ success: false, error: err.message }); }
});
router.get('/status/:id', async (req, res) => {
  const client = await manager.getSession(req.params.id);
  res.json({ success: true, status: client ? (client.info ? 'connected' : 'connecting') : 'disconnected' });
});
router.get('/qr/:id', (req, res) => {
  const qr = manager.getQr(req.params.id);
  if (qr) return res.json({ success: true, qr });
  const error = manager.getLastError(req.params.id);
  res.json({ success: false, qr: null, error: error || null });
});
router.delete('/:id', async (req, res) => { await manager.terminateSession(req.params.id); res.json({ success: true }); });
module.exports = router;
