export function formatDate(
  iso?: string | null,
  opts: Intl.DateTimeFormatOptions = { dateStyle: 'medium', timeStyle: 'short' },
  timeZone?: string
) {
  if (!iso) return '-';
  const d = new Date(iso);
  if (isNaN(d.getTime())) return '-';
  return new Intl.DateTimeFormat(navigator.language, { ...opts, timeZone }).format(d);
}
