import RNFS from 'react-native-fs';

const STORE_PATH = `${RNFS.DocumentDirectoryPath}/local_scan_history.json`;

let inMemoryScans = null;

/**
 * Retrieve all local scans from persistent file storage.
 * Runs instantly on device even when completely offline.
 */
export async function getLocalScans(targetUserId = null) {
  let all = inMemoryScans;
  if (all === null) {
    try {
      if (await RNFS.exists(STORE_PATH)) {
        const content = await RNFS.readFile(STORE_PATH, 'utf8');
        const parsed = JSON.parse(content);
        if (Array.isArray(parsed)) {
          inMemoryScans = parsed;
          all = parsed;
        }
      }
    } catch (err) {
      console.warn('[LocalScanStore] Failed to read local scans:', err?.message);
    }
  }
  if (!all) {
    inMemoryScans = [];
    all = [];
  }
  if (targetUserId) {
    return all.filter((s) => !s.user_id || String(s.user_id) === String(targetUserId));
  }
  return all;
}

/**
 * Save / prepend a scan into local store immediately.
 */
export async function saveLocalScan(scanItem) {
  try {
    const existing = await getLocalScans();
    const targetKey = scanItem.scanId || scanItem.soil_scan_id;
    const filtered = existing.filter(
      (item) => (item.scanId || item.soil_scan_id) !== targetKey
    );
    const updated = [scanItem, ...filtered];
    inMemoryScans = updated;
    await RNFS.writeFile(STORE_PATH, JSON.stringify(updated), 'utf8');
    return updated;
  } catch (err) {
    console.warn('[LocalScanStore] Failed to save local scan:', err?.message);
    return inMemoryScans || [];
  }
}

/**
 * Merge remote server scans with any pending offline scans.
 * Ensures offline scans awaiting sync stay visible at the top.
 */
export async function mergeServerScans(serverScans, targetUserId = null) {
  try {
    const existing = await getLocalScans();
    const pendingOffline = existing.filter((s) => {
      const isQueued = s.syncState === 'queued';
      if (!isQueued) return false;
      if (targetUserId && s.user_id && String(s.user_id) !== String(targetUserId)) {
        return false;
      }
      return true;
    });
    const serverScanIds = new Set(
      serverScans.map((s) => String(s.soil_scan_id || s.id || s.scanId))
    );

    const stillPending = pendingOffline.filter(
      (p) => !serverScanIds.has(String(p.soil_scan_id)) && !serverScanIds.has(String(p.scanId))
    );

    // Keep other accounts' scans in storage if partitioned, but merge active account scans
    const otherAccountScans = targetUserId
      ? existing.filter((s) => s.user_id && String(s.user_id) !== String(targetUserId) && s.syncState !== 'queued')
      : [];

    const activeMerged = [...stillPending, ...serverScans];
    const fullStore = [...activeMerged, ...otherAccountScans];
    inMemoryScans = fullStore;
    await RNFS.writeFile(STORE_PATH, JSON.stringify(fullStore), 'utf8');
    return activeMerged;
  } catch (err) {
    console.warn('[LocalScanStore] Failed to merge server scans:', err?.message);
    return serverScans;
  }
}

/**
 * Update scan state when successfully synced to server.
 */
export async function markScanSynced(scanId, { serverScanId, serverImageUrl }) {
  try {
    const existing = await getLocalScans();
    let updatedAny = false;
    const updated = existing.map((item) => {
      if (item.scanId === scanId || item.soil_scan_id === scanId) {
        updatedAny = true;
        return {
          ...item,
          soil_scan_id: serverScanId,
          id: 'scan_' + serverScanId,
          syncState: 'synced',
          isOffline: false,
          scanImage: serverImageUrl || item.scanImage,
        };
      }
      return item;
    });

    if (updatedAny) {
      inMemoryScans = updated;
      await RNFS.writeFile(STORE_PATH, JSON.stringify(updated), 'utf8');
    }
    return inMemoryScans;
  } catch (err) {
    console.warn('[LocalScanStore] Failed to mark scan synced:', err?.message);
    return inMemoryScans || [];
  }
}
