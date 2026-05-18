/**
 * @param {string} registry
 * @param {string|null|undefined} referenceDate YYYY-MM-DD
 * @returns {{ age: number, gender: string }|null}
 */
export function parseMongolianRegistryDemographics(registry, referenceDate) {
  const normalized = String(registry || '').trim().toUpperCase()
  if (!normalized) {
    return null
  }

  const letterMatch = normalized.match(/^[\p{L}]{2}(\d{8})$/u)
  if (letterMatch) {
    const digits = letterMatch[1]
    const yy = Number.parseInt(digits.slice(0, 2), 10)
    const mm = Number.parseInt(digits.slice(2, 4), 10)
    const dd = Number.parseInt(digits.slice(4, 6), 10)
    const genderDigit = Number.parseInt(digits.slice(-1), 10)

    return birthFromYmd(yy, mm, dd, genderDigit, referenceDate)
  }

  const digitsOnly = normalized.replace(/\D+/g, '')
  if (digitsOnly.length < 8) {
    return null
  }

  const datePart = digitsOnly.slice(0, 6)
  let yy = Number.parseInt(datePart.slice(0, 2), 10)
  let mmRaw = Number.parseInt(datePart.slice(2, 4), 10)
  const dd = Number.parseInt(datePart.slice(4, 6), 10)
  let mm = mmRaw
  let fullYear = 1900 + yy
  if (mmRaw > 20) {
    mm = mmRaw - 20
    fullYear = 2000 + yy
  }

  const genderDigit = Number.parseInt(digitsOnly.length >= 8 ? digitsOnly.charAt(7) : digitsOnly.slice(-1), 10)
  const birth = new Date(fullYear, mm - 1, dd)
  if (birth.getFullYear() !== fullYear || birth.getMonth() !== mm - 1 || birth.getDate() !== dd) {
    return null
  }

  return finalizeDemographics(birth, genderDigit, referenceDate)
}

function birthFromYmd(yy, mm, dd, genderDigit, referenceDate) {
  if (mm < 1 || mm > 12 || dd < 1 || dd > 31) {
    return null
  }

  const reference = referenceDate ? new Date(`${referenceDate}T00:00:00`) : new Date()
  const refYearTwo = reference.getFullYear() % 100
  const century = yy <= refYearTwo ? 2000 : 1900
  const fullYear = century + yy
  const birth = new Date(fullYear, mm - 1, dd)
  if (birth.getFullYear() !== fullYear || birth.getMonth() !== mm - 1 || birth.getDate() !== dd) {
    return null
  }

  return finalizeDemographics(birth, genderDigit, referenceDate)
}

function finalizeDemographics(birthDate, genderDigit, referenceDate) {
  const reference = referenceDate ? new Date(`${referenceDate}T00:00:00`) : new Date()
  let age = reference.getFullYear() - birthDate.getFullYear()
  const monthDiff = reference.getMonth() - birthDate.getMonth()
  if (monthDiff < 0 || (monthDiff === 0 && reference.getDate() < birthDate.getDate())) {
    age -= 1
  }

  const gender = genderDigit % 2 === 0 ? 'Эмэгтэй' : 'Эрэгтэй'

  return { age, gender }
}
