// src/screens/SignInScreen.js
import React, { useContext, useState } from 'react';
import { Text, TouchableOpacity, StyleSheet, View } from 'react-native';
import { AuthContext } from '../context/AuthContext';
import AuthCardLayout from '../components/AuthCardLayout';
import { AuthHero, FloatingField, PillButton, ErrorBanner, LoadingOverlay, MailIcon, LockIcon } from '../components/AuthUI';

export default function SignInScreen({ navigation }) {
  const { signInWithEmail } = useContext(AuthContext);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [emailError, setEmailError] = useState('');
  const [passwordError, setPasswordError] = useState('');
  const [generalError, setGeneralError] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const validateEmailFormat = (val) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);

  const handleEmailChange = (val) => {
    setEmail(val);
    if (emailError) setEmailError('');
    if (generalError) setGeneralError('');
  };
  const handlePasswordChange = (val) => {
    setPassword(val);
    if (passwordError) setPasswordError('');
    if (emailError) setEmailError('');
    if (generalError) setGeneralError('');
  };

  const handleEmailLogin = async () => {
    let hasError = false;
    setEmailError(''); setPasswordError(''); setGeneralError('');
    const e = email.trim();
    const p = password;

    if (!e) { setEmailError('The email field is required.'); hasError = true; }
    else if (!validateEmailFormat(e)) { setEmailError('Please enter a valid email address.'); hasError = true; }
    if (!p) { setPasswordError('The password field is required.'); hasError = true; }
    if (hasError) return;

    setIsSubmitting(true);
    try {
      await signInWithEmail(e, p);
    } catch (error) {
      const r = error.response?.data;
      if (r?.errors?.email) setEmailError(r.errors.email[0]);
      else if (r?.errors?.password) setPasswordError(r.errors.password[0]);
      else if (r?.message) setGeneralError(r.message);
      else if (!error.response) setGeneralError('Unable to connect to AgriCo server. Please ensure the backend server is running.');
      else setGeneralError('These credentials do not match our records.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <AuthCardLayout
      hero={<AuthHero big />}
      heroRatio={0.4}
      title="Welcome back "
      overlay={<LoadingOverlay visible={isSubmitting} />}
    >
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
      <FloatingField
        label="Password"
        icon={<LockIcon />}
        secure
        value={password}
        onChangeText={handlePasswordChange}
        error={passwordError}
        autoCapitalize="none"
      />
      {!!generalError && <ErrorBanner text={generalError} />}

      <TouchableOpacity style={s.forgot} onPress={() => navigation.navigate('ForgotPassword')}>
        <Text style={s.forgotText}>Forgot password?</Text>
      </TouchableOpacity>

      <PillButton title="Log In" onPress={handleEmailLogin} loading={isSubmitting} />
    </AuthCardLayout>
  );
}

const s = StyleSheet.create({
  forgot: { alignSelf: 'flex-end', marginTop: -2, marginBottom: 18, paddingHorizontal: 2 },
  forgotText: { color: '#22c55e', fontSize: 12.5, fontWeight: '600' },
});

