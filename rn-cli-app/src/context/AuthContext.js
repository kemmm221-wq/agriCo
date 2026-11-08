import React, { createContext, useState, useEffect, useCallback } from 'react';
import {
  getStoredToken,
  setStoredToken,
  getStoredUser,
  setStoredUser,
  removeStoredToken,
  fetchCurrentUser,
  loginWithEmail,
  registerUser,
  logoutUser,
} from '../services/auth';
import { setUnauthorizedHandler, probeBestApiHost } from '../services/api';

export const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [userToken, setUserToken] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  const handleUnauthorized = useCallback(() => {
    setUser(null);
    setUserToken(null);
    setIsLoading(false);
  }, []);

  useEffect(() => {
    setUnauthorizedHandler(handleUnauthorized);
  }, [handleUnauthorized]);

  const checkAuth = async () => {
    setIsLoading(true);
    try {
      try {
        await probeBestApiHost();
      } catch (_) {}

      const [token, storedUser] = await Promise.all([
        getStoredToken(),
        getStoredUser(),
      ]);

      if (token) {
        setUserToken(token);
        if (storedUser) {
          setUser(storedUser);
        }

        try {
          const userData = await fetchCurrentUser();
          if (userData && (userData.user_id || userData.email)) {
            setUser(userData);
          }
        } catch (apiErr) {
          console.warn('[AuthContext] Profile fetch error:', apiErr?.message);
          if (apiErr?.response?.status === 401) {
            await removeStoredToken();
            setUser(null);
            setUserToken(null);
          }
        }
      } else {
        setUser(null);
        setUserToken(null);
      }
    } catch (error) {
      console.warn('Session check failed, clearing token:', error?.message);
      await removeStoredToken();
      setUser(null);
      setUserToken(null);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    checkAuth();
  }, []);

  const signInWithEmail = async (email, password) => {
    try {
      await probeBestApiHost();
    } catch (_) {}
    const { token, user: userData } = await loginWithEmail(email, password);
    setUserToken(token);
    setUser(userData);
    return userData;
  };

  const signUpWithEmail = async (name, email, password, passwordConfirmation) => {
    const { token, user: userData } = await registerUser(
      name,
      email,
      password,
      passwordConfirmation
    );
    setUserToken(token);
    setUser(userData);
    return userData;
  };

  const logout = async () => {
    setIsLoading(true);
    try {
      await logoutUser();
    } finally {
      setUser(null);
      setUserToken(null);
      setIsLoading(false);
    }
  };

  const continueAsGuest = () => {
    setUserToken('guest-offline-token');
    setUser({ id: 9999, name: 'Guest Farmer', email: 'guest@agrico.com', role: 'guest' });
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        userToken,
        isLoading,
        signInWithEmail,
        signUpWithEmail,
        continueAsGuest,
        logout,
        checkAuth,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};
