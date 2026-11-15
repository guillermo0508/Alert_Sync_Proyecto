// Servicio de correo de AlertSync (Vercel).
// Recibe los correos del backend Laravel por HTTPS y los envía por SMTP de Gmail.
// Render (plan gratuito) bloquea SMTP; Vercel sólo bloquea el puerto 25, así que Gmail por 587 funciona.
//
// Variables de entorno (en Vercel → Settings → Environment Variables):
//   GMAIL_USER          alertsync26@gmail.com
//   GMAIL_APP_PASSWORD  contraseña de aplicación de Google (16 caracteres)
//   RELAY_SECRET        clave larga compartida con el backend (MAIL_RELAY_SECRET)
//   MAIL_FROM_NAME      (opcional) nombre del remitente, por defecto "Alert Sync"

const nodemailer = require('nodemailer');
const crypto = require('crypto');

let transporter;
function getTransporter() {
  if (!transporter) {
    transporter = nodemailer.createTransport({
      host: 'smtp.gmail.com',
      port: 587,
      secure: false, // STARTTLS
      requireTLS: true,
      auth: { user: process.env.GMAIL_USER, pass: process.env.GMAIL_APP_PASSWORD },
    });
  }
  return transporter;
}

function isAuthorized(req) {
  const secret = process.env.RELAY_SECRET || '';
  const header = req.headers['authorization'] || '';
  const token = header.startsWith('Bearer ') ? header.slice(7) : '';
  if (!secret || token.length !== secret.length) return false;
  return crypto.timingSafeEqual(Buffer.from(token), Buffer.from(secret));
}

const asList = (value) => (Array.isArray(value) ? value : value ? [value] : []).filter(Boolean);

module.exports = async (req, res) => {
  if (req.method === 'GET') {
    return res.status(200).json({ ok: true, service: 'alertsync-mail-relay' });
  }
  if (req.method !== 'POST') {
    return res.status(405).json({ ok: false, error: 'Método no permitido' });
  }
  if (!isAuthorized(req)) {
    return res.status(401).json({ ok: false, error: 'No autorizado' });
  }
  if (!process.env.GMAIL_USER || !process.env.GMAIL_APP_PASSWORD) {
    return res.status(500).json({ ok: false, error: 'Faltan GMAIL_USER / GMAIL_APP_PASSWORD en Vercel' });
  }

  const body = typeof req.body === 'string' ? JSON.parse(req.body || '{}') : req.body || {};
  const to = asList(body.to);
  if (to.length === 0 || !body.subject || (!body.html && !body.text)) {
    return res.status(422).json({ ok: false, error: 'Se requieren to, subject y html o text' });
  }

  try {
    const info = await getTransporter().sendMail({
      from: { name: body.from_name || process.env.MAIL_FROM_NAME || 'Alert Sync', address: process.env.GMAIL_USER },
      to,
      cc: asList(body.cc),
      bcc: asList(body.bcc),
      replyTo: body.reply_to || undefined,
      subject: body.subject,
      html: body.html || undefined,
      text: body.text || undefined,
      // Adjuntos; los "en línea" llevan cid para que el HTML muestre imágenes como el logo.
      attachments: (Array.isArray(body.attachments) ? body.attachments : []).map((a) => ({
        filename: a.filename || 'adjunto',
        content: a.content,
        encoding: 'base64',
        contentType: a.content_type || undefined,
        cid: a.cid || undefined,
        contentDisposition: a.inline ? 'inline' : 'attachment',
      })),
    });
    return res.status(200).json({ ok: true, messageId: info.messageId, accepted: info.accepted });
  } catch (error) {
    console.error('Error SMTP Gmail:', error);
    return res.status(502).json({ ok: false, error: error.message });
  }
};
