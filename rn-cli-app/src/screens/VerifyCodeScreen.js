import React, { useRef, useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet } from 'react-native';
import AuthCardLayout from '../components/AuthCardLayout';
import { AuthHero, PillButton, BackLink, ErrorBanner, LoadingOverlay } from '../components/AuthUI';
import { ENV } from '../config/env';
import api from '../services/api';

const CODE_LENGTH = 6;
const RESEND_COOLDOWN_SECONDS = 60;

export default function VerifyCodeScreen({ route, navigation }) {
  const { email, code: initialCode } = route.params || { email: '', code: '' };

  const parsedInitial = (initialCode || '').replace(/[^0-9]/g, '');
  const [digits, setDigits] = useState(
    parsedInitial.length === CODE_LENGTH ? parsedInitial.split('') : Array(CODE_LENGTH).fill('')
  );
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [cooldown, setCooldown] = useState(RESEND_COOLDOWN_SECONDS);

  const inputRefs = useRef([]);

  useEffect(() => {
    if (cooldown <= 0) return;
    const t = setInterval(() => setCooldown((c) => Math.max(0, c - 1)), 1000);
    return () => clearInterval(t);
  }, [cooldown]);

  const handleChange = (text, index) => {
    const cleaned = text.replace(/[^0-9]/g, '').slice(-1);
    const next = [...digits];
    next[index] = cleaned;
    setDigits(next);
    setError(null);

    if (cleaned && index < CODE_LENGTH - 1) {
      inputRefs.current[index + 1]?.focus();
    }
  };

  const handleKeyPress = (e, index) => {
    if (e.nativeEvent.key === 'Backspace' && !digits[index] && index > 0) {
      const next = [...digits];
      next[index - 1] = '';
      setDigits(next);
      inputRefs.current[index - 1]?.focus();
    }
  };

  const code = digits.join('');

  const handleVerify = async () => {
    if (code.length !== CODE_LENGTH) {
      setError('Please enter the full 6-digit code.');
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const { data } = await api.post('/forgot-password/verify-code', { email, code });
      navigation.navigate('ResetPassword', { email, code });
    } catch (err) {
      const msg = err.response?.data?.message || err.message || 'Verification failed. Please try again.';
      setError(msg);
    } finally {
      setLoading(false);
    }
  };

  const handleResend = async () => {
    if (cooldown > 0) return;
    setLoading(true);
    setError(null);

    try {
      const { data } = await api.post('/forgot-password/send-code', { email });
      if (data?.code && String(data.code).length === CODE_LENGTH) {
        setDigits(String(data.code).split(''));
      }
      setCooldown(RESEND_COOLDOWN_SECONDS);
    } catch (err) {
      const msg = err.response?.data?.message || err.message || 'Could not resend code.';
      setError(msg);
    } finally {
      setLoading(false);
    }
  };

  const [focusedIndex, setFocusedIndex] = useState(null);

  return (
    <AuthCardLayout
      hero={<AuthHero />}
      heroRatio={0.34}
      title="Verify your"
      highlight="Identity"
      overlay={<LoadingOverlay visible={loading} title="Verifying code" subtitle="Checking your credentials" />}
      footer={<BackLink text="Back to Log In" onPress={() => navigation.navigate('SignIn')} />}
    >
      <Text style={styles.desc}>
        We sent a 6-digit verification code to <Text style={styles.boldEmail}>{email || 'your email'}</Text>.
      </Text>

      {!!error && <ErrorBanner text={error} />}

      <View style={styles.codeRow}>
        {digits.map((d, i) => (
          <TextInput
            key={i}
            ref={(ref) => (inputRefs.current[i] = ref)}
            value={d}
            onChangeText={(text) => handleChange(text, i)}
            onKeyPress={(e) => handleKeyPress(e, i)}
            onFocus={() => setFocusedIndex(i)}
            onBlur={() => setFocusedIndex(null)}
            keyboardType="number-pad"
            maxLength={1}
            autoFocus={i === 0}
            showSoftInputOnFocus={true}
            style={[
              styles.box,
              d ? styles.boxFilled : null,
              focusedIndex === i ? styles.boxFocused : null,
              error ? styles.boxError : null,
            ]}
            selectTextOnFocus
          />
        ))}
      </View>

      <TouchableOpacity
        onPress={handleResend}
        disabled={cooldown > 0}
        style={styles.resendBtn}
        hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
      >
        <Text style={[styles.resendText, cooldown > 0 && styles.resendDisabled]}>
          {cooldown > 0 ? `Resend code in ${cooldown}s` : 'Resend code'}
        </Text>
      </TouchableOpacity>

      <PillButton title="Verify & Continue" onPress={handleVerify} loading={loading} />
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
  boldEmail: {
    fontWeight: '700',
    color: '#0f172a',
  },
  codeRow: {
    flexDirection: 'row',
    justifyContent: 'center',
    marginBottom: 16,
    gap: 8,
  },
  box: {
    width: 44,
    height: 52,
    borderWidth: 1.5,
    borderColor: '#cbd5e1',
    borderRadius: 12,
    textAlign: 'center',
    fontSize: 22,
    fontWeight: '700',
    color: '#0f172a',
    backgroundColor: '#ffffff',
  },
  boxFilled: {
    borderColor: '#94a3b8',
    backgroundColor: '#f8fafc',
  },
  boxFocused: {
    borderColor: '#64748b',
    backgroundColor: '#ffffff',
  },
  boxError: {
    borderColor: '#ef4444',
  },
  resendBtn: {
    alignSelf: 'center',
    marginBottom: 16,
  },
  resendText: {
    fontSize: 13,
    fontWeight: '600',
    color: '#16a34a',
  },
  resendDisabled: {
    color: '#94a3b8',
    fontWeight: '500',
  },
});
