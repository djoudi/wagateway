'use strict';
const axios = require('axios');
const { logger } = require('./logger');
async function notifyLaravel(event, data) {
  const url = process.env.LARAVEL_WEBHOOK_URL;
  const secret = process.env.LARAVEL_SECRET;
  if (!url) { logger.warn('LARAVEL_WEBHOOK_URL not set', { event }); return; }
  try {
    await axios.post(url, { event, data }, { headers: { 'X-WG-Secret': secret, 'Content-Type': 'application/json' }, timeout: 5000 });
  } catch (err) { logger.error(`Laravel notify failed [${event}]: ${err.message}`); }
}
module.exports = { notifyLaravel };
