import React, { useState } from 'react';
import { View, Text, StyleSheet, Alert } from 'react-native';
import AuthCardLayout from '../components/AuthCardLayout';
import {
  AuthHero,
  FloatingField,
  PillButton,
  BackLink,
  ErrorBanner,
  LoadingOverlay,
  LockIcon,
  ShieldIcon,
} from '../components/AuthUI';
import { ENV } from '../config/env';
import api from '../services/api';

export default function ResetPasswordScreen({ route, navigation }) {
  const { email, code } = route.params || { email: '', code: '' };

  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [passwordError, setPasswordError] = useState('');
  const [confirmError, setConfirmError] = useState('');
  const [generalError, setGeneralError] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handlePasswordChange = (val) => {
    setPassword(val);
    if (passwordError) setPasswordError('');
    if (generalError) setGeneralError('');
  };

  const handleConfirmChange = (val) => {
    setPasswordConfirmation(val);
    if (confirmError) setConfirmError('');
    if (generalError) setGeneralError('');
  };

  const handleReset = async () => {
    let hasErr = false;
    setPasswordError('');
    setConfirmError('');
    setGeneralError('');

    if (!password) {
      setPasswordError('New password is required.');
      hasErr = true;
    } else if (password.length < 8) {
      setPasswordError('Password must be at least 8 characters.');
      hasErr = true;
    }

    if (!passwordConfirmation) {
      setConfirmError('Please confirm your new password.');
      hasErr = true;
    } else if (password !== passwordConfirmation) {
      setConfirmError('Passwords do not match.');
      hasErr = true;
    }

    if (hasErr) return;

    setIsSubmitting(true);

    try {
      const { data } = await api.post('/forgot-password/reset', {
        email,
        code,
        password,
        password_confirmation: passwordConfirmation,
      });

      Alert.alert(
        'Password Reset Successful',
        'Your password has been reset. Please log in with your new password.',
        [{ text: 'Log In', onPress: () => navigation.navigate('SignIn') }]
      );
    } catch (err) {
      setGeneralError(err.message || 'Something went wrong. Please check your connection.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <AuthCardLayout
      hero={<AuthHero />}
      heroRatio={0.34}
      title="Create New"
      highlight="Password"
      overlay={<LoadingOverlay visible={isSubmitting} title="Resetting password" subtitle="Securing your account" />}
      footer={<BackLink text="Back to Log In" onPress={() => navigation.navigate('SignIn')} />}
    >
      <Text style={styles.desc}>
        Your new password must be at least 8 characters long.
      </Text>

      {!!generalError && <ErrorBanner text={generalError} />}

      <FloatingField
        label="New Password"
        icon={<LockIcon />}
        secure
        value={password}
        onChangeText={handlePasswordChange}
        error={passwordError}
        autoCapitalize="none"
      />

      <FloatingField
        label="Confirm New Password"
        icon={<ShieldIcon />}
        secure
        value={passwordConfirmation}
        onChangeText={handleConfirmChange}
        error={confirmError}
        autoCapitalize="none"
      />

      <PillButton title="Reset Password" onPress={handleReset} loading={isSubmitting} />
    </AuthCardLayout>
  );
}

const styles = StyleSheet.create({
  desc: {
    fontSize: 13.5,
    lineHeight: 20,
    color: '#64748b',
    textAlign: 'center',
    marginBottom: 16,
    paddingHorizontal: 10,
  },
});
