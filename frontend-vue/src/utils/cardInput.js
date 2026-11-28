// Reglas de los campos de tarjeta (iguales en la web, la app del iPhone y el servidor).

/** Número: solo dígitos, máximo 16, en grupos de 4. */
export function formatCardNumber(text) {
  const digits = String(text).replace(/\D/g, '').slice(0, 16)
  return (digits.match(/.{1,4}/g) || []).join(' ')
}

/** CVV: solo dígitos, máximo 3. */
export function formatCvv(text) {
  return String(text).replace(/\D/g, '').slice(0, 3)
}

/**
 * MM/AA: solo un mes válido (01–12) y dos dígitos de año. Un primer dígito de 2 a 9 se completa
 * como "0X"; un segundo dígito que haría un mes inválido (13, 00…) se ignora.
 */
export function formatExpiry(text) {
  let month = ''
  let year = ''
  for (const d of String(text).replace(/\D/g, '')) {
    if (month.length === 0) {
      month = d > '1' ? `0${d}` : d
    } else if (month.length === 1) {
      if ((month === '0' && d === '0') || (month === '1' && d > '2')) continue
      month += d
    } else if (year.length < 2) {
      year += d
    }
  }
  return year ? `${month}/${year}` : month
}

/** MM/AA completo, con mes 01–12 y que no esté vencido. */
export function isValidExpiry(expiry) {
  const m = /^(0[1-9]|1[0-2])\/(\d{2})$/.exec(expiry || '')
  if (!m) return false
  const now = new Date()
  const current = (now.getFullYear() % 100) * 100 + (now.getMonth() + 1)
  return Number(m[2]) * 100 + Number(m[1]) >= current
}

/** Mensaje de error, o null si la tarjeta es válida. */
export function cardError({ number, expiry, cvv }) {
  if (String(number).replace(/\D/g, '').length !== 16) return 'El número de tarjeta debe tener 16 dígitos.'
  if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(expiry || '')) return 'Escribe el vencimiento como MM/AA (mes y año).'
  if (!isValidExpiry(expiry)) return 'La tarjeta está vencida.'
  if (String(cvv).replace(/\D/g, '').length !== 3) return 'El CVV debe tener 3 dígitos.'
  return null
}
