import * as Keychain from 'react-native-keychain';
import api, { getActiveBaseUrl } from './api';
import { ENV } from '../config/env';

// The backend returns profile_photo_url with "localhost" as the host,
// which is unreachable from a real device or Android emulator.
// This rewrites it to the configured API base host so the image loads.
const fixPhotoUrl = (user) => {
  if (!user || !user.profile_photo_url) return user;
  try {
    const activeUrl = typeof getActiveBaseUrl === 'function' ? getActiveBaseUrl() : (ENV.API_URL || 'http://10.0.2.2:8080/api');
    const serverBase = activeUrl.replace(/\/api\/?$/, ''); // e.g. http://10.0.2.2:8080
    const fixed = user.profile_photo_url
      .replace(/https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?/g, serverBase)
      .replace(/https?:\/\/10\.0\.2\.2(:\d+)?/g, serverBase); // normalise in case already set
    return { ...user, profile_photo_url: fixed };
  } catch {
    return user;
  }
};

const SERVICE_KEY = 'agrico_user_token';
const USER_PROFILE_KEY = 'agrico_user_profile';

/**
 * Retrieve persistent Sanctum token from Keychain.
 */
export const getStoredToken = async () => {
  try {
    const credentials = await Keychain.getGenericPassword({ service: SERVICE_KEY });
    if (credentials && credentials.password) {
      return credentials.password;
    }
    const fallback = await Keychain.getGenericPassword();
    return fallback ? fallback.password : null;
  } catch (error) {
    console.error('Failed to retrieve token from Keychain:', error);
    return null;
  }
};

/**
 * Persist Sanctum token into Keychain.
 */
export const setStoredToken = async (token) => {
  try {
    await Keychain.setGenericPassword('auth_user', token, { service: SERVICE_KEY });
  } catch (error) {
    console.error('Failed to save token to Keychain:', error);
  }
};

/**
 * Retrieve persistent User profile from Keychain.
 */
export const getStoredUser = async () => {
  try {
    const credentials = await Keychain.getGenericPassword({ service: USER_PROFILE_KEY });
    if (credentials && credentials.password) {
      return JSON.parse(credentials.password);
    }
  } catch (error) {
    console.warn('Failed to retrieve stored user from Keychain:', error);
  }
  return null;
};

/**
 * Persist User profile into Keychain.
 */
export const setStoredUser = async (user) => {
  try {
    if (!user) return;
    await Keychain.setGenericPassword('auth_profile', JSON.stringify(user), {
      service: USER_PROFILE_KEY,
    });
  } catch (error) {
    console.warn('Failed to save user profile to Keychain:', error);
  }
};

/**
 * Reset stored Sanctum token and profile from Keychain.
 */
export const removeStoredToken = async () => {
  try {
    await Keychain.resetGenericPassword({ service: SERVICE_KEY });
    await Keychain.resetGenericPassword({ service: USER_PROFILE_KEY });
    await Keychain.resetGenericPassword();
  } catch (error) {
    console.error('Failed to remove token from Keychain:', error);
  }
};

/**
 * Validate session & fetch current user profile from database.
 * GET /api/user
 */
export const fetchCurrentUser = async () => {
  const response = await api.get('/user');
  let user = response.data?.data || response.data?.user || response.data;
  if (user && (user.user_id || user.email)) {
    user = fixPhotoUrl(user);
    await setStoredUser(user);
  }
  return user;
};

/**
 * Register a new user account.
 * POST /api/register
 */
export const registerUser = async (name, email, password, passwordConfirmation) => {
  const response = await api.post('/register', {
    name,
    email,
    password,
    password_confirmation: passwordConfirmation,
  });

  const { token, user } = response.data;
  const userData = user || response.data;
  if (token) {
    await setStoredToken(token);
  }
  if (userData) {
    await setStoredUser(userData);
  }
  return { token, user: userData };
};

/**
 * Sign in using Email & Password.
 * POST /api/login
 */
export const loginWithEmail = async (email, password) => {
  const response = await api.post('/login', { email, password });
  const { token, user } = response.data;
  let userData = user || response.data;
  userData = fixPhotoUrl(userData);

  if (token) {
    await setStoredToken(token);
  }
  if (userData) {
    await setStoredUser(userData);
  }
  return { token, user: userData };
};

/**
 * Logout session.
 * POST /api/logout
 */
export const logoutUser = async () => {
  try {
    await api.post('/logout');
  } catch (error) {
    console.warn('Backend logout failed or network offline:', error?.message);
  } finally {
    await removeStoredToken();
  }
};
