// Reglas de los campos de tarjeta (iguales en la web, la app del iPhone y el servidor).

/**
 * Marca por los primeros dígitos. AMEX usa 15 dígitos y CVV de 4; las demás, 16 y CVV de 3
 * (igual que en la app del iPhone y en el servidor).
 */
export function cardBrand(number) {
  const d = String(number || '').replace(/\D/g, '')
  const p2 = Number(d.slice(0, 2)) || -1
  const p3 = Number(d.slice(0, 3)) || -1
  const p4 = Number(d.slice(0, 4)) || -1
  let id = 'unknown'
  if (d.startsWith('4')) id = 'visa'
  else if (p2 === 34 || p2 === 37) id = 'amex'
  else if ((p2 >= 51 && p2 <= 55) || (p4 >= 2221 && p4 <= 2720)) id = 'mastercard'
  else if (p4 === 6011 || p2 === 65 || (p3 >= 644 && p3 <= 649)) id = 'discover'
  const names = { visa: 'Visa', mastercard: 'Mastercard', amex: 'American Express', discover: 'Discover', unknown: 'Tarjeta' }
  return {
    id,
    name: names[id],
    numberLength: id === 'amex' ? 15 : 16,
    cvvLength: id === 'amex' ? 4 : 3,
  }
}

/** Texto para mostrar junto al número mientras se escribe. */
export function brandLabel(number) {
  if (!String(number || '').replace(/\D/g, '')) return 'La marca se detecta al escribir'
  const b = cardBrand(number)
  return `${b.name} · ${b.numberLength} dígitos · CVV de ${b.cvvLength}`
}

/** Número: solo dígitos, 15 (AMEX, en grupos 4-6-5) o 16 (en grupos de 4). */
export function formatCardNumber(text) {
  const all = String(text).replace(/\D/g, '')
  const brand = cardBrand(all)
  const digits = all.slice(0, brand.numberLength)
  const groups = brand.id === 'amex' ? [4, 6, 5] : [4, 4, 4, 4]
  const parts = []
  let i = 0
  for (const size of groups) {
    if (i >= digits.length) break
    parts.push(digits.slice(i, i + size))
    i += size
  }
  return parts.join(' ')
}

/** CVV: solo dígitos, 4 con AMEX y 3 con las demás. */
export function formatCvv(text, number = '') {
  return String(text).replace(/\D/g, '').slice(0, cardBrand(number).cvvLength)
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
  const brand = cardBrand(number)
  if (String(number).replace(/\D/g, '').length !== brand.numberLength) {
    return `El número de tarjeta ${brand.name === 'Tarjeta' ? '' : `${brand.name} `}debe tener ${brand.numberLength} dígitos.`
  }
  if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(expiry || '')) return 'Escribe el vencimiento como MM/AA (mes y año).'
  if (!isValidExpiry(expiry)) return 'La tarjeta está vencida.'
  if (String(cvv).replace(/\D/g, '').length !== brand.cvvLength) return `El CVV debe tener ${brand.cvvLength} dígitos.`
  return null
}
