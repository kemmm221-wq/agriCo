import React, { useState } from 'react';
import { View, Text, StyleSheet } from 'react-native';
import AuthCardLayout from '../components/AuthCardLayout';
import {
  AuthHero,
  FloatingField,
  PillButton,
  BackLink,
  ErrorBanner,
  LoadingOverlay,
  MailIcon,
} from '../components/AuthUI';
import { ENV } from '../config/env';
import api from '../services/api';

export default function ForgotPasswordScreen({ navigation }) {
  const [email, setEmail] = useState('');
  const [emailError, setEmailError] = useState('');
  const [generalError, setGeneralError] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const validateEmailFormat = (val) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);

  const handleEmailChange = (val) => {
    setEmail(val);
    if (emailError) setEmailError('');
    if (generalError) setGeneralError('');
  };

  const handleSendCode = async () => {
    const trimmed = email.trim();
    if (!trimmed) {
      setEmailError('Email address is required.');
      return;
    }
    if (!validateEmailFormat(trimmed)) {
      setEmailError('Please enter a valid email address.');
      return;
    }

    setIsSubmitting(true);
    setGeneralError('');
    setEmailError('');

    try {
      const { data } = await api.post('/forgot-password/send-code', { email: trimmed });

      navigation.navigate('VerifyCode', { email: trimmed, code: data?.code || '' });
    } catch (err) {
      const msg = err.response?.data?.message || err.message || 'Something went wrong. Please check your connection.';
      setGeneralError(msg);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <AuthCardLayout
      hero={<AuthHero />}
      heroRatio={0.35}
      title="Reset your"
      highlight="Password"
      overlay={<LoadingOverlay visible={isSubmitting} title="Sending code" subtitle="Checking your email address" />}
      footer={<BackLink text="Back to Log In" onPress={() => navigation.navigate('SignIn')} />}
    >
      <Text style={styles.desc}>
        Enter your registered email address and we will send you a 6-digit verification code.
      </Text>

      {!!generalError && <ErrorBanner text={generalError} />}

      <FloatingField
        label="Email Address"
        icon={<MailIcon />}
        value={email}
        onChangeText={handleEmailChange}
        error={emailError}
        keyboardType="email-address"
        autoCapitalize="none"
        autoCorrect={false}
      />

      <PillButton title="Send Reset Code" onPress={handleSendCode} loading={isSubmitting} />
    </AuthCardLayout>
  );
}

const styles = StyleSheet.create({
  desc: {
    fontSize: 13.5,
    lineHeight: 20,
    color: '#64748b',
    textAlign: 'center',
    marginBottom: 18,
    paddingHorizontal: 10,
  },
});
