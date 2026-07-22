'use strict';
const express = require('express');
const router  = express.Router();
const manager = require('../sessions/manager');
const { MessageMedia, Location } = require('whatsapp-web.js');

function getClient(res, sid) {
  const c = manager.sessions.get(sid);
  if (!c || !c.info) { res.status(422).json({ success: false, error: 'Device not connected' }); return null; }
  return c;
}

router.post('/text', async (req, res) => {
  const { session_id, to, body } = req.body;
  const c = getClient(res, session_id); if (!c) return;
  try { const m = await c.sendMessage(to, body); res.json({ success: true, id: m.id._serialised }); }
  catch (e) { res.status(500).json({ success: false, error: e.message }); }
});

for (const type of ['image','document','audio','video']) {
  router.post(`/${type}`, async (req, res) => {
    const { session_id, to, url, caption, filename } = req.body;
    const c = getClient(res, session_id); if (!c) return;
    try {
      const media = await MessageMedia.fromUrl(url, { unsafeMime: true });
      if (filename) media.filename = filename;
      const m = await c.sendMessage(to, media, { caption });
      res.json({ success: true, id: m.id._serialised });
    } catch (e) { res.status(500).json({ success: false, error: e.message }); }
  });
}

router.post('/location', async (req, res) => {
  const { session_id, to, latitude, longitude, name } = req.body;
  const c = getClient(res, session_id); if (!c) return;
  try { const m = await c.sendMessage(to, new Location(latitude, longitude, name)); res.json({ success: true, id: m.id._serialised }); }
  catch (e) { res.status(500).json({ success: false, error: e.message }); }
});

module.exports = router;
