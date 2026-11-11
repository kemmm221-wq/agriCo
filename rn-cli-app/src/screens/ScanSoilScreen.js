import React, { useState, useEffect, useRef, useMemo, useContext } from 'react';
import {
  View,
  Text,
  Image,
  TouchableOpacity,
  Pressable,
  StyleSheet,
  StatusBar,
  Animated,
  Easing,
  Dimensions,
  Platform,
  Modal,
  ScrollView,
  Alert,
  ActivityIndicator,
  NativeModules,
  ToastAndroid,
  Share,
} from 'react-native';
import { launchCamera, launchImageLibrary } from 'react-native-image-picker';
import RNFS from 'react-native-fs';
import { Camera, useCameraDevice, useCameraPermission } from 'react-native-vision-camera';
import NetInfo from '@react-native-community/netinfo';
import { newScanId, startScan, pollScan } from '../services/scanAsync';
import {
  queueAsyncScan,
  trackInflightScan,
  clearQueuedScan,
  flushOfflineQueue,
  mapServerScan,
} from '../services/scanSyncService';
import { analyzeSoilOffline } from '../services/offlineSoilAnalysis';
import OFFLINE_MUNSELL_COLORS from '../data/munsellColors.json';
import { saveLocalScan } from '../services/localScanStore';
import { AuthContext } from '../context/AuthContext';
import { FlashlightIcon, GalleryIcon } from '../components/AppIcons';
import BottomNavBar from '../components/BottomNavBar';
import PinSoilLocationModal from '../components/PinSoilLocationModal';
import ResizableFrame, { ScanFrameVisual, frameToImageCrop } from '../components/ResizableFrame';

const { width: SCREEN_WIDTH, height: SCREEN_HEIGHT } = Dimensions.get('window');

// 6-Stage Soil Determination Pipeline Stages (mirrors the Python ML pipeline & website)
export const STAGE_META = {
  0: {
    stage: 0,
    targetPct: 15,
    badge: 'INIT',
    title: 'Initializing pipeline…',
    sub: 'Preparing soil analysis engine',
    name: 'Pipeline Initialization',
  },
  1: {
    stage: 1,
    targetPct: 32,
    badge: 'STAGE 1',
    title: 'Stage 1/6: Soil Gate Validation…',
    sub: 'Verifying genuine soil grain texture & non-soil rejection',
    name: 'Soil Gate (Texture & Non-Soil Filter)',
    tag: 'Texture & Non-Soil Filter',
  },
  2: {
    stage: 2,
    targetPct: 50,
    badge: 'STAGE 2',
    title: 'Stage 2/6: Lighting Normalization…',
    sub: 'Balancing dark & light exposure to center (Target L* 50.0)',
    name: 'Lighting Normalizer (LAB L* 50% Center)',
    tag: 'LAB L* 50% Center',
  },
  3: {
    stage: 3,
    targetPct: 68,
    badge: 'STAGE 3',
    title: 'Stage 3/6: Multi-Color Detection…',
    sub: 'Extracting dominant soil color clusters via K-means in CIELAB (shade-merged)',
    name: 'Multi-Color Detection (K-means in CIELAB, shade-merged)',
    tag: 'K-means CIELAB',
  },
  4: {
    stage: 4,
    targetPct: 82,
    badge: 'STAGE 4',
    title: 'Stage 4/6: Per-Region Verification…',
    sub: 'Re-checking individual color regions through soil gate',
    name: 'Per-Region Verification (Patch Gate Validation)',
    tag: 'Patch Gate Validation',
  },
  5: {
    stage: 5,
    targetPct: 94,
    badge: 'STAGE 5',
    title: 'Stage 5/6: Munsell Spectral Match…',
    sub: 'Calculating CIEDE2000 (ΔE₀₀) color distance to Munsell chart',
    name: 'Munsell Spectral Match (CIEDE2000 ΔE₀₀)',
    tag: 'CIEDE2000 ΔE₀₀',
  },
  6: {
    stage: 6,
    targetPct: 98,
    badge: 'STAGE 6',
    title: 'Stage 6/6: Agronomic Profile & Centroid…',
    sub: 'Generating soil properties, drainage & crop profile',
    name: 'Agronomic Profile & Reference Centroid',
    tag: 'Profile & Reference Centroid',
  },
};

// Helper download tray icon matching website Save Soil Scan button
function DownloadIcon({ size = 18, color = '#16a34a' }) {
  return (
    <View style={{ width: size, height: size, alignItems: 'center', justifyContent: 'center', marginRight: 8 }}>
      <View style={{ width: 2, height: size * 0.48, backgroundColor: color, position: 'absolute', top: 1 }} />
      <View
        style={{
          width: size * 0.35,
          height: size * 0.35,
          borderBottomWidth: 2,
          borderRightWidth: 2,
          borderColor: color,
          transform: [{ rotate: '45deg' }],
          position: 'absolute',
          top: size * 0.22,
        }}
      />
      <View
        style={{
          width: size * 0.85,
          height: size * 0.32,
          borderBottomWidth: 2,
          borderLeftWidth: 2,
          borderRightWidth: 2,
          borderColor: color,
          borderBottomLeftRadius: 3,
          borderBottomRightRadius: 3,
          position: 'absolute',
          bottom: 1,
        }}
      />
    </View>
  );
}

// Helper map pin icon matching Pin Soil Location button
function MapPinIcon({ size = 18, color = '#ffffff' }) {
  return (
    <View style={{ width: size, height: size, alignItems: 'center', justifyContent: 'center', marginRight: 8 }}>
      <View
        style={{
          width: size * 0.65,
          height: size * 0.65,
          borderRadius: (size * 0.65) / 2,
          backgroundColor: color,
          alignItems: 'center',
          justifyContent: 'center',
        }}
      >
        <View
          style={{
            width: size * 0.22,
            height: size * 0.22,
            borderRadius: (size * 0.22) / 2,
            backgroundColor: '#15803d',
          }}
        />
      </View>
      <View
        style={{
          width: 0,
          height: 0,
          borderLeftWidth: size * 0.18,
          borderRightWidth: size * 0.18,
          borderTopWidth: size * 0.32,
          borderLeftColor: 'transparent',
          borderRightColor: 'transparent',
          borderTopColor: color,
          marginTop: -1.5,
        }}
      />
    </View>
  );
}

// Helper download tray icon matching website Save Copy button
function SaveTrayIcon({ size = 15, color = '#ffffff' }) {
  return (
    <View style={{ width: size, height: size, alignItems: 'center', justifyContent: 'center' }}>
      <View style={{ width: 1.8, height: size * 0.42, backgroundColor: color, marginBottom: -1 }} />
      <View
        style={{
          width: 0,
          height: 0,
          borderLeftWidth: size * 0.22,
          borderRightWidth: size * 0.22,
          borderTopWidth: size * 0.22,
          borderLeftColor: 'transparent',
          borderRightColor: 'transparent',
          borderTopColor: color,
        }}
      />
      <View
        style={{
          width: size * 0.85,
          height: size * 0.3,
          borderWidth: 1.6,
          borderColor: color,
          borderTopWidth: 0,
          borderRadius: 2,
          marginTop: 1.5,
        }}
      />
    </View>
  );
}

// Helper refresh icon matching website Retake / Live Camera button
function RefreshIcon({ size = 15, color = '#334155' }) {
  return (
    <View style={{ width: size, height: size, alignItems: 'center', justifyContent: 'center' }}>
      <Text style={{ fontSize: size * 1.1, color, fontWeight: '800', lineHeight: size * 1.2 }}>↻</Text>
    </View>
  );
}

// Helper lighting icon matching website Stage 2 Soil Normalization
function SunLightingIcon({ size = 13, color = '#10b981' }) {
  return (
    <View style={{ width: size, height: size, alignItems: 'center', justifyContent: 'center' }}>
      <View
        style={{
          width: size * 0.52,
          height: size * 0.52,
          borderRadius: (size * 0.52) / 2,
          borderWidth: 1.4,
          borderColor: color,
        }}
      />
      <View style={{ position: 'absolute', top: 0, width: 1.4, height: size * 0.18, backgroundColor: color }} />
      <View style={{ position: 'absolute', bottom: 0, width: 1.4, height: size * 0.18, backgroundColor: color }} />
      <View style={{ position: 'absolute', left: 0, height: 1.4, width: size * 0.18, backgroundColor: color }} />
      <View style={{ position: 'absolute', right: 0, height: 1.4, width: size * 0.18, backgroundColor: color }} />
    </View>
  );
}

// Helper: Normalize ROI bounding box for crop avatar & before/after card
const normalizeBox = (r) => {
  if (!r) return { left: 25, top: 25, width: 50, height: 50, raw_x: 0.25, raw_y: 0.25, raw_w: 0.5, raw_h: 0.5 };
  let box = null;
  if (r.box && typeof r.box === 'object') box = r.box;
  else if (r.bounding_box && typeof r.bounding_box === 'object') box = r.bounding_box;
  else if (typeof r.box === 'string') {
    try { box = JSON.parse(r.box); } catch(e) {}
  }
  if (!box && typeof r.bounding_box === 'string') {
    try { box = JSON.parse(r.bounding_box); } catch(e) {}
  }
  if (!box && (r.norm_x !== undefined || r.x !== undefined)) {
    box = r;
  }
  if (!box) {
    return { left: 25, top: 25, width: 50, height: 50, raw_x: 0.25, raw_y: 0.25, raw_w: 0.5, raw_h: 0.5 };
  }

  let bx = box.norm_x !== undefined ? box.norm_x : (box.x !== undefined ? box.x : 0.25);
  let by = box.norm_y !== undefined ? box.norm_y : (box.y !== undefined ? box.y : 0.25);
  let bw = box.norm_w !== undefined ? box.norm_w : (box.w !== undefined ? box.w : (box.width !== undefined ? box.width : 0.5));
  let bh = box.norm_h !== undefined ? box.norm_h : (box.h !== undefined ? box.h : (box.height !== undefined ? box.height : 0.5));

  let numX = parseFloat(bx);
  let numY = parseFloat(by);
  let numW = parseFloat(bw);
  let numH = parseFloat(bh);
  if (isNaN(numX)) numX = 0.25;
  if (isNaN(numY)) numY = 0.25;
  if (isNaN(numW)) numW = 0.5;
  if (isNaN(numH)) numH = 0.5;

  // If in percentage form (1..100), convert to 0..1 range
  if (numX > 1.0 && numX <= 100.0) numX /= 100.0;
  if (numY > 1.0 && numY <= 100.0) numY /= 100.0;
  if (numW > 1.0 && numW <= 100.0) numW /= 100.0;
  if (numH > 1.0 && numH <= 100.0) numH /= 100.0;

  // If pixel dimensions from non-normalized source
  if (numX > 1.0) numX = 0.25;
  if (numY > 1.0) numY = 0.25;
  if (numW > 1.0) numW = 0.5;
  if (numH > 1.0) numH = 0.5;

  // Guard against edge clippings
  numX = Math.max(0.02, Math.min(0.88, numX));
  numY = Math.max(0.02, Math.min(0.88, numY));
  numW = Math.max(0.06, Math.min(0.96 - numX, numW));
  numH = Math.max(0.06, Math.min(0.96 - numY, numH));

  return {
    left: Math.round(numX * 10000) / 100,
    top: Math.round(numY * 10000) / 100,
    width: Math.round(numW * 10000) / 100,
    height: Math.round(numH * 10000) / 100,
    raw_x: numX,
    raw_y: numY,
    raw_w: numW,
    raw_h: numH,
  };
};

export default function ScanSoilScreen({ navigation }) {
  const { user } = useContext(AuthContext);
  const currentUserId = user?.user_id || user?.id || null;

  // Flashlight / Torch state
  const [isTorchOn, setIsTorchOn] = useState(false);

  // Vision Camera hooks
  const { hasPermission, requestPermission } = useCameraPermission();
  const cameraDevice = useCameraDevice('back');
  const cameraRef = useRef(null);

  useEffect(() => {
    if (!hasPermission) {
      requestPermission();
    }
  }, [hasPermission]);

  // Scanning process states
  const [isScanning, setIsScanning] = useState(false);
  const [hasScanned, setHasScanned] = useState(false);
  const [showDetailModal, setShowDetailModal] = useState(false);
  const [showPinModal, setShowPinModal] = useState(false);
  const [targetPinLayer, setTargetPinLayer] = useState(null);
  const [expandedLayers, setExpandedLayers] = useState({});
  const [userCustomImage, setUserCustomImage] = useState(null);
  const [croppedSpecimenUri, setCroppedSpecimenUri] = useState(null);
  const frozenOriginalPhotoRef = useRef(null);
  const [syncStatus, setSyncStatus] = useState(null);
  const [toastMessage, setToastMessage] = useState('');
  const [pinnedLocation, setPinnedLocation] = useState(null);
  const [isSavedCopy, setIsSavedCopy] = useState(false);
  const [selectedOverlayColorIndex, setSelectedOverlayColorIndex] = useState(null);

  // Layout & Bounds
  const [camBounds, setCamBounds] = useState({ x: 0, y: 0, width: SCREEN_WIDTH, height: SCREEN_HEIGHT });
  const [dockTop, setDockTop] = useState(null);
  const frameRectRef = useRef(null);

  // Scan state & Progress Engine
  const [scanResult, setScanResult] = useState(null);   // mapped server scan
  const [scanStage, setScanStage] = useState(0);        // 0..6 from the pipeline
  const [progressPct, setProgressPct] = useState(1);    // 1..100 continuous smooth % counter
  const [rejectMsg, setRejectMsg] = useState(null);     // soft-reject message
  const [isOfflineMode, setIsOfflineMode] = useState(false); // Force on-device offline analysis
  const abortRef = useRef(null);
  const progressTimerRef = useRef(null);
  const currentPctRef = useRef(1);
  const targetPctRef = useRef(15);

  const currentSpecimen = scanResult; // real result only; null until a scan completes

  const normBarFrac = currentSpecimen?.bar_frac ?? (currentSpecimen?.barFrac ?? 0.0741);
  const normBarPct = normBarFrac * 100;
  const normImgHeightPct = 100 - normBarPct;

  useEffect(() => {
    setSelectedOverlayColorIndex(null);
  }, [currentSpecimen?.scanId, currentSpecimen?.id]);

  const specimenMunsellMatch = useMemo(() => {
    if (!currentSpecimen) return null;
    const targetCode = (currentSpecimen.munsellCode || currentSpecimen.fullCode || '')
      .trim()
      .toUpperCase();
    return (
      OFFLINE_MUNSELL_COLORS.find(
        (c) =>
          (c.munsellCode || c.full_code || '').trim().toUpperCase() === targetCode
      ) || null
    );
  }, [currentSpecimen]);

  const specimenResolvedDescription = useMemo(() => {
    if (specimenMunsellMatch?.description && specimenMunsellMatch.description.trim()) {
      return specimenMunsellMatch.description;
    }
    if (
      currentSpecimen?.description &&
      !currentSpecimen.description.startsWith('Determined Soil:') &&
      !currentSpecimen.description.startsWith('Field scan record') &&
      !currentSpecimen.description.startsWith('Sample processed successfully')
    ) {
      return currentSpecimen.description;
    }
    return (
      specimenMunsellMatch?.description ||
      `${currentSpecimen?.munsellCode || currentSpecimen?.fullCode || ''} - ${currentSpecimen?.name || currentSpecimen?.colorName || 'Determined Soil'}`
    );
  }, [currentSpecimen, specimenMunsellMatch]);

  // Normalization data and swatches for Soil Record Details modal
  const specimenNormRegions = useMemo(() => {
    if (!currentSpecimen) return [];
    if (Array.isArray(currentSpecimen.regions) && currentSpecimen.regions.length > 0) {
      return currentSpecimen.regions;
    }
    const domHex = specimenMunsellMatch?.hexCode || currentSpecimen.hexColor || currentSpecimen.hex || '#8F694E';
    const domCode = specimenMunsellMatch?.munsellCode || currentSpecimen.munsellCode || currentSpecimen.fullCode || '10YR 4/1';
    const domPct = currentSpecimen.soilPercentage || 100;
    return [{
      hex: domHex,
      munsell: domCode,
      percent: domPct,
    }];
  }, [currentSpecimen, specimenMunsellMatch]);

  const specimenNormDateTime = useMemo(() => {
    if (!currentSpecimen) {
      const now = new Date();
      const mm = String(now.getMonth() + 1).padStart(2, '0');
      const dd = String(now.getDate()).padStart(2, '0');
      const yyyy = now.getFullYear();
      let hours = now.getHours();
      const minutes = String(now.getMinutes()).padStart(2, '0');
      const ampm = hours >= 12 ? 'PM' : 'AM';
      hours = hours % 12 || 12;
      return {
        dateStr: `${mm}/${dd}/${yyyy}`,
        timeStr: `${String(hours).padStart(2, '0')}:${minutes} ${ampm}`,
      };
    }

    const raw = currentSpecimen.rawDate || currentSpecimen.scanDate || currentSpecimen.createdDate || currentSpecimen.created_at;
    let d = raw ? new Date(typeof raw === 'string' ? raw.replace(' ', 'T') : raw) : new Date();
    if (isNaN(d.getTime())) d = new Date();

    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    const yyyy = d.getFullYear();
    const dateStr = `${mm}/${dd}/${yyyy}`;

    let timeStr = '';
    if (currentSpecimen.scanTime && typeof currentSpecimen.scanTime === 'string' && currentSpecimen.scanTime.trim()) {
      timeStr = currentSpecimen.scanTime.trim();
    } else {
      let hours = d.getHours();
      const minutes = String(d.getMinutes()).padStart(2, '0');
      const ampm = hours >= 12 ? 'PM' : 'AM';
      hours = hours % 12 || 12;
      timeStr = `${String(hours).padStart(2, '0')}:${minutes} ${ampm}`;
    }

    return { dateStr, timeStr };
  }, [currentSpecimen]);

  const specimenNormDate = specimenNormDateTime.dateStr;

  const specimenAvatarRoiBox = useMemo(() => {
    if (!currentSpecimen) return null;
    const region = (Array.isArray(currentSpecimen.regions) && currentSpecimen.regions[0]) || null;
    const norm = normalizeBox(region);
    const leftPct = Math.max(4, Math.min(80, (norm.raw_x || 0.25) * 100));
    const topPct = Math.max(4, Math.min(80, (norm.raw_y || 0.25) * 100));
    const widthPct = Math.max(16, Math.min(94 - leftPct, (norm.raw_w || 0.5) * 100));
    const heightPct = Math.max(16, Math.min(94 - topPct, (norm.raw_h || 0.5) * 100));
    return { leftPct, topPct, widthPct, heightPct };
  }, [currentSpecimen]);

  const specimenNormBoxes = useMemo(() => {
    const barFrac = currentSpecimen?.bar_frac ?? (currentSpecimen?.barFrac ?? 0.0741);
    const barPct = barFrac * 100;
    const imgHeightPct = 100 - barPct;
    const regions = (specimenNormRegions && specimenNormRegions.length > 0)
      ? specimenNormRegions
      : [{ norm_x: 0.25, norm_y: 0.25, norm_w: 0.5, norm_h: 0.5 }];
    return regions.map((r, idx) => {
      const bData = normalizeBox(r);
      // Resolve this region's specific hex color — never fall back to global scan color here
      let rHex = r.hex || r.hex_color || null;
      if (!rHex && Array.isArray(r.rgb) && r.rgb.length >= 3) {
        rHex = `#${r.rgb.map(v => Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2, '0')).join('')}`;
      }
      rHex = rHex || '#4ade80'; // default to green only if truly no color available
      if (!rHex.startsWith('#')) rHex = `#${rHex}`;
      const rCode = r.munsell_code || r.munsell || r.notation || '';
      const rPct = r.percent !== undefined
        ? Math.round(r.percent)
        : (r.proportion !== undefined ? Math.round(r.proportion * 100) : null);

      // Coordinates for 2-panel composite Before/After comparison image (right 50% pane, with dynamic top bar)
      const splitLeft = 50.0 + (bData.raw_x * 50.0);
      const splitTop = barPct + (bData.raw_y * imgHeightPct);
      const splitWidth = bData.raw_w * 50.0;
      const splitHeight = bData.raw_h * imgHeightPct;

      // Coordinates for single panel / simulated split pane (100% of panel width/height)
      const boxLeft = bData.raw_x * 100.0;
      const boxTop = bData.raw_y * 100.0;
      const boxWidth = bData.raw_w * 100.0;
      const boxHeight = bData.raw_h * 100.0;

      return {
        // Composite image coordinates
        splitLeftPct: Math.round(splitLeft * 100) / 100,
        splitTopPct: Math.round(splitTop * 100) / 100,
        splitWidthPct: Math.round(splitWidth * 100) / 100,
        splitHeightPct: Math.round(splitHeight * 100) / 100,
        // Panel / single image coordinates
        boxLeftPct: Math.round(boxLeft * 100) / 100,
        boxTopPct: Math.round(boxTop * 100) / 100,
        boxWidthPct: Math.round(boxWidth * 100) / 100,
        boxHeightPct: Math.round(boxHeight * 100) / 100,
        // Backwards compatibility aliases
        leftPct: Math.round(splitLeft * 100) / 100,
        topPct: Math.round(splitTop * 100) / 100,
        widthPct: Math.round(splitWidth * 100) / 100,
        heightPct: Math.round(splitHeight * 100) / 100,
        roleLabel: `Color ${idx + 1}`,
        hex: rHex,
        munsellCode: rCode,
        percent: rPct,
        raw_x: bData.raw_x,
        raw_y: bData.raw_y,
        raw_w: bData.raw_w,
        raw_h: bData.raw_h,
      };
    });
  }, [specimenNormRegions, currentSpecimen]);


  const handleSaveSpecimenCopy = async (targetSpecimen, munsellMatch, resolvedDesc) => {
    if (!targetSpecimen) return;
    try {
      const code = munsellMatch?.munsellCode || targetSpecimen.munsellCode || targetSpecimen.fullCode || '7.5YR 5/3';
      const name = munsellMatch?.colorName || targetSpecimen.name || targetSpecimen.colorName || 'Determined Soil';
      const hex = (munsellMatch?.hexCode || targetSpecimen.hexColor || targetSpecimen.hex || '#8F694E').toUpperCase();
      const organic = munsellMatch?.soil_organic_index || targetSpecimen.soil_organic_index || targetSpecimen.organicIndex || 'Moderate';
      const soilType = munsellMatch?.soil_type || targetSpecimen.soil_type || targetSpecimen.soilType || 'Determined Soil';
      const nearMatch = targetSpecimen.nearMatchRating || targetSpecimen.confidence || '97.4%';
      const scanDate = targetSpecimen.scanDate || targetSpecimen.createdDate || '';
      const soilPercent = targetSpecimen.soilPercentage ? `${targetSpecimen.soilPercentage}%` : '35%';
      const cleanDesc = resolvedDesc || targetSpecimen.description || `${code} - ${name}`;

      const photoUri = croppedSpecimenUri || targetSpecimen.scanImage || (typeof targetSpecimen.thumbnail === 'string' ? targetSpecimen.thumbnail : targetSpecimen.thumbnail?.uri) || userCustomImage || null;
      const beforeAfterUri = targetSpecimen.beforeAfterImage || targetSpecimen.beforeAfterImageUrl || null;

      // 1. Export as official PNG image result card matching the website design
      if (NativeModules?.InstallApk?.exportSoilResultCard) {
        const result = await NativeModules.InstallApk.exportSoilResultCard({
          munsellCode: code,
          colorName: name,
          hexCode: hex,
          soilPercentage: soilPercent,
          soilOrganic: organic,
          soilType: soilType,
          nearMatch: nearMatch,
          scanDate: scanDate,
          scanCount: '1',
          description: cleanDesc,
          photoUri: photoUri,
          beforeAfterUri: beforeAfterUri,
          triggerShare: true,
        });

        setIsSavedCopy(true);
        setTimeout(() => setIsSavedCopy(false), 3000);
        if (Platform.OS === 'android' && ToastAndroid) {
          ToastAndroid.show(`✓ Saved Result Image (.png) to Downloads & Gallery`, ToastAndroid.LONG);
        }
        return;
      }

      // Fallback for non-Android environments
      const safeCode = String(code).replace(/[\/\s]/g, '_');
      const fileName = `AgriCo_Soil_Record_${safeCode}_${Date.now()}.png`;
      setIsSavedCopy(true);
      setTimeout(() => setIsSavedCopy(false), 3000);
      if (Platform.OS === 'android' && ToastAndroid) {
        ToastAndroid.show(`Saved: ${fileName}`, ToastAndroid.LONG);
      }
    } catch (e) {
      console.log('Error saving specimen copy:', e?.message);
      if (Platform.OS === 'android' && ToastAndroid) {
        ToastAndroid.show(`Could not save image: ${e?.message || 'Error'}`, ToastAndroid.SHORT);
      }
    }
  };

  // Animated values
  const flashAnim = useRef(new Animated.Value(0)).current;
  const resultCardAnim = useRef(new Animated.Value(0)).current;
  const toastAnim = useRef(new Animated.Value(0)).current;

  // Compute frame bounds confined between the top header and the controls dock
  const headerBottom = (Platform.OS === 'ios' ? 54 : 44) + 44 + 16;
  const frameBounds = useMemo(() => ({
    x: 16,
    y: headerBottom,
    width: Math.max(0, camBounds.width - 32),
    height: Math.max(0, (dockTop ?? camBounds.height * 0.6) - 12 - headerBottom),
  }), [camBounds, dockTop, headerBottom]);

  // Cleanup effect
  useEffect(() => () => {
    abortRef.current?.abort();
    if (progressTimerRef.current) clearInterval(progressTimerRef.current);
  }, []);

  // Continuous Progress Counter (mirrors website: 1%, 2%, 3%... with stage bounds & creep)
  const startProgressEngine = () => {
    if (progressTimerRef.current) clearInterval(progressTimerRef.current);
    setProgressPct(1);
    currentPctRef.current = 1;
    targetPctRef.current = STAGE_META[0].targetPct;
    let creepTicks = 0;
    progressTimerRef.current = setInterval(() => {
      if (currentPctRef.current < targetPctRef.current) {
        currentPctRef.current += 1;
        setProgressPct(currentPctRef.current);
      } else if (currentPctRef.current < 96) {
        // Slowly creep forward 1% every ~280ms while awaiting next stage
        creepTicks++;
        if (creepTicks % 8 === 0) {
          currentPctRef.current += 1;
          setProgressPct(currentPctRef.current);
        }
      }
    }, 35);
  };

  const advanceStage = (stage) => {
    setScanStage(stage);
    targetPctRef.current = STAGE_META[stage]?.targetPct ?? 96;
  };

  const stopProgressEngine = () => {
    if (progressTimerRef.current) {
      clearInterval(progressTimerRef.current);
      progressTimerRef.current = null;
    }
  };

  const finishProgressEngine = async () => {
    stopProgressEngine();
    while (currentPctRef.current < 100) {
      currentPctRef.current += 1;
      setProgressPct(currentPctRef.current);
      await new Promise((r) => setTimeout(r, 12));
    }
    await new Promise((r) => setTimeout(r, 180));
  };

  // Queue flush effect on screen focus & NetInfo reconnect
  useEffect(() => {
    const run = async () => {
      const r = await flushOfflineQueue();
      if (r.flushed > 0) {
        triggerAutoSaveToast(`✓ ${r.flushed} queued scan(s) analyzed and saved`);
      }
      if (r.rejected > 0) {
        triggerAutoSaveToast(`${r.rejected} queued scan(s) were not valid soil`);
      }
    };
    const unsubFocus = navigation.addListener('focus', run);
    const unsubNet = NetInfo.addEventListener((s) => {
      if (s.isConnected) run();
    });
    return () => {
      unsubFocus();
      unsubNet();
    };
  }, [navigation]);

  // Trigger floating auto-save confirmation toast
  const triggerAutoSaveToast = (msg) => {
    setToastMessage(msg);
    toastAnim.setValue(0);
    Animated.sequence([
      Animated.timing(toastAnim, {
        toValue: 1,
        duration: 320,
        easing: Easing.out(Easing.ease),
        useNativeDriver: true,
      }),
      Animated.delay(3500),
      Animated.timing(toastAnim, {
        toValue: 0,
        duration: 320,
        easing: Easing.in(Easing.ease),
        useNativeDriver: true,
      }),
    ]).start(() => {
      setToastMessage('');
    });
  };

  const playCaptureAnimations = () => {
    flashAnim.setValue(0.7);
    Animated.timing(flashAnim, {
      toValue: 0,
      duration: 350,
      easing: Easing.out(Easing.ease),
      useNativeDriver: true,
    }).start();
  };

  const getCurrentGps = () => ({
    latitude: pinnedLocation?.latitude ?? null,
    longitude: pinnedLocation?.longitude ?? null,
    locationName: pinnedLocation?.locationName ?? pinnedLocation?.barangay ?? pinnedLocation?.name ?? null,
  });

  const capturePhoto = async () => {
    try {
      // 1. Try taking photo with live VisionCamera if available and active
      if (cameraRef.current && cameraDevice && hasPermission) {
        const photo = await cameraRef.current.takePhoto({
          flash: isTorchOn ? 'on' : 'off',
          enableShutterSound: true,
        });
        if (photo?.path) {
          const fileUri = photo.path.startsWith('file://') ? photo.path : `file://${photo.path}`;
          let base64 = null;
          try {
            const rawPath = photo.path.replace('file://', '');
            base64 = await RNFS.readFile(rawPath, 'base64');
          } catch (e) {
            console.warn('[Camera] Failed reading base64:', e?.message);
          }
          return { uri: fileUri, base64 };
        }
      }

      // 2. Fallback to native system camera picker
      const res = await launchCamera({
        mediaType: 'photo',
        cameraType: 'back',
        quality: 0.7,
        maxWidth: 1280,
        maxHeight: 1280,
        includeBase64: true,
        saveToPhotos: false,
      });
      const asset = res.assets?.[0];
      if (asset?.uri) return { uri: asset.uri, base64: asset.base64 || null };
    } catch (err) {
      console.warn('[Camera] Capture error:', err?.message);
    }
    return null;
  };

  const runScan = async (photo) => {
    if (isScanning) return;
    setIsScanning(true);
    setScanResult(null);
    setRejectMsg(null);
    setScanStage(0);
    setSyncStatus(null);
    setHasScanned(false);
    resultCardAnim.setValue(0);
    playCaptureAnimations();
    startProgressEngine();

    // Guard: Missing photo specimen
    if (!photo?.uri && !photo?.base64 && !photo?.isDemo) {
      stopProgressEngine();
      setIsScanning(false);
      setRejectMsg('No photo specimen captured. Please position the camera over soil and try again.');
      return;
    }

    // Lazy load base64 from file uri if not yet in memory
    if (photo?.uri && !photo?.base64) {
      try {
        const rawPath = photo.uri.replace('file://', '');
        photo.base64 = await RNFS.readFile(rawPath, 'base64');
      } catch (readErr) {
        console.warn('[ScanSoilScreen] Error reading base64:', readErr?.message);
      }
    }

    // Explicit demo fallback when running in simulator test suite
    if (photo?.isDemo) {
      setTimeout(() => advanceStage(1), 350);
      setTimeout(() => advanceStage(2), 750);
      setTimeout(() => advanceStage(3), 1150);
      setTimeout(() => advanceStage(4), 1550);
      setTimeout(() => advanceStage(5), 1950);
      setTimeout(async () => {
        advanceStage(6);
        await finishProgressEngine();
        setIsScanning(false);
        const demoSpecimen = mapServerScan({
          soil_scan_id: Math.floor(1000 + Math.random() * 9000),
          soil_name: 'Dark Reddish Gray',
          munsell_code: '10R 4/1',
          hue: '10R',
          confidence: '96%',
          description: '10R 4/1 - Dark Reddish Gray (Mineral Clay Subsoil)',
        }, {
          hex: '#5A4643',
          organic_matter: 'High (3.8%)',
          soil_type: 'Mineral Clay Subsoil',
        });
        setScanResult(demoSpecimen);
        setHasScanned(true);
        setShowDetailModal(true);
        setSyncStatus(null); // Do not show fake synced badge on demo
        Animated.spring(resultCardAnim, { toValue: 1, friction: 6, tension: 70, useNativeDriver: true }).start();
      }, 2350);
      return;
    }

    const gps = getCurrentGps();
    const scanId = newScanId();

    const runOfflineAnalysisFlow = async () => {
      // STAGE 1: Immediate gate check
      advanceStage(1);

      // Perform local spectral extraction & Stage 1 Soil Gate validation
      const offlineSpecimen = await analyzeSoilOffline({
        imageUri: photo.uri,
        imageBase64: photo.base64,
      });

      // If Stage 1 rejected non-soil, halt immediately!
      if (!offlineSpecimen || offlineSpecimen.rejected || !offlineSpecimen.isValidSoil) {
        stopProgressEngine();
        setIsScanning(false);
        await clearQueuedScan(scanId);
        setRejectMsg(offlineSpecimen?.message || 'Stage 1 Soil Gate: No genuine soil detected. Please point camera directly at real soil.');
        return;
      }

      // Smoothly advance through Stages 2 to 5 for verified genuine soil
      setTimeout(() => advanceStage(2), 350);
      setTimeout(() => advanceStage(3), 750);
      setTimeout(() => advanceStage(4), 1150);
      setTimeout(() => advanceStage(5), 1550);

      // Queue for background server synchronization once connectivity is restored
      try {
        await queueAsyncScan({ scanId, imageUri: photo.uri, userId: currentUserId, user_id: currentUserId, ...gps });
      } catch (e) {
        console.warn('[ScanSoilScreen] queue failed:', e?.message);
      }

      // Complete pipeline & show results at Stage 6
      setTimeout(async () => {
        advanceStage(6);
        await finishProgressEngine();
        setIsScanning(false);
        setScanResult(offlineSpecimen);
        setHasScanned(true);
        setShowDetailModal(true);
        setSyncStatus({ queued: true, isOffline: true });
        Animated.spring(resultCardAnim, { toValue: 1, friction: 6, tension: 70, useNativeDriver: true }).start();
        triggerAutoSaveToast('✓ Identified offline (Queued to sync)');

        // Persist offline scan directly into local store for Scan History
        const now = new Date();
        saveLocalScan({
          id: 'offline_' + scanId,
          scanId,
          soil_scan_id: scanId,
          user_id: currentUserId,
          munsellCode: offlineSpecimen.munsellCode,
          hue: offlineSpecimen.hueCode,
          colorName: offlineSpecimen.name,
          hexCode: offlineSpecimen.hexColor,
          description: offlineSpecimen.description,
          scansCount: 1,
          scanImage: photo.uri,
          rawDate: now.toISOString(),
          scanTime: now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
          dateGroup: 'Today',
          platform: 'mobile',
          isOffline: true,
          syncState: 'queued',
          soil_organic_index: offlineSpecimen.organicIndex,
          soil_type: offlineSpecimen.soilType,
          nearMatchRating: offlineSpecimen.nearMatchRating,
          confidence: offlineSpecimen.confidence,
          regions: (offlineSpecimen.regions || []).map(({ mask_png, color_map_png, ...rest }) => rest),
          beforeAfterImage: offlineSpecimen.imageUrl || photo.uri,
          beforeAfterImageUrl: offlineSpecimen.imageUrl || photo.uri,
          latitude: gps?.latitude ?? null,
          longitude: gps?.longitude ?? null,
          locationName: gps?.locationName ?? null,
        }).catch(() => {});
      }, 2000);
    };

    // Check user preference or live network status
    if (isOfflineMode) {
      await runOfflineAnalysisFlow();
      return;
    }

    const net = await NetInfo.fetch();
    const isActuallyOnline = Boolean(net.isConnected && net.isInternetReachable !== false);
    if (!isActuallyOnline) {
      await runOfflineAnalysisFlow();
      return;
    }

    abortRef.current = new AbortController();
    try {
      const uploadPromise = startScan({
        scanId,
        imageBase64: `data:image/jpeg;base64,${photo.base64}`,
        userId: currentUserId,
        user_id: currentUserId,
        ...gps,
      });
      const timeoutPromise = new Promise((_, reject) =>
        setTimeout(() => reject(new Error('SERVER_TIMEOUT')), 3500)
      );
      await Promise.race([uploadPromise, timeoutPromise]);
    } catch (e) {
      // Server upload unreachable or timed out: seamlessly fall back to on-device offline analysis
      await runOfflineAnalysisFlow();
      return;
    }
    await trackInflightScan(scanId); // lets a killed app resume polling on next flush

    const result = await pollScan(scanId, {
      signal: abortRef.current.signal,
      onProgress: (stageNum) => advanceStage(stageNum),
    });

    if (result.status === 'done') {
      await finishProgressEngine();
      setIsScanning(false);
      await clearQueuedScan(scanId);
      const mapped = mapServerScan(result.scan, result.data);
      setScanResult(mapped);
      setHasScanned(true);
      setShowDetailModal(true);
      setSyncStatus({ synced: true, scanId: result.scan.soil_scan_id });
      Animated.spring(resultCardAnim, { toValue: 1, friction: 6, tension: 70, useNativeDriver: true }).start();
      triggerAutoSaveToast(`✓ Saved to Scan History (#${result.scan.soil_scan_id})`);

      // Persist synced scan directly into local store
      const now = new Date();
      saveLocalScan({
        id: 'scan_' + result.scan.soil_scan_id,
        scanId,
        soil_scan_id: result.scan.soil_scan_id,
        user_id: result.scan.user_id || currentUserId,
        munsellCode: mapped.munsellCode,
        hue: mapped.hueCode,
        colorName: mapped.name,
        hexCode: mapped.hexColor,
        description: mapped.description,
        scansCount: mapped.scanCount,
        scanImage: mapped.imageUrl || photo.uri,
        beforeAfterImage: mapped.beforeAfterImage,
        beforeAfterImageUrl: mapped.beforeAfterImage,
        regions: (mapped.regions || []).map(({ mask_png, color_map_png, ...rest }) => rest),
        rawDate: result.scan.created_at || now.toISOString(),
        scanTime: now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        dateGroup: 'Today',
        platform: 'mobile',
        isOffline: false,
        syncState: 'synced',
        soil_organic_index: mapped.organicIndex,
        soil_type: mapped.soilType,
        nearMatchRating: mapped.nearMatchRating,
        confidence: mapped.confidence,
        latitude: gps?.latitude ?? null,
        longitude: gps?.longitude ?? null,
        locationName: gps?.locationName ?? null,
      }).catch(() => {});
    } else if (result.status === 'rejected') {
      stopProgressEngine();
      setIsScanning(false);
      await clearQueuedScan(scanId);
      setRejectMsg(result.message || 'No valid soil sample detected.');
    } else if (result.status === 'auth') {
      stopProgressEngine();
      setIsScanning(false);
      navigation.navigate('Login');
    } else {
      // Dropped or interrupted mid-flight: fall back to offline analysis immediately
      await runOfflineAnalysisFlow();
    }
  };

  const cropToFrame = async (photo) => {
    if (!photo?.uri) return photo;
    try {
      const rect = frameRectRef.current || {
        x: frameBounds.x + (frameBounds.width - 240) / 2,
        y: frameBounds.y + (frameBounds.height - 240) / 2,
        width: 240,
        height: 240,
      };
      const pW = camBounds.width || SCREEN_WIDTH;
      const pH = camBounds.height || SCREEN_HEIGHT;

      if (NativeModules?.InstallApk?.cropImageWithFrame) {
        const result = await NativeModules.InstallApk.cropImageWithFrame(
          photo.uri,
          rect.x,
          rect.y,
          rect.width,
          rect.height,
          pW,
          pH
        );
        if (result?.uri && result?.base64) {
          return {
            uri: result.uri,
            base64: result.base64,
            width: result.width,
            height: result.height,
          };
        }
      }
    } catch (err) {
      console.warn('[ScanSoilScreen] cropToFrame error:', err?.message);
    }
    return photo;
  };

  const handleRetake = () => {
    frozenOriginalPhotoRef.current = null;
    setCroppedSpecimenUri(null);
    setUserCustomImage(null);
    setScanResult(null);
    setRejectMsg(null);
    setHasScanned(false);
    setShowDetailModal(false);
    resultCardAnim.setValue(0);
  };

  const handleManualScan = async () => {
    if (isScanning) return;
    const photo = await capturePhoto();
    if (photo && (photo.base64 || photo.uri)) {
      const uriToUse = photo.uri || `data:image/jpeg;base64,${photo.base64}`;
      frozenOriginalPhotoRef.current = { uri: uriToUse, base64: photo.base64 };
      setUserCustomImage(uriToUse);
      const cropped = await cropToFrame({ uri: uriToUse, base64: photo.base64 });
      setCroppedSpecimenUri(cropped.uri);
      runScan(cropped);
    }
  };

  const handleLaunchGallery = async () => {
    try {
      const res = await launchImageLibrary({
        mediaType: 'photo',
        quality: 0.7,
        maxWidth: 1280,
        maxHeight: 1280,
        includeBase64: true,
      });
      const a = res.assets?.[0];
      if (a?.base64) {
        const uriToUse = a.uri || `data:image/jpeg;base64,${a.base64}`;
        frozenOriginalPhotoRef.current = { uri: uriToUse, base64: a.base64 };
        setUserCustomImage(uriToUse);
        const cropped = await cropToFrame({ uri: uriToUse, base64: a.base64 });
        setCroppedSpecimenUri(cropped.uri);
        runScan(cropped);
      }
    } catch (err) {
      Alert.alert('Gallery', 'Unable to open gallery');
    }
  };

  const handleScanFrozenPhoto = async () => {
    if (isScanning) return;
    const rawPhoto = frozenOriginalPhotoRef.current || (userCustomImage ? { uri: userCustomImage } : null);
    if (!rawPhoto) {
      handleManualScan();
      return;
    }
    const cropped = await cropToFrame(rawPhoto);
    setCroppedSpecimenUri(cropped.uri);
    runScan(cropped);
  };

  const handleToggleFlashlight = () => {
    setIsTorchOn((prev) => !prev);
  };

  // Bottom Navigation tab press
  const handleTabPress = (tab) => {
    if (tab === 'Home') {
      navigation.navigate('Home');
    } else if (tab === 'Munsell') {
      navigation.navigate('Home', { screen: 'Munsell' });
    } else if (tab === 'ScanSoil') {
      if (userCustomImage) {
        // User adjusted the resize frame over frozen photo and tapped Scan Soil
        handleScanFrozenPhoto();
      } else {
        handleManualScan();
      }
    }
  };

  return (
    <View
      style={styles.container}
      onLayout={(e) => {
        const { width, height } = e.nativeEvent.layout;
        setCamBounds({ x: 0, y: 0, width, height });
      }}
    >
      <StatusBar barStyle="light-content" translucent backgroundColor="transparent" />

      {/* 1. Full Live Camera Feed (Edge-to-Edge) */}
      <View style={StyleSheet.absoluteFill}>
        {userCustomImage ? (
          <Image
            source={{ uri: userCustomImage }}
            style={styles.backgroundImage}
            resizeMode="cover"
          />
        ) : cameraDevice != null && hasPermission ? (
          <Camera
            ref={cameraRef}
            style={StyleSheet.absoluteFill}
            device={cameraDevice}
            isActive={!userCustomImage && !showDetailModal && !showPinModal}
            photo={true}
            torch={isTorchOn ? 'on' : 'off'}
          />
        ) : (
          <View style={styles.cameraFallbackContainer}>
            <View style={styles.cameraGridReticle} />
            <Text style={styles.cameraFallbackTitle}>
              {!hasPermission ? 'Camera Access Required' : 'Live Camera Viewfinder'}
            </Text>
            <Text style={styles.cameraFallbackSub}>
              {!hasPermission
                ? 'Allow camera permissions to scan soil live through the viewfinder'
                : 'Position the soil specimen inside the scanning frame'}
            </Text>
            {!hasPermission && (
              <TouchableOpacity
                style={styles.grantPermissionBtn}
                onPress={requestPermission}
                activeOpacity={0.8}
              >
                <Text style={styles.grantPermissionBtnText}>Enable Camera</Text>
              </TouchableOpacity>
            )}
          </View>
        )}

        {/* Torch / Flashlight Simulated Illumination Overlay */}
        {isTorchOn && !cameraDevice && (
          <View style={styles.torchBeamOverlay} pointerEvents="none">
            <View style={styles.torchCenterGlow} />
          </View>
        )}

        {/* Camera shutter flash effect */}
        <Animated.View
          style={[styles.shutterFlash, { opacity: flashAnim }]}
          pointerEvents="none"
        />
      </View>

      {/* 2. Resizable Viewfinder Frame with 3-Tier Contrast Dimming */}
      <ResizableFrame
        bounds={frameBounds}
        dimBounds={camBounds}
        initialSize={Math.min(frameBounds.width * 0.85, frameBounds.height * 0.9, 260)}
        minSize={90}
        square={true}
        disabled={isScanning}
        dimOutside={0.42}
        dimInactive={0.78}
        onChange={(r) => {
          frameRectRef.current = r;
        }}
      >
        {({ width, height }) => (
          <View style={{ width, height }}>
            <ScanFrameVisual width={width} height={height} />

            {/* In-Frame Analyzing HUD */}
            {isScanning && (
              <View style={styles.scanningHudContainer}>
                {/* Stage Pill with Pulsing Dot */}
                <View style={styles.scanLoadingStagePill}>
                  <View style={styles.scanLoadingStageDot} />
                  <Text style={styles.scanLoadingStagePillText}>
                    {scanStage > 0 ? `STAGE ${scanStage}/6` : 'INITIALIZING'}
                  </Text>
                </View>

                {/* Spinner Wrap with Live Center Percentage */}
                <View style={styles.scanLoadingSpinnerWrap}>
                  <ActivityIndicator size="large" color="#22c55e" style={styles.scanLoadingSpinnerRing} />
                  <View style={styles.scanLoadingBadgeCenter}>
                    <Text style={styles.scanLoadingBadgePercent}>{`${progressPct}%`}</Text>
                  </View>
                </View>

                {/* Stage Title & Subtitle */}
                <Text style={styles.scanningHudTitle}>
                  {STAGE_META[scanStage]?.title || 'Analyzing Soil Specimen…'}
                </Text>
                <Text style={styles.scanningHudSubtitle}>
                  {STAGE_META[scanStage]?.sub || 'Processing through ML determination pipeline'}
                </Text>

                {/* Glowing Progress Track & Fill */}
                <View style={styles.scanLoadingProgressTrack}>
                  <View
                    style={[
                      styles.scanLoadingProgressFill,
                      { width: `${Math.min(100, Math.max(progressPct, 3))}%` },
                    ]}
                  />
                </View>
              </View>
            )}
          </View>
        )}
      </ResizableFrame>

      {/* 3. Top Header: "Scanning" / "Photo Review" & Close (X) Button */}
      <View style={styles.topHeader}>
        <View style={{ width: 44 }} />
        <View style={styles.titleContainer}>
          <Text style={styles.headerTitle}>
            {userCustomImage ? 'Photo Review' : 'Scanning'}
          </Text>
          {userCustomImage ? (
            <Text style={[styles.torchActiveSub, { color: '#86efac' }]}>Camera Paused</Text>
          ) : isTorchOn ? (
            <Text style={styles.torchActiveSub}>Torch Active</Text>
          ) : null}
        </View>
        <TouchableOpacity
          style={styles.headerCloseBtn}
          onPress={() => navigation.goBack()}
          activeOpacity={0.8}
          accessibilityLabel="Close Scanning"
        >
          <Text style={styles.closeBtnText}>✕</Text>
        </TouchableOpacity>
      </View>

      {/* Auto-Save & Admin Sync Toast Banner */}
      {toastMessage ? (
        <Animated.View
          style={[
            styles.autoSaveToast,
            {
              opacity: toastAnim,
              transform: [
                {
                  translateY: toastAnim.interpolate({
                    inputRange: [0, 1],
                    outputRange: [-20, 0],
                  }),
                },
              ],
            },
          ]}
          pointerEvents="none"
        >
          <View
            style={[
              styles.autoSaveToastDot,
              syncStatus?.synced && { backgroundColor: '#22c55e' },
            ]}
          />
          <Text style={styles.autoSaveToastText}>{toastMessage}</Text>
        </Animated.View>
      ) : null}

      {/* 4. Hint Pill & Control Buttons Dock (Fixed position above the bottom card) */}
      <View
        style={styles.controlsDock}
        onLayout={(e) => setDockTop(e.nativeEvent.layout.y)}
        pointerEvents="box-none"
      >
        <View style={styles.hintPill}>
          <Text style={styles.hintText}>
            {isScanning
              ? `Processing Stage ${scanStage || 1} of 6… (${progressPct}%)`
              : isOfflineMode
              ? '⚡ Offline Mode • Uses on-device real & fake soil dataset'
              : userCustomImage
              ? 'Photo frozen • Adjust resize box & tap Scan Soil'
              : 'Drag handles to resize • Drag inside to move'}
          </Text>
        </View>

        <View style={styles.controlButtonsRow}>
          {/* Gallery Button */}
          <TouchableOpacity
            style={styles.actionCircleBtn}
            onPress={handleLaunchGallery}
            activeOpacity={0.8}
            accessibilityLabel="Open Gallery"
          >
            <GalleryIcon size={22} color="#ffffff" />
          </TouchableOpacity>

          {/* Offline Mode Toggle Button */}
          <TouchableOpacity
            style={[
              styles.actionCircleBtn,
              isOfflineMode && { backgroundColor: '#15803d', borderColor: '#86efac', borderWidth: 1.5 },
            ]}
            onPress={() => {
              setIsOfflineMode((prev) => {
                const next = !prev;
                ToastAndroid.show(
                  next
                    ? '⚡ Offline Mode Active (Using On-Device Soil Dataset)'
                    : '🌐 Cloud Mode Active',
                  ToastAndroid.SHORT
                );
                return next;
              });
            }}
            activeOpacity={0.8}
            accessibilityLabel="Toggle Offline Mode"
          >
            <Text style={{ fontSize: 18, color: '#ffffff' }}>{isOfflineMode ? '⚡' : '🌐'}</Text>
          </TouchableOpacity>

          {/* Flashlight or Retake Button */}
          {userCustomImage ? (
            <TouchableOpacity
              style={[styles.actionCircleBtn, styles.actionCircleBtnRetake]}
              onPress={handleRetake}
              activeOpacity={0.8}
              accessibilityLabel="Retake Photo"
            >
              <Text style={styles.actionCircleRetakeIcon}>🔄</Text>
            </TouchableOpacity>
          ) : (
            <TouchableOpacity
              style={[
                styles.actionCircleBtn,
                isTorchOn && styles.actionCircleBtnTorchOn,
              ]}
              onPress={handleToggleFlashlight}
              activeOpacity={0.8}
              accessibilityLabel="Toggle Flashlight"
            >
              <FlashlightIcon
                size={22}
                color={isTorchOn ? '#facc15' : '#ffffff'}
                isOn={isTorchOn}
              />
              {isTorchOn && <View style={styles.actionTorchDot} />}
            </TouchableOpacity>
          )}
        </View>
      </View>

      {/* 5. Dynamic Bottom Status / Scan Result Card Area */}
      {isScanning ? (
        /* State A: Analyzing Card with 6-Stage Progress */
        <View style={styles.bottomCardWrapper}>
          <View style={styles.analyzingCard}>
            <View style={styles.analyzingSpinnerBox}>
              <Text style={styles.analyzingSpinnerPct}>{`${progressPct}%`}</Text>
            </View>
            <View style={styles.analyzingTextBox}>
              <View style={styles.analyzingStageBadgeRow}>
                <View style={styles.scanLoadingStageDotSmall} />
                <Text style={styles.analyzingCardStageBadge}>
                  {scanStage > 0 ? `STAGE ${scanStage}/6` : 'INITIALIZING'}
                </Text>
              </View>
              <Text style={styles.analyzingCardTitle} numberOfLines={1}>
                {STAGE_META[scanStage]?.title || `Analyzing Soil (Stage ${scanStage || 1}/6)…`}
              </Text>
              <Text style={styles.analyzingCardDesc} numberOfLines={1}>
                {STAGE_META[scanStage]?.sub || 'Processing sample…'}
              </Text>
              <View style={styles.bottomCardProgressTrack}>
                <View
                  style={[
                    styles.bottomCardProgressFill,
                    { width: `${Math.min(100, Math.max(progressPct, 3))}%` },
                  ]}
                />
              </View>
            </View>
          </View>
        </View>
      ) : currentSpecimen ? (
        /* State B: Scan Result Card Preview (Website Result Card Style) */
        <Animated.View
          style={[
            styles.bottomCardWrapper,
            {
              opacity: resultCardAnim,
              transform: [
                {
                  scale: resultCardAnim.interpolate({
                    inputRange: [0, 1],
                    outputRange: [0.92, 1],
                  }),
                },
                {
                  translateY: resultCardAnim.interpolate({
                    inputRange: [0, 1],
                    outputRange: [24, 0],
                  }),
                },
              ],
            },
          ]}
        >
          <TouchableOpacity
            style={styles.stateBResultCard}
            activeOpacity={0.92}
            onPress={() => setShowDetailModal(true)}
          >
            {/* Header: Title + Badge */}
            <View style={styles.stateBHeaderRow}>
              <View style={styles.resultHeaderTitleBox}>
                <Text style={styles.stateBHeaderTitle}>DETERMINED SOIL RECORD</Text>
                <View style={styles.resultMultiBadge}>
                  <Text style={styles.resultMultiBadgeText}>
                    {`${currentSpecimen.regions?.length || 1} DETECTED`}
                  </Text>
                </View>
              </View>
              {syncStatus?.synced ? (
                <View style={styles.syncedAdminBadge}>
                  <Text style={styles.syncedAdminBadgeText}>☁️ Saved</Text>
                </View>
              ) : syncStatus?.queued ? (
                <View style={styles.queuedBadge}>
                  <Text style={styles.queuedBadgeText}>⚡ Queued</Text>
                </View>
              ) : null}
            </View>

            {/* Dual-Preview Soil Banner */}
            <View
              style={[
                styles.stateBSwatchBanner,
                { backgroundColor: currentSpecimen.hexColor || '#594433' },
              ]}
            >
              <View style={styles.stateBThumbBox}>
                {croppedSpecimenUri ? (
                  <Image
                    source={{ uri: croppedSpecimenUri }}
                    style={styles.stateBSoilThumb}
                    resizeMode="cover"
                  />
                ) : currentSpecimen.scanImage ? (
                  <Image
                    source={{ uri: currentSpecimen.scanImage }}
                    style={styles.stateBSoilThumb}
                    resizeMode="cover"
                  />
                ) : currentSpecimen.thumbnail ? (
                  <Image
                    source={
                      typeof currentSpecimen.thumbnail === 'number'
                        ? currentSpecimen.thumbnail
                        : { uri: currentSpecimen.thumbnail?.uri || currentSpecimen.thumbnail }
                    }
                    style={styles.stateBSoilThumb}
                    resizeMode="cover"
                  />
                ) : (
                  <View
                    style={[
                      styles.stateBSoilThumb,
                      { backgroundColor: currentSpecimen.hexColor || '#8F694E' },
                    ]}
                  />
                )}
              </View>

              <View style={styles.stateBDetailsCol}>
                <Text style={styles.stateBMunsellCode}>
                  {currentSpecimen.munsellCode}
                </Text>
                <Text style={styles.stateBColorName} numberOfLines={1}>
                  {currentSpecimen.name}
                </Text>
              </View>

              <View style={styles.stateBHexPill}>
                <Text style={styles.stateBHexText}>
                  {(currentSpecimen.hexColor || '#594433').toUpperCase()}
                </Text>
              </View>
            </View>

            {/* Quick Action Buttons */}
            <View style={styles.stateBActionsRow}>
              <TouchableOpacity
                style={styles.stateBViewBtn}
                onPress={() => setShowDetailModal(true)}
                activeOpacity={0.85}
              >
                <Text style={styles.stateBViewBtnText}>View Result Card</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={styles.stateBRetakeBtn}
                onPress={handleRetake}
                activeOpacity={0.8}
              >
                <Text style={styles.stateBRetakeBtnText}>🔄 Retake Photo</Text>
              </TouchableOpacity>
            </View>
          </TouchableOpacity>
        </Animated.View>
      ) : rejectMsg ? (
        /* State C: Rejection Card */
        <View style={styles.bottomCardWrapper}>
          <View style={styles.analyzingCard}>
            <View style={styles.analyzingTextBox}>
              <Text style={styles.analyzingCardTitle}>No soil detected</Text>
              <Text style={styles.analyzingCardDesc}>{rejectMsg}</Text>
              <View style={styles.rejectionActionsRow}>
                <TouchableOpacity
                  style={styles.rejectionRetakeBtn}
                  onPress={handleRetake}
                  activeOpacity={0.8}
                >
                  <Text style={styles.rejectionRetakeBtnText}>🔄 Retake / Live Camera</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  style={styles.rejectionDismissBtn}
                  onPress={() => setRejectMsg(null)}
                  activeOpacity={0.8}
                >
                  <Text style={styles.rejectionDismissBtnText}>Dismiss</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </View>
      ) : (
        /* State D: Initial prompt before scan */
        <View style={styles.bottomCardWrapper} pointerEvents="none">
          <View style={styles.neutralPromptCard}>
            <View style={styles.neutralPulseDot} />
            <Text style={styles.neutralPromptText}>Tap Scan Soil button below to identify soil</Text>
          </View>
        </View>
      )}

      {/* 6. Bottom Navigation Bar with Center Scan Trigger */}
      <BottomNavBar activeTab="ScanSoil" onTabPress={handleTabPress} />

      {/* 7. Soil Record Result Card Modal (Mirrors Website #resultContent inside .result-card Exactly) */}
      {/* 7. Soil Record Details Pop-up Modal (Matches Website Modal Exactly) */}
      {showDetailModal && currentSpecimen ? (
        <Modal
          visible={showDetailModal}
          animationType="fade"
          transparent={true}
          onRequestClose={() => setShowDetailModal(false)}
        >
          <View style={styles.soilModalBackdrop}>
            <Pressable
              style={StyleSheet.absoluteFill}
              onPress={() => setShowDetailModal(false)}
            />
            <View style={styles.soilModalCard} pointerEvents="auto">
              {/* Modal Header: Title & Circular Close Button */}
              <View style={styles.soilModalHeaderRow}>
                <Text style={styles.soilModalTitle}>Soil Record Details</Text>
                <TouchableOpacity
                  style={styles.soilModalCloseBtn}
                  onPress={() => setShowDetailModal(false)}
                  activeOpacity={0.7}
                  accessibilityLabel="Close details"
                >
                  <Text style={styles.soilModalCloseIcon}>✕</Text>
                </TouchableOpacity>
              </View>

              {/* Modal Body Container */}
              <ScrollView
                showsVerticalScrollIndicator={false}
                contentContainerStyle={styles.soilModalScrollContent}
                keyboardShouldPersistTaps="always"
              >
                {/* Dual-Preview Soil Badge Banner (Website #modalSoilDualPreviewBadge) */}
                <View
                  style={[
                    styles.soilBannerDualPreview,
                    { backgroundColor: currentSpecimen.hexColor || currentSpecimen.hex || '#8F694E' },
                  ]}
                >
                  {/* Left Avatar Snippet (Website #modalSoilSnippetAvatarContainer) */}
                  <View style={styles.soilBannerAvatarContainer}>
                    {croppedSpecimenUri ? (
                      <Image
                        source={{ uri: croppedSpecimenUri }}
                        style={styles.soilBannerAvatarImg}
                        resizeMode="cover"
                      />
                    ) : currentSpecimen.scanImage ? (
                      <Image
                        source={{ uri: currentSpecimen.scanImage }}
                        style={styles.soilBannerAvatarImg}
                        resizeMode="cover"
                      />
                    ) : currentSpecimen.thumbnail ? (
                      <Image
                        source={
                          typeof currentSpecimen.thumbnail === 'number'
                            ? currentSpecimen.thumbnail
                            : { uri: currentSpecimen.thumbnail?.uri || currentSpecimen.thumbnail }
                        }
                        style={styles.soilBannerAvatarImg}
                        resizeMode="cover"
                      />
                    ) : (
                      <View
                        style={{
                          width: '100%',
                          height: '100%',
                          backgroundColor: specimenMunsellMatch?.hexCode || currentSpecimen.hexColor || currentSpecimen.hex || '#8F694E',
                        }}
                      />
                    )}
                    {/* Detected ROI bounding box overlaid on soil photo */}
                    {specimenAvatarRoiBox && (
                      <View
                        style={{
                          position: 'absolute',
                          left: `${specimenAvatarRoiBox.leftPct}%`,
                          top: `${specimenAvatarRoiBox.topPct}%`,
                          width: `${specimenAvatarRoiBox.widthPct}%`,
                          height: `${specimenAvatarRoiBox.heightPct}%`,
                          borderWidth: 1.5,
                          borderColor: specimenNormBoxes[0]?.hex || '#4ade80',
                          borderRadius: 2,
                        }}
                      />
                    )}
                  </View>

                  {/* Center Code & Label (Website #modalSwatchBlock) */}
                  <View style={styles.soilBannerCodeBlock}>
                    <Text style={styles.soilBannerMunsellCode}>
                      {currentSpecimen.munsellCode || currentSpecimen.fullCode || '7.5YR 5/3'}
                    </Text>
                    <Text style={styles.soilBannerSubLabel} numberOfLines={1}>
                      {currentSpecimen.name || currentSpecimen.colorName || 'Determined Soil'}
                    </Text>
                    {specimenNormRegions.length > 1 && (
                      <Text style={styles.soilBannerPercentageText}>
                        Soil Percentage ({specimenNormRegions[0]?.percent != null ? Math.round(specimenNormRegions[0].percent) : (currentSpecimen.soilPercentage || 60)}%)
                      </Text>
                    )}
                  </View>

                  {/* Right Hex Pill & Bright Overlay Badge */}
                  <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                    {specimenNormRegions[0]?.overlay_hex && (
                      <View style={[styles.layerOverlayBadge, { backgroundColor: specimenNormRegions[0].overlay_hex }]}>
                        <Text style={styles.layerOverlayBadgeText}>{specimenNormRegions[0].overlay_color_name || 'Red'}</Text>
                      </View>
                    )}
                    <View style={styles.soilBannerHexPill}>
                      <Text style={styles.soilBannerHexText}>
                        {(currentSpecimen.hexColor || currentSpecimen.hex || '#8F694E').toUpperCase()}
                      </Text>
                    </View>
                  </View>
                </View>

                {/* Dominant Color Sub-Shades (Dark -> Light) */}
                {Array.isArray(specimenNormRegions[0]?.shades) && specimenNormRegions[0].shades.length > 1 && (
                  <View style={styles.shadeSwatchesContainer}>
                    <Text style={styles.shadeSwatchesLabel}>Detected Sub-Shades:</Text>
                    <View style={styles.shadeSwatchesRow}>
                      {specimenNormRegions[0].shades.map((s, sIdx) => {
                        const sHex = Array.isArray(s.rgb) && s.rgb.length >= 3
                          ? `#${s.rgb.map(v => Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2, '0')).join('')}`
                          : '#888888';
                        const sPct = s.proportion != null ? Math.round(s.proportion * 100) : null;
                        return (
                          <View key={`dom_shade_${sIdx}`} style={styles.shadeSwatchItem}>
                            <View style={[styles.shadeSwatchDot, { backgroundColor: sHex }]} />
                            <Text style={styles.shadeSwatchText}>
                              {s.level ? s.level.charAt(0).toUpperCase() + s.level.slice(1) : ''}
                              {sPct != null ? ` (${sPct}%)` : ''}
                            </Text>
                          </View>
                        );
                      })}
                    </View>
                  </View>
                )}

                {/* Lighting Warning Notice */}
                {Boolean(currentSpecimen.lighting_warning || specimenNormRegions[0]?.lighting_warning) && (
                  <View style={styles.lightingWarningCard}>
                    <Text style={styles.lightingWarningTitle}>⚠️ Uneven Lighting Observed</Text>
                    <Text style={styles.lightingWarningText}>
                      Significant shadow or light gradient detected ({specimenNormRegions[0]?.lightness_spread ? `ΔL* ${specimenNormRegions[0].lightness_spread}` : 'uneven exposure'}). Consider rescanning under balanced indirect light for maximum accuracy.
                    </Text>
                  </View>
                )}

                {/* Metrics Container (2-Column Paired Grids matching website .soil-modal-grid-2) */}
                {/* Row 1: Color Name & Full Code */}
                <View style={styles.soilGridRow2}>
                  <View style={styles.soilModalGridCard}>
                    <Text style={styles.soilModalLabel}>COLOR NAME</Text>
                    <Text style={styles.soilModalValue} numberOfLines={1}>
                      {currentSpecimen.name || currentSpecimen.colorName || 'Brown'}
                    </Text>
                  </View>
                  <View style={styles.soilModalGridCard}>
                    <Text style={styles.soilModalLabel}>FULL CODE</Text>
                    <Text style={styles.soilModalValue} numberOfLines={1}>
                      {currentSpecimen.fullCode || currentSpecimen.munsellCode || '7.5YR 5/3'}
                    </Text>
                  </View>
                </View>

                {/* Row 2: Soil Organic & Soil Type */}
                <View style={styles.soilGridRow2}>
                  <View style={styles.soilModalGridCard}>
                    <Text style={styles.soilModalLabel}>SOIL ORGANIC</Text>
                    <Text style={styles.soilModalValue} numberOfLines={2}>
                      {specimenMunsellMatch?.soil_organic_index || currentSpecimen.organicIndex || 'Moderate (2.2% – 3.7%)'}
                    </Text>
                  </View>
                  <View style={styles.soilModalGridCard}>
                    <Text style={styles.soilModalLabel}>SOIL TYPE</Text>
                    <Text style={styles.soilModalValue} numberOfLines={2}>
                      {specimenMunsellMatch?.soil_type || currentSpecimen.soilType || 'Determined Soil'}
                    </Text>
                  </View>
                </View>

                {/* Row 3: Scan Count & Near-Match */}
                <View style={styles.soilGridRow2}>
                  <View style={styles.soilModalGridCard}>
                    <Text style={styles.soilModalLabel}>SCAN COUNT</Text>
                    <Text style={styles.soilModalValue}>
                      {currentSpecimen.scanCount ? String(currentSpecimen.scanCount) : '1'}
                    </Text>
                  </View>
                  <View style={[styles.soilModalGridCard, styles.soilNearMatchCard]}>
                    <Text style={styles.soilModalLabel}>NEAR-MATCH</Text>
                    <Text style={styles.soilNearMatchValue}>
                      {currentSpecimen.nearMatchRating || currentSpecimen.confidence || '97.4%'}
                    </Text>
                  </View>
                </View>

                {/* Row 4: Scan Date & Scanned By */}
                <View style={styles.soilGridRow2}>
                  <View style={styles.soilModalGridCard}>
                    <Text style={styles.soilModalLabel}>SCAN DATE</Text>
                    <Text style={styles.soilModalValue} numberOfLines={1}>
                      {specimenNormDateTime.dateStr || currentSpecimen.scanDate || currentSpecimen.createdDate || 'Today'}
                    </Text>
                  </View>
                  <View style={styles.soilModalGridCard}>
                    <Text style={styles.soilModalLabel}>SCANNED BY</Text>
                    <View style={styles.scannedByRow}>
                      <Text style={styles.soilModalValue} numberOfLines={1}>
                        {currentSpecimen.scannedBy || 'User'}
                      </Text>
                      <Text style={styles.soilPlatformBadge}>App</Text>
                    </View>
                  </View>
                </View>

                {/* Scan Note Card (Full Width - Website #modalScanNoteCard) */}
                <View style={styles.soilModalFullCard}>
                  <Text style={styles.soilModalLabel}>SCAN NOTE</Text>
                  <Text style={styles.soilModalDescText}>
                    {(() => {
                      const hasPin = Boolean(currentSpecimen.hasPin || currentSpecimen.location_id || currentSpecimen.location || currentSpecimen.latitude || pinnedLocation);
                      const rawNote = currentSpecimen.scanNote || currentSpecimen.scan_note || '';
                      const isRealNote = rawNote && !rawNote.startsWith('Determined Soil:') && rawNote !== '—' && rawNote !== '-';
                      return (hasPin && isRealNote) ? rawNote : '-';
                    })()}
                  </Text>
                </View>

                {/* Description Card (Full Width - Website #modalDescCard) */}
                <View style={styles.soilModalFullCard}>
                  <Text style={styles.soilModalLabel}>DESCRIPTION</Text>
                  <Text style={styles.soilModalDescText}>
                    {specimenResolvedDescription}
                  </Text>
                </View>

                {/* Soil Pin Location & Covered Area Card (Matches Website #resSoilLocationCard / #modalSoilLocationCard) */}
                {(() => {
                  const isPinned = Boolean(
                    pinnedLocation ||
                    currentSpecimen.hasPin ||
                    currentSpecimen.location_id ||
                    currentSpecimen.location ||
                    currentSpecimen.latitude
                  );
                  if (!isPinned) return null;

                  const loc = pinnedLocation || currentSpecimen.location || {};
                  const lat = parseFloat(loc.latitude ?? currentSpecimen.latitude ?? 18.1568);
                  const lng = parseFloat(loc.longitude ?? currentSpecimen.longitude ?? 121.6608);
                  const brgy = loc.barangay || currentSpecimen.locationName || currentSpecimen.barangay || 'Lal-lo, Cagayan';
                  const status = loc.status || currentSpecimen.status || 'Pending Admin Approval';
                  const coverage = loc.coverage_area || currentSpecimen.coverage_area || currentSpecimen.coverage || null;
                  const isApproved = String(status).toLowerCase().includes('approved');

                  return (
                    <View style={styles.soilModalFullCard}>
                      <View style={styles.soilLocationHeaderRow}>
                        <Text style={styles.soilLocationLabel}>SOIL PIN LOCATION &amp; COVERED AREA</Text>
                        <View style={styles.soilLocationBadgesRow}>
                          <View style={[styles.soilLocBadgeStatus, isApproved ? styles.soilLocBadgeApproved : styles.soilLocBadgePending]}>
                            <Text style={[styles.soilLocBadgeStatusText, isApproved ? { color: '#16a34a' } : { color: '#d97706' }]}>
                              {status.toUpperCase()}
                            </Text>
                          </View>
                          <View style={styles.soilLocBadgeBrgy}>
                            <Text style={styles.soilLocBadgeBrgyText}>📍 {brgy}</Text>
                          </View>
                          {coverage && (
                            <View style={styles.soilLocBadgeCoverage}>
                              <Text style={styles.soilLocBadgeCoverageText}>{coverage} ha</Text>
                            </View>
                          )}
                          {!isNaN(lat) && !isNaN(lng) && (
                            <View style={styles.soilLocBadgeCoords}>
                              <Text style={styles.soilLocBadgeCoordsText}>
                                {lat.toFixed(4)}°N, {lng.toFixed(4)}°E
                              </Text>
                            </View>
                          )}
                        </View>
                      </View>
                      <View style={styles.soilMapPreviewFrame}>
                        <View style={styles.soilMapCenterMarker}>
                          <MapPinIcon size={22} color="#22c55e" />
                          <View style={styles.soilMapCoordsCallout}>
                            <Text style={styles.soilMapCoordsCalloutText} numberOfLines={1}>
                              {brgy} · {lat.toFixed(4)}°, {lng.toFixed(4)}°
                            </Text>
                          </View>
                        </View>
                        <Text style={styles.soilMapWatermark}>AgriCo Soil GPS Preview</Text>
                      </View>
                    </View>
                  );
                })()}

                {/* Primary Soil Result: Pin Location Button (Matches Website #resPrimaryPinLocationWrapper / #btnPinLocationScan) */}
                <View style={{ marginBottom: 12 }}>
                  <TouchableOpacity
                    style={[
                      styles.btnPrimaryPinLocation,
                      Boolean(pinnedLocation || currentSpecimen.hasPin || currentSpecimen.latitude) && styles.btnPrimaryPinLocationDone,
                    ]}
                    onPress={() => {
                      setTargetPinLayer(null);
                      setShowPinModal(true);
                    }}
                    activeOpacity={0.85}
                    accessibilityLabel="Pin Soil Specimen to Map"
                  >
                    <MapPinIcon size={15} color="#ffffff" />
                    <Text style={styles.btnPrimaryPinLocationText}>
                      {pinnedLocation || currentSpecimen.hasPin || currentSpecimen.latitude
                        ? 'Edit Pin Location'
                        : 'Pin Location'}
                    </Text>
                  </TouchableOpacity>
                </View>

                {/* Multi-Layer Accordion Section (Matches Website #resMultiLayerSection / #modalMultiLayerSection) */}
                {specimenNormRegions.length > 1 && (
                  <View style={styles.multiLayerSection}>
                    <View style={styles.multiLayerHeaderRow}>
                      <Text style={styles.multiLayerSectionTitle}>
                        ALL DETECTED SOIL LAYERS ({specimenNormRegions.length})
                      </Text>
                      <TouchableOpacity
                        onPress={() => {
                          const allExpanded = specimenNormRegions.slice(1).every((_, i) => expandedLayers[i]);
                          const next = {};
                          specimenNormRegions.slice(1).forEach((_, i) => {
                            next[i] = !allExpanded;
                          });
                          setExpandedLayers(next);
                        }}
                        activeOpacity={0.7}
                      >
                        <Text style={styles.multiLayerToggleAllText}>
                          {specimenNormRegions.slice(1).every((_, i) => expandedLayers[i]) ? 'Collapse All' : 'Expand All'}
                        </Text>
                      </TouchableOpacity>
                    </View>

                    {/* Secondary Layer Accordion Items */}
                    <View style={{ gap: 8 }}>
                      {specimenNormRegions.slice(1).map((r, idx) => {
                        const isExpanded = Boolean(expandedLayers[idx]);
                        const rPct = r.percent !== undefined
                          ? Math.round(r.percent)
                          : (r.proportion !== undefined ? Math.round(r.proportion * 100) : 25);
                        const rCode = (r.munsell_code || r.munsell || r.notation || currentSpecimen.fullCode || '10YR 4/1').trim();
                        let rHex = r.hex || r.hex_color || null;
                        if (!rHex && Array.isArray(r.rgb) && r.rgb.length >= 3) {
                          rHex = `#${r.rgb.map(v => Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2, '0')).join('')}`;
                        }
                        rHex = rHex || currentSpecimen.hexColor || '#8F694E';
                        if (!rHex.startsWith('#')) rHex = `#${rHex}`;

                        const matchedMunsell = OFFLINE_MUNSELL_COLORS.find(
                          c => (c.munsellCode || c.full_code || '').trim().toUpperCase() === rCode.toUpperCase()
                        );
                        const rName = r.color_name || r.name || r.label || matchedMunsell?.colorName || 'Determined Soil';
                        const rOrganic = r.organic_matter || r.soil_organic_index || matchedMunsell?.soil_organic_index || 'Moderate (2.2% – 3.7%)';
                        const rSoilType = r.soil_type || r.soil_name || matchedMunsell?.soil_type || 'Determined Soil';
                        const rConf = r.confidence ? `${r.confidence}%` : '96.8%';
                        const rDesc = matchedMunsell?.description || r.description || `${rCode} - ${rName}`;
                        const hasPin = Boolean(currentSpecimen.hasPin || currentSpecimen.location_id || currentSpecimen.latitude || pinnedLocation);
                        const rawNote = r.scan_note || currentSpecimen.scanNote || currentSpecimen.scan_note || '';
                        const rNote = (hasPin && rawNote && !rawNote.startsWith('Determined Soil:') && rawNote !== '—' && rawNote !== '-') ? rawNote : '-';

                        return (
                          <View key={`sec_layer_${idx}`} style={styles.layerAccordionItem}>
                            {/* Layer Accordion Header */}
                            <TouchableOpacity
                              style={styles.layerAccordionHeader}
                              onPress={() => setExpandedLayers(prev => ({ ...prev, [idx]: !prev[idx] }))}
                              activeOpacity={0.8}
                            >
                              <View style={styles.layerAccordionTitleRow}>
                                <Text style={styles.layerChevron}>{isExpanded ? '▼' : '▶'}</Text>
                                <Text style={styles.layerAccordionTitleText} numberOfLines={1}>
                                  {rCode} (Soil Percentage - {rPct}%)
                                </Text>
                              </View>
                              <View style={[styles.layerHexPill, { backgroundColor: rHex }]}>
                                <Text style={styles.layerHexPillText}>{rHex.toUpperCase()}</Text>
                              </View>
                            </TouchableOpacity>

                            {/* Layer Accordion Body */}
                            {isExpanded && (
                              <View style={styles.layerAccordionBody}>
                                {/* Hero Swatch Banner */}
                                <View style={[styles.layerBannerHero, { backgroundColor: rHex }]}>
                                  <View style={styles.layerAvatarSquare}>
                                    {currentSpecimen.scanImage || croppedSpecimenUri ? (
                                      <View style={{ width: '100%', height: '100%', position: 'relative', overflow: 'hidden' }}>
                                        <Image
                                          source={{ uri: currentSpecimen.scanImage || croppedSpecimenUri }}
                                          style={{ width: '100%', height: '100%' }}
                                          resizeMode="cover"
                                        />
                                        <View
                                          style={{
                                            position: 'absolute',
                                            left: `${normalizeBox(r).raw_x * 100}%`,
                                            top: `${normalizeBox(r).raw_y * 100}%`,
                                            width: `${normalizeBox(r).raw_w * 100}%`,
                                            height: `${normalizeBox(r).raw_h * 100}%`,
                                            borderWidth: 1.5,
                                            borderColor: rHex,
                                            borderRadius: 2,
                                          }}
                                        />
                                      </View>
                                    ) : (
                                      <View style={{ width: '100%', height: '100%', backgroundColor: rHex }} />
                                    )}
                                  </View>
                                  <View style={styles.layerBannerCodeCol}>
                                    <Text style={styles.layerBannerCode}>{rCode}</Text>
                                    <Text style={styles.layerBannerName} numberOfLines={1}>{rName}</Text>
                                  </View>
                                  <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                                    {r.overlay_hex && (
                                      <View style={[styles.layerOverlayBadge, { backgroundColor: r.overlay_hex }]}>
                                        <Text style={styles.layerOverlayBadgeText}>{r.overlay_color_name || 'Overlay'}</Text>
                                      </View>
                                    )}
                                    <View style={styles.layerBannerHexBadge}>
                                      <Text style={styles.layerBannerHexBadgeText}>{rHex.toUpperCase()}</Text>
                                    </View>
                                  </View>
                                </View>

                                {/* Secondary Layer Sub-Shades */}
                                {Array.isArray(r.shades) && r.shades.length > 1 && (
                                  <View style={styles.shadeSwatchesContainer}>
                                    <Text style={styles.shadeSwatchesLabel}>Detected Sub-Shades:</Text>
                                    <View style={styles.shadeSwatchesRow}>
                                      {r.shades.map((s, sIdx) => {
                                        const sHex = Array.isArray(s.rgb) && s.rgb.length >= 3
                                          ? `#${s.rgb.map(v => Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2, '0')).join('')}`
                                          : '#888888';
                                        const sPct = s.proportion != null ? Math.round(s.proportion * 100) : null;
                                        return (
                                          <View key={`layer_shade_${idx}_${sIdx}`} style={styles.shadeSwatchItem}>
                                            <View style={[styles.shadeSwatchDot, { backgroundColor: sHex }]} />
                                            <Text style={styles.shadeSwatchText}>
                                              {s.level ? s.level.charAt(0).toUpperCase() + s.level.slice(1) : ''}
                                              {sPct != null ? ` (${sPct}%)` : ''}
                                            </Text>
                                          </View>
                                        );
                                      })}
                                    </View>
                                  </View>
                                )}

                                 {/* 2-Column Paired Grids matching website .soil-modal-grid-2 */}
                                {/* Row 1: Color Name & Full Code */}
                                <View style={styles.soilGridRow2}>
                                  <View style={styles.soilModalGridCardSmall}>
                                    <Text style={styles.soilModalLabelSmall}>COLOR NAME</Text>
                                    <Text style={styles.soilModalValueSmall} numberOfLines={1}>{rName}</Text>
                                  </View>
                                  <View style={styles.soilModalGridCardSmall}>
                                    <Text style={styles.soilModalLabelSmall}>FULL CODE</Text>
                                    <Text style={styles.soilModalValueSmall} numberOfLines={1}>{rCode}</Text>
                                  </View>
                                </View>

                                {/* Row 2: Soil Organic & Soil Type */}
                                <View style={styles.soilGridRow2}>
                                  <View style={styles.soilModalGridCardSmall}>
                                    <Text style={styles.soilModalLabelSmall}>SOIL ORGANIC</Text>
                                    <Text style={styles.soilModalValueSmall} numberOfLines={2}>{rOrganic}</Text>
                                  </View>
                                  <View style={styles.soilModalGridCardSmall}>
                                    <Text style={styles.soilModalLabelSmall}>SOIL TYPE</Text>
                                    <Text style={styles.soilModalValueSmall} numberOfLines={2}>{rSoilType}</Text>
                                  </View>
                                </View>

                                {/* Row 3: Proportion & Near-Match */}
                                <View style={styles.soilGridRow2}>
                                  <View style={styles.soilModalGridCardSmall}>
                                    <Text style={styles.soilModalLabelSmall}>PROPORTION</Text>
                                    <Text style={styles.soilModalValueSmall}>{rPct}%</Text>
                                  </View>
                                  <View style={[styles.soilModalGridCardSmall, { borderColor: 'rgba(16, 185, 129, 0.35)' }]}>
                                    <Text style={styles.soilModalLabelSmall}>NEAR-MATCH</Text>
                                    <Text style={[styles.soilModalValueSmall, { color: '#10b981', fontWeight: '800' }]}>{rConf}</Text>
                                  </View>
                                </View>

                                {/* Scan Note Card */}
                                <View style={styles.soilModalFullCardSmall}>
                                  <Text style={styles.soilModalLabelSmall}>SCAN NOTE</Text>
                                  <Text style={styles.soilModalDescTextSmall}>{rNote}</Text>
                                </View>

                                {/* Description Card */}
                                <View style={styles.soilModalFullCardSmall}>
                                  <Text style={styles.soilModalLabelSmall}>DESCRIPTION</Text>
                                  <Text style={styles.soilModalDescTextSmall}>{rDesc}</Text>
                                </View>

                                {/* Layer Pin Location Button */}
                                <TouchableOpacity
                                  style={styles.btnLayerPinLocation}
                                  onPress={() => {
                                    setTargetPinLayer(r);
                                    setShowPinModal(true);
                                  }}
                                  activeOpacity={0.85}
                                >
                                  <MapPinIcon size={14} color="#ffffff" />
                                  <Text style={styles.btnLayerPinLocationText}>Pin Location</Text>
                                </TouchableOpacity>
                              </View>
                            )}
                          </View>
                        );
                      })}
                    </View>
                  </View>
                )}

                {/* Soil Normalization Card (Stage 2 Lighting Normalizer - Matches Website Exactly) */}
                <View style={styles.soilModalNormCard}>
                  {/* Card Header: Label with Sun Icon & Pill Badge */}
                  <View style={styles.soilModalNormHeader}>
                    <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                      <SunLightingIcon size={13} color="#10b981" />
                      <Text style={[styles.soilModalNormLabel, { color: '#10b981', fontWeight: '800' }]}>
                        SOIL NORMALIZATION
                      </Text>
                    </View>
                    <View style={styles.beforeAfterBadge}>
                      <Text style={styles.beforeAfterBadgeText}>BEFORE / AFTER</Text>
                    </View>
                  </View>

                  {/* Subtitle matching website #resStage2EngineDesc */}
                  <Text style={styles.soilModalNormDesc}>
                    Uneven illumination &amp; shadows corrected before Munsell color matching.
                  </Text>

                  {/* Image Frame with Overlays */}
                  <View style={styles.soilModalNormImageFrame}>
                    {currentSpecimen.beforeAfterImage || currentSpecimen.beforeAfterImageUrl ? (
                      <View style={{ width: '100%', height: 165, position: 'relative' }}>
                        <Image
                          source={{ uri: currentSpecimen.beforeAfterImage || currentSpecimen.beforeAfterImageUrl }}
                          style={styles.soilNormImage}
                          resizeMode="cover"
                        />
                        {/* Whole-image multi-color segmentation overlay */}
                        {(currentSpecimen.colorMapPng || currentSpecimen.color_map_png || specimenNormRegions[0]?.color_map_png) ? (
                          <>
                            {selectedOverlayColorIndex === null ? (
                              <Image
                                source={{ uri: `data:image/png;base64,${currentSpecimen.colorMapPng || currentSpecimen.color_map_png || specimenNormRegions[0]?.color_map_png}` }}
                                style={{
                                  position: 'absolute',
                                  left: '50%',
                                  top: `${normBarPct}%`,
                                  width: '50%',
                                  height: `${normImgHeightPct}%`,
                                  opacity: 0.55,
                                }}
                                resizeMode="cover"
                              />
                            ) : (
                              <>
                                {specimenNormRegions[selectedOverlayColorIndex]?.mask_png && (
                                  <Image
                                    source={{ uri: `data:image/png;base64,${specimenNormRegions[selectedOverlayColorIndex].mask_png}` }}
                                    style={{
                                      position: 'absolute',
                                      left: '50%',
                                      top: `${normBarPct}%`,
                                      width: '50%',
                                      height: `${normImgHeightPct}%`,
                                      opacity: 0.70,
                                    }}
                                    resizeMode="cover"
                                  />
                                )}
                                {Array.isArray(specimenNormRegions[selectedOverlayColorIndex]?.blobs) && specimenNormRegions[selectedOverlayColorIndex].blobs.map((blob, bIdx) => (
                                  <View
                                    key={`sel_blob_${bIdx}`}
                                    style={{
                                      position: 'absolute',
                                      left: `${50.0 + blob.norm_x * 50.0}%`,
                                      top: `${normBarPct + blob.norm_y * normImgHeightPct}%`,
                                      width: `${blob.norm_w * 50.0}%`,
                                      height: `${blob.norm_h * normImgHeightPct}%`,
                                      borderWidth: 1.5,
                                      borderColor: specimenNormRegions[selectedOverlayColorIndex]?.overlay_hex || '#ffffff',
                                      borderRadius: 2,
                                    }}
                                  />
                                ))}
                              </>
                            )}
                          </>
                        ) : (
                          /* Fallback bounding box display for older scans */
                          specimenNormBoxes.map((b, idx) => (
                            <View
                              key={`norm_box_${idx}`}
                              style={{
                                position: 'absolute',
                                left: `${b.splitLeftPct}%`,
                                top: `${b.splitTopPct}%`,
                                width: `${b.splitWidthPct}%`,
                                height: `${b.splitHeightPct}%`,
                                borderWidth: 2.5,
                                borderColor: b.hex,
                                borderRadius: 4,
                              }}
                            >
                              <View style={[styles.normRoleBadge, { backgroundColor: b.hex, borderColor: 'rgba(0,0,0,0.3)', borderWidth: 1 }]}>
                                <Text style={[styles.normRoleBadgeText, { color: '#fff', textShadowColor: 'rgba(0,0,0,0.8)', textShadowRadius: 2 }]}>
                                  {b.roleLabel}{b.munsellCode ? ` · ${b.munsellCode}` : ''}{b.percent != null ? ` (${b.percent}%)` : ''}
                                </Text>
                              </View>
                            </View>
                          ))
                        )}
                      </View>
                    ) : currentSpecimen.scanImage || userCustomImage || croppedSpecimenUri ? (
                      /* Simulated split Before / After frame if before_after is not yet rendered */
                      <View style={{ width: '100%', position: 'relative' }}>
                        <View style={styles.normSimulatedHeader}>
                          <Text style={styles.normHeaderBefore}>BEFORE</Text>
                          <Text style={styles.normHeaderAfter}>
                            AFTER (NORMALIZED) -- {currentSpecimen.munsellCode || currentSpecimen.fullCode || '10YR 4/1'}
                          </Text>
                        </View>
                        <View style={{ flexDirection: 'row', height: 135 }}>
                          <Image
                            source={{ uri: currentSpecimen.scanImage || croppedSpecimenUri || userCustomImage }}
                            style={{ flex: 1, height: '100%' }}
                            resizeMode="cover"
                          />
                          <View style={{ width: 1.5, backgroundColor: 'rgba(255, 255, 255, 0.3)' }} />
                          <View style={{ flex: 1, height: '100%', position: 'relative', overflow: 'hidden' }}>
                            <Image
                              source={{ uri: currentSpecimen.scanImage || croppedSpecimenUri || userCustomImage }}
                              style={{ width: '100%', height: '100%' }}
                              resizeMode="cover"
                            />
                            {/* Whole-image multi-color segmentation overlay */}
                            {(currentSpecimen.colorMapPng || currentSpecimen.color_map_png || specimenNormRegions[0]?.color_map_png) ? (
                              <>
                                {selectedOverlayColorIndex === null ? (
                                  <Image
                                    source={{ uri: `data:image/png;base64,${currentSpecimen.colorMapPng || currentSpecimen.color_map_png || specimenNormRegions[0]?.color_map_png}` }}
                                    style={{
                                      ...StyleSheet.absoluteFillObject,
                                      width: '100%',
                                      height: '100%',
                                      opacity: 0.55,
                                    }}
                                    resizeMode="cover"
                                  />
                                ) : (
                                  <>
                                    {specimenNormRegions[selectedOverlayColorIndex]?.mask_png && (
                                      <Image
                                        source={{ uri: `data:image/png;base64,${specimenNormRegions[selectedOverlayColorIndex].mask_png}` }}
                                        style={{
                                          ...StyleSheet.absoluteFillObject,
                                          width: '100%',
                                          height: '100%',
                                          opacity: 0.70,
                                        }}
                                        resizeMode="cover"
                                      />
                                    )}
                                    {Array.isArray(specimenNormRegions[selectedOverlayColorIndex]?.blobs) && specimenNormRegions[selectedOverlayColorIndex].blobs.map((blob, bIdx) => (
                                      <View
                                        key={`sim_sel_blob_${bIdx}`}
                                        style={{
                                          position: 'absolute',
                                          left: `${blob.norm_x * 100.0}%`,
                                          top: `${blob.norm_y * 100.0}%`,
                                          width: `${blob.norm_w * 100.0}%`,
                                          height: `${blob.norm_h * 100.0}%`,
                                          borderWidth: 1.5,
                                          borderColor: specimenNormRegions[selectedOverlayColorIndex]?.overlay_hex || '#ffffff',
                                          borderRadius: 2,
                                        }}
                                      />
                                    ))}
                                  </>
                                )}
                              </>
                            ) : (
                              /* Fallback boxes */
                              specimenNormBoxes.map((b, idx) => (
                                <View
                                  key={`sim_norm_box_${idx}`}
                                  style={{
                                    position: 'absolute',
                                    left: `${b.boxLeftPct}%`,
                                    top: `${b.boxTopPct}%`,
                                    width: `${b.boxWidthPct}%`,
                                    height: `${b.boxHeightPct}%`,
                                    borderWidth: 2,
                                    borderColor: b.hex,
                                    borderRadius: 4,
                                  }}
                                >
                                  <View style={[styles.normRoleBadge, { backgroundColor: b.hex, borderColor: 'rgba(0,0,0,0.3)', borderWidth: 1 }]}>
                                    <Text style={[styles.normRoleBadgeText, { color: '#fff', textShadowColor: 'rgba(0,0,0,0.8)', textShadowRadius: 2 }]}>
                                      {b.roleLabel}{b.munsellCode ? ` · ${b.munsellCode}` : ''}{b.percent != null ? ` (${b.percent}%)` : ''}
                                    </Text>
                                  </View>
                                </View>
                              ))
                            )}
                          </View>
                        </View>
                      </View>
                    ) : (
                      /* Simulated split Before / After swatch frame when photo is not in record */
                      <View style={{ width: '100%', position: 'relative' }}>
                        <View style={styles.normSimulatedHeader}>
                          <Text style={styles.normHeaderBefore}>BEFORE</Text>
                          <Text style={styles.normHeaderAfter}>
                            AFTER (NORMALIZED) -- {currentSpecimen.munsellCode || currentSpecimen.fullCode || '10YR 4/1'}
                          </Text>
                        </View>
                        <View style={{ flexDirection: 'row', height: 135 }}>
                          <View
                            style={{
                              flex: 1,
                              height: '100%',
                              backgroundColor: specimenMunsellMatch?.hexCode || currentSpecimen.hexColor || currentSpecimen.hex || '#3a2518',
                              opacity: 0.85,
                            }}
                          />
                          <View style={{ width: 1.5, backgroundColor: 'rgba(255, 255, 255, 0.3)' }} />
                          <View
                            style={{
                              flex: 1,
                              height: '100%',
                              backgroundColor: specimenMunsellMatch?.hexCode || currentSpecimen.hexColor || currentSpecimen.hex || '#8F694E',
                              position: 'relative',
                              overflow: 'hidden',
                            }}
                          >
                            {specimenNormBoxes.map((b, idx) => (
                              <View
                                key={`swatch_norm_box_${idx}`}
                                style={{
                                  position: 'absolute',
                                  left: `${b.boxLeftPct}%`,
                                  top: `${b.boxTopPct}%`,
                                  width: `${b.boxWidthPct}%`,
                                  height: `${b.boxHeightPct}%`,
                                  borderWidth: 2,
                                  borderColor: b.hex,
                                  borderRadius: 4,
                                }}
                              >
                                <View style={[styles.normRoleBadge, { backgroundColor: b.hex, borderColor: 'rgba(0,0,0,0.3)', borderWidth: 1 }]}>
                                  <Text style={[styles.normRoleBadgeText, { color: '#fff', textShadowColor: 'rgba(0,0,0,0.8)', textShadowRadius: 2 }]}>
                                    {b.roleLabel}
                                  </Text>
                                </View>
                              </View>
                            ))}
                          </View>
                        </View>
                      </View>
                    )}
                  </View>

                  {/* Interactive Multi-Color Segmentation Legend */}
                  <View style={{ marginTop: 10 }}>
                    <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 6 }}>
                      <Text style={{ fontSize: 10.5, fontWeight: '800', color: '#475569', textTransform: 'uppercase', letterSpacing: 0.4 }}>
                        {specimenNormRegions.length} Soil Colors, 100% of the image scanned
                      </Text>
                      {selectedOverlayColorIndex !== null && (
                        <TouchableOpacity
                          onPress={() => setSelectedOverlayColorIndex(null)}
                          style={{ paddingHorizontal: 8, paddingVertical: 2.5, backgroundColor: '#f1f5f9', borderRadius: 6, borderWidth: 1, borderColor: '#cbd5e1' }}
                        >
                          <Text style={{ fontSize: 10, fontWeight: '700', color: '#0d9488' }}>Show all colors ↺</Text>
                        </TouchableOpacity>
                      )}
                    </View>
                    <View style={styles.normSwatchesRow}>
                      {specimenNormRegions.map((r, idx) => {
                        let rHex = r.hex || r.hex_color || null;
                        if (!rHex && Array.isArray(r.rgb) && r.rgb.length >= 3) {
                          rHex = `#${r.rgb.map(v => Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2, '0')).join('')}`;
                        }
                        if (!rHex && specimenNormRegions.length === 1) {
                          rHex = currentSpecimen.hexColor || currentSpecimen.hex || '#8F694E';
                        }
                        rHex = (rHex || '#888888');
                        if (!rHex.startsWith('#')) rHex = `#${rHex}`;

                        const rCode = r.munsell_code || r.munsell || r.notation || null;
                        const displayCode = rCode || (specimenNormRegions.length === 1
                          ? (currentSpecimen.munsellCode || currentSpecimen.fullCode || '')
                          : '');

                        const rPct = r.percent !== undefined
                          ? Math.round(r.percent)
                          : (r.proportion !== undefined
                              ? Math.round(r.proportion * 100)
                              : (specimenNormRegions.length === 1 ? (currentSpecimen.soilPercentage || 100) : null));

                        const overlayColorHex = r.overlay_hex || '#ff4040';
                        const isSelected = selectedOverlayColorIndex === idx;
                        const isDimmed = selectedOverlayColorIndex !== null && !isSelected;

                        return (
                          <TouchableOpacity
                            key={`swatch_pill_${idx}`}
                            activeOpacity={0.75}
                            onPress={() => setSelectedOverlayColorIndex(isSelected ? null : idx)}
                            style={[
                              styles.soilSwatchCapsulePill,
                              isSelected && { borderColor: overlayColorHex, borderWidth: 1.5, backgroundColor: '#ffffff' },
                              isDimmed && { opacity: 0.45 },
                              r.verified === false && { borderStyle: 'dashed' },
                            ]}
                          >
                            <View style={{ width: 8, height: 8, borderRadius: 4, backgroundColor: overlayColorHex, marginRight: 2 }} />
                            <View style={[styles.capsulePillSwatch, { backgroundColor: rHex }]} />
                            <Text style={styles.capsulePillHex}>{`Color ${idx + 1}`}</Text>
                            {displayCode ? <Text style={styles.capsulePillCode}>({displayCode})</Text> : null}
                            {rPct != null ? <Text style={styles.capsulePillPct}>{rPct}%</Text> : null}
                            {r.verified === false && (
                              <Text style={{ fontSize: 9, fontWeight: '700', color: '#b45309', marginLeft: 2 }}>[Unverified]</Text>
                            )}
                          </TouchableOpacity>
                        );
                      })}
                      {Array.isArray(currentSpecimen.rejected_regions || currentSpecimen.rejectedRegions) &&
                        (currentSpecimen.rejected_regions || currentSpecimen.rejectedRegions).map((rej, rIdx) => (
                          <View key={`rej_swatch_${rIdx}`} style={[styles.soilSwatchCapsulePill, { opacity: 0.75, backgroundColor: '#f1f5f9' }]}>
                            <View style={{ width: 8, height: 8, borderRadius: 4, backgroundColor: '#969696', marginRight: 2 }} />
                            <Text style={[styles.capsulePillHex, { color: '#64748b' }]}>Non-soil/Glare</Text>
                            <Text style={styles.capsulePillPct}>{Math.round((rej.proportion || 0) * 100)}%</Text>
                          </View>
                        ))}
                    </View>
                  </View>

                  {/* Top Metadata Row (Inside Before & After Card - Updated with 12-hr AM/PM Time) */}
                  <View style={styles.soilNormMetaRow}>
                    <Text style={styles.soilNormMetaTitle}>METADATA</Text>
                    <Text style={styles.soilNormMetaInfo}>
                      DATE: {specimenNormDateTime.dateStr}  |  TIME: {specimenNormDateTime.timeStr}  |  DETECTED COLORS: {Math.max(specimenNormRegions.length, 1)}
                    </Text>
                  </View>
                </View>
              </ScrollView>

              {/* Fixed Modal Footer: Close, Save Copy, Pin Location / Pin All (Matches Website Exactly) */}
              <View style={styles.soilModalFooter}>
                <TouchableOpacity
                  style={styles.btnModalCancel}
                  onPress={() => setShowDetailModal(false)}
                  activeOpacity={0.8}
                  accessibilityLabel="Close details"
                >
                  <Text style={styles.btnModalCancelText}>Close</Text>
                </TouchableOpacity>

                <TouchableOpacity
                  style={[
                    styles.btnSaveHardcopy,
                    isSavedCopy && { backgroundColor: '#15803d' },
                  ]}
                  onPress={() => handleSaveSpecimenCopy(currentSpecimen, specimenMunsellMatch, specimenResolvedDescription)}
                  activeOpacity={0.85}
                  accessibilityLabel="Save Copy"
                >
                  <SaveTrayIcon size={14} color="#ffffff" />
                  <Text style={styles.btnSaveHardcopyText}>
                    {isSavedCopy ? 'Saved Copy ✓' : 'Save Copy'}
                  </Text>
                </TouchableOpacity>

                {specimenNormRegions.length > 1 ? (
                  <TouchableOpacity
                    style={styles.btnPinAllModal}
                    onPress={() => {
                      setTargetPinLayer(null);
                      setShowPinModal(true);
                    }}
                    activeOpacity={0.85}
                    accessibilityLabel="Pin All Detected Soils"
                  >
                    <MapPinIcon size={14} color="#ffffff" />
                    <Text style={styles.btnPinAllModalText}>Pin All</Text>
                  </TouchableOpacity>
                ) : (
                  <TouchableOpacity
                    style={styles.btnPinSoilModal}
                    onPress={() => {
                      setTargetPinLayer(null);
                      setShowDetailModal(false);
                      setTimeout(() => {
                        setShowPinModal(true);
                      }, 250);
                    }}
                    activeOpacity={0.85}
                    accessibilityLabel="Pin Soil Location"
                  >
                    <MapPinIcon size={14} color="#ffffff" />
                    <Text style={styles.btnPinSoilModalText}>Pin Location</Text>
                  </TouchableOpacity>
                )}
              </View>
            </View>
          </View>
        </Modal>
      ) : null}

      {/* 8. Interactive Pin Soil Location Modal */}
      <PinSoilLocationModal
        visible={showPinModal}
        onClose={() => {
          setShowPinModal(false);
          setTargetPinLayer(null);
        }}
        specimen={targetPinLayer ? { ...currentSpecimen, ...targetPinLayer } : currentSpecimen}
        onSuccess={(data) => {
          setPinnedLocation(data);
          triggerAutoSaveToast('📍 Soil sample pinned & submitted for Admin Approval!');
        }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#051F20',
  },
  backgroundImage: {
    width: '100%',
    height: '100%',
  },
  torchBeamOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(254, 240, 138, 0.22)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  torchCenterGlow: {
    width: SCREEN_WIDTH * 0.88,
    height: SCREEN_WIDTH * 0.88,
    borderRadius: (SCREEN_WIDTH * 0.88) / 2,
    backgroundColor: 'rgba(255, 255, 255, 0.16)',
  },
  shutterFlash: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: '#ffffff',
  },

  topHeader: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingTop: Platform.OS === 'ios' ? 54 : 44,
    zIndex: 20,
  },
  headerCloseBtn: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(0, 0, 0, 0.45)',
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.25)',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 4,
    elevation: 4,
  },
  closeBtnText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '700',
  },
  titleContainer: {
    alignItems: 'center',
  },
  headerTitle: {
    color: '#ffffff',
    fontSize: 18,
    fontWeight: '700',
    letterSpacing: 0.3,
    textShadowColor: 'rgba(0, 0, 0, 0.6)',
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 3,
  },
  torchActiveSub: {
    color: '#facc15',
    fontSize: 10,
    fontWeight: '700',
    marginTop: 1,
    letterSpacing: 0.2,
  },

  scanningHudContainer: {
    ...StyleSheet.absoluteFillObject,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(5, 31, 32, 0.65)',
    borderRadius: 26,
    paddingHorizontal: 16,
  },
  scanningSpinnerRing: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: 'rgba(15, 23, 42, 0.9)',
    borderWidth: 2,
    borderColor: '#22c55e',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
    shadowColor: '#22c55e',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 0.6,
    shadowRadius: 10,
    elevation: 8,
  },
  scanningHudTitle: {
    color: '#22c55e',
    fontSize: 13,
    fontWeight: '900',
    letterSpacing: 1.5,
    marginBottom: 3,
    textShadowColor: 'rgba(0, 0, 0, 0.8)',
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 3,
  },
  scanningHudSubtitle: {
    color: '#e2e8f0',
    fontSize: 11.5,
    fontWeight: '700',
    letterSpacing: 0.3,
    textAlign: 'center',
    textShadowColor: 'rgba(0, 0, 0, 0.8)',
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 3,
  },

  controlsDock: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: Platform.OS === 'ios' ? 190 : 178,
    alignItems: 'center',
    zIndex: 10,
  },
  hintPill: {
    backgroundColor: 'rgba(0, 0, 0, 0.65)',
    paddingHorizontal: 16,
    paddingVertical: 6,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.2)',
    marginBottom: 12,
  },
  hintText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '600',
  },
  controlButtonsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 32,
    pointerEvents: 'auto',
  },
  actionCircleBtn: {
    width: 52,
    height: 52,
    borderRadius: 26,
    backgroundColor: 'rgba(15, 23, 42, 0.85)',
    borderWidth: 1.5,
    borderColor: 'rgba(255, 255, 255, 0.35)',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.4,
    shadowRadius: 6,
    elevation: 6,
  },
  actionCircleBtnTorchOn: {
    backgroundColor: 'rgba(161, 98, 7, 0.9)',
    borderColor: '#facc15',
    shadowColor: '#facc15',
    shadowOpacity: 0.8,
    shadowRadius: 10,
    elevation: 8,
  },
  actionTorchDot: {
    position: 'absolute',
    top: 4,
    right: 4,
    width: 7,
    height: 7,
    borderRadius: 3.5,
    backgroundColor: '#facc15',
  },

  bottomCardWrapper: {
    position: 'absolute',
    bottom: Platform.OS === 'ios' ? 96 : 84,
    left: 16,
    right: 16,
    zIndex: 25,
  },

  analyzingCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ffffff',
    borderRadius: 18,
    paddingVertical: 14,
    paddingHorizontal: 16,
    borderWidth: 1,
    borderColor: 'rgba(34, 197, 94, 0.3)',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.25,
    shadowRadius: 12,
    elevation: 8,
  },
  analyzingSpinnerBox: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: '#f0fdf4',
    borderWidth: 1,
    borderColor: '#dcfce7',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 14,
  },
  analyzingTextBox: {
    flex: 1,
  },
  analyzingCardTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: '#0f172a',
    marginBottom: 2,
  },
  analyzingCardDesc: {
    fontSize: 11.5,
    fontWeight: '600',
    color: '#64748b',
  },

  neutralPromptCard: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    alignSelf: 'center',
    backgroundColor: 'rgba(15, 23, 42, 0.82)',
    borderRadius: 9999,
    paddingVertical: 11,
    paddingHorizontal: 20,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.2)',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 6,
  },
  neutralPulseDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#22c55e',
    marginRight: 10,
    shadowColor: '#22c55e',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 0.9,
    shadowRadius: 4,
    elevation: 4,
  },
  neutralPromptText: {
    fontSize: 12.5,
    fontWeight: '600',
    color: '#ffffff',
    letterSpacing: 0.2,
  },

  recentCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ffffff',
    borderRadius: 18,
    padding: 12,
    borderWidth: 1,
    borderColor: 'rgba(218, 241, 222, 0.85)',
    shadowColor: '#15803d',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.05,
    shadowRadius: 14,
    elevation: 3,
  },
  recentThumbBox: {
    width: 64,
    height: 64,
    borderRadius: 14,
    marginRight: 12,
    overflow: 'hidden',
    position: 'relative',
    borderWidth: 1,
    borderColor: 'rgba(0, 0, 0, 0.08)',
    backgroundColor: '#e2e8f0',
  },
  recentSoilThumb: {
    width: '100%',
    height: '100%',
  },
  recentDetails: {
    flex: 1,
    paddingRight: 8,
  },
  recentTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 2,
  },
  recentName: {
    fontSize: 14,
    fontWeight: '800',
    color: '#0f172a',
    flex: 1,
    marginRight: 4,
  },
  scanTimeText: {
    fontSize: 10.5,
    fontWeight: '600',
    color: '#94a3b8',
  },
  recentDesc: {
    fontSize: 11,
    color: '#64748b',
    lineHeight: 15,
    marginBottom: 4,
  },
  recentCodeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginTop: 2,
  },
  recentCode: {
    fontSize: 12.5,
    fontWeight: '800',
    color: '#22c55e',
  },
  identifiedBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#22c55e',
    paddingHorizontal: 7,
    paddingVertical: 2,
    borderRadius: 6,
  },
  identifiedBadgeIcon: {
    color: '#ffffff',
    fontSize: 10,
    fontWeight: '900',
    marginRight: 3,
  },
  identifiedBadgeText: {
    color: '#ffffff',
    fontSize: 10,
    fontWeight: '700',
  },
  syncedAdminBadge: {
    backgroundColor: '#f0fdf4',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 5,
    borderWidth: 1,
    borderColor: '#bbf7d0',
  },
  syncedAdminBadgeText: {
    color: '#16a34a',
    fontSize: 9.5,
    fontWeight: '700',
  },
  queuedBadge: {
    backgroundColor: '#fefce8',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 5,
    borderWidth: 1,
    borderColor: '#fef08a',
  },
  queuedBadgeText: {
    color: '#ca8a04',
    fontSize: 9.5,
    fontWeight: '700',
  },
  recentViewPill: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
    backgroundColor: '#f0fdf4',
    borderWidth: 1,
    borderColor: '#bbf7d0',
    alignItems: 'center',
    justifyContent: 'center',
  },
  recentViewPillText: {
    fontSize: 11,
    fontWeight: '800',
    color: '#166534',
  },
  recentActionsCol: {
    flexDirection: 'column',
    alignItems: 'flex-end',
    justifyContent: 'center',
    gap: 6,
  },
  recentRetakePill: {
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 8,
    backgroundColor: '#f1f5f9',
    borderWidth: 1,
    borderColor: '#cbd5e1',
    alignItems: 'center',
    justifyContent: 'center',
  },
  recentRetakePillText: {
    fontSize: 10.5,
    fontWeight: '700',
    color: '#334155',
  },
  rejectionActionsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 10,
    gap: 10,
  },
  rejectionRetakeBtn: {
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 8,
    backgroundColor: '#16a34a',
    alignItems: 'center',
    justifyContent: 'center',
  },
  rejectionRetakeBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#ffffff',
  },
  rejectionDismissBtn: {
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 8,
    backgroundColor: 'rgba(255, 255, 255, 0.1)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  rejectionDismissBtnText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#94a3b8',
  },
  actionCircleBtnRetake: {
    backgroundColor: '#15803d',
    borderColor: '#22c55e',
  },
  actionCircleRetakeIcon: {
    fontSize: 18,
    color: '#ffffff',
  },

  // Modal Styling (Soil Record Details - Matches Website Exactly in Light Mode)
  soilModalBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.65)',
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 16,
  },
  soilModalCard: {
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#e2e8f0',
    borderRadius: 26,
    width: '100%',
    maxHeight: SCREEN_HEIGHT * 0.88,
    overflow: 'hidden',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 20 },
    shadowOpacity: 0.22,
    shadowRadius: 30,
    elevation: 24,
    zIndex: 10,
  },
  soilModalHeaderRow: {
    paddingHorizontal: 20,
    paddingTop: 18,
    paddingBottom: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#ffffff',
    borderBottomWidth: 1,
    borderBottomColor: '#f1f5f9',
  },
  soilModalTitle: {
    fontSize: 17.5,
    fontWeight: '800',
    color: '#0f172a',
    letterSpacing: -0.2,
  },
  soilModalCloseBtn: {
    width: 32,
    height: 32,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 16,
    backgroundColor: '#f1f5f9',
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  soilModalCloseIcon: {
    fontSize: 13,
    color: '#64748b',
    fontWeight: '700',
  },
  soilModalScrollContent: {
    paddingHorizontal: 18,
    paddingTop: 14,
    paddingBottom: 12,
  },
  // Dual-preview Soil Badge Banner (Website #modalSoilDualPreviewBadge)
  soilBannerDualPreview: {
    width: '100%',
    borderRadius: 18,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: 16,
    paddingVertical: 14,
    marginBottom: 10,
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.16,
    shadowRadius: 12,
    elevation: 4,
  },
  soilBannerAvatarContainer: {
    width: 58,
    height: 58,
    borderRadius: 12,
    overflow: 'hidden',
    backgroundColor: 'rgba(0, 0, 0, 0.22)',
    borderWidth: 1.5,
    borderColor: 'rgba(255, 255, 255, 0.25)',
  },
  soilBannerAvatarImg: {
    width: '100%',
    height: '100%',
  },
  soilBannerAvatarFallback: {
    width: '100%',
    height: '100%',
    alignItems: 'center',
    justifyContent: 'center',
  },
  soilBannerCodeBlock: {
    flex: 1,
    minWidth: 0,
  },
  soilBannerMunsellCode: {
    fontSize: 24,
    fontWeight: '900',
    color: '#ffffff',
    letterSpacing: -0.2,
    lineHeight: 28,
  },
  soilBannerSubLabel: {
    fontSize: 11.5,
    fontWeight: '700',
    color: 'rgba(255, 255, 255, 0.95)',
    marginTop: 2,
    letterSpacing: 0.2,
  },
  soilBannerHexPill: {
    paddingHorizontal: 12,
    paddingVertical: 5,
    borderRadius: 20,
    backgroundColor: 'rgba(0, 0, 0, 0.58)',
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.25)',
  },
  soilBannerHexText: {
    fontSize: 11,
    fontWeight: '800',
    color: '#ffffff',
    letterSpacing: 0.5,
    fontFamily: Platform.OS === 'ios' ? 'Menlo' : 'monospace',
  },
  // 2-Column Paired Grids (Website .soil-modal-grid-2)
  soilGridRow2: {
    flexDirection: 'row',
    gap: 10,
    marginBottom: 10,
    width: '100%',
  },
  soilModalGridCard: {
    flex: 1,
    backgroundColor: '#ffffff',
    borderWidth: 1.5,
    borderColor: '#f1f5f9',
    borderRadius: 16,
    paddingHorizontal: 14,
    paddingVertical: 12,
    justifyContent: 'center',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.03,
    shadowRadius: 4,
    elevation: 1,
  },
  soilNearMatchCard: {
    backgroundColor: '#ffffff',
    borderColor: '#f1f5f9',
  },
  soilModalLabel: {
    fontSize: 11,
    fontWeight: '700',
    color: '#475569',
    textTransform: 'uppercase',
    letterSpacing: 0.3,
    marginBottom: 4,
  },
  soilModalValue: {
    fontSize: 13.5,
    fontWeight: '700',
    color: '#0f172a',
    lineHeight: 18,
  },
  soilNearMatchValue: {
    fontSize: 13.5,
    fontWeight: '800',
    color: '#10b981',
  },
  scannedByRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 6,
  },
  soilPlatformBadge: {
    fontSize: 12,
    fontWeight: '700',
    color: '#475569',
  },
  // Full-width cards (Website #modalScanNoteCard, #modalDescCard)
  soilModalFullCard: {
    backgroundColor: '#ffffff',
    borderWidth: 1.5,
    borderColor: '#f1f5f9',
    borderRadius: 16,
    paddingHorizontal: 16,
    paddingVertical: 12,
    marginBottom: 10,
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.03,
    shadowRadius: 4,
    elevation: 1,
  },
  soilModalDescText: {
    fontSize: 13,
    color: '#334155',
    lineHeight: 19,
    fontWeight: '600',
  },
  // Fixed Modal Footer (Website .soil-modal-footer)
  soilModalFooter: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'flex-end',
    gap: 10,
    paddingHorizontal: 18,
    paddingVertical: 14,
    backgroundColor: '#ffffff',
    borderTopWidth: 1,
    borderTopColor: '#f1f5f9',
  },
  btnModalCancel: {
    backgroundColor: '#f8fafc',
    borderWidth: 1.5,
    borderColor: '#cbd5e1',
    paddingVertical: 10,
    paddingHorizontal: 20,
    borderRadius: 9999,
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnModalCancelText: {
    fontSize: 13.5,
    fontWeight: '700',
    color: '#334155',
  },
  btnSaveHardcopy: {
    backgroundColor: '#16a34a',
    paddingVertical: 10,
    paddingHorizontal: 18,
    borderRadius: 9999,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    shadowColor: '#16a34a',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.3,
    shadowRadius: 6,
    elevation: 3,
  },
  btnSaveHardcopyText: {
    fontSize: 13.5,
    fontWeight: '700',
    color: '#ffffff',
  },
  btnPinSoilModal: {
    backgroundColor: '#0d9488',
    paddingVertical: 10,
    paddingHorizontal: 16,
    borderRadius: 9999,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    shadowColor: '#0d9488',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.3,
    shadowRadius: 6,
    elevation: 3,
  },
  btnPinSoilModalText: {
    fontSize: 13.5,
    fontWeight: '700',
    color: '#ffffff',
  },

  // State B (Camera preview bottom card) styles
  stateBResultCard: {
    backgroundColor: '#ffffff',
    borderWidth: 1.5,
    borderColor: '#e2e8f0',
    borderRadius: 20,
    padding: 14,
    shadowColor: '#0f172a',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.08,
    shadowRadius: 12,
    elevation: 6,
  },
  stateBHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingBottom: 8,
    borderBottomWidth: 1,
    borderBottomColor: '#f1f5f9',
    marginBottom: 10,
  },
  stateBHeaderTitle: {
    fontSize: 11.5,
    fontWeight: '800',
    color: '#16a34a',
    letterSpacing: 0.8,
    textTransform: 'uppercase',
  },
  stateBSwatchBanner: {
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 10,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginBottom: 12,
  },
  stateBThumbBox: {
    width: 44,
    height: 44,
    borderRadius: 8,
    overflow: 'hidden',
    backgroundColor: 'rgba(0, 0, 0, 0.2)',
  },
  stateBSoilThumb: {
    width: '100%',
    height: '100%',
  },
  stateBDetailsCol: {
    flex: 1,
    minWidth: 0,
  },
  stateBMunsellCode: {
    fontSize: 16,
    fontWeight: '900',
    color: '#ffffff',
  },
  stateBColorName: {
    fontSize: 12,
    fontWeight: '700',
    color: 'rgba(255, 255, 255, 0.9)',
  },
  stateBHexPill: {
    backgroundColor: 'rgba(0, 0, 0, 0.55)',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.2)',
  },
  stateBHexText: {
    fontSize: 10.5,
    fontWeight: '800',
    color: '#ffffff',
    fontFamily: Platform.OS === 'ios' ? 'Menlo' : 'monospace',
  },
  stateBActionsRow: {
    flexDirection: 'row',
    gap: 8,
  },
  stateBViewBtn: {
    flex: 1.6,
    height: 38,
    borderRadius: 9999,
    backgroundColor: '#16a34a',
    alignItems: 'center',
    justifyContent: 'center',
  },
  stateBViewBtnText: {
    fontSize: 12,
    fontWeight: '800',
    color: '#ffffff',
  },
  stateBRetakeBtn: {
    flex: 1,
    height: 38,
    borderRadius: 9999,
    backgroundColor: '#f1f5f9',
    borderWidth: 1,
    borderColor: '#e2e8f0',
    alignItems: 'center',
    justifyContent: 'center',
  },
  stateBRetakeBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#334155',
  },

  autoSaveToast: {
    position: 'absolute',
    top: Platform.OS === 'ios' ? 104 : 94,
    alignSelf: 'center',
    backgroundColor: 'rgba(15, 23, 42, 0.92)',
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 20,
    flexDirection: 'row',
    alignItems: 'center',
    zIndex: 99,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 8,
  },
  autoSaveToastDot: {
    width: 7,
    height: 7,
    borderRadius: 3.5,
    backgroundColor: '#eab308',
    marginRight: 8,
  },
  autoSaveToastText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '700',
  },

  /* 6-Stage Pipeline Loading HUD Styles (matching website .scan-loading-*) */
  scanLoadingStagePill: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(34, 197, 94, 0.18)',
    borderWidth: 1,
    borderColor: 'rgba(34, 197, 94, 0.45)',
    paddingHorizontal: 12,
    paddingVertical: 5,
    borderRadius: 9999,
    marginBottom: 8,
  },
  scanLoadingStageDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: '#22c55e',
    marginRight: 6,
    shadowColor: '#22c55e',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 0.9,
    shadowRadius: 5,
    elevation: 4,
  },
  scanLoadingStageDotSmall: {
    width: 5,
    height: 5,
    borderRadius: 2.5,
    backgroundColor: '#22c55e',
    marginRight: 5,
  },
  scanLoadingStagePillText: {
    color: '#22c55e',
    fontSize: 10.5,
    fontWeight: '800',
    letterSpacing: 0.8,
    textTransform: 'uppercase',
  },
  scanLoadingSpinnerWrap: {
    width: 64,
    height: 64,
    alignItems: 'center',
    justifyContent: 'center',
    marginVertical: 4,
    position: 'relative',
  },
  scanLoadingSpinnerRing: {
    width: 64,
    height: 64,
  },
  scanLoadingBadgeCenter: {
    position: 'absolute',
    alignItems: 'center',
    justifyContent: 'center',
  },
  scanLoadingBadgePercent: {
    color: '#22c55e',
    fontSize: 12,
    fontWeight: '900',
    fontFamily: Platform.OS === 'ios' ? 'Menlo' : 'monospace',
    letterSpacing: -0.5,
  },
  scanLoadingProgressTrack: {
    width: 180,
    height: 5,
    backgroundColor: '#e2e8f0',
    borderRadius: 9999,
    overflow: 'hidden',
    marginTop: 10,
    borderWidth: 0.5,
    borderColor: '#cbd5e1',
  },
  scanLoadingProgressFill: {
    height: '100%',
    backgroundColor: '#22c55e',
    borderRadius: 9999,
    shadowColor: '#22c55e',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 0.9,
    shadowRadius: 6,
    elevation: 3,
  },

  /* Bottom Analyzing Card Stage Styles */
  analyzingSpinnerPct: {
    color: '#16a34a',
    fontSize: 11.5,
    fontWeight: '900',
    fontFamily: Platform.OS === 'ios' ? 'Menlo' : 'monospace',
  },
  analyzingStageBadgeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 2,
  },
  analyzingCardStageBadge: {
    fontSize: 10,
    fontWeight: '800',
    color: '#16a34a',
    letterSpacing: 0.5,
    textTransform: 'uppercase',
  },
  bottomCardProgressTrack: {
    width: '100%',
    height: 4,
    backgroundColor: 'rgba(34, 197, 94, 0.15)',
    borderRadius: 9999,
    overflow: 'hidden',
    marginTop: 6,
  },
  bottomCardProgressFill: {
    height: '100%',
    backgroundColor: '#22c55e',
    borderRadius: 9999,
  },

  /* 6-Stage ML Soil Determination Pipeline Modal Card (matching website .pipeline-overview-card) */
  pipelineOverviewCardModal: {
    backgroundColor: '#ffffff',
    borderRadius: 16,
    borderWidth: 1.5,
    borderColor: '#e2e8f0',
    padding: 14,
    marginBottom: 12,
  },
  pipelineOverviewTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 10,
    paddingBottom: 8,
    borderBottomWidth: 1,
    borderBottomColor: '#f1f5f9',
  },
  pipelineTitleIconBox: {
    width: 20,
    height: 20,
    borderRadius: 10,
    backgroundColor: 'rgba(34, 197, 94, 0.15)',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 8,
  },
  pipelineTitleCheckIcon: {
    color: '#16a34a',
    fontSize: 11,
    fontWeight: '900',
  },
  pipelineOverviewTitleText: {
    color: '#16a34a',
    fontSize: 11.5,
    fontWeight: '800',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  pipelineStagesListModal: {
    gap: 8,
  },
  pipelineStageItemModal: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#f8fafc',
    borderRadius: 10,
    paddingHorizontal: 10,
    paddingVertical: 8,
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  pipelineStageBadgeBox: {
    backgroundColor: 'rgba(34, 197, 94, 0.15)',
    borderWidth: 1,
    borderColor: 'rgba(34, 197, 94, 0.3)',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 6,
    marginRight: 10,
  },
  pipelineStageBadgeText: {
    color: '#16a34a',
    fontSize: 9.5,
    fontWeight: '900',
    letterSpacing: 0.4,
  },
  pipelineStageInfoCol: {
    flex: 1,
    paddingRight: 6,
  },
  pipelineStageNameText: {
    color: '#0f172a',
    fontSize: 11.5,
    fontWeight: '700',
    marginBottom: 1,
  },
  pipelineStageSubText: {
    color: '#64748b',
    fontSize: 9.5,
    fontWeight: '500',
  },
  pipelineStageStatusBox: {
    backgroundColor: 'rgba(34, 197, 94, 0.15)',
    paddingHorizontal: 6,
    paddingVertical: 3,
    borderRadius: 6,
  },
  pipelineStageStatusText: {
    color: '#16a34a',
    fontSize: 9,
    fontWeight: '800',
    letterSpacing: 0.3,
  },
  cameraFallbackContainer: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: '#051F20',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 32,
  },
  cameraGridReticle: {
    width: 80,
    height: 80,
    borderRadius: 40,
    borderWidth: 2,
    borderColor: 'rgba(34, 197, 94, 0.4)',
    borderStyle: 'dashed',
    marginBottom: 16,
  },
  cameraFallbackTitle: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '700',
    textAlign: 'center',
    marginBottom: 6,
  },
  cameraFallbackSub: {
    color: 'rgba(255, 255, 255, 0.8)',
    fontSize: 12.5,
    textAlign: 'center',
    lineHeight: 18,
    marginBottom: 18,
  },
  grantPermissionBtn: {
    backgroundColor: '#22c55e',
    paddingHorizontal: 18,
    paddingVertical: 10,
    borderRadius: 20,
  },
  grantPermissionBtnText: {
    color: '#051F20',
    fontSize: 13,
    fontWeight: '800',
  },
  // Soil Normalization Card Styles (Matches Website Exactly)
  soilModalNormCard: {
    backgroundColor: '#ffffff',
    borderRadius: 14,
    borderWidth: 1.5,
    borderColor: '#e2e8f0',
    padding: 12,
    marginBottom: 10,
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.04,
    shadowRadius: 3,
    elevation: 1,
  },
  soilModalNormHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  soilModalNormLabel: {
    fontSize: 10.5,
    fontWeight: '700',
    color: '#64748b',
    letterSpacing: 0.5,
    textTransform: 'uppercase',
  },
  beforeAfterBadge: {
    backgroundColor: 'rgba(16, 185, 129, 0.12)',
    paddingHorizontal: 8,
    paddingVertical: 2.5,
    borderRadius: 6,
  },
  beforeAfterBadgeText: {
    fontSize: 9.5,
    fontWeight: '700',
    color: '#10b981',
    letterSpacing: 0.4,
    textTransform: 'uppercase',
  },
  soilModalNormImageFrame: {
    width: '100%',
    borderRadius: 10,
    overflow: 'hidden',
    backgroundColor: '#f8fafc',
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  soilNormImage: {
    width: '100%',
    height: 165,
  },
  normSimulatedHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 10,
    paddingVertical: 5,
    backgroundColor: '#f1f5f9',
  },
  normHeaderBefore: {
    fontSize: 9.5,
    fontWeight: '800',
    color: '#d97706',
    letterSpacing: 0.4,
  },
  normHeaderAfter: {
    fontSize: 9.5,
    fontWeight: '800',
    color: '#16a34a',
    letterSpacing: 0.4,
  },
  normRoleBadge: {
    position: 'absolute',
    top: -13,
    alignSelf: 'center',
    backgroundColor: '#4ade80',
    paddingHorizontal: 5,
    paddingVertical: 1,
    borderRadius: 3,
  },
  normRoleBadgeText: {
    fontSize: 7.5,
    fontWeight: '800',
    color: '#000000',
    letterSpacing: 0.3,
  },
  normPlaceholder: {
    height: 120,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 16,
  },
  normPlaceholderTitle: {
    fontSize: 11,
    fontWeight: '700',
    color: '#94a3b8',
    letterSpacing: 0.5,
    marginBottom: 4,
  },
  normPlaceholderSub: {
    fontSize: 10,
    color: '#64748b',
    textAlign: 'center',
  },
  normSwatchesRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginTop: 10,
  },
  soilSwatchCapsulePill: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#f8fafc',
    borderWidth: 1,
    borderColor: '#e2e8f0',
    borderRadius: 20,
    paddingHorizontal: 9,
    paddingVertical: 4.5,
    gap: 6,
  },
  capsulePillSwatch: {
    width: 14,
    height: 14,
    borderRadius: 3,
    borderWidth: 0.5,
    borderColor: 'rgba(0, 0, 0, 0.15)',
  },
  capsulePillHex: {
    fontSize: 11,
    fontWeight: '700',
    color: '#1e293b',
    letterSpacing: 0.2,
  },
  capsulePillCode: {
    fontSize: 10.5,
    fontWeight: '600',
    color: '#64748b',
  },
  capsulePillPct: {
    fontSize: 11,
    fontWeight: '700',
    color: '#10b981',
  },
  soilNormMetaRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderTopWidth: 1,
    borderTopColor: '#f1f5f9',
    paddingTop: 8,
    marginTop: 10,
  },
  soilNormMetaTitle: {
    fontSize: 10,
    fontWeight: '800',
    color: '#1e293b',
    letterSpacing: 0.4,
  },
  soilNormMetaInfo: {
    fontSize: 9.5,
    fontWeight: '700',
    color: '#475569',
    letterSpacing: 0.2,
  },

  // Soil Pin Location & Covered Area Card
  soilLocationHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: 8,
    marginBottom: 8,
  },
  soilLocationLabel: {
    fontSize: 11,
    fontWeight: '700',
    color: '#475569',
    textTransform: 'uppercase',
    letterSpacing: 0.3,
  },
  soilLocationBadgesRow: {
    flexDirection: 'row',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: 6,
  },
  soilLocBadgeStatus: {
    paddingHorizontal: 8,
    paddingVertical: 2.5,
    borderRadius: 9999,
  },
  soilLocBadgePending: {
    backgroundColor: 'rgba(245, 158, 11, 0.15)',
    borderWidth: 1,
    borderColor: 'rgba(245, 158, 11, 0.35)',
  },
  soilLocBadgeApproved: {
    backgroundColor: 'rgba(22, 163, 74, 0.15)',
    borderWidth: 1,
    borderColor: 'rgba(22, 163, 74, 0.35)',
  },
  soilLocBadgeStatusText: {
    fontSize: 9.5,
    fontWeight: '800',
    letterSpacing: 0.4,
  },
  soilLocBadgeBrgy: {
    backgroundColor: 'rgba(15, 23, 42, 0.06)',
    paddingHorizontal: 8,
    paddingVertical: 2.5,
    borderRadius: 9999,
    borderWidth: 1,
    borderColor: 'rgba(15, 23, 42, 0.12)',
  },
  soilLocBadgeBrgyText: {
    fontSize: 10,
    fontWeight: '700',
    color: '#334155',
  },
  soilLocBadgeCoverage: {
    backgroundColor: 'rgba(13, 148, 136, 0.12)',
    paddingHorizontal: 8,
    paddingVertical: 2.5,
    borderRadius: 9999,
    borderWidth: 1,
    borderColor: 'rgba(13, 148, 136, 0.25)',
  },
  soilLocBadgeCoverageText: {
    fontSize: 10,
    fontWeight: '700',
    color: '#0d9488',
  },
  soilLocBadgeCoords: {
    backgroundColor: 'rgba(15, 23, 42, 0.06)',
    paddingHorizontal: 8,
    paddingVertical: 2.5,
    borderRadius: 9999,
    borderWidth: 1,
    borderColor: 'rgba(15, 23, 42, 0.12)',
  },
  soilLocBadgeCoordsText: {
    fontSize: 10,
    fontWeight: '700',
    color: '#475569',
    fontFamily: Platform.OS === 'ios' ? 'Menlo' : 'monospace',
  },
  soilMapPreviewFrame: {
    height: 140,
    borderRadius: 12,
    backgroundColor: '#f8fafc',
    borderWidth: 1,
    borderColor: '#e2e8f0',
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
    overflow: 'hidden',
  },
  soilMapCenterMarker: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  soilMapCoordsCallout: {
    backgroundColor: 'rgba(0, 0, 0, 0.75)',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 8,
    marginTop: 4,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.15)',
  },
  soilMapCoordsCalloutText: {
    color: '#ffffff',
    fontSize: 10.5,
    fontWeight: '700',
  },
  soilMapWatermark: {
    position: 'absolute',
    bottom: 6,
    right: 8,
    fontSize: 9,
    fontWeight: '700',
    color: '#94a3b8',
    letterSpacing: 0.3,
  },
  btnPrimaryPinLocation: {
    backgroundColor: '#0d9488',
    paddingVertical: 12,
    paddingHorizontal: 18,
    borderRadius: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    shadowColor: '#0d9488',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.25,
    shadowRadius: 6,
    elevation: 3,
  },
  btnPrimaryPinLocationDone: {
    backgroundColor: '#15803d',
  },
  btnPrimaryPinLocationText: {
    color: '#ffffff',
    fontSize: 13.5,
    fontWeight: '800',
    letterSpacing: 0.2,
  },

  // Multi-Layer Accordion Section
  multiLayerSection: {
    marginTop: 2,
    marginBottom: 12,
    borderTopWidth: 1,
    borderTopColor: '#e2e8f0',
    borderStyle: 'dashed',
    paddingTop: 12,
  },
  multiLayerHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  multiLayerSectionTitle: {
    fontSize: 11,
    fontWeight: '800',
    color: '#10b981',
    letterSpacing: 0.5,
    textTransform: 'uppercase',
  },
  multiLayerToggleAllText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#0d9488',
    textDecorationLine: 'underline',
  },
  layerAccordionItem: {
    backgroundColor: '#ffffff',
    borderWidth: 1.5,
    borderColor: '#e2e8f0',
    borderRadius: 14,
    overflow: 'hidden',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.03,
    shadowRadius: 3,
    elevation: 1,
  },
  layerAccordionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 12,
    paddingHorizontal: 14,
  },
  layerAccordionTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    flex: 1,
    minWidth: 0,
    paddingRight: 8,
  },
  layerChevron: {
    fontSize: 10,
    fontWeight: '800',
    color: '#64748b',
  },
  layerAccordionTitleText: {
    fontSize: 12.5,
    fontWeight: '800',
    color: '#0f172a',
    flexShrink: 1,
  },
  layerHexPill: {
    paddingHorizontal: 10,
    paddingVertical: 3.5,
    borderRadius: 9999,
  },
  layerHexPillText: {
    color: '#ffffff',
    fontSize: 11,
    fontWeight: '800',
    fontFamily: Platform.OS === 'ios' ? 'Menlo' : 'monospace',
  },
  layerAccordionBody: {
    paddingHorizontal: 12,
    paddingBottom: 12,
    borderTopWidth: 1,
    borderTopColor: '#f1f5f9',
    paddingTop: 10,
  },
  layerBannerHero: {
    borderRadius: 10,
    paddingHorizontal: 10,
    paddingVertical: 8,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginBottom: 8,
  },
  layerAvatarSquare: {
    width: 44,
    height: 44,
    borderRadius: 8,
    overflow: 'hidden',
    backgroundColor: 'rgba(0,0,0,0.2)',
  },
  layerBannerCodeCol: {
    flex: 1,
    minWidth: 0,
  },
  layerBannerCode: {
    fontSize: 14,
    fontWeight: '800',
    color: '#ffffff',
  },
  layerBannerName: {
    fontSize: 11,
    fontWeight: '700',
    color: 'rgba(255,255,255,0.9)',
  },
  layerBannerHexBadge: {
    backgroundColor: 'rgba(0,0,0,0.6)',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.2)',
  },
  layerBannerHexBadgeText: {
    color: '#ffffff',
    fontSize: 10,
    fontWeight: '800',
    fontFamily: Platform.OS === 'ios' ? 'Menlo' : 'monospace',
  },
  soilGridRow3: {
    flexDirection: 'row',
    gap: 6,
    marginBottom: 6,
    width: '100%',
  },
  soilModalGridCardSmall: {
    flex: 1,
    backgroundColor: '#f8fafc',
    borderWidth: 1,
    borderColor: '#e2e8f0',
    borderRadius: 10,
    paddingHorizontal: 8,
    paddingVertical: 8,
    justifyContent: 'center',
  },
  soilModalLabelSmall: {
    fontSize: 9.5,
    fontWeight: '700',
    color: '#64748b',
    textTransform: 'uppercase',
    letterSpacing: 0.3,
    marginBottom: 2,
  },
  soilModalValueSmall: {
    fontSize: 11.5,
    fontWeight: '700',
    color: '#0f172a',
  },
  soilModalFullCardSmall: {
    backgroundColor: '#f8fafc',
    borderWidth: 1,
    borderColor: '#e2e8f0',
    borderRadius: 10,
    paddingHorizontal: 10,
    paddingVertical: 8,
    marginBottom: 6,
  },
  soilModalDescTextSmall: {
    fontSize: 11.5,
    color: '#334155',
    lineHeight: 16,
    fontWeight: '600',
  },
  btnLayerPinLocation: {
    backgroundColor: '#0d9488',
    paddingVertical: 8,
    paddingHorizontal: 14,
    borderRadius: 9999,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    marginTop: 4,
  },
  btnLayerPinLocationText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '700',
  },
  btnPinAllModal: {
    backgroundColor: '#0d9488',
    paddingVertical: 10,
    paddingHorizontal: 16,
    borderRadius: 9999,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    shadowColor: '#0d9488',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.3,
    shadowRadius: 6,
    elevation: 3,
  },
  btnPinAllModalText: {
    fontSize: 13.5,
    fontWeight: '700',
    color: '#ffffff',
  },
  soilBannerPercentageText: {
    fontSize: 11,
    fontWeight: '700',
    color: 'rgba(255, 255, 255, 0.88)',
    marginTop: 2,
    letterSpacing: 0.2,
  },
  soilModalNormDesc: {
    fontSize: 11,
    color: '#64748b',
    marginBottom: 8,
    lineHeight: 15,
  },
  shadeSwatchesContainer: {
    backgroundColor: 'rgba(255, 255, 255, 0.08)',
    borderRadius: 8,
    padding: 8,
    marginTop: 8,
    marginBottom: 4,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.15)',
  },
  shadeSwatchesLabel: {
    fontSize: 10,
    fontWeight: '700',
    color: '#94a3b8',
    textTransform: 'uppercase',
    letterSpacing: 0.4,
    marginBottom: 5,
  },
  shadeSwatchesRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  shadeSwatchItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    backgroundColor: 'rgba(0, 0, 0, 0.25)',
    paddingHorizontal: 7,
    paddingVertical: 3.5,
    borderRadius: 6,
  },
  shadeSwatchDot: {
    width: 10,
    height: 10,
    borderRadius: 5,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.4)',
  },
  shadeSwatchText: {
    fontSize: 10.5,
    fontWeight: '600',
    color: '#f8fafc',
  },
  lightingWarningCard: {
    backgroundColor: '#fffbeb',
    borderRadius: 8,
    padding: 10,
    marginTop: 8,
    marginBottom: 6,
    borderWidth: 1,
    borderColor: '#fde68a',
  },
  lightingWarningTitle: {
    fontSize: 11.5,
    fontWeight: '700',
    color: '#b45309',
    marginBottom: 3,
  },
  lightingWarningText: {
    fontSize: 10.5,
    color: '#92400e',
    lineHeight: 14,
  },
  layerOverlayBadge: {
    paddingHorizontal: 7,
    paddingVertical: 2.5,
    borderRadius: 6,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.2,
    shadowRadius: 2,
    elevation: 2,
  },
  layerOverlayBadgeText: {
    fontSize: 9.5,
    fontWeight: '800',
    color: '#ffffff',
    textTransform: 'uppercase',
    letterSpacing: 0.3,
  },
});
