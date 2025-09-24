// Returns an i18n key or a meaningful fallback string for display.
// Avoids hardcoded UI strings where possible, but prefers server messages on 422.
import axios, { AxiosError } from 'axios';

export type ErrorContext = 'login' | 'register' | 'generic';
export type ValidationErrors = Record<string, string[]>;

/** Safely gets the first validation message from Laravel's { errors: { field: [msg] } } shape */
function firstErrorMessage(errors: unknown): string | undefined {
  if (!errors || typeof errors !== 'object') return undefined
  console.log("firstErrorMessage errors:", errors);
  for (const value of Object.values(errors as Record<string, unknown>)) {
    console.log("firstErrorMessage value:", value);
    if (Array.isArray(value)) {
      const firstString = value.find(v => typeof v === 'string') as string | undefined
      if (firstString && firstString.trim()) return firstString.trim()
    } else if (typeof value === 'string' && value.trim()) {
      return value.trim()
    } else if (typeof value === 'object' && value) {
      const nested = firstErrorMessage(value)
      if (nested) return nested
    }
  }
  return undefined
}

export function parseApiError(err: unknown, context: ErrorContext = 'generic'): string {
  // Non-Axios error or no response (likely network)
  if (!axios.isAxiosError(err)) return 'errors.network';

  const ax = err as AxiosError<any>;

  if ((ax as any).code === 'ECONNABORTED') return 'errors.timeout'

  if (!ax.response) return 'errors.network'

  const status = ax.response?.status;
  const data = ax.response?.data;

  console.log("fromErrors start");
  const fromErrors = firstErrorMessage(data?.errors)
  if (fromErrors) return fromErrors

  // 419 CSRF / session expired
  if (status === 419) return 'errors.csrf';

  // 401 Unauthorized
  if (status === 401) {
    return context === 'login' ? 'auth.invalidCredentials' : 'errors.unauthorized';
  }

  // 403 Forbidden
  if (status === 403) return 'errors.forbidden';

  // 404 Not found
  if (status === 404) return 'errors.notFound';

  // 422 Unprocessable Entity (other than validation)
  if (status === 422) return 'errors.unprocessable';

  // 423 Locked (e.g. resource disabled)
  if (status === 423) return 'errors.locked';

  // 429 Too Many Requests
  if (status === 429) return 'errors.tooManyRequests';

  // 5xx Server error
  if (status && status >= 500) return 'errors.server';

  // If backend sends a translatable key as message, prefer it
  if (typeof data?.message === 'string') return data.message;

  return 'errors.unknown';
}
