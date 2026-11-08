import api from './api';

const POLL_MS = 1500;
const MAX_BACKOFF_MS = 8000;
const GIVE_UP_AFTER_FAILURES_MS = 60000;
const OVERALL_TIMEOUT_MS = 180000;
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

// Matches the server's /^[a-zA-Z0-9\-]{8,64}$/ check
export const newScanId = () =>
  `m-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;

export async function startScan({ scanId, imageBase64, latitude, longitude, locationName, userId, user_id }) {
  const activeUserId = user_id || userId || null;
  const { data } = await api.post(
    '/scan/analyze',
    {
      scan_id: scanId,
      image_base64: imageBase64, // 'data:image/jpeg;base64,...'
      latitude: latitude ?? null,
      longitude: longitude ?? null,
      location_name: locationName ?? null,
      user_id: activeUserId,
    },
    { timeout: 45000 }
  );
  return data.scan_id;
}

export async function pollScan(scanId, { onProgress, signal } = {}) {
  let cursor = 0;
  let delay = POLL_MS;
  let failingSince = null;
  const startedAt = Date.now();

  while (!signal?.aborted) {
    if (Date.now() - startedAt > OVERALL_TIMEOUT_MS) return { status: 'lost' };
    try {
      const { data } = await api.get(`/scan/progress/${scanId}`, {
        params: { since: cursor },
        timeout: 10000,
      });

      failingSince = null;
      delay = POLL_MS;
      onProgress?.(data.stage ?? 0);
      cursor = data.cursor ?? cursor;

      if (data.status === 'rejected') {
        return { status: 'rejected', message: data.message };
      }
      if (data.status === 'done' && data.scan) {
        return { status: 'done', scan: data.scan, data: data.data };
      }
      if (data.status === 'error' || data.status === 'failed') {
        return { status: 'failed', message: data.message };
      }
    } catch (err) {
      const code = err?.response?.status;
      if (code === 401 || code === 403) return { status: 'auth' };
      if (code === 400) return { status: 'lost' };

      failingSince = failingSince ?? Date.now();
      if (Date.now() - failingSince > GIVE_UP_AFTER_FAILURES_MS) {
        return { status: 'lost' };
      }
      delay = Math.min(delay * 2, MAX_BACKOFF_MS);
    }
    await sleep(delay);
  }
  return { status: 'lost' };
}
