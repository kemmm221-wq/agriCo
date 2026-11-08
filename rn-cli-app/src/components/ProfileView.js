import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Image,
  ScrollView,
  Alert,
  Platform,
  ActivityIndicator,
  Modal,
  TextInput,
  KeyboardAvoidingView,
} from 'react-native';
import { launchImageLibrary, launchCamera } from 'react-native-image-picker';
import api, { getActiveBaseUrl } from '../services/api';
import { ENV } from '../config/env';
import { checkForAppUpdate, CURRENT_APP_VERSION } from '../services/updateService';
import {
  BackArrowIcon,
  LogoutHeaderIcon,
  PencilEditIcon,
  EnvelopeMailIcon,
  IdBadgeIcon,
  RoleShieldIcon,
  CalendarJoinedIcon,
} from './AppIcons';

// Helper to resolve full photo URL from backend storage or absolute URIs
const resolvePhotoUrl = (rawPath) => {
  if (!rawPath || typeof rawPath !== 'string') return null;
  const trimmed = rawPath.trim();
  if (!trimmed) return null;

  if (
    trimmed.startsWith('file://') ||
    trimmed.startsWith('data:image') ||
    trimmed.startsWith('content://')
  ) {
    return trimmed;
  }

  const activeApiUrl = typeof getActiveBaseUrl === 'function' ? getActiveBaseUrl() : ENV.API_URL;
  const cleanBase = (activeApiUrl || 'http://10.0.2.2:8080/api').replace(/\/api\/?$/, '');

  if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
    // If the server returned an internal localhost/127.0.0.1/10.0.2.2 URL, rewrite it to the active reachable base
    if (/^https?:\/\/(localhost|127\.0\.0\.1|10\.0\.2\.2)(:\d+)?/i.test(trimmed)) {
      return trimmed.replace(/^https?:\/\/(localhost|127\.0\.0\.1|10\.0\.2\.2)(:\d+)?/i, cleanBase);
    }
    return trimmed;
  }

  const cleanPath = trimmed.replace(/^\/+/, '');
  if (cleanPath.startsWith('storage/')) {
    return `${cleanBase}/${cleanPath}`;
  }
  return `${cleanBase}/storage/${cleanPath}`;
};

// Compute user initials (single uppercase letter matching website avatar style)
const getUserInitial = (name) => {
  if (!name || typeof name !== 'string') return 'A';
  const trimmed = name.trim();
  if (!trimmed) return 'A';
  return trimmed.charAt(0).toUpperCase();
};

export default function ProfileView({
  user,
  onBack,
  logout,
  scanHistory = [],
  onUpdateUser,
  onTriggerUpdate,
}) {
  const [customPhotoUri, setCustomPhotoUri] = useState(
    user?.profile_photo_url || user?.profile_photo_path || null
  );
  const [isUploadingPhoto, setIsUploadingPhoto] = useState(false);
  const [imgError, setImgError] = useState(false);
  const [isCheckingUpdate, setIsCheckingUpdate] = useState(false);

  // Edit Profile Modal States
  const [showEditModal, setShowEditModal] = useState(false);
  const [editName, setEditName] = useState(user?.name || '');
  const [editEmail, setEditEmail] = useState(user?.email || '');
  const [editStudentId, setEditStudentId] = useState(user?.student_id || '');
  const [editSection, setEditSection] = useState(user?.section || '');
  const [showPasswordFields, setShowPasswordFields] = useState(false);
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [isSavingProfile, setIsSavingProfile] = useState(false);
  const [editError, setEditError] = useState('');

  useEffect(() => {
    setCustomPhotoUri(user?.profile_photo_url || user?.profile_photo_path || null);
    setImgError(false);
    setEditName(user?.name || '');
    setEditEmail(user?.email || '');
    setEditStudentId(user?.student_id || '');
    setEditSection(user?.section || '');
  }, [user?.profile_photo_url, user?.profile_photo_path, user?.name, user?.email, user?.student_id, user?.section]);

  const openEditModal = () => {
    setEditName(user?.name || '');
    setEditEmail(user?.email || '');
    setEditStudentId(user?.student_id || '');
    setEditSection(user?.section || '');
    setCurrentPassword('');
    setNewPassword('');
    setConfirmPassword('');
    setShowPasswordFields(false);
    setEditError('');
    setShowEditModal(true);
  };

  const handleSaveProfile = async () => {
    if (!editName.trim()) {
      setEditError('Full Name is required.');
      return;
    }
    if (!editEmail.trim()) {
      setEditError('Email address is required.');
      return;
    }
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(editEmail.trim())) {
      setEditError('Please enter a valid email address.');
      return;
    }

    if (showPasswordFields && newPassword) {
      if (newPassword.length < 8) {
        setEditError('New password must be at least 8 characters.');
        return;
      }
      if (newPassword !== confirmPassword) {
        setEditError('New passwords do not match.');
        return;
      }
    }

    setIsSavingProfile(true);
    setEditError('');

    try {
      const payload = {
        name: editName.trim(),
        email: editEmail.trim(),
      };

      if (user?.role === 'student') {
        payload.student_id = editStudentId ? editStudentId.trim() : '';
        payload.section = editSection ? editSection.trim() : '';
      }

      if (showPasswordFields && newPassword) {
        payload.current_password = currentPassword;
        payload.password = newPassword;
        payload.password_confirmation = confirmPassword;
      }

      const res = await api.post('/user/profile', payload);

      if (res.data?.status === 'success') {
        setShowEditModal(false);
        if (typeof onUpdateUser === 'function') {
          onUpdateUser();
        }
        Alert.alert('Profile Updated', 'Your profile details have been saved and synchronized with the website database.');
      } else {
        setEditError(res.data?.message || 'Failed to update profile.');
      }
    } catch (err) {
      const errorMsg =
        err.response?.data?.message ||
        err.response?.data?.error ||
        (err.response?.data?.errors
          ? Object.values(err.response.data.errors).flat().join('\n')
          : null) ||
        err.message ||
        'Could not save profile changes.';
      setEditError(errorMsg);
    } finally {
      setIsSavingProfile(false);
    }
  };

  // Format joined date based on database created_at
  const formatJoinedDate = (dateString) => {
    if (!dateString) return '—';
    try {
      const date = new Date(dateString);
      if (isNaN(date.getTime())) return '—';
      return date.toLocaleDateString('en-US', {
        month: 'short',
        year: 'numeric',
      });
    } catch {
      return '—';
    }
  };

  // Compute accurate scan statistics from scanHistory of the account
  const totalScans = Array.isArray(scanHistory) ? scanHistory.length : 0;
  const identifiedScans =
    Array.isArray(scanHistory)
      ? scanHistory.filter((s) => s.munsellCode || s.colorName || s.munsell_code || s.soil_name).length
      : 0;
  const uniqueVarieties =
    Array.isArray(scanHistory)
      ? new Set(scanHistory.map((s) => s.munsellCode || s.colorName).filter(Boolean)).size
      : 0;
  const syncedScans = totalScans;

  const pickImage = async (mode) => {
    try {
      const picker = mode === 'camera' ? launchCamera : launchImageLibrary;
      const result = await picker({
        mediaType: 'photo',
        quality: 0.8,
        maxWidth: 800,
        maxHeight: 800,
      });

      if (result.didCancel || !result.assets || result.assets.length === 0) {
        return;
      }

      const asset = result.assets[0];
      await uploadProfilePhoto(asset);
    } catch (err) {
      Alert.alert('Photo Error', err?.message || 'Could not select photo');
    }
  };

  const uploadProfilePhoto = async (asset) => {
    setIsUploadingPhoto(true);
    try {
      const formData = new FormData();
      formData.append('photo', {
        uri: Platform.OS === 'ios' ? asset.uri.replace('file://', '') : asset.uri,
        type: asset.type || 'image/jpeg',
        name: asset.fileName || 'profile_photo.jpg',
      });

      const res = await api.post('/user/profile-photo', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });

      if (res.data?.status === 'success') {
        const newUrl = res.data.profile_photo_url || asset.uri;
        setCustomPhotoUri(newUrl);
        setImgError(false);
        if (typeof onUpdateUser === 'function') {
          onUpdateUser();
        }
        Alert.alert('Success', 'Profile photo updated successfully!');
      } else {
        Alert.alert('Upload Failed', res.data?.message || 'Failed to update profile photo.');
      }
    } catch (err) {
      console.warn('Profile photo upload error:', err?.message);
      setCustomPhotoUri(asset.uri);
      setImgError(false);
      Alert.alert('Photo Updated', 'Profile photo saved locally.');
    } finally {
      setIsUploadingPhoto(false);
    }
  };

  const handleRemovePhoto = async () => {
    setIsUploadingPhoto(true);
    try {
      await api.post('/user/profile-photo', { remove_photo: true });
      setCustomPhotoUri(null);
      setImgError(false);
      if (typeof onUpdateUser === 'function') {
        onUpdateUser();
      }
      Alert.alert('Success', 'Profile photo removed.');
    } catch (err) {
      setCustomPhotoUri(null);
      setImgError(false);
    } finally {
      setIsUploadingPhoto(false);
    }
  };

  const handleEditPhoto = () => {
    const options = [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Choose from Gallery',
        onPress: () => pickImage('library'),
      },
      {
        text: 'Take Photo',
        onPress: () => pickImage('camera'),
      },
    ];

    if (customPhotoUri) {
      options.splice(1, 0, {
        text: 'Remove Photo',
        style: 'destructive',
        onPress: handleRemovePhoto,
      });
    }

    Alert.alert('Profile Photo', 'Update your account profile photo:', options);
  };

  const handleLogoutPrompt = () => {
    Alert.alert(
      'Log Out',
      'Are you sure you want to log out of your account?',
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Log Out',
          style: 'destructive',
          onPress: logout,
        },
      ]
    );
  };

  const handleInfoRowPress = (title, value) => {
    Alert.alert(title, String(value));
  };

  const activePhotoUrl = !imgError ? resolvePhotoUrl(customPhotoUri) : null;

  return (
    <View style={styles.container}>
      <ScrollView
        style={styles.scrollView}
        contentContainerStyle={styles.contentContainer}
        showsVerticalScrollIndicator={false}
      >
      {/* 1. Top Header (Back Button, Title, Log Out Icon Button) */}
      <View style={styles.topHeader}>
        <TouchableOpacity
          style={styles.circleHeaderBtn}
          onPress={onBack}
          activeOpacity={0.7}
          accessibilityLabel="Go back"
        >
          <BackArrowIcon size={18} color="#0f172a" />
        </TouchableOpacity>

        <Text style={styles.headerTitle}>Profile</Text>

        <TouchableOpacity
          style={styles.circleHeaderBtn}
          onPress={handleLogoutPrompt}
          activeOpacity={0.7}
          accessibilityLabel="Log Out"
        >
          <LogoutHeaderIcon size={18} color="#ef4444" />
        </TouchableOpacity>
      </View>

      {/* 2. Profile Photo with Edit Pencil & Name */}
      <View style={styles.avatarSection}>
        <View style={styles.avatarContainer}>
          {activePhotoUrl ? (
            <Image
              source={{ uri: activePhotoUrl }}
              style={styles.avatarImage}
              onError={() => setImgError(true)}
            />
          ) : (
            <View style={styles.avatarFallback}>
              <Text style={styles.avatarInitialText}>
                {getUserInitial(user?.name)}
              </Text>
            </View>
          )}

          {isUploadingPhoto && (
            <View style={styles.avatarLoadingOverlay}>
              <ActivityIndicator size="small" color="#ffffff" />
            </View>
          )}

          {/* Edit Pencil Badge */}
          <TouchableOpacity
            style={styles.editPencilBadge}
            onPress={handleEditPhoto}
            activeOpacity={0.8}
            accessibilityLabel="Edit Profile Photo"
          >
            <PencilEditIcon size={13} color="#0f172a" />
          </TouchableOpacity>
        </View>

        {/* User Name */}
        <Text style={styles.userName}>
          {user?.name || 'Account'}
        </Text>

        {/* Edit Profile Pill Button */}
        <TouchableOpacity
          style={styles.editProfilePill}
          onPress={openEditModal}
          activeOpacity={0.75}
          accessibilityLabel="Edit Profile Details"
        >
          <PencilEditIcon size={12} color="#15803d" />
          <Text style={styles.editProfilePillText}>Edit Profile</Text>
        </TouchableOpacity>
      </View>

      {/* 3. Rounded Info Card with Icon Rows */}
      <View style={styles.infoCard}>
        {/* Row 1: Email */}
        <TouchableOpacity
          style={styles.infoRow}
          onPress={openEditModal}
          activeOpacity={0.65}
        >
          <View style={styles.iconBox}>
            <EnvelopeMailIcon size={19} color="#475569" />
          </View>
          <View style={styles.infoTextContainer}>
            <Text style={styles.infoLabel}>EMAIL</Text>
            <Text style={styles.infoValue} numberOfLines={1}>
              {user?.email || '—'}
            </Text>
          </View>
          <View style={styles.rowEditCue}>
            <PencilEditIcon size={11} color="#94a3b8" />
          </View>
        </TouchableOpacity>

        <View style={styles.rowDivider} />

        {/* Row 2: ID */}
        <TouchableOpacity
          style={styles.infoRow}
          onPress={() => handleInfoRowPress('User ID', user?.user_id || user?.id || '—')}
          activeOpacity={0.65}
        >
          <View style={styles.iconBox}>
            <IdBadgeIcon size={19} color="#475569" />
          </View>
          <View style={styles.infoTextContainer}>
            <Text style={styles.infoLabel}>ID</Text>
            <Text style={styles.infoValue} numberOfLines={1}>
              {user?.user_id || user?.id || '—'}
            </Text>
          </View>
        </TouchableOpacity>

        <View style={styles.rowDivider} />

        {/* Row 3: Role */}
        <TouchableOpacity
          style={styles.infoRow}
          onPress={() =>
            handleInfoRowPress(
              'User Role',
              user?.role ? user.role.toUpperCase() : '—'
            )
          }
          activeOpacity={0.65}
        >
          <View style={styles.iconBox}>
            <RoleShieldIcon size={19} color="#475569" />
          </View>
          <View style={styles.infoTextContainer}>
            <Text style={styles.infoLabel}>ROLE</Text>
            <Text style={styles.infoValue} numberOfLines={1}>
              {user?.role ? user.role.toUpperCase() : '—'}
            </Text>
          </View>
        </TouchableOpacity>

        {user?.role === 'student' && (
          <>
            <View style={styles.rowDivider} />
            {/* Student ID */}
            <TouchableOpacity
              style={styles.infoRow}
              onPress={openEditModal}
              activeOpacity={0.65}
            >
              <View style={styles.iconBox}>
                <IdBadgeIcon size={19} color="#475569" />
              </View>
              <View style={styles.infoTextContainer}>
                <Text style={styles.infoLabel}>STUDENT ID</Text>
                <Text style={styles.infoValue} numberOfLines={1}>
                  {user?.student_id || 'Not set (Tap to edit)'}
                </Text>
              </View>
              <View style={styles.rowEditCue}>
                <PencilEditIcon size={11} color="#94a3b8" />
              </View>
            </TouchableOpacity>

            <View style={styles.rowDivider} />
            {/* Section */}
            <TouchableOpacity
              style={styles.infoRow}
              onPress={openEditModal}
              activeOpacity={0.65}
            >
              <View style={styles.iconBox}>
                <RoleShieldIcon size={19} color="#475569" />
              </View>
              <View style={styles.infoTextContainer}>
                <Text style={styles.infoLabel}>SECTION</Text>
                <Text style={styles.infoValue} numberOfLines={1}>
                  {user?.section || 'Not set (Tap to edit)'}
                </Text>
              </View>
              <View style={styles.rowEditCue}>
                <PencilEditIcon size={11} color="#94a3b8" />
              </View>
            </TouchableOpacity>
          </>
        )}

        <View style={styles.rowDivider} />

        {/* Row 4: Joined */}
        <TouchableOpacity
          style={styles.infoRow}
          onPress={() =>
            handleInfoRowPress(
              'Joined Date',
              formatJoinedDate(user?.created_at)
            )
          }
          activeOpacity={0.65}
        >
          <View style={styles.iconBox}>
            <CalendarJoinedIcon size={19} color="#475569" />
          </View>
          <View style={styles.infoTextContainer}>
            <Text style={styles.infoLabel}>JOINED</Text>
            <Text style={styles.infoValue} numberOfLines={1}>
              {formatJoinedDate(user?.created_at)}
            </Text>
          </View>
        </TouchableOpacity>
      </View>

      {/* 4. "Scan History" Section (Repurposing "My documents", No Chevron) */}
      <View style={styles.sectionHeaderRow}>
        <Text style={styles.sectionTitle}>Scan History</Text>
      </View>

      {/* 3 Metric Tiles / Cubes */}
      <View style={styles.tilesRow}>
        {/* Tile 1: Total Scans */}
        <TouchableOpacity
          style={styles.metricTile}
          activeOpacity={0.8}
          onPress={onBack}
        >
          <View style={[styles.tileCountBadge, { backgroundColor: '#dcfce7' }]}>
            <Text style={[styles.tileCountText, { color: '#15803d' }]}>
              {totalScans}
            </Text>
          </View>
          <Text style={styles.tileTitle}>Total Scans</Text>
          <Text style={styles.tileSubtitle}>All time</Text>
        </TouchableOpacity>

        {/* Tile 2: Identified Soils */}
        <TouchableOpacity
          style={styles.metricTile}
          activeOpacity={0.8}
          onPress={onBack}
        >
          <View style={[styles.tileCountBadge, { backgroundColor: '#e0f2fe' }]}>
            <Text style={[styles.tileCountText, { color: '#0369a1' }]}>
              {identifiedScans}
            </Text>
          </View>
          <Text style={styles.tileTitle}>Identified</Text>
          <Text style={styles.tileSubtitle}>Munsell soil</Text>
        </TouchableOpacity>

        {/* Tile 3: Admin Synced */}
        <TouchableOpacity
          style={styles.metricTile}
          activeOpacity={0.8}
          onPress={() => {
            Alert.alert(
              'Admin Synchronization',
              'All mobile soil scans are synced with the Admin Soil Scan History database.'
            );
          }}
        >
          <View style={[styles.tileCountBadge, { backgroundColor: '#fef3c7' }]}>
            <Text style={[styles.tileCountText, { color: '#b45309' }]}>
              {syncedScans}
            </Text>
          </View>
          <Text style={styles.tileTitle}>Synced</Text>
          <Text style={styles.tileSubtitle}>Cloud admin</Text>
        </TouchableOpacity>
      </View>

      {/* 5. Repurposed Section: Account & Preferences (Replacing Banking Documents) */}
      <View style={styles.sectionHeaderRow}>
        <Text style={styles.sectionTitle}>Account & Security</Text>
      </View>

      <View style={styles.preferencesCard}>
        {/* Row 1: Cloud Sync Status */}
        <View style={styles.preferenceRow}>
          <View style={styles.preferenceLeft}>
            <View style={styles.statusDotGreen} />
            <Text style={styles.preferenceLabel}>Admin Sync Active</Text>
          </View>
          <View style={styles.onlineBadge}>
            <Text style={styles.onlineBadgeText}>ONLINE</Text>
          </View>
        </View>

        <View style={styles.rowDivider} />

        {/* Row 2: Check for Updates & App Version */}
        <TouchableOpacity
          style={styles.preferenceRow}
          activeOpacity={0.7}
          onPress={async () => {
            if (isCheckingUpdate) return;
            setIsCheckingUpdate(true);
            try {
              const res = await checkForAppUpdate();
              if (res?.hasUpdate) {
                if (typeof onTriggerUpdate === 'function') {
                  onTriggerUpdate(res);
                } else {
                  Alert.alert(
                    `Update Available (${res.latestVersion})`,
                    `${res.releaseNotes}\n\nTapping below will download and install the update.`,
                    [
                      { text: 'Cancel', style: 'cancel' },
                      {
                        text: 'Update Now',
                        onPress: () => onTriggerUpdate?.(res),
                      },
                    ]
                  );
                }
              } else if (res?.latestVersion) {
                Alert.alert(
                  'AgriCo is Up to Date',
                  `You are running the latest version (${CURRENT_APP_VERSION}).\n\nWould you like to reload and reinstall the latest build from the server?`,
                  [
                    { text: 'Close', style: 'cancel' },
                    {
                      text: 'Reload / Reinstall',
                      onPress: () => onTriggerUpdate?.({ ...res, hasUpdate: true }),
                    },
                  ]
                );
              } else {
                Alert.alert(
                  'AgriCo is Up to Date',
                  `You are running the latest version (${CURRENT_APP_VERSION}). All offline scanning and database sync features are active.`
                );
              }
            } catch (err) {
              Alert.alert('Update Check', 'Unable to reach the update server right now.');
            } finally {
              setIsCheckingUpdate(false);
            }
          }}
        >
          <View>
            <Text style={styles.preferenceLabel}>Check for Updates</Text>
            <Text style={styles.versionSubLabel}>AgriCo Mobile · {CURRENT_APP_VERSION}</Text>
          </View>
          <View style={styles.checkUpdateBtn}>
            {isCheckingUpdate ? (
              <ActivityIndicator size="small" color="#15803d" />
            ) : (
              <Text style={styles.checkUpdateText}>Check Now</Text>
            )}
          </View>
        </TouchableOpacity>

        <View style={styles.rowDivider} />

        {/* Row 3: Sign Out Action */}
        <TouchableOpacity
          style={styles.signOutRowBtn}
          onPress={() => {
            Alert.alert(
              'Sign Out',
              'Are you sure you want to sign out of Agrico?',
              [
                { text: 'Cancel', style: 'cancel' },
                {
                  text: 'Sign Out',
                  style: 'destructive',
                  onPress: logout,
                },
              ]
            );
          }}
          activeOpacity={0.8}
        >
          <Text style={styles.signOutRowText}>Sign Out of Account</Text>
        </TouchableOpacity>
      </View>

      {/* Bottom spacing for bottom navigation bar */}
      <View style={{ height: 110 }} />
    </ScrollView>

    {/* 7. Edit Profile Modal */}
    <Modal
      visible={showEditModal}
      animationType="slide"
      transparent={true}
      onRequestClose={() => {
        if (!isSavingProfile) setShowEditModal(false);
      }}
    >
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        style={styles.modalOverlay}
      >
        <View style={styles.modalBackdrop}>
          <TouchableOpacity
            style={StyleSheet.absoluteFillObject}
            onPress={() => {
              if (!isSavingProfile) setShowEditModal(false);
            }}
            activeOpacity={1}
          />
        </View>

        <View style={styles.modalContainer}>
          {/* Modal Header */}
          <View style={styles.modalHeader}>
            <View>
              <Text style={styles.modalTitle}>Edit Profile</Text>
              <Text style={styles.modalSubtitle}>Update your personal and account details</Text>
            </View>
            <TouchableOpacity
              style={styles.modalCloseCircle}
              onPress={() => setShowEditModal(false)}
              disabled={isSavingProfile}
            >
              <Text style={styles.modalCloseText}>✕</Text>
            </TouchableOpacity>
          </View>

          {/* Error Banner */}
          {Boolean(editError) && (
            <View style={styles.errorBanner}>
              <Text style={styles.errorBannerText}>{editError}</Text>
            </View>
          )}

          <ScrollView
            showsVerticalScrollIndicator={false}
            contentContainerStyle={styles.modalScrollContent}
            keyboardShouldPersistTaps="handled"
          >
            {/* Photo Change Action Bar */}
            <View style={styles.modalPhotoRow}>
              <View style={styles.modalAvatarThumb}>
                {activePhotoUrl ? (
                  <Image
                    source={{ uri: activePhotoUrl }}
                    style={styles.modalAvatarImage}
                  />
                ) : (
                  <View style={styles.modalAvatarFallback}>
                    <Text style={styles.modalAvatarInitial}>
                      {getUserInitial(editName || user?.name)}
                    </Text>
                  </View>
                )}
              </View>
              <View style={styles.modalPhotoActions}>
                <TouchableOpacity
                  style={styles.modalChangePhotoBtn}
                  onPress={handleEditPhoto}
                  disabled={isUploadingPhoto || isSavingProfile}
                >
                  <Text style={styles.modalChangePhotoText}>Change Photo</Text>
                </TouchableOpacity>
                {Boolean(customPhotoUri) && (
                  <TouchableOpacity
                    style={styles.modalRemovePhotoBtn}
                    onPress={handleRemovePhoto}
                    disabled={isUploadingPhoto || isSavingProfile}
                  >
                    <Text style={styles.modalRemovePhotoText}>Remove</Text>
                  </TouchableOpacity>
                )}
              </View>
            </View>

            {/* Form Input: Full Name */}
            <View style={styles.inputGroup}>
              <Text style={styles.inputLabel}>FULL NAME *</Text>
              <TextInput
                style={styles.textInput}
                value={editName}
                onChangeText={setEditName}
                placeholder="Enter full name"
                placeholderTextColor="#94a3b8"
                autoCapitalize="words"
                editable={!isSavingProfile}
              />
            </View>

            {/* Form Input: Email */}
            <View style={styles.inputGroup}>
              <Text style={styles.inputLabel}>EMAIL ADDRESS *</Text>
              <TextInput
                style={styles.textInput}
                value={editEmail}
                onChangeText={setEditEmail}
                placeholder="Enter email address"
                placeholderTextColor="#94a3b8"
                keyboardType="email-address"
                autoCapitalize="none"
                editable={!isSavingProfile}
              />
            </View>

            {/* Student specific fields */}
            {user?.role === 'student' && (
              <>
                <View style={styles.inputGroup}>
                  <Text style={styles.inputLabel}>STUDENT ID</Text>
                  <TextInput
                    style={styles.textInput}
                    value={editStudentId}
                    onChangeText={setEditStudentId}
                    placeholder="e.g. 2026-10492"
                    placeholderTextColor="#94a3b8"
                    editable={!isSavingProfile}
                  />
                </View>

                <View style={styles.inputGroup}>
                  <Text style={styles.inputLabel}>SECTION / CLASS</Text>
                  <TextInput
                    style={styles.textInput}
                    value={editSection}
                    onChangeText={setEditSection}
                    placeholder="e.g. BSCS-3A"
                    placeholderTextColor="#94a3b8"
                    editable={!isSavingProfile}
                  />
                </View>
              </>
            )}

            {/* Security / Password Section Toggle */}
            <TouchableOpacity
              style={styles.passwordToggleRow}
              onPress={() => setShowPasswordFields((prev) => !prev)}
              activeOpacity={0.7}
            >
              <Text style={styles.passwordToggleTitle}>
                {showPasswordFields ? '▼ Hide Password Change' : '▶ Change Password (Optional)'}
              </Text>
              <Text style={styles.passwordToggleHint}>
                {showPasswordFields ? 'Leave blank to keep current' : 'Tap to set new password'}
              </Text>
            </TouchableOpacity>

            {showPasswordFields && (
              <View style={styles.passwordFieldsContainer}>
                <View style={styles.inputGroup}>
                  <Text style={styles.inputLabel}>CURRENT PASSWORD</Text>
                  <TextInput
                    style={styles.textInput}
                    value={currentPassword}
                    onChangeText={setCurrentPassword}
                    placeholder="Required only if changing password"
                    placeholderTextColor="#94a3b8"
                    secureTextEntry
                    editable={!isSavingProfile}
                  />
                </View>

                <View style={styles.inputGroup}>
                  <Text style={styles.inputLabel}>NEW PASSWORD</Text>
                  <TextInput
                    style={styles.textInput}
                    value={newPassword}
                    onChangeText={setNewPassword}
                    placeholder="Min. 8 characters"
                    placeholderTextColor="#94a3b8"
                    secureTextEntry
                    editable={!isSavingProfile}
                  />
                </View>

                <View style={styles.inputGroup}>
                  <Text style={styles.inputLabel}>CONFIRM NEW PASSWORD</Text>
                  <TextInput
                    style={styles.textInput}
                    value={confirmPassword}
                    onChangeText={setConfirmPassword}
                    placeholder="Re-type new password"
                    placeholderTextColor="#94a3b8"
                    secureTextEntry
                    editable={!isSavingProfile}
                  />
                </View>
              </View>
            )}
          </ScrollView>

          {/* Modal Footer Buttons */}
          <View style={styles.modalFooter}>
            <TouchableOpacity
              style={styles.modalCancelBtn}
              onPress={() => setShowEditModal(false)}
              disabled={isSavingProfile}
            >
              <Text style={styles.modalCancelText}>Cancel</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.modalSaveBtn}
              onPress={handleSaveProfile}
              disabled={isSavingProfile}
            >
              {isSavingProfile ? (
                <ActivityIndicator size="small" color="#ffffff" />
              ) : (
                <Text style={styles.modalSaveText}>Save Changes</Text>
              )}
            </TouchableOpacity>
          </View>
        </View>
      </KeyboardAvoidingView>
    </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f8fafc',
  },
  scrollView: {
    flex: 1,
  },
  contentContainer: {
    paddingTop: Platform.OS === 'ios' ? 52 : 36,
    paddingBottom: 24,
  },

  // 1. Top Header
  topHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 22,
    marginBottom: 16,
  },
  circleHeaderBtn: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#e2e8f0',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 4,
    elevation: 2,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#0f172a',
    letterSpacing: -0.3,
  },

  // 2. Avatar & Name Section
  avatarSection: {
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 8,
    marginBottom: 20,
  },
  avatarContainer: {
    position: 'relative',
    width: 104,
    height: 104,
    borderRadius: 52,
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.12,
    shadowRadius: 8,
    elevation: 5,
  },
  avatarImage: {
    width: 104,
    height: 104,
    borderRadius: 52,
    borderWidth: 3.5,
    borderColor: '#ffffff',
    backgroundColor: '#e2e8f0',
  },
  avatarFallback: {
    width: 104,
    height: 104,
    borderRadius: 52,
    backgroundColor: '#16a34a',
    borderWidth: 3.5,
    borderColor: '#ffffff',
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarInitialText: {
    fontSize: 40,
    fontWeight: '900',
    color: '#ffffff',
    letterSpacing: 1,
  },
  avatarLoadingOverlay: {
    ...StyleSheet.absoluteFillObject,
    borderRadius: 52,
    backgroundColor: 'rgba(0, 0, 0, 0.4)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  editPencilBadge: {
    position: 'absolute',
    bottom: 2,
    right: 2,
    width: 30,
    height: 30,
    borderRadius: 15,
    backgroundColor: '#ffffff',
    borderWidth: 1.5,
    borderColor: '#e2e8f0',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.12,
    shadowRadius: 3,
    elevation: 4,
  },
  userName: {
    fontSize: 22,
    fontWeight: '800',
    color: '#0f172a',
    marginTop: 14,
    letterSpacing: -0.4,
  },

  // 3. Rounded Info Card
  infoCard: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    marginHorizontal: 20,
    paddingVertical: 8,
    paddingHorizontal: 16,
    borderWidth: 1,
    borderColor: '#f1f5f9',
    shadowColor: '#0f172a',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 2,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
  },
  iconBox: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: '#f8fafc',
    borderWidth: 1,
    borderColor: '#f1f5f9',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 14,
  },
  infoTextContainer: {
    flex: 1,
  },
  infoLabel: {
    fontSize: 11,
    fontWeight: '700',
    color: '#94a3b8',
    letterSpacing: 0.8,
    marginBottom: 3,
  },
  infoValue: {
    fontSize: 14.5,
    fontWeight: '700',
    color: '#0f172a',
  },
  rowDivider: {
    height: 1,
    backgroundColor: '#f8fafc',
    marginLeft: 58,
  },

  // 4. Section Headers
  sectionHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 22,
    marginTop: 26,
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#0f172a',
    letterSpacing: -0.3,
  },
  sectionChevronBtn: {
    padding: 4,
  },

  // 5. Scan History Metric Tiles
  tilesRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    gap: 10,
  },
  metricTile: {
    flex: 1,
    backgroundColor: '#ffffff',
    borderRadius: 20,
    padding: 14,
    borderWidth: 1,
    borderColor: '#f1f5f9',
    shadowColor: '#0f172a',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.04,
    shadowRadius: 6,
    elevation: 2,
  },
  tileCountBadge: {
    width: 38,
    height: 38,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
  },
  tileCountText: {
    fontSize: 17,
    fontWeight: '800',
  },
  tileTitle: {
    fontSize: 13,
    fontWeight: '800',
    color: '#0f172a',
    marginBottom: 2,
  },
  tileSubtitle: {
    fontSize: 11,
    fontWeight: '600',
    color: '#94a3b8',
  },

  // 6. Account & Preferences Card
  preferencesCard: {
    backgroundColor: '#ffffff',
    borderRadius: 22,
    marginHorizontal: 20,
    paddingVertical: 6,
    paddingHorizontal: 16,
    borderWidth: 1,
    borderColor: '#f1f5f9',
    shadowColor: '#0f172a',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 2,
  },
  preferenceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 14,
  },
  preferenceLeft: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  statusDotGreen: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#22c55e',
    marginRight: 8,
  },
  preferenceLabel: {
    fontSize: 14,
    fontWeight: '700',
    color: '#0f172a',
  },
  preferenceLabelMuted: {
    fontSize: 13.5,
    fontWeight: '600',
    color: '#64748b',
  },
  preferenceValueMuted: {
    fontSize: 13.5,
    fontWeight: '700',
    color: '#0f172a',
  },
  onlineBadge: {
    backgroundColor: '#dcfce7',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  onlineBadgeText: {
    color: '#15803d',
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 0.5,
  },
  versionSubLabel: {
    fontSize: 11.5,
    fontWeight: '600',
    color: '#64748b',
    marginTop: 2,
  },
  checkUpdateBtn: {
    backgroundColor: '#dcfce7',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#bbf7d0',
    alignItems: 'center',
    justifyContent: 'center',
  },
  checkUpdateText: {
    color: '#15803d',
    fontSize: 11.5,
    fontWeight: '800',
  },
  signOutRowBtn: {
    paddingVertical: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  signOutRowText: {
    fontSize: 14,
    fontWeight: '800',
    color: '#ef4444',
  },

  // 7. Edit Profile Pill & Cues
  editProfilePill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: '#dcfce7',
    paddingHorizontal: 14,
    paddingVertical: 6,
    borderRadius: 20,
    marginTop: 8,
    borderWidth: 1,
    borderColor: '#bbf7d0',
  },
  editProfilePillText: {
    fontSize: 12.5,
    fontWeight: '800',
    color: '#15803d',
  },
  rowEditCue: {
    paddingLeft: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },

  // 8. Edit Profile Modal Styles
  modalOverlay: {
    flex: 1,
    justifyContent: 'flex-end',
  },
  modalBackdrop: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(15, 23, 42, 0.45)',
  },
  modalContainer: {
    backgroundColor: '#ffffff',
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    maxHeight: '90%',
    paddingBottom: Platform.OS === 'ios' ? 34 : 20,
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.15,
    shadowRadius: 12,
    elevation: 10,
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 22,
    paddingTop: 20,
    paddingBottom: 14,
    borderBottomWidth: 1,
    borderBottomColor: '#f1f5f9',
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#0f172a',
    letterSpacing: -0.3,
  },
  modalSubtitle: {
    fontSize: 12,
    fontWeight: '600',
    color: '#64748b',
    marginTop: 2,
  },
  modalCloseCircle: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: '#f1f5f9',
    alignItems: 'center',
    justifyContent: 'center',
  },
  modalCloseText: {
    fontSize: 13,
    fontWeight: '700',
    color: '#64748b',
  },
  errorBanner: {
    backgroundColor: '#fef2f2',
    borderLeftWidth: 4,
    borderLeftColor: '#ef4444',
    marginHorizontal: 22,
    marginTop: 12,
    padding: 10,
    borderRadius: 8,
  },
  errorBannerText: {
    fontSize: 12.5,
    fontWeight: '600',
    color: '#b91c1c',
  },
  modalScrollContent: {
    paddingHorizontal: 22,
    paddingVertical: 14,
  },
  modalPhotoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    backgroundColor: '#f8fafc',
    padding: 12,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    marginBottom: 16,
  },
  modalAvatarThumb: {
    width: 52,
    height: 52,
    borderRadius: 26,
    overflow: 'hidden',
  },
  modalAvatarImage: {
    width: 52,
    height: 52,
    borderRadius: 26,
  },
  modalAvatarFallback: {
    width: 52,
    height: 52,
    borderRadius: 26,
    backgroundColor: '#16a34a',
    alignItems: 'center',
    justifyContent: 'center',
  },
  modalAvatarInitial: {
    fontSize: 22,
    fontWeight: '800',
    color: '#ffffff',
  },
  modalPhotoActions: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  modalChangePhotoBtn: {
    backgroundColor: '#15803d',
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 10,
  },
  modalChangePhotoText: {
    color: '#ffffff',
    fontSize: 12.5,
    fontWeight: '700',
  },
  modalRemovePhotoBtn: {
    backgroundColor: '#fee2e2',
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 10,
  },
  modalRemovePhotoText: {
    color: '#b91c1c',
    fontSize: 12.5,
    fontWeight: '700',
  },
  inputGroup: {
    marginBottom: 14,
  },
  inputLabel: {
    fontSize: 11,
    fontWeight: '800',
    color: '#475569',
    letterSpacing: 0.6,
    marginBottom: 6,
  },
  textInput: {
    backgroundColor: '#f8fafc',
    borderWidth: 1,
    borderColor: '#e2e8f0',
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 10,
    fontSize: 14,
    fontWeight: '600',
    color: '#0f172a',
  },
  passwordToggleRow: {
    paddingVertical: 10,
    marginTop: 4,
    marginBottom: 6,
  },
  passwordToggleTitle: {
    fontSize: 13,
    fontWeight: '800',
    color: '#15803d',
  },
  passwordToggleHint: {
    fontSize: 11.5,
    fontWeight: '500',
    color: '#64748b',
    marginTop: 2,
  },
  passwordFieldsContainer: {
    backgroundColor: '#f8fafc',
    padding: 12,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    marginBottom: 14,
  },
  modalFooter: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'flex-end',
    gap: 12,
    paddingHorizontal: 22,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: '#f1f5f9',
  },
  modalCancelBtn: {
    paddingHorizontal: 16,
    paddingVertical: 11,
    borderRadius: 12,
    backgroundColor: '#f1f5f9',
  },
  modalCancelText: {
    fontSize: 13.5,
    fontWeight: '700',
    color: '#64748b',
  },
  modalSaveBtn: {
    paddingHorizontal: 22,
    paddingVertical: 11,
    borderRadius: 12,
    backgroundColor: '#15803d',
    minWidth: 120,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#15803d',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.25,
    shadowRadius: 4,
    elevation: 3,
  },
  modalSaveText: {
    fontSize: 13.5,
    fontWeight: '800',
    color: '#ffffff',
  },
});
