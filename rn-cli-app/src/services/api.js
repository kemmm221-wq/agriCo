import axios from 'axios';
import * as Keychain from 'react-native-keychain';
import { ENV } from '../config/env';

const SERVICE_KEY = 'agrico_user_token';

// Candidate API hosts in order of priority:
// 1. Live Railway Production Cloud URL
// 2. Local Android Emulator (Port 8080 & 8000)
// 3. Physical Phone over Wi-Fi (Host PC LAN IP Port 8080 & 8000)
// 4. USB adb reverse / localhost (Port 8080 & 8000)
export const CANDIDATE_BASE_URLS = [
  'https://agrico-web-production.up.railway.app/api',
  ENV.API_URL,
  ENV.RAILWAY_API_URL,
  'http://10.0.2.2:8080/api',
  'http://10.0.2.2:8000/api',
  'http://192.168.1.103:8080/api',
  'http://192.168.1.103:8000/api',
  'http://127.0.0.1:8080/api',
  'http://127.0.0.1:8000/api',
  'http://192.168.100.37:8080/api',
  'http://192.168.100.37:8000/api',
  ENV.RENDER_API_URL,
].filter((url, index, self) => Boolean(url) && self.indexOf(url) === index);

let activeBaseUrl = ENV.API_URL || 'https://agrico-web-production.up.railway.app/api';

const api = axios.create({
  baseURL: activeBaseUrl,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Bypass-Tunnel-Reminder': 'true',
  },
  timeout: 10000,
});

export const getActiveBaseUrl = () => activeBaseUrl;

export const setActiveBaseUrl = (newUrl) => {
  if (!newUrl) return;
  activeBaseUrl = newUrl;
  api.defaults.baseURL = newUrl;
};

let probePromise = null;

/**
 * Fast concurrent ping across candidate URLs to detect the live backend.
 * Checks /app-version (public fast endpoint) in parallel.
 * Prioritizes the Railway production cloud host.
 */
export const probeBestApiHost = async (force = false) => {
  if (probePromise && !force) {
    return probePromise;
  }

  probePromise = (async () => {
    const testHost = async (candidateUrl) => {
      const cleanUrl = candidateUrl.replace(/\/+$/, '');
      const res = await axios.get(`${cleanUrl}/app-version`, {
        timeout: 2500,
        headers: {
          'Accept': 'application/json',
          'Bypass-Tunnel-Reminder': 'true',
        },
      });
      if (res.status >= 200 && res.status < 400) {
        return cleanUrl;
      }
      throw new Error(`Invalid status: ${res.status}`);
    };

    const railwayCandidates = CANDIDATE_BASE_URLS.filter((u) => u.includes('railway.app'));
    const localCandidates = CANDIDATE_BASE_URLS.filter(
      (u) => u.includes('10.0.2.2') || u.includes('127.0.0.1') || u.includes('192.168.')
    );
    const otherCandidates = CANDIDATE_BASE_URLS.filter(
      (u) => !railwayCandidates.includes(u) && !localCandidates.includes(u)
    );

    // Try Railway production endpoint first
    try {
      const winner = await Promise.any(railwayCandidates.map(testHost));
      if (winner) {
        setActiveBaseUrl(winner);
        console.log(`[AgriCo API] Connected to live Railway backend: ${winner}`);
        return winner;
      }
    } catch (_) {
      // If Railway is unreachable, fall back to local development servers
      try {
        const localWinner = await Promise.any(localCandidates.map(testHost));
        if (localWinner) {
          setActiveBaseUrl(localWinner);
          console.log(`[AgriCo API] Connected to live local backend: ${localWinner}`);
          return localWinner;
        }
      } catch (__) {
        try {
          const otherWinner = await Promise.any(otherCandidates.map(testHost));
          if (otherWinner) {
            setActiveBaseUrl(otherWinner);
            return otherWinner;
          }
        } catch (___) {
          console.warn('[AgriCo API] No candidate backend responded to /app-version');
        }
      }
    }

    return activeBaseUrl;
  })();

  try {
    return await probePromise;
  } finally {
    probePromise = null;
  }
};

// Proactively run initial probe in the background
probeBestApiHost().catch(() => {});

let onUnauthorizedCallback = null;

export const setUnauthorizedHandler = (handler) => {
  onUnauthorizedCallback = handler;
};

// Request Interceptor: Attach Bearer Token & ensure active baseURL
api.interceptors.request.use(
  async (config) => {
    try {
      let token = null;
      const creds = await Keychain.getGenericPassword({ service: SERVICE_KEY });
      if (creds && creds.password) {
        token = creds.password;
      } else {
        const fallback = await Keychain.getGenericPassword();
        if (fallback && fallback.password) {
          token = fallback.password;
        }
      }

      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }
    } catch (err) {
      console.warn('[AgriCo API] Error reading keychain in request interceptor:', err);
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response Interceptor: Handle HTTP 401 & Fast Network Fallback
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const config = error.config;

    const isNetworkError =
      !error.response ||
      error.code === 'ERR_NETWORK' ||
      error.code === 'ECONNABORTED' ||
      error.message?.includes('Network Error') ||
      error.message?.includes('timeout');

    if (isNetworkError && config && !config._fallbackAttempted) {
      config._fallbackAttempted = true;
      console.log(`[AgriCo API] Request to ${config.baseURL} failed (${error.message}). Probing live host...`);

      try {
        const workingHost = await probeBestApiHost(true);
        if (workingHost && workingHost !== config.baseURL) {
          console.log(`[AgriCo API] Retrying request on live host: ${workingHost}`);
          config.baseURL = workingHost;
          return await axios(config);
        }
      } catch (probeErr) {
        console.warn('[AgriCo API] Fallback retry failed:', probeErr?.message);
      }
    }

    if (error.response && error.response.status === 401) {
      const url = error.config?.url || '';
      if (url.includes('/user') || url.includes('/logout')) {
        try {
          await Keychain.resetGenericPassword({ service: SERVICE_KEY });
          await Keychain.resetGenericPassword();
        } catch (err) {
          console.warn('[AgriCo API] Error resetting keychain on 401:', err);
        }
        if (typeof onUnauthorizedCallback === 'function') {
          onUnauthorizedCallback();
        }
      }
    }

    console.warn(
      `[AgriCo API Error] ${config?.method?.toUpperCase()} ${config?.url} failed: ${error.message} (Base: ${config?.baseURL})`
    );
    return Promise.reject(error);
  }
);

export default api;
