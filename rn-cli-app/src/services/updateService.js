import { NativeModules, Linking, Platform } from 'react-native';
import RNFS from 'react-native-fs';
import api from './api';

export const CURRENT_APP_VERSION = 'v1.0.8';

/**
 * Compare two semver-like version strings (e.g., 'v1.0.1' vs 'v1.0.0').
 * Returns 1 if vA > vB, -1 if vA < vB, 0 if equal.
 */
export const compareVersions = (vA, vB) => {
  if (!vA || !vB) return 0;
  const cleanA = String(vA).replace(/^[^\d]*/, '').split('.').map((n) => parseInt(n, 10) || 0);
  const cleanB = String(vB).replace(/^[^\d]*/, '').split('.').map((n) => parseInt(n, 10) || 0);

  const len = Math.max(cleanA.length, cleanB.length);
  for (let i = 0; i < len; i++) {
    const numA = cleanA[i] || 0;
    const numB = cleanB[i] || 0;
    if (numA > numB) return 1;
    if (numA < numB) return -1;
  }
  return 0;
};

/**
 * Check backend for new mobile application releases.
 */
export const checkForAppUpdate = async () => {
  try {
    const res = await api.get('/app-version', {
      params: { current_version: CURRENT_APP_VERSION },
    });
    if (res.data?.status === 'success' && res.data?.latest_version) {
      const serverVersion = res.data.latest_version;
      const isNewer = compareVersions(serverVersion, CURRENT_APP_VERSION) > 0;

      let directApk = res.data.direct_apk_url || '';

      return {
        hasUpdate: isNewer,
        currentVersion: CURRENT_APP_VERSION,
        latestVersion: serverVersion,
        releaseNotes: res.data.release_notes || 'New improvements and bug fixes.',
        fileSize: res.data.file_size || '62 MB',
        downloadUrl: res.data.download_url,
        directApkUrl: directApk,
        isMaintenance: Boolean(res.data.is_maintenance),
      };
    }
  } catch (error) {
    // Offline or network error - silently skip
    console.log('[UpdateService] Update check skipped (offline or unreachable):', error?.message);
  }
  return { hasUpdate: false };
};

/**
 * Download the APK and trigger the Android package installer.
 */
export const downloadAndInstallApk = async (apkUrl, onProgress) => {
  let cleanUrl = apkUrl;

  if (Platform.OS !== 'android') {
    if (cleanUrl) {
      Linking.openURL(cleanUrl);
    }
    return;
  }

  const targetApkPath = `${RNFS.CachesDirectoryPath}/agrico-update.apk`;

  // Remove any previously downloaded temp APK
  if (await RNFS.exists(targetApkPath)) {
    try {
      await RNFS.unlink(targetApkPath);
    } catch (_) {}
  }

  const downloadOptions = {
    fromUrl: apkUrl,
    toFile: targetApkPath,
    background: true,
    progressDivider: 2,
    progress: (res) => {
      if (res.totalBytesExpectedToSend > 0 && typeof onProgress === 'function') {
        const percent = Math.min(
          100,
          Math.round((res.totalBytesWritten / res.totalBytesExpectedToSend) * 100)
        );
        onProgress(percent, res.totalBytesWritten, res.totalBytesExpectedToSend);
      }
    },
  };

  const downloadResult = await RNFS.downloadFile(downloadOptions).promise;

  if (downloadResult.statusCode === 200) {
    if (NativeModules.InstallApk && typeof NativeModules.InstallApk.install === 'function') {
      try {
        await NativeModules.InstallApk.install(targetApkPath);
        return true;
      } catch (installErr) {
        console.warn('[UpdateService] Native install prompt error:', installErr?.message);
      }
    }
  }

  // Fallback to direct browser download if native install cannot trigger
  if (apkUrl) {
    Linking.openURL(apkUrl);
  }
};
