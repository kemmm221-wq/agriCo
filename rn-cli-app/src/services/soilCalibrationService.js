import RNFS from 'react-native-fs';
import api from './api';
import BUNDLED_CALIBRATION from '../data/soilReferenceDataset.json';

const CACHE_FILE_PATH = `${RNFS.DocumentDirectoryPath}/soil_reference_cache.json`;
const IMAGES_DIR = `${RNFS.DocumentDirectoryPath}/soil_reference_images`;

let inMemoryCalibration = null;

const REAL_IMAGES_DIR = `${IMAGES_DIR}/real`;
const FAKE_IMAGES_DIR = `${IMAGES_DIR}/fake`;

async function ensureImagesDir() {
  try {
    if (!(await RNFS.exists(IMAGES_DIR))) {
      await RNFS.mkdir(IMAGES_DIR);
    }
    if (!(await RNFS.exists(REAL_IMAGES_DIR))) {
      await RNFS.mkdir(REAL_IMAGES_DIR);
    }
    if (!(await RNFS.exists(FAKE_IMAGES_DIR))) {
      await RNFS.mkdir(FAKE_IMAGES_DIR);
    }
  } catch (e) {
    // ignore
  }
}

/**
 * Helper to emit progress message and percentage
 */
function reportProgress(callback, text, percent) {
  if (typeof callback === 'function') {
    try {
      callback(text, percent);
    } catch (e) {}
  }
}

/**
 * Get active soil calibration data (Stage 1 Soil Gate parameters + real and fake reference centroids).
 * Returns cached website data if available, falling back to bundled dataset.
 */
export async function getActiveCalibration() {
  if (inMemoryCalibration && inMemoryCalibration.reference_centroids?.length > 0) {
    return inMemoryCalibration;
  }

  try {
    if (await RNFS.exists(CACHE_FILE_PATH)) {
      const content = await RNFS.readFile(CACHE_FILE_PATH, 'utf8');
      const parsed = JSON.parse(content);
      if (parsed && typeof parsed === 'object') {
        // Guarantee reference_centroids has valid spectral color coordinates
        const hasValidCentroids = Array.isArray(parsed.reference_centroids) &&
          parsed.reference_centroids.length > 0 &&
          (parsed.reference_centroids[0].lab || parsed.reference_centroids[0].rgb || parsed.reference_centroids[0].hexCode);

        if (!hasValidCentroids) {
          parsed.reference_centroids = BUNDLED_CALIBRATION.reference_centroids;
        }

        if (!Array.isArray(parsed.fake_centroids) || parsed.fake_centroids.length === 0) {
          parsed.fake_centroids = BUNDLED_CALIBRATION.fake_centroids;
        }

        if (!Array.isArray(parsed.real_samples) || parsed.real_samples.length === 0) {
          parsed.real_samples = BUNDLED_CALIBRATION.real_samples;
        }

        if (!Array.isArray(parsed.fake_samples) || parsed.fake_samples.length === 0) {
          parsed.fake_samples = BUNDLED_CALIBRATION.fake_samples;
        }

        if (!parsed.gate_config) {
          parsed.gate_config = BUNDLED_CALIBRATION.gate_config;
        }

        inMemoryCalibration = parsed;
        return inMemoryCalibration;
      }
    }
  } catch (err) {
    console.warn('[SoilCalibrationService] Error reading cached calibration:', err?.message);
  }

  inMemoryCalibration = BUNDLED_CALIBRATION;
  return inMemoryCalibration;
}

/**
 * Sync latest Stage 1 real soil and non-soil (fake soil) reference datasets and images from website API.
 * Called during Splash / Loading screen or on demand.
 * Downloads and stores both real soil and fake soil images locally on device for 100% offline accuracy.
 */
export async function syncSoilCalibrationFromWebsite(onProgress) {
  try {
    reportProgress(onProgress, 'Connecting to AgriCo Soil Database...', 0.08);

    const response = await api.get('/real-soil/calibration', { timeout: 9000 });
    if (response?.data?.status === 'success' && response?.data?.gate_config) {
      await ensureImagesDir();

      const realSamples = response.data.real_samples || response.data.reference_samples || [];
      const fakeSamples = response.data.fake_samples || [];
      const cachedReal = [];
      const cachedFake = [];

      // 1. Download representative real soil samples (up to 12)
      const realLimit = Math.min(12, realSamples.length);
      for (let i = 0; i < realLimit; i++) {
        const sample = realSamples[i];
        const progressVal = 0.12 + (i / Math.max(1, realLimit)) * 0.40; // 0.12 -> 0.52
        reportProgress(
          onProgress,
          `Syncing real soil dataset (${i + 1}/${realLimit})...`,
          progressVal
        );

        if (sample?.url) {
          const filename = sample.filename || `real_${i}.jpg`;
          const localPath = `${REAL_IMAGES_DIR}/${filename}`;
          try {
            const fileExists = await RNFS.exists(localPath);
            if (!fileExists) {
              await RNFS.downloadFile({
                fromUrl: sample.url,
                toFile: localPath,
                connectionTimeout: 4000,
                readTimeout: 4000,
              }).promise;
            }
            cachedReal.push({
              ...sample,
              localUri: `file://${localPath}`,
            });
          } catch (dlErr) {
            cachedReal.push(sample);
          }
        } else {
          cachedReal.push(sample);
        }
      }
      for (let i = realLimit; i < realSamples.length; i++) {
        cachedReal.push(realSamples[i]);
      }

      // 2. Download representative fake soil / non-soil samples (up to 12)
      const fakeLimit = Math.min(12, fakeSamples.length);
      for (let j = 0; j < fakeLimit; j++) {
        const sample = fakeSamples[j];
        const progressVal = 0.54 + (j / Math.max(1, fakeLimit)) * 0.36; // 0.54 -> 0.90
        reportProgress(
          onProgress,
          `Syncing non-soil references (${j + 1}/${fakeLimit})...`,
          progressVal
        );

        if (sample?.url) {
          const filename = sample.filename || `fake_${j}.jpg`;
          const localPath = `${FAKE_IMAGES_DIR}/${filename}`;
          try {
            const fileExists = await RNFS.exists(localPath);
            if (!fileExists) {
              await RNFS.downloadFile({
                fromUrl: sample.url,
                toFile: localPath,
                connectionTimeout: 4000,
                readTimeout: 4000,
              }).promise;
            }
            cachedFake.push({
              ...sample,
              localUri: `file://${localPath}`,
            });
          } catch (dlErr) {
            cachedFake.push(sample);
          }
        } else {
          cachedFake.push(sample);
        }
      }
      for (let j = fakeLimit; j < fakeSamples.length; j++) {
        cachedFake.push(fakeSamples[j]);
      }

      reportProgress(onProgress, 'Updating offline soil gate engine...', 0.94);

      const merged = {
        version: response.data.model_version || '2.1.0',
        name: 'AgriCo Calibrated Real & Non-Soil Reference Dataset',
        gate_config: response.data.gate_config,
        reference_centroids: cachedReal.length > 0 ? cachedReal : BUNDLED_CALIBRATION.reference_centroids,
        real_samples: cachedReal,
        fake_samples: cachedFake,
        fake_centroids: response.data.fake_centroids || BUNDLED_CALIBRATION.fake_centroids || [],
        munsell_colors: response.data.munsell_colors || null,
        synced_at: new Date().toISOString(),
      };

      await RNFS.writeFile(CACHE_FILE_PATH, JSON.stringify(merged), 'utf8');
      inMemoryCalibration = merged;

      reportProgress(onProgress, '✓ Offline soil models synchronized', 1.0);

      return {
        success: true,
        realCount: cachedReal.length,
        fakeCount: cachedFake.length,
        version: merged.version,
      };
    }
  } catch (err) {
    console.warn('[SoilCalibrationService] Calibration sync skipped (offline or server unreachable):', err?.message);
  }

  inMemoryCalibration = await getActiveCalibration();
  reportProgress(onProgress, '✓ Offline soil models active (ready)', 1.0);

  return {
    success: false,
    fallback: true,
    realCount: (inMemoryCalibration.reference_centroids || []).length,
    fakeCount: (inMemoryCalibration.fake_samples || []).length,
    version: inMemoryCalibration.version || '2.0.0',
  };
}

/**
 * Return real soil reference samples for Stage 1.
 */
export async function getStage1ReferenceSamples() {
  const cal = await getActiveCalibration();
  return cal.real_samples || cal.reference_centroids || BUNDLED_CALIBRATION.reference_centroids || [];
}

/**
 * Return non-soil (fake soil) reference samples.
 */
export async function getStage1FakeSamples() {
  const cal = await getActiveCalibration();
  return cal.fake_samples || [];
}

