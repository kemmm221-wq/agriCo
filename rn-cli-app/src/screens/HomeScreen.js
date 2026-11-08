import React, { useState, useEffect, useContext, useMemo, useCallback, useRef } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  Pressable,
  StyleSheet,
  StatusBar,
  ScrollView,
  Dimensions,
  Alert,
  Modal,
  Image,
  ActivityIndicator,
  RefreshControl,
  AppState,
  Platform,
  BackHandler,
  ToastAndroid,
  Share,
} from 'react-native';
import NetInfo from '@react-native-community/netinfo';
import { WebView } from 'react-native-webview';
import RNFS from 'react-native-fs';

import OFFLINE_MUNSELL_COLORS from '../data/munsellColors.json';
import OFFLINE_SOIL_CARDS from '../data/offlineSoils.json';
import { getLocalScans, mergeServerScans } from '../services/localScanStore';
import UpdateModal from '../components/UpdateModal';
import { checkForAppUpdate } from '../services/updateService';
import { AuthContext } from '../context/AuthContext';
import BottomNavBar from '../components/BottomNavBar';
import api, { getActiveBaseUrl } from '../services/api';
import { ENV } from '../config/env';
import {
  BellIcon,
  SearchIcon,
  LocationTargetIcon,
  FilterSlidersIcon,
  WifiOffIcon,
} from '../components/AppIcons';
import ProfileView from '../components/ProfileView';

const { width, height: SCREEN_HEIGHT } = Dimensions.get('window');

// 6 Official Munsell Soil Hues in AgriCo (+ Recommend)
const HUE_CATEGORIES = ['Recommend', '10YR', '7.5YR', '5YR', '2.5YR', '10R', '2.5Y'];

// Leaflet HTML Builder connected to Website Application Map (Centered on Lal-lo, Cagayan, Philippines)
const buildLeafletMapHtml = (locations = [], serverBase = 'http://10.0.2.2:8080') => {
  const safeLocationsJson = JSON.stringify(locations).replace(/<\//g, '<\\/');
  return `
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="${serverBase}/js/lallo-boundary.js"></script>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body, #soilMap {
      width: 100%;
      height: 100%;
      background-color: #f1f5f9;
      overflow: hidden;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    .leaflet-control-attribution {
      font-size: 8.5px !important;
      background: rgba(255, 255, 255, 0.85) !important;
      padding: 2px 7px !important;
      border-radius: 4px;
      margin-bottom: 90px !important;
      color: #64748b !important;
    }
    .leaflet-control-zoom {
      display: none !important;
    }

    /* Agrico Custom Leaflet Popup Styling - Matching Website Exactly */
    .leaflet-popup-content-wrapper,
    .agrico-custom-popup .leaflet-popup-content-wrapper {
      background: #ffffff !important;
      color: #0f172a !important;
      border-radius: 20px !important;
      padding: 0 !important;
      overflow: hidden !important;
      box-shadow: 0 16px 36px -4px rgba(0, 0, 0, 0.16), 0 6px 12px -2px rgba(0, 0, 0, 0.08) !important;
      border: 1px solid rgba(226, 232, 240, 0.9) !important;
    }
    .leaflet-popup-tip,
    .agrico-custom-popup .leaflet-popup-tip {
      background: #ffffff !important;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.06) !important;
    }
    .leaflet-popup-content,
    .agrico-custom-popup .leaflet-popup-content {
      margin: 0 !important;
      line-height: normal !important;
      width: 260px !important;
      max-width: calc(100vw - 48px) !important;
    }
    .leaflet-popup-tip-container,
    .agrico-custom-popup .leaflet-popup-tip-container {
      margin-top: -1px;
    }
    .leaflet-container a.leaflet-popup-close-button,
    .agrico-custom-popup a.leaflet-popup-close-button,
    a.leaflet-popup-close-button {
      top: 12px !important;
      right: 12px !important;
      width: 24px !important;
      height: 24px !important;
      border-radius: 50% !important;
      background: #ffffff !important;
      color: #64748b !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      font-size: 14px !important;
      font-weight: 700 !important;
      border: 1px solid #e2e8f0 !important;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important;
      transition: all 0.15s ease !important;
      padding: 0 !important;
      z-index: 20 !important;
      text-decoration: none !important;
    }
    .leaflet-container a.leaflet-popup-close-button:hover,
    .agrico-custom-popup a.leaflet-popup-close-button:hover,
    a.leaflet-popup-close-button:hover {
      background: #f1f5f9 !important;
      color: #0f172a !important;
      border-color: #cbd5e1 !important;
    }

    /* Website Light-Mode Soil Record Details Modal */
    .soil-detail-backdrop {
      position: fixed;
      inset: 0;
      z-index: 100001;
      background: rgba(15, 23, 42, 0.45);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 30px 10px 16px;
      box-sizing: border-box;
      width: 100vw;
      max-width: 100%;
      overflow-x: hidden !important;
      animation: wsMapFadeIn 0.2s ease-out;
    }
    .soil-detail-modal-container {
      background: #ffffff;
      border: 1px solid #f1f5f9;
      border-radius: 24px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      width: 100%;
      max-width: 440px;
      margin: auto;
      display: flex;
      flex-direction: column;
      max-height: 88vh;
      overflow: hidden;
      position: relative;
      box-sizing: border-box;
    }
    .soil-detail-modal-header {
      padding: 14px 16px 12px;
      border-bottom: 1px solid #f1f5f9;
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #ffffff;
      flex-shrink: 0;
    }
    .soil-detail-modal-header h3 {
      margin: 0;
      font-size: 16px;
      font-weight: 800;
      color: #0f172a;
      letter-spacing: -0.01em;
    }
    .modal-close-circle-btn {
      background: #f1f5f9;
      border: 1px solid #e2e8f0;
      color: #64748b;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      padding: 0;
      font-size: 13px;
      font-weight: 700;
      transition: all 0.2s ease;
      flex-shrink: 0;
    }
    .modal-close-circle-btn:active {
      background: #e2e8f0;
      color: #0f172a;
    }
    .soil-detail-modal-body {
      padding: 12px 10px;
      overflow-y: auto;
      overflow-x: hidden !important;
      flex: 1 1 auto;
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
      gap: 8px;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: none !important;
      -ms-overflow-style: none !important;
    }
    .soil-detail-modal-body::-webkit-scrollbar {
      display: none !important;
      width: 0 !important;
      height: 0 !important;
    }

    /* Grids for Metric Cards */
    .soil-modal-grid-3 {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 6px;
      margin-bottom: 2px;
      width: 100%;
      box-sizing: border-box;
      flex-shrink: 0;
    }
    .soil-modal-grid-2 {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 6px;
      margin-bottom: 2px;
      width: 100%;
      box-sizing: border-box;
      flex-shrink: 0;
    }
    .soil-metric-card {
      background: #ffffff;
      border: 1.5px solid #f1f5f9;
      border-radius: 12px;
      padding: 8px 6px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.03);
      box-sizing: border-box;
      min-width: 0;
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
    }
    .soil-metric-card-label {
      font-size: 8px;
      font-weight: 800;
      color: #64748b;
      margin-bottom: 3px;
      letter-spacing: 0.02em;
      text-transform: uppercase;
      line-height: 1.15;
      word-break: break-word;
      overflow-wrap: anywhere;
    }
    .soil-metric-card-val {
      font-size: 10.5px;
      font-weight: 700;
      color: #0f172a;
      line-height: 1.25;
      word-break: break-word;
      overflow-wrap: anywhere;
    }
    .soil-metric-card-text {
      color: #0f172a;
    }
    .truncate {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .covered-area-highlight {
      color: #16a34a;
      font-weight: 800;
      white-space: nowrap;
    }

    @media (max-width: 380px) {
      .soil-detail-backdrop {
        padding: 24px 6px 12px;
      }
      .soil-modal-grid-3 {
        gap: 4px;
      }
      .soil-metric-card {
        padding: 7px 4px;
      }
      .soil-metric-card-label {
        font-size: 7.5px;
      }
      .soil-metric-card-val {
        font-size: 10px;
      }
      .soil-swatch-banner {
        padding: 10px 10px;
        gap: 8px;
      }
      .soil-swatch-avatar {
        width: 42px;
        height: 42px;
        min-width: 42px;
      }
      .soil-swatch-code {
        font-size: 18px;
      }
      .soil-swatch-hex-pill {
        font-size: 9.5px;
        padding: 4px 7px;
      }
    }

    /* Swatch Banner */
    .soil-swatch-banner {
      position: relative;
      width: 100%;
      border-radius: 16px;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 14px;
      box-sizing: border-box;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
      min-height: 64px;
      min-width: 0;
      flex-shrink: 0;
    }
    .soil-swatch-avatar {
      width: 48px;
      height: 48px;
      min-width: 48px;
      aspect-ratio: 1 / 1;
      border-radius: 10px;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      position: relative;
      background: rgba(0,0,0,0.18);
    }
    .soil-swatch-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .soil-swatch-center {
      flex: 1;
      min-width: 0;
    }
    .soil-swatch-code {
      font-size: 20px;
      font-weight: 900;
      color: #ffffff;
      letter-spacing: -0.01em;
      line-height: 1.15;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .soil-swatch-sub {
      font-size: 11px;
      font-weight: 700;
      color: rgba(255, 255, 255, 0.95);
      margin-top: 2px;
      letter-spacing: 0.02em;
    }
    .soil-swatch-hex-pill {
      font-size: 10.5px;
      font-weight: 800;
      color: #ffffff;
      background: rgba(0, 0, 0, 0.55);
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
      padding: 5px 10px;
      border-radius: 20px;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      font-family: monospace;
      border: 1px solid rgba(255, 255, 255, 0.25);
      user-select: none;
      line-height: 1;
      flex-shrink: 0;
      white-space: nowrap;
    }

    /* Accordion for Secondary Layers */
    .layer-accordion-item {
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      overflow: hidden;
      background: #ffffff;
      margin-bottom: 8px;
      flex-shrink: 0;
    }
    .layer-accordion-header {
      background: #f8fafc;
      color: #0f172a;
      width: 100%;
      padding: 11px 14px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      cursor: pointer;
      border: none;
      text-align: left;
      font-family: inherit;
      user-select: none;
    }
    .layer-accordion-header-title {
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 700;
      font-size: 13px;
      color: #0f172a;
    }
    .layer-accordion-arrow {
      font-size: 11px;
      color: #475569;
    }
    .layer-accordion-body {
      background: #ffffff;
      border-top: 1px solid #f1f5f9;
      padding: 14px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    /* Soil Normalization Before / After */
    .soil-normalization-meta-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 12px;
      padding-top: 10px;
      border-top: 1px solid #e2e8f0;
      font-size: 10.5px;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .soil-normalization-meta-title {
      color: #0f172a;
      font-weight: 800;
      letter-spacing: 0.05em;
    }
    .soil-normalization-meta-info {
      color: #475569;
      font-weight: 600;
    }
    .soil-swatch-capsule-pill {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 5px 12px;
      background: #f8fafc;
      border: 1.5px solid #cbd5e1;
      border-radius: 9999px;
      box-shadow: none;
      font-size: 11px;
      cursor: default;
      user-select: none;
    }
    .soil-swatch-capsule-pill .capsule-pill-swatch {
      width: 14px;
      height: 14px;
      border-radius: 3.5px;
      border: 1px solid rgba(0, 0, 0, 0.15);
      display: inline-block;
      flex-shrink: 0;
    }
    .soil-swatch-capsule-pill .capsule-pill-hex {
      color: #0f172a;
      font-weight: 700;
      font-family: monospace;
    }
    .soil-swatch-capsule-pill .capsule-pill-code {
      color: #475569;
      font-weight: 600;
      font-size: 11px;
    }
    .soil-swatch-capsule-pill .capsule-pill-pct {
      color: #15803d;
      font-weight: 800;
      font-size: 11.5px;
      margin-left: 2px;
    }
    .soil-platform-badge {
      font-size: 11.5px;
      font-weight: 700;
      color: #000000;
      display: inline;
    }

    /* Modal Footer */
    .soil-detail-modal-footer {
      padding: 13px 18px 15px;
      background: #ffffff;
      border-top: 1px solid #f1f5f9;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      width: 100%;
      box-sizing: border-box;
    }
    .modal-cancel-btn {
      background: #f1f5f9;
      border: 1.5px solid #cbd5e1;
      color: #1e293b;
      padding: 11px 20px;
      border-radius: 12px;
      font-weight: 800;
      font-size: 13.5px;
      cursor: pointer;
      width: 100%;
      text-align: center;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
      transition: all 0.2s ease;
    }
    .modal-cancel-btn:active {
      background: #e2e8f0;
      color: #0f172a;
    }
  </style>
</head>
<body>
  <div id="soilMap"></div>

  <!-- Website Light Color Soil Record Details Modal -->
  <div id="websiteSoilDetailModal" class="soil-detail-backdrop" onclick="if(event.target === this) closeSoilModal()">
    <div class="soil-detail-modal-container" onclick="event.stopPropagation()">
      <div class="soil-detail-modal-header">
        <h3 id="wsModalHeaderTitle">Soil Record Details</h3>
        <button type="button" class="modal-close-circle-btn" onclick="closeSoilModal()" ontouchend="closeSoilModal()">✕</button>
      </div>
      <div id="wsModalBodyContent" class="soil-detail-modal-body"></div>
      <div class="soil-detail-modal-footer">
        <button type="button" class="modal-cancel-btn" onclick="closeSoilModal()" ontouchend="closeSoilModal()">Close</button>
      </div>
    </div>
  </div>

  <script>
    var defaultLat = 18.1972;
    var defaultLng = 121.6612;
    var defaultZoom = 12;

    var map = L.map('soilMap', {
      center: [defaultLat, defaultLng],
      zoom: defaultZoom,
      zoomControl: false,
      attributionControl: true
    });

    var modalElem = document.getElementById('websiteSoilDetailModal');
    if (modalElem) {
      L.DomEvent.disableClickPropagation(modalElem);
      L.DomEvent.disableScrollPropagation(modalElem);
    }

    // 1. Standard Bright OpenStreetMap Tile Layer (Matching Website Map Exactly)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors | AgriCo GIS'
    }).addTo(map);

    var markersGroup = L.featureGroup().addTo(map);

    // Official Lal-lo municipal & barangay boundary overlay (same module as Website/Admin)
    try {
      if (window.LalloBoundary) {
        window.LalloBoundary.geoJsonUrl = '${serverBase}/geojson/lal-lo-with-barangays.geojson';
        window.LalloBoundary.render(map, {
          fitBounds: false,
          maxBounds: true,
          maxBoundsPadding: 0.08,
          minZoom: 11,
          showTooltips: true,
          bringMarkersToFront: markersGroup
        }).catch(function(err) { console.log('Lal-lo boundary note:', err); });
      }
    } catch (e) {}
    var allLocationsData = [];
    var activeMarkers = [];

    // 2. Official AgriCo 3D Glossy Marker Pins (Matching Website agrico-map-pin.png)
    var redPinUrl = '${serverBase}/images/agrico-map-pin.png';
    var grayPinUrl = '${serverBase}/images/agrico-map-pin-gray.png';

    var redIcon = L.icon({
      iconUrl: redPinUrl,
      iconSize: [38, 56],
      iconAnchor: [19, 56],
      popupAnchor: [0, -50]
    });

    var grayIcon = L.icon({
      iconUrl: grayPinUrl,
      iconSize: [38, 56],
      iconAnchor: [19, 56],
      popupAnchor: [0, -50]
    });

    // Helper: Parcel Polygon Dimensions based on Covered Area
    function getParcelDimensions(covStr) {
      var s = String(covStr || '').toLowerCase();
      if (s.includes('50 m')) return { w: 35, h: 35 };
      if (s.includes('250 m')) return { w: 55, h: 55 };
      if (s.includes('500 m')) return { w: 75, h: 75 };
      if (s.includes('1,000') || s.includes('1000')) return { w: 100, h: 100 };
      if (s.includes('2,500') || s.includes('2500')) return { w: 130, h: 130 };
      if (s.includes('5,000') || s.includes('5000')) return { w: 160, h: 160 };
      if (s.includes('hectare')) return { w: 190, h: 190 };
      return { w: 75, h: 75 };
    }

    function getRoundedBoxCoords(centerLat, centerLng, widthMeters, heightMeters) {
      var latConversion = 111320;
      var lngConversion = 111320 * Math.cos(centerLat * Math.PI / 180);
      var halfW = widthMeters / 2;
      var halfH = heightMeters / 2;
      return [
        [centerLat + (halfH / latConversion), centerLng - (halfW / lngConversion)],
        [centerLat + (halfH / latConversion), centerLng + (halfW / lngConversion)],
        [centerLat - (halfH / latConversion), centerLng + (halfW / lngConversion)],
        [centerLat - (halfH / latConversion), centerLng - (halfW / lngConversion)]
      ];
    }

    function centerMapOnPopup(lat, lng, offsetPx) {
      offsetPx = typeof offsetPx === 'number' ? offsetPx : 165;
      var latlng = L.latLng(lat, lng);
      var zoom = map.getZoom();
      var point = map.project(latlng, zoom);
      var targetPoint = point.subtract([0, offsetPx]);
      var targetLatLng = map.unproject(targetPoint, zoom);
      map.panTo(targetLatLng, { animate: true, duration: 0.4 });
    }

    // Helper: Normalize ROI bounding box for crop avatar & before/after card
    function normalizeBox(r) {
      if (!r) return { left: 25, top: 25, width: 50, height: 50, raw_x: 0.25, raw_y: 0.25, raw_w: 0.5, raw_h: 0.5 };
      var box = r.box || r.bounding_box || null;
      if (!box && (r.x !== undefined && r.y !== undefined && r.w !== undefined && r.h !== undefined)) {
        box = { x: r.x, y: r.y, w: r.w, h: r.h };
      }
      if (!box && (r.norm_x !== undefined && r.norm_y !== undefined && r.norm_w !== undefined && r.norm_h !== undefined)) {
        box = { x: r.norm_x, y: r.norm_y, w: r.norm_w, h: r.norm_h };
      }
      if (typeof box === 'string') {
        try { box = JSON.parse(box); } catch(e) { box = null; }
      }
      if (!box) {
        return { left: 25, top: 25, width: 50, height: 50, raw_x: 0.25, raw_y: 0.25, raw_w: 0.5, raw_h: 0.5 };
      }
      var bx = parseFloat(box.norm_x !== undefined ? box.norm_x : (box.x !== undefined ? box.x : 0.25));
      var by = parseFloat(box.norm_y !== undefined ? box.norm_y : (box.y !== undefined ? box.y : 0.25));
      var bw = parseFloat(box.norm_w !== undefined ? box.norm_w : (box.w !== undefined ? box.w : 0.5));
      var bh = parseFloat(box.norm_h !== undefined ? box.norm_h : (box.h !== undefined ? box.h : 0.5));

      if (bx > 1.0 || by > 1.0 || bw > 1.0 || bh > 1.0) {
        bx = bx / 400; by = by / 400; bw = bw / 400; bh = bh / 400;
      }
      bx = Math.max(0.02, Math.min(0.88, bx));
      by = Math.max(0.02, Math.min(0.88, by));
      bw = Math.max(0.06, Math.min(0.96 - bx, bw));
      bh = Math.max(0.06, Math.min(0.96 - by, bh));

      return {
        left: Math.round(bx * 10000) / 100,
        top: Math.round(by * 10000) / 100,
        width: Math.round(bw * 10000) / 100,
        height: Math.round(bh * 10000) / 100,
        raw_x: bx,
        raw_y: by,
        raw_w: bw,
        raw_h: bh
      };
    }

    // Helper: Calculate Near-Match Rating matching website
    function calcNearMatchRating(item) {
      if (!item) return '98.7%';
      var de = item.color_delta_e !== undefined ? item.color_delta_e : (item.delta_e !== undefined ? item.delta_e : item.distance);
      if (de !== undefined && de !== null && !isNaN(de) && parseFloat(de) >= 0) {
        return (Math.max(88.5, Math.min(99.6, 99.4 - (parseFloat(de) * 0.45)))).toFixed(1) + '%';
      }
      if (item.near_match && typeof item.near_match === 'string' && item.near_match !== '100%' && item.near_match !== '100.0%') {
        return item.near_match;
      }
      if (item.confidence && typeof item.confidence === 'string' && item.confidence !== '100%' && item.confidence !== '100.0%') {
        return item.confidence.indexOf('%') !== -1 ? item.confidence : item.confidence + '%';
      }
      return '87.9%';
    }

    // Toggle secondary layer accordion
    window.toggleWebsiteLayerAccordion = function(idx) {
      var body = document.getElementById('wsLayerBody_' + idx);
      var arrow = document.getElementById('wsLayerArrow_' + idx);
      if (body) {
        var isHidden = body.style.display === 'none' || !body.style.display;
        body.style.display = isHidden ? 'flex' : 'none';
        if (arrow) arrow.textContent = isHidden ? '▼' : '▶';
      }
    };

    window.closeSoilModal = function() {
      var m = document.getElementById('websiteSoilDetailModal');
      if (m && m.style.display !== 'none') {
        m.style.display = 'none';
        try {
          if (window.ReactNativeWebView) {
            window.ReactNativeWebView.postMessage(JSON.stringify({ type: 'MAP_MODAL_STATE', isOpen: false }));
          }
        } catch(e) {}
      }
    };
    window.closeWebsiteSoilDetailModal = window.closeSoilModal;

    function fixImgUrl(url) {
      if (!url) return '';
      if (url.indexOf('localhost') !== -1 || url.indexOf('127.0.0.1') !== -1) {
        try {
          var sHost = new URL('${serverBase}').host;
          url = url.replace(/localhost(:\d+)?/g, sHost).replace(/127\.0\.0\.1(:\d+)?/g, sHost);
        } catch(e) {}
      }
      if (!url.startsWith('http://') && !url.startsWith('https://') && !url.startsWith('data:')) {
        url = '${serverBase}' + (url.startsWith('/') ? '' : '/') + url;
      }
      return url;
    }

    // View Details Modal Handler (Matching Screenshots 2, 3, and 4 from Website Map)
    window.viewWebsitePinDetails = function(locId) {
      try { if (map) map.closePopup(); } catch(e) {}
      var m = allLocationsData.find(function(item) {
        return (item.location_mapping_id || item.id) == locId;
      });
      if (!m) return;

      var modal = document.getElementById('websiteSoilDetailModal');
      if (!modal) return;

      var soil = m.soil || {};
      var mColor = soil.munsell_color || {};
      var soilName = soil.soil_name || mColor.color_name || 'Soil Specimen';
      var fullCode = soil.full_code || mColor.full_code || '—';
      var hexColor = soil.hex_color || mColor.hex_code || '#8F694E';
      if (hexColor && !hexColor.startsWith('#')) hexColor = '#' + hexColor;
      var soilType = m.munsell_soil_type || soil.effective_soil_type || soil.soil_type_label || soil.soil_type || mColor.soil_type || 'Leached grayish brown';
      var covStr = m.coverage || '500 m²';
      var lt = parseFloat(m.latitude) || 0;
      var ln = parseFloat(m.longitude) || 0;

      var cleanBrgy = (m.barangay && m.barangay !== 'Lal-lo Overall') ? m.barangay : 'Centro (Poblacion)';
      cleanBrgy = cleanBrgy.replace(/^(brgy\.?|barangay\.?)\s*/i, '').replace(/,\s*lal-lo\s*$/i, '').replace(/,\s*cagayan\s*$/i, '');
      var brgy = 'Brgy. ' + cleanBrgy + ', Lal-lo, Cagayan';
      var brgyDisplay = 'Brgy. ' + cleanBrgy + ',<br/>Lal-lo, Cagayan';

      var drainage = soil.drainage || mColor.drainage_profile || 'Well-Drained Clay / Latosol';
      var organic = soil.effective_organic_matter || mColor.organic_matter || 'Low (1.1% – 2.0%)';
      var desc = soil.description || (m.soil_scan && m.soil_scan.scan_note ? m.soil_scan.scan_note : (fullCode + ' - ' + soilName));

      // Build detected regions array
      var regions = Array.isArray(m.regions) && m.regions.length > 0 ? m.regions : [];
      if (regions.length <= 1 && Array.isArray(m.detected_soils) && m.detected_soils.length > regions.length) {
        regions = m.detected_soils.map(function(ds, idx) {
          var rawPct = parseFloat(String(ds.percentage || '25').replace('%', '')) || 25;
          return {
            munsell: ds.full_code || '—',
            munsell_code: ds.full_code || '—',
            hex: ds.hex_color || '#8F694E',
            label: ds.soil_name || 'Soil Layer',
            color_name: ds.soil_name || 'Soil Layer',
            percent: rawPct,
            proportion: rawPct / 100,
            confidence: ds.confidence || '96.8%',
            organic_matter: ds.organic_matter || organic,
            soil_name: ds.soil_type || drainage,
            description: ds.description || '',
            box: { x: 0.15 + ((idx % 2) * 0.4), y: 0.2 + (Math.floor(idx / 2) * 0.35), w: 0.3, h: 0.3 }
          };
        });
      }
      if (regions.length === 0) {
        regions = [{
          munsell: fullCode,
          munsell_code: fullCode,
          hex: hexColor,
          label: soilName,
          color_name: soilName,
          percent: 100,
          proportion: 1.0,
          confidence: '98.7%',
          organic_matter: organic,
          soil_name: drainage,
          description: desc,
          box: { x: 0.25, y: 0.25, w: 0.5, h: 0.5 }
        }];
      }

      var isMulti = regions.length > 1;
      var dominant = regions[0] || {};
      var domBox = normalizeBox(dominant);
      var domCode = dominant.munsell || dominant.munsell_code || fullCode;
      var domHex = dominant.hex || hexColor;
      if (domHex && !domHex.startsWith('#')) domHex = '#' + domHex;
      var domName = dominant.label || dominant.color_name || soilName;
      var domPct = dominant.percent !== undefined ? Math.round(dominant.percent) : (dominant.proportion !== undefined ? Math.round(dominant.proportion * 100) : 100);
      var domConf = calcNearMatchRating(dominant);
      var domOrganic = dominant.organic_matter || organic;
      var domDrainage = dominant.soil_name || drainage;
      var domSoilType = dominant.soil_type || dominant.soil_name || soilType;
      var domDesc = dominant.description || desc;

      var scanImgUrl = fixImgUrl(m.scan_image_url || '');
      var baImgUrl = fixImgUrl(m.before_after_image_url || '');
      var recordedDate = m.scan_date_formatted || '09/27/2026';
      var scannedBy = m.scanned_by || 'Admin Analyst';
      var scanPlatform = m.scan_platform || 'Web';
      var scanCount = soil.scan_count || (m.soil_scan_id ? 51 : 1);
      var noteText = m.scan_note_text || (m.soil_scan && m.soil_scan.scan_note ? m.soil_scan.scan_note : ('Soil sample classified as ' + domName + ' (' + domCode + ').'));

      // Update Header Title (Matching Screenshot 2: Soil Record Details (4 DETECTED))
      document.getElementById('wsModalHeaderTitle').textContent = 'Soil Record Details' + (isMulti ? ' (' + regions.length + ' DETECTED)' : '');

      var bodyHtml = '';

      // 1. LOCATION METRICS (Barangay Location, Coordinates, Covered Area) (Matching Screenshot 2)
      bodyHtml += '' +
        '<div class="soil-modal-grid-3">' +
          '<div class="soil-metric-card">' +
            '<div class="soil-metric-card-label">Barangay Location</div>' +
            '<div class="soil-metric-card-val" title="' + brgy + '">' + brgyDisplay + '</div>' +
          '</div>' +
          '<div class="soil-metric-card">' +
            '<div class="soil-metric-card-label">Coordinates</div>' +
            '<div class="soil-metric-card-val" style="font-size: 11px; line-height: 1.25;">' + lt.toFixed(4) + ',<br/>' + ln.toFixed(4) + '</div>' +
          '</div>' +
          '<div class="soil-metric-card">' +
            '<div class="soil-metric-card-label">Covered Area</div>' +
            '<div class="soil-metric-card-val"><span class="covered-area-highlight">' + covStr + '</span></div>' +
          '</div>' +
        '</div>';

      // 2. DUAL-PREVIEW BANNER SWATCH CARD (Matching Screenshot 2)
      bodyHtml += '' +
        '<div class="soil-swatch-banner" style="background: ' + domHex + ';">' +
          '<div class="soil-swatch-avatar">' +
            (scanImgUrl ? (
              '<img src="' + scanImgUrl + '" alt="Soil Sample" />' +
              '<div style="position: absolute; left: ' + domBox.left + '%; top: ' + domBox.top + '%; width: ' + domBox.width + '%; height: ' + domBox.height + '%; border: 1.5px solid #4ade80; pointer-events: none; box-sizing: border-box; z-index: 5;"></div>'
            ) : (
              '<div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.2);">' +
                '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.9)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>' +
              '</div>'
            )) +
          '</div>' +
          '<div class="soil-swatch-center">' +
            '<div style="display: flex; align-items: center; gap: 6px;">' +
              (isMulti ? '<span style="font-size: 12px; color: #ffffff; opacity: 0.9;">▼</span>' : '') +
              '<span class="soil-swatch-code">' + domCode + '</span>' +
            '</div>' +
            '<div class="soil-swatch-sub">' + (isMulti ? ('Soil Percentage (' + domPct + '%)') : domName) + '</div>' +
          '</div>' +
          '<span class="soil-swatch-hex-pill">' + domHex.toUpperCase() + '</span>' +
        '</div>';

      // 3. METRICS ROW 1 (Color Name & Full Code) (2-Column Paired Grid matching website .soil-modal-grid-2)
      bodyHtml += '' +
        '<div class="soil-modal-grid-2">' +
          '<div class="soil-metric-card">' +
            '<div class="soil-metric-card-label">Color Name</div>' +
            '<div class="soil-metric-card-val truncate" title="' + domName + '">' + domName + '</div>' +
          '</div>' +
          '<div class="soil-metric-card">' +
            '<div class="soil-metric-card-label">Full Code</div>' +
            '<div class="soil-metric-card-val">' + domCode + '</div>' +
          '</div>' +
        '</div>';

      // 4. METRICS ROW 2 (Soil Organic & Soil Type)
      bodyHtml += '' +
        '<div class="soil-modal-grid-2">' +
          '<div class="soil-metric-card">' +
            '<div class="soil-metric-card-label">Soil Organic</div>' +
            '<div class="soil-metric-card-val truncate" title="' + domOrganic + '">' + domOrganic + '</div>' +
          '</div>' +
          '<div class="soil-metric-card">' +
            '<div class="soil-metric-card-label">Soil Type</div>' +
            '<div class="soil-metric-card-val truncate" title="' + domSoilType + '">' + domSoilType + '</div>' +
          '</div>' +
        '</div>';

      // 5. METRICS ROW 3 (Scan Count & Near-Match)
      bodyHtml += '' +
        '<div class="soil-modal-grid-2">' +
          '<div class="soil-metric-card">' +
            '<div class="soil-metric-card-label">Scan Count</div>' +
            '<div class="soil-metric-card-val">' + scanCount + '</div>' +
          '</div>' +
          '<div class="soil-metric-card" style="border-color: rgba(16, 185, 129, 0.3);">' +
            '<div class="soil-metric-card-label">Near-Match</div>' +
            '<div class="soil-metric-card-val" style="color: #10b981; font-weight: 800;">' + domConf + '</div>' +
          '</div>' +
        '</div>';

      // 6. SCAN NOTE (Matching Website)
      bodyHtml += '' +
        '<div class="soil-metric-card">' +
          '<div class="soil-metric-card-label">Scan Note</div>' +
          '<div class="soil-metric-card-text" style="font-size: 12.5px; font-weight: 600; line-height: 1.5; word-break: normal; overflow-wrap: break-word; white-space: pre-line;">' +
            noteText +
          '</div>' +
        '</div>';

      // 7. DESCRIPTION (Matching Website)
      bodyHtml += '' +
        '<div class="soil-metric-card">' +
          '<div class="soil-metric-card-label">Description</div>' +
          '<div class="soil-metric-card-text" style="font-size: 12.5px; font-weight: 600; line-height: 1.5; word-break: normal; overflow-wrap: break-word; white-space: pre-line;">' +
            (isMulti ? domDesc : desc) +
          '</div>' +
        '</div>';

      // 8. SECONDARY DETECTED SOIL LAYERS ACCORDIONS (Matching Website)
      if (isMulti) {
        bodyHtml += '' +
          '<div style="margin: 4px 0 6px; border-top: 1px dashed #cbd5e1; padding-top: 10px;">' +
            '<div style="display: flex; flex-direction: column; gap: 8px;">';

        regions.slice(1).forEach(function(sub, subIdx) {
          var idx = subIdx + 1;
          var subPct = sub.percent !== undefined ? Math.round(sub.percent) : (sub.proportion !== undefined ? Math.round(sub.proportion * 100) : 18);
          var subCode = sub.munsell || sub.munsell_code || 'Soil Color';
          var subHex = sub.hex || '#8F694E';
          if (subHex && !subHex.startsWith('#')) subHex = '#' + subHex;
          var subName = sub.label || sub.color_name || 'Soil Color';
          var subConf = calcNearMatchRating(sub);
          var subOrganic = sub.organic_matter || organic;
          var subSoilType = sub.soil_type || sub.soil_name || soilType;
          var subDesc = sub.description || (subCode + ' - ' + subName);
          var subBox = normalizeBox(sub);

          bodyHtml += '' +
            '<div class="layer-accordion-item">' +
              '<button type="button" class="layer-accordion-header" onclick="toggleWebsiteLayerAccordion(' + idx + ')">' +
                '<div class="layer-accordion-header-title">' +
                  '<span id="wsLayerArrow_' + idx + '" class="layer-accordion-arrow">▶</span>' +
                  '<span>' + subCode + ' (Soil Percentage - ' + subPct + '%)</span>' +
                '</div>' +
                '<span style="font-size: 11px; font-weight: 800; color: #ffffff; background: ' + subHex + '; padding: 3px 10px; border-radius: 12px; font-family: monospace;">' +
                  subHex.toUpperCase() +
                '</span>' +
              '</button>' +

              '<div id="wsLayerBody_' + idx + '" class="layer-accordion-body" style="display: none;">' +
                '<div class="soil-swatch-banner" style="background: ' + subHex + '; min-height: 60px; padding: 10px 14px;">' +
                  (scanImgUrl ? (
                    '<div class="soil-swatch-avatar" style="width: 44px; height: 44px; min-width: 44px;">' +
                      '<img src="' + scanImgUrl + '" alt="Soil Sample" />' +
                      '<div style="position: absolute; left: ' + subBox.left + '%; top: ' + subBox.top + '%; width: ' + subBox.width + '%; height: ' + subBox.height + '%; border: 1.5px solid #4ade80; pointer-events: none; box-sizing: border-box; z-index: 5;"></div>' +
                    '</div>'
                  ) : '') +
                  '<div class="soil-swatch-center">' +
                    '<span class="soil-swatch-code" style="font-size: 18px;">' + subCode + '</span>' +
                    '<div class="soil-swatch-sub">Soil Percentage (' + subPct + '%)</div>' +
                  '</div>' +
                  '<span class="soil-swatch-hex-pill">' + subHex.toUpperCase() + '</span>' +
                '</div>' +

                '<div class="soil-modal-grid-2">' +
                  '<div class="soil-metric-card">' +
                    '<div class="soil-metric-card-label">Color Name</div>' +
                    '<div class="soil-metric-card-val truncate" title="' + subName + '">' + subName + '</div>' +
                  '</div>' +
                  '<div class="soil-metric-card">' +
                    '<div class="soil-metric-card-label">Full Code</div>' +
                    '<div class="soil-metric-card-val">' + subCode + '</div>' +
                  '</div>' +
                '</div>' +

                '<div class="soil-modal-grid-2">' +
                  '<div class="soil-metric-card">' +
                    '<div class="soil-metric-card-label">Soil Organic</div>' +
                    '<div class="soil-metric-card-val truncate">' + subOrganic + '</div>' +
                  '</div>' +
                  '<div class="soil-metric-card">' +
                    '<div class="soil-metric-card-label">Soil Type</div>' +
                    '<div class="soil-metric-card-val truncate" title="' + subSoilType + '">' + subSoilType + '</div>' +
                  '</div>' +
                '</div>' +

                '<div class="soil-modal-grid-2">' +
                  '<div class="soil-metric-card">' +
                    '<div class="soil-metric-card-label">Proportion</div>' +
                    '<div class="soil-metric-card-val">' + subPct + '%</div>' +
                  '</div>' +
                  '<div class="soil-metric-card" style="border-color: rgba(16, 185, 129, 0.3);">' +
                    '<div class="soil-metric-card-label">Near-Match</div>' +
                    '<div class="soil-metric-card-val" style="color: #10b981; font-weight: 800;">' + subConf + '</div>' +
                  '</div>' +
                '</div>' +

                '<div class="soil-metric-card">' +
                  '<div class="soil-metric-card-label">Scan Note</div>' +
                  '<div class="soil-metric-card-text" style="font-size: 12px; font-weight: 600; line-height: 1.5;">' +
                    'No scan note available for this record.' +
                  '</div>' +
                '</div>' +

                '<div class="soil-metric-card">' +
                  '<div class="soil-metric-card-label">Description</div>' +
                  '<div class="soil-metric-card-text" style="font-size: 12px; font-weight: 600; line-height: 1.5; word-break: normal; overflow-wrap: break-word; white-space: pre-line;">' +
                    subDesc +
                  '</div>' +
                '</div>' +
              '</div>' +
            '</div>';
        });

        bodyHtml += '</div></div>';
      }

      // 9. SOIL NORMALIZATION: BEFORE / AFTER CARD (Matching Website)
      if (baImgUrl) {
        var ordinalNames = ['1ST', '2ND', '3RD', '4TH', '5TH', '6TH', '7TH', '8TH', '9TH', '10TH'];
        var overlayBoxesHtml = '';
        var swatchPillsHtml = '';

        regions.forEach(function(r, idx) {
          var bData = normalizeBox(r);
          var bx = bData.raw_x;
          var by = bData.raw_y;
          var bw = bData.raw_w;
          var bh = bData.raw_h;
          var leftPct = 50 + (bx * 50);
          var topPct = 7.41 + (by * 92.59);
          var widthPct = bw * 50;
          var heightPct = bh * 92.59;
          var roleLabel = (ordinalNames[idx] || (idx + 1) + 'TH') + ' COLOR';

          // Resolve per-region hex BEFORE building the overlay box HTML
          var rHex = r.hex || r.hex_color || null;
          if (!rHex && Array.isArray(r.rgb) && r.rgb.length >= 3) {
            rHex = '#' + r.rgb.map(function(v) { return Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2, '0'); }).join('');
          }
          if (!rHex) rHex = domHex; // fall back to dominant scan color
          if (rHex && !rHex.startsWith('#')) rHex = '#' + rHex;
          if (!rHex) rHex = '#4ade80'; // last-resort fallback
          var rCode = r.munsell || r.munsell_code || domCode;
          var rPct = r.percent !== undefined ? Math.round(r.percent) : (r.proportion !== undefined ? Math.round(r.proportion * 100) : domPct);

          // Compute label text color: dark text on light backgrounds, white on dark
          var labelTextColor = '#ffffff';
          try {
            var hex6 = rHex.replace('#', '');
            if (hex6.length === 3) hex6 = hex6[0]+hex6[0]+hex6[1]+hex6[1]+hex6[2]+hex6[2];
            var rC = parseInt(hex6.substring(0,2), 16);
            var gC = parseInt(hex6.substring(2,4), 16);
            var bC = parseInt(hex6.substring(4,6), 16);
            var luminance = 0.299 * rC + 0.587 * gC + 0.114 * bC;
            labelTextColor = luminance > 160 ? '#000000' : '#ffffff';
          } catch(e) {}

          overlayBoxesHtml += '' +
            '<div style="position: absolute; left: ' + leftPct + '%; top: ' + topPct + '%; width: ' + widthPct + '%; height: ' + heightPct + '%; border: 2.5px solid ' + rHex + '; pointer-events: none; border-radius: 4px; box-sizing: border-box;">' +
              '<span style="position: absolute; top: -14px; left: 50%; transform: translateX(-50%); font-size: 8px; font-weight: 800; background: ' + rHex + '; color: ' + labelTextColor + '; padding: 1px 6px; border-radius: 4px; white-space: nowrap; line-height: 1.2; letter-spacing: 0.03em;">' +
                roleLabel +
              '</span>' +
            '</div>';

          swatchPillsHtml += '' +
            '<div class="soil-swatch-capsule-pill">' +
              '<span class="capsule-pill-swatch" style="background: ' + rHex + ';"></span>' +
              '<span class="capsule-pill-hex">' + rHex.toUpperCase() + '</span>' +
              (rCode ? '<span class="capsule-pill-code">(' + rCode + ')</span>' : '') +
              '<span class="capsule-pill-pct">' + rPct + '%</span>' +
            '</div>';
        });

        bodyHtml += '' +
          '<div class="soil-metric-card" style="padding: 12px 14px 14px; margin-bottom: 4px;">' +
            '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">' +
              '<div class="soil-metric-card-label" style="margin-bottom: 0;">SOIL NORMALIZATION</div>' +
              '<span style="font-size: 9.5px; font-weight: 700; padding: 2px 8px; border-radius: 6px; background: rgba(16, 185, 129, 0.15); color: #10b981; text-transform: uppercase; letter-spacing: 0.04em;">BEFORE / AFTER</span>' +
            '</div>' +
            '<div style="position: relative; width: 100%; border-radius: 10px; overflow: hidden; background: #f8fafc; border: 1px solid #e2e8f0;">' +
              '<img src="' + baImgUrl + '" alt="Soil Before & After Normalization" style="width: 100%; height: auto; display: block; border-radius: 8px;" />' +
              '<div style="position: absolute; inset: 0; pointer-events: none;">' +
                overlayBoxesHtml +
              '</div>' +
            '</div>' +
            '<div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px;">' +
              swatchPillsHtml +
            '</div>' +
            '<div class="soil-normalization-meta-row">' +
              '<div><strong class="soil-normalization-meta-title">METADATA</strong></div>' +
              '<div class="soil-normalization-meta-info">CREATED: ' + recordedDate + ' &nbsp;|&nbsp; DETECTED COLORS: ' + regions.length + '</div>' +
            '</div>' +
          '</div>';
      }

      document.getElementById('wsModalBodyContent').innerHTML = bodyHtml;
      document.getElementById('websiteSoilDetailModal').style.display = 'flex';
      try {
        if (window.ReactNativeWebView) {
          window.ReactNativeWebView.postMessage(JSON.stringify({ type: 'MAP_MODAL_STATE', isOpen: true }));
        }
      } catch(e) {}
    };

    function renderMarkers(locations) {
      if (!markersGroup || !map) return;
      markersGroup.clearLayers();
      activeMarkers = [];
      allLocationsData = locations || [];
      if (!locations || !locations.length) return;

      var boundsGroup = [];

      locations.forEach(function(loc, index) {
        if (!loc.latitude || !loc.longitude) return;

        var lt = parseFloat(loc.latitude);
        var ln = parseFloat(loc.longitude);
        if (isNaN(lt) || isNaN(ln)) return;

        var soil = loc.soil || {};
        var mColor = soil.munsell_color || {};
        var soilName = soil.soil_name || mColor.color_name || 'Soil Specimen';
        var fullCode = soil.full_code || mColor.full_code || '—';
        var hexColor = soil.hex_color || mColor.hex_code || '#8F694E';
        if (hexColor && !hexColor.startsWith('#')) hexColor = '#' + hexColor;
        var soilType = loc.munsell_soil_type || soil.effective_soil_type || soil.soil_type || mColor.soil_type || 'Leached grayish brown';
        var covStr = loc.coverage || '500 m²';
        var locId = loc.location_mapping_id || loc.id || (index + 1);

        var rawBrgy = (loc.barangay && loc.barangay !== '') ? loc.barangay : 'Centro (Poblacion)';
        var brgyClean = rawBrgy.replace(/^(brgy\.?|barangay\.?)\s*/i, '').replace(/,\s*lal-lo\s*$/i, '').replace(/,\s*cagayan\s*$/i, '');
        var brgyLabel = 'Brgy. ' + brgyClean + ', Lal-lo';

        // Parse detected regions/layers
        var regions = [];
        if (Array.isArray(loc.regions) && loc.regions.length > 0) {
          regions = loc.regions;
        } else if (typeof loc.regions === 'string') {
          try {
            var parsedR = JSON.parse(loc.regions);
            if (Array.isArray(parsedR)) regions = parsedR;
          } catch(e) {}
        }
        if (regions.length <= 1 && Array.isArray(loc.detected_soils) && loc.detected_soils.length > regions.length) {
          regions = loc.detected_soils;
        } else if (regions.length <= 1 && typeof loc.detected_soils === 'string') {
          try {
            var parsedDs = JSON.parse(loc.detected_soils);
            if (Array.isArray(parsedDs) && parsedDs.length > regions.length) regions = parsedDs;
          } catch(e) {}
        }

        var isMulti = regions.length > 1;
        var domSoil = regions[0] || {};
        var displayTitle = (isMulti && (domSoil.label || domSoil.color_name || domSoil.soil_name)) ? (domSoil.label || domSoil.color_name || domSoil.soil_name) : soilName;
        var displayCode = (isMulti && (domSoil.munsell || domSoil.munsell_code || domSoil.full_code)) ? (domSoil.munsell || domSoil.munsell_code || domSoil.full_code) : fullCode;
        var displayHex = (isMulti && (domSoil.hex || domSoil.hex_color)) ? (domSoil.hex || domSoil.hex_color) : hexColor;
        if (displayHex && !displayHex.startsWith('#')) displayHex = '#' + displayHex;

        var isApproved = String(loc.status || '').toLowerCase() === 'active' || String(loc.status || '').toLowerCase() === 'approved';
        var markerIcon = isApproved ? redIcon : grayIcon;

        // Type row for 1 result vs Detected Soil row for >1 result
        var typeRowHtml = '';
        if (isMulti) {
          typeRowHtml = '' +
            '<div style="display: flex; justify-content: space-between; align-items: center;">' +
              '<span style="color: #64748b; font-weight: 600; font-size: 11px;">Detected Soil:</span>' +
              '<span style="font-weight: 800; color: #0f172a; font-size: 11.5px;">' + regions.length + ' Detected</span>' +
            '</div>';
        } else {
          typeRowHtml = '' +
            '<div style="display: flex; justify-content: space-between; align-items: center;">' +
              '<span style="color: #64748b; font-weight: 600; font-size: 11px;">Type:</span>' +
              '<span style="font-weight: 800; color: #0f172a; font-size: 11.5px; max-width: 155px; text-align: right; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="' + soilType + '">' + soilType + '</span>' +
            '</div>';
        }

        // Code row: only shown when 1 result only
        var codeRowHtml = !isMulti ? '' +
          '<div style="display: flex; justify-content: space-between; align-items: center;">' +
            '<span style="color: #64748b; font-weight: 600; font-size: 11px;">Code:</span>' +
            '<span style="font-weight: 800; color: #0f172a; font-size: 11.5px;">' + displayCode + '</span>' +
          '</div>' : '';

        // Soil title row: only shown when 1 result only
        var titleHtml = !isMulti ? '' +
          '<div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 2px; line-height: 1.25; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="' + displayTitle + '">' +
            displayTitle +
          '</div>' : '';

        // Matching Screenshot popup design exactly
        var popupHtml = '' +
          '<div style="padding: 12px 14px; font-family: inherit; width: 100%; box-sizing: border-box;">' +
            '<div style="margin-bottom: 3px; padding-right: 28px;">' +
              '<span style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">LOCATION #' + locId + '</span>' +
            '</div>' +
            titleHtml +
            '<div style="font-size: 11.5px; color: #15803d; font-weight: 700; display: flex; align-items: center; gap: 4px;' + (isMulti ? ' margin-top: 4px;' : '') + ' margin-bottom: 9px;">' +
              '<svg width="12" height="12" fill="#16a34a" viewBox="0 0 20 20" style="flex-shrink:0;"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>' +
              '<span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">' + brgyLabel + '</span>' +
            '</div>' +
            '<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 11px; margin-bottom: 10px; font-size: 11.5px; display: flex; flex-direction: column; gap: 5px;">' +
              codeRowHtml +
              typeRowHtml +
              '<div style="display: flex; justify-content: space-between; align-items: center;">' +
                '<span style="color: #64748b; font-weight: 600; font-size: 11px;">Covered Area:</span>' +
                '<span style="font-weight: 700; color: #0f172a; font-size: 11.5px;">' + covStr + '</span>' +
              '</div>' +
              '<div style="border-top: 1px dotted #cbd5e1; margin-top: 2px; padding-top: 5px; display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #94a3b8;">' +
                '<span>Coords:</span>' +
                '<span style="font-family: monospace; font-size: 10.5px; font-weight: 500;">' + lt.toFixed(4) + ', ' + ln.toFixed(4) + '</span>' +
              '</div>' +
            '</div>' +
            '<div>' +
              '<button type="button" onclick="event.stopPropagation(); viewWebsitePinDetails(' + locId + ');" style="width: 100%; padding: 8.5px 12px; background: #16a34a; color: #ffffff; border: none; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; transition: background 0.15s ease; box-shadow: 0 1px 2px rgba(22, 163, 74, 0.2);">' +
                'View Details' +
              '</button>' +
            '</div>' +
          '</div>';

        // 3. Covered Area Parcel Polygon Overlay (Matching Website/Admin GIS)
        var dims = getParcelDimensions(covStr);
        var boxCoords = getRoundedBoxCoords(lt, ln, dims.w, dims.h);
        var polyBorderColor = isApproved ? '#16a34a' : '#6b7280';
        var polyFillColor = isApproved ? '#22c55e' : '#9ca3af';

        var poly = L.polygon(boxCoords, {
          color: polyBorderColor,
          fillColor: polyFillColor,
          fillOpacity: 0.22,
          weight: 2.2,
          interactive: isApproved
        });

        if (isApproved) {
          poly.bindTooltip('<div style="font-size:11px; font-weight:700; color:#14532d;">Covered Area: ' + covStr + '</div>', {
            sticky: true,
            direction: 'top',
            offset: [0, -10]
          });
        }

        var marker = L.marker([lt, ln], { icon: markerIcon, interactive: true });
        marker.bindPopup(popupHtml, { className: 'agrico-custom-popup', maxWidth: 320, autoPan: false });

        marker.on('click', function(e) {
          if (e && e.originalEvent) L.DomEvent.stopPropagation(e);
          centerMapOnPopup(lt, ln, 165);
        });
        marker.on('popupopen', function() {
          centerMapOnPopup(lt, ln, 165);
        });

        markersGroup.addLayer(poly);
        markersGroup.addLayer(marker);

        activeMarkers.push({
          marker: marker,
          lat: lt,
          lng: ln,
          locId: locId,
          soilName: soilName,
          fullCode: fullCode,
          barangay: loc.barangay || ''
        });

        boundsGroup.push([lt, ln]);
      });

      if (boundsGroup.length > 0) {
        try {
          map.fitBounds(boundsGroup, { padding: [40, 40], maxZoom: 13 });
        } catch(e) {}
      } else {
        map.setView([defaultLat, defaultLng], defaultZoom);
      }
    }

    function setLocation(lat, lng, zoom) {
      map.setView([lat, lng], zoom || 15, { animate: true });
    }

    function focusLocation(lat, lng, zoom) {
      map.setView([lat, lng], zoom || 16, { animate: true });
      for (var i = 0; i < activeMarkers.length; i++) {
        var m = activeMarkers[i];
        if (Math.abs(m.lat - lat) < 0.005 && Math.abs(m.lng - lng) < 0.005) {
          setTimeout(function() { m.marker.openPopup(); }, 350);
          break;
        }
      }
    }

    function handleIncomingMessage(event) {
      try {
        var rawData = event.data;
        var msg = typeof rawData === 'string' ? JSON.parse(rawData) : rawData;
        if (msg && msg.type === 'SET_LOCATIONS') {
          renderMarkers(msg.locations);
        } else if (msg && msg.type === 'SET_CENTER') {
          setLocation(msg.lat, msg.lng, msg.zoom);
        } else if (msg && msg.type === 'FOCUS_LOCATION') {
          focusLocation(msg.lat, msg.lng, msg.zoom);
        }
      } catch (err) {}
    }

    window.addEventListener('message', handleIncomingMessage);
    document.addEventListener('message', handleIncomingMessage);

    var initialLocations = ${safeLocationsJson};
    if (initialLocations && initialLocations.length > 0) {
      renderMarkers(initialLocations);
    }
  </script>
</body>
</html>
`;
};


// High-Resolution Scanned Soil Images
const SOIL_IMAGES = {
  s1: require('../assets/soils/soil_10YR_4_4.jpg'),
  s2: require('../assets/soils/soil_10YR_3_2.jpg'),
  s3: require('../assets/soils/soil_7_5YR_4_4.jpg'),
  s4: require('../assets/soils/soil_7_5YR_5_6.jpg'),
  s5: require('../assets/soils/soil_5YR_4_6.jpg'),
  s6: require('../assets/soils/soil_2_5YR_4_6.jpg'),
  s7: require('../assets/soils/soil_10R_4_6.jpg'),
  s8: require('../assets/soils/soil_2_5Y_5_4.jpg'),
  r1: require('../assets/soils/soil_10YR_2_2.jpg'),
  r2: require('../assets/soils/soil_7_5YR_6_6.jpg'),
};

const HUE_IMAGE_FALLBACK = {
  '10YR': SOIL_IMAGES.s1,
  '7.5YR': SOIL_IMAGES.s3,
  '5YR': SOIL_IMAGES.s5,
  '2.5YR': SOIL_IMAGES.s6,
  '10R': SOIL_IMAGES.s7,
  '2.5Y': SOIL_IMAGES.s8,
};

// Region crop PNGs (region_<id>_N.png) are often missing in cloud storage, while the
// full normalized photo of the same scan (scan_<id>_normalized.jpg) exists. Build that URL.
const deriveNormalizedScanUrl = (url) => {
  if (!url || typeof url !== 'string') return null;
  const m = url.match(/^(.*\/)(?:region|scan)_(.+?)(?:_\d+\.png|_normalized\.jpg|\.\w+)$/i);
  if (!m) return null;
  return `${m[1]}scan_${m[2]}_normalized.jpg`;
};

// Image that retries with the normalized scan photo if the primary URL fails to load.
const FallbackImage = ({ uri, style, resizeMode = 'cover' }) => {
  const [src, setSrc] = useState(uri);
  const [triedFallback, setTriedFallback] = useState(false);
  useEffect(() => {
    setSrc(uri);
    setTriedFallback(false);
  }, [uri]);
  return (
    <Image
      source={{ uri: src }}
      style={style}
      resizeMode={resizeMode}
      onError={() => {
        if (triedFallback) return;
        setTriedFallback(true);
        const alt = deriveNormalizedScanUrl(src);
        if (alt && alt !== src) setSrc(alt);
      }}
    />
  );
};

// Helper: Safely resolve image from DB scanImage URI or offline high-res sample
const resolveSoilImage = (item) => {
  if (item?.scanImage && typeof item.scanImage === 'string' && item.scanImage.length > 5) {
    return { uri: item.scanImage };
  }
  if (item?.image) {
    return item.image;
  }
  if (item?.id && SOIL_IMAGES[item.id]) {
    return SOIL_IMAGES[item.id];
  }
  if (item?.hue && HUE_IMAGE_FALLBACK[item.hue]) {
    return HUE_IMAGE_FALLBACK[item.hue];
  }
  return SOIL_IMAGES.s1;
};

// Baseline / Fallback Data (Offline-ready with pre-bundled database records)
const INITIAL_SOIL_CARDS = Array.isArray(OFFLINE_SOIL_CARDS) && OFFLINE_SOIL_CARDS.length > 0
  ? OFFLINE_SOIL_CARDS
  : [];

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

// Date Grouping Engine for Scan History (Today, Yesterday, Specific Date)
const groupScansByDate = (scansList) => {
  if (!Array.isArray(scansList) || scansList.length === 0) return [];

  const now = new Date();
  const todayYear = now.getFullYear();
  const todayMonth = now.getMonth();
  const todayDate = now.getDate();

  const yesterdayDateObj = new Date(todayYear, todayMonth, todayDate - 1);
  const yYear = yesterdayDateObj.getFullYear();
  const yMonth = yesterdayDateObj.getMonth();
  const yDate = yesterdayDateObj.getDate();

  const groups = {};
  const groupOrder = [];

  scansList.forEach((scan) => {
    let label = 'Earlier';
    const raw = scan.rawDate;

    if (raw) {
      const d = new Date(typeof raw === 'string' ? raw.replace(' ', 'T') : raw);
      if (!isNaN(d.getTime())) {
        const itemYear = d.getFullYear();
        const itemMonth = d.getMonth();
        const itemDate = d.getDate();

        if (itemYear === todayYear && itemMonth === todayMonth && itemDate === todayDate) {
          label = 'Today';
        } else if (itemYear === yYear && itemMonth === yMonth && itemDate === yDate) {
          label = 'Yesterday';
        } else {
          label = d.toLocaleDateString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
          });
        }
      } else if (scan.dateGroup) {
        label = scan.dateGroup;
      }
    } else if (scan.dateGroup) {
      label = scan.dateGroup;
    }

    if (!groups[label]) {
      groups[label] = [];
      groupOrder.push(label);
    }
    groups[label].push(scan);
  });

  return groupOrder.map((dateLabel) => ({
    date: dateLabel,
    data: groups[dateLabel],
  }));
}

// Helper map pin icon matching Pin Soil Location button
function MapPinIcon({ size = 16, color = '#ffffff' }) {
  return (
    <View style={{ width: size, height: size, alignItems: 'center', justifyContent: 'center', marginRight: 4 }}>
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
function SaveTrayIcon({ size = 14, color = '#ffffff' }) {
  return (
    <View style={{ width: size, height: size, alignItems: 'center', justifyContent: 'center' }}>
      <View style={{ width: 1.6, height: size * 0.42, backgroundColor: color, marginBottom: -1 }} />
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
          borderWidth: 1.5,
          borderColor: color,
          borderTopWidth: 0,
          borderRadius: 2,
          marginTop: 1.5,
        }}
      />
    </View>
  );
}

// Helper lighting icon matching website Stage 2 illumination normalizer header
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

export default function HomeScreen({ navigation, route }) {
  const { user, logout, checkAuth } = useContext(AuthContext);

  // Extract first name from authenticated account
  const userFirstName = useMemo(() => {
    const fullName = user?.name || user?.full_name || '';
    const clean = fullName.trim();
    if (!clean) return 'Farmer';
    return clean.split(/\s+/)[0];
  }, [user]);

  const [activeTab, setActiveTab] = useState(route?.params?.initialTab || route?.params?.screen || 'Home');

  useEffect(() => {
    const target = route?.params?.initialTab || route?.params?.screen;
    if (target) {
      setActiveTab(target);
    }
  }, [route?.params?.initialTab, route?.params?.screen]);
  const [selectedHue, setSelectedHue] = useState('Recommend');
  const [searchQuery, setSearchQuery] = useState('');
  const [soilCards, setSoilCards] = useState(INITIAL_SOIL_CARDS);
  const [recommendedSoils, setRecommendedSoils] = useState(
    INITIAL_SOIL_CARDS.length > 0 ? INITIAL_SOIL_CARDS.slice(0, 2) : []
  );
  const [scanHistory, setScanHistory] = useState([]);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [detailSoil, setDetailSoil] = useState(null);
  const [isSavedCopy, setIsSavedCopy] = useState(false);
  const [expandedLayers, setExpandedLayers] = useState({});

  useEffect(() => {
    setExpandedLayers({});
  }, [detailSoil]);

  const [isLoadingSoils, setIsLoadingSoils] = useState(false);
  const [showAllCards, setShowAllCards] = useState(false);
  const [currentPage, setCurrentPage] = useState(1);
  const rowsPerPage = 5;

  // Full Munsell Directory States (207 Colors synced with Website & Admin, offline-ready)
  const [munsellList, setMunsellList] = useState(
    Array.isArray(OFFLINE_MUNSELL_COLORS) && OFFLINE_MUNSELL_COLORS.length > 0
      ? OFFLINE_MUNSELL_COLORS
      : []
  );
  const [munsellTotalCount, setMunsellTotalCount] = useState(
    Array.isArray(OFFLINE_MUNSELL_COLORS) && OFFLINE_MUNSELL_COLORS.length > 0
      ? OFFLINE_MUNSELL_COLORS.length
      : 207
  );
  const [munsellSearch, setMunsellSearch] = useState('');
  const [munsellSelectedHue, setMunsellSelectedHue] = useState('All');
  const [isLoadingMunsell, setIsLoadingMunsell] = useState(false);
  const [munsellPage, setMunsellPage] = useState(1);
  const [munsellRowsPerPage, setMunsellRowsPerPage] = useState(10);
  const [showRowsPerPageModal, setShowRowsPerPageModal] = useState(false);

  // Auto-Update States
  const [appUpdateInfo, setAppUpdateInfo] = useState(null);
  const [showUpdateModal, setShowUpdateModal] = useState(false);
  const dismissedUpdateVersionRef = useRef(null);

  // Check for new mobile application version on launch (without aggressive intervals)
  const runUpdateCheck = useCallback(async (isManual = false) => {
    try {
      const updateData = await checkForAppUpdate();
      if (updateData?.hasUpdate) {
        // If user already dismissed this update in current session, skip automatic popup
        if (!isManual && dismissedUpdateVersionRef.current === updateData.latestVersion) {
          return;
        }
        setAppUpdateInfo(updateData);
        setShowUpdateModal(true);
      }
    } catch (_) {}
  }, []);

  useEffect(() => {
    runUpdateCheck();

    const unsubscribe = NetInfo.addEventListener(state => {
      if (state.isConnected && state.isInternetReachable !== false) {
        runUpdateCheck();
      }
    });

    return () => {
      unsubscribe();
    };
  }, [runUpdateCheck]);

  // Restore any updated cached datasets from previous online sessions
  useEffect(() => {
    let isMounted = true;
    (async () => {
      try {
        const munsellCachePath = `${RNFS.DocumentDirectoryPath}/munsell_cache.json`;
        if (await RNFS.exists(munsellCachePath)) {
          const raw = await RNFS.readFile(munsellCachePath, 'utf8');
          const parsed = JSON.parse(raw);
          if (isMounted && Array.isArray(parsed) && parsed.length > 0) {
            setMunsellList(parsed);
            setMunsellTotalCount(parsed.length);
          }
        }
      } catch (_) {}

      try {
        const soilsCachePath = `${RNFS.DocumentDirectoryPath}/soils_cache.json`;
        if (await RNFS.exists(soilsCachePath)) {
          const raw = await RNFS.readFile(soilsCachePath, 'utf8');
          const parsed = JSON.parse(raw);
          if (isMounted && Array.isArray(parsed) && parsed.length > 0) {
            setSoilCards(parsed);
            setRecommendedSoils(parsed.slice(0, 2));
          }
        }
      } catch (_) {}
    })();
    return () => { isMounted = false; };
  }, []);

  // Map Screen States: NetInfo connection tracking, live admin locations & WebView ref
  const [mapSearchText, setMapSearchText] = useState('');
  const [adminLocations, setAdminLocations] = useState([]);
  const [isConnected, setIsConnected] = useState(true);
  const [isCheckingConnection, setIsCheckingConnection] = useState(false);
  const [hasLoadedOnce, setHasLoadedOnce] = useState(false);
  const webViewRef = useRef(null);
  const [isMapModalOpen, setIsMapModalOpen] = useState(false);

  // Handle Android hardware back press when map modal is open
  useEffect(() => {
    const onBackPress = () => {
      if (activeTab === 'Map' && isMapModalOpen) {
        if (webViewRef.current) {
          webViewRef.current.injectJavaScript('window.closeSoilModal(); true;');
        }
        setIsMapModalOpen(false);
        return true;
      }
      return false;
    };
    const sub = BackHandler.addEventListener('hardwareBackPress', onBackPress);
    return () => sub.remove();
  }, [activeTab, isMapModalOpen]);

  // Reset modal state when tab changes
  useEffect(() => {
    if (activeTab !== 'Map') {
      setIsMapModalOpen(false);
    }
  }, [activeTab]);

  // Fetch Live Location Mappings from Admin Side
  const fetchAdminLocations = useCallback(async () => {
    try {
      const res = await api.get('/locations');
      if (res.data?.data && Array.isArray(res.data.data)) {
        setAdminLocations(res.data.data);
        if (webViewRef.current) {
          webViewRef.current.postMessage(
            JSON.stringify({ type: 'SET_LOCATIONS', locations: res.data.data })
          );
        }
      }
    } catch (err) {
      console.warn('Could not fetch admin map locations:', err);
    }
  }, []);

  // Fetch admin locations whenever Map tab is active and connected
  useEffect(() => {
    if (activeTab === 'Map' && isConnected) {
      fetchAdminLocations();
    }
  }, [activeTab, isConnected, fetchAdminLocations]);

  // Memoized Leaflet HTML generated with current admin locations and server base
  const activeApi = typeof getActiveBaseUrl === 'function' ? getActiveBaseUrl() : ENV.API_URL;
  const serverBase = activeApi ? activeApi.replace(/\/api\/?$/, '') : 'http://10.0.2.2:8080';
  const leafletMapHtml = useMemo(
    () => buildLeafletMapHtml(adminLocations, serverBase),
    [adminLocations, serverBase]
  );

  // Step 2: Track connection status in state with NetInfo listener
  useEffect(() => {
    const unsubscribe = NetInfo.addEventListener((state) => {
      const reachable = Boolean(state.isConnected && state.isInternetReachable !== false);
      setIsConnected(reachable);
    });

    NetInfo.fetch().then((state) => {
      const reachable = Boolean(state.isConnected && state.isInternetReachable !== false);
      setIsConnected(reachable);
    });

    return () => {
      unsubscribe();
    };
  }, []);

  // Step 4: Retry connection function
  const handleRetryConnection = useCallback(async () => {
    setIsCheckingConnection(true);
    try {
      const state = await NetInfo.fetch();
      const reachable = Boolean(state.isConnected && state.isInternetReachable !== false);
      setIsConnected(reachable);
      if (!reachable) {
        Alert.alert('No Internet Connection', 'Unable to reach the internet. Please check your Wi-Fi or mobile data.');
      } else {
        fetchAdminLocations();
        if (webViewRef.current) {
          webViewRef.current.reload();
        }
      }
    } finally {
      setIsCheckingConnection(false);
    }
  }, [fetchAdminLocations]);

  // Interactive Map Search handler matching admin locations
  const handleMapSearchSubmit = useCallback(() => {
    if (!mapSearchText.trim()) {
      if (webViewRef.current) {
        webViewRef.current.postMessage(
          JSON.stringify({ type: 'SET_CENTER', lat: 18.1972, lng: 121.6612, zoom: 12 })
        );
      }
      return;
    }
    const q = mapSearchText.trim().toLowerCase();

    // Look for matching location in adminLocations (barangay, soil name, munsell code, note)
    const matched = adminLocations.find((loc) => {
      const b = (loc.barangay || '').toLowerCase();
      const sn = (loc.soil?.soil_name || '').toLowerCase();
      const code = (loc.soil?.full_code || '').toLowerCase();
      const note = (loc.soil_scan?.scan_note || '').toLowerCase();
      return b.includes(q) || sn.includes(q) || code.includes(q) || note.includes(q);
    });

    if (matched && matched.latitude && matched.longitude) {
      if (webViewRef.current) {
        webViewRef.current.postMessage(
          JSON.stringify({
            type: 'FOCUS_LOCATION',
            lat: parseFloat(matched.latitude),
            lng: parseFloat(matched.longitude),
            zoom: 16,
          })
        );
      }
    } else {
      if (q.includes('lal-lo') || q.includes('cagayan')) {
        if (webViewRef.current) {
          webViewRef.current.postMessage(
            JSON.stringify({ type: 'SET_CENTER', lat: 18.1972, lng: 121.6612, zoom: 13 })
          );
        }
      } else {
        Alert.alert(
          'Location Search',
          `No mapped soil sites matched "${mapSearchText}". Showing all mapped locations in Lal-lo, Cagayan.`,
          [{ text: 'OK' }]
        );
      }
    }
  }, [mapSearchText, adminLocations]);

  const totalPages = Math.max(1, Math.ceil(scanHistory.length / rowsPerPage));

  // Clamp current page if total pages change
  useEffect(() => {
    if (currentPage > totalPages) {
      setCurrentPage(totalPages);
    }
  }, [currentPage, totalPages]);

  // Sliced 5 rows per page
  const paginatedScans = useMemo(() => {
    const start = (currentPage - 1) * rowsPerPage;
    return scanHistory.slice(start, start + rowsPerPage);
  }, [scanHistory, currentPage]);

  // Memoized date-grouped scan history for the current page
  const groupedScans = useMemo(() => {
    return groupScansByDate(paginatedScans);
  }, [paginatedScans]);

  // Generate page numbers to display
  const pageNumbers = useMemo(() => {
    if (totalPages <= 5) {
      return Array.from({ length: totalPages }, (_, i) => i + 1);
    }
    let start = Math.max(1, currentPage - 2);
    let end = Math.min(totalPages, start + 4);
    if (end - start < 4) {
      start = Math.max(1, end - 4);
    }
    const pages = [];
    for (let i = start; i <= end; i++) {
      pages.push(i);
    }
    return pages;
  }, [totalPages, currentPage]);

  // Lookup matched official Munsell color from Admin / Manage Munsell Color database
  const detailMunsellMatch = useMemo(() => {
    if (!detailSoil) return null;
    const targetCode = (detailSoil.munsellCode || detailSoil.fullCode || detailSoil.full_code || '')
      .trim()
      .toUpperCase();
    const source = Array.isArray(munsellList) && munsellList.length > 0 ? munsellList : OFFLINE_MUNSELL_COLORS;
    return (
      source.find(
        (c) =>
          (c.munsellCode || c.full_code || c.munsell_code || '').trim().toUpperCase() === targetCode
      ) || null
    );
  }, [detailSoil, munsellList]);

  // Unified official description based on Admin Manage Munsell Color
  const detailResolvedDescription = useMemo(() => {
    if (detailMunsellMatch?.description && detailMunsellMatch.description.trim()) {
      return detailMunsellMatch.description;
    }
    if (
      detailSoil?.description &&
      !detailSoil.description.startsWith('Determined Soil:') &&
      !detailSoil.description.startsWith('Field scan record') &&
      !detailSoil.description.startsWith('Sample processed successfully')
    ) {
      return detailSoil.description;
    }
    return (
      detailMunsellMatch?.description ||
      `${detailSoil?.munsellCode || detailSoil?.fullCode || ''} - ${detailSoil?.colorName || detailSoil?.name || 'Determined Soil'}`
    );
  }, [detailSoil, detailMunsellMatch]);

  // Differentiate between Munsell Color Chart record details vs Scan History record details
  const isMunsellDetail = useMemo(() => {
    if (!detailSoil) return false;
    if (detailSoil.isMunsellColor) return true;
    if (detailSoil.isScanRecord) return false;
    if (
      detailSoil.soil_scan_id ||
      detailSoil.scan_image ||
      detailSoil.scanImage ||
      detailSoil.beforeAfterImage ||
      detailSoil.beforeAfterImageUrl ||
      (typeof detailSoil.id === 'string' && detailSoil.id.startsWith('scan_')) ||
      detailSoil.syncState ||
      detailSoil.isOffline
    ) {
      return false;
    }
    return true;
  }, [detailSoil]);

  // Normalization data and swatches for Soil Record Details modal
  const detailNormRegions = useMemo(() => {
    if (!detailSoil) return [];
    if (Array.isArray(detailSoil.regions) && detailSoil.regions.length > 0) {
      return detailSoil.regions;
    }
    const domHex = detailMunsellMatch?.hexCode || detailSoil.hexCode || detailSoil.hexColor || '#8F694E';
    const domCode = detailMunsellMatch?.munsellCode || detailSoil.munsellCode || detailSoil.fullCode || '10YR 4/1';
    const domPct = detailSoil.soilPercentage || 100;
    return [{
      hex: domHex,
      munsell: domCode,
      percent: domPct,
    }];
  }, [detailSoil, detailMunsellMatch]);

  const detailNormDateTime = useMemo(() => {
    if (!detailSoil) {
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

    const raw = detailSoil.rawDate || detailSoil.scanDate || detailSoil.createdDate || detailSoil.created_at;
    let d = raw ? new Date(typeof raw === 'string' ? raw.replace(' ', 'T') : raw) : new Date();
    if (isNaN(d.getTime())) d = new Date();

    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    const yyyy = d.getFullYear();
    const dateStr = `${mm}/${dd}/${yyyy}`;

    let timeStr = '';
    if (detailSoil.scanTime && typeof detailSoil.scanTime === 'string' && detailSoil.scanTime.trim()) {
      timeStr = detailSoil.scanTime.trim();
    } else {
      let hours = d.getHours();
      const minutes = String(d.getMinutes()).padStart(2, '0');
      const ampm = hours >= 12 ? 'PM' : 'AM';
      hours = hours % 12 || 12;
      timeStr = `${String(hours).padStart(2, '0')}:${minutes} ${ampm}`;
    }

    return { dateStr, timeStr };
  }, [detailSoil]);

  const detailNormDate = detailNormDateTime.dateStr;

  const avatarRoiBox = useMemo(() => {
    if (!detailSoil || isMunsellDetail) return null;
    const region = (Array.isArray(detailSoil.regions) && detailSoil.regions[0]) || null;
    const norm = normalizeBox(region);
    const leftPct = Math.max(4, Math.min(80, (norm.raw_x || 0.25) * 100));
    const topPct = Math.max(4, Math.min(80, (norm.raw_y || 0.25) * 100));
    const widthPct = Math.max(16, Math.min(94 - leftPct, (norm.raw_w || 0.5) * 100));
    const heightPct = Math.max(16, Math.min(94 - topPct, (norm.raw_h || 0.5) * 100));
    return { leftPct, topPct, widthPct, heightPct };
  }, [detailSoil, isMunsellDetail]);

  const detailNormBoxes = useMemo(() => {
    const ordinalNames = ['1ST', '2ND', '3RD', '4TH', '5TH', '6TH', '7TH', '8TH', '9TH', '10TH'];
    const regions = (detailNormRegions && detailNormRegions.length > 0)
      ? detailNormRegions
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

      // Coordinates for 2-panel composite Before/After comparison image (right 50% pane, with 7.41% top bar)
      const splitLeft = 50.0 + (bData.raw_x * 50.0);
      const splitTop = 7.41 + (bData.raw_y * 92.59);
      const splitWidth = bData.raw_w * 50.0;
      const splitHeight = bData.raw_h * 92.59;

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
        roleLabel: `${ordinalNames[idx] || (idx + 1) + 'TH'} COLOR`,
        hex: rHex,
        munsellCode: rCode,
        percent: rPct,
        raw_x: bData.raw_x,
        raw_y: bData.raw_y,
        raw_w: bData.raw_w,
        raw_h: bData.raw_h,
      };
    });
  }, [detailNormRegions]);

  const handleSaveRecordCopy = useCallback(async (targetSoil, munsellMatch, resolvedDesc) => {
    if (!targetSoil) return;
    try {
      const code = munsellMatch?.munsellCode || targetSoil.munsellCode || targetSoil.fullCode || '7.5YR 5/3';
      const name = munsellMatch?.colorName || targetSoil.colorName || targetSoil.name || 'Determined Soil';
      const hex = (munsellMatch?.hexCode || targetSoil.hexCode || targetSoil.hex || '#8F694E').toUpperCase();
      const organic = munsellMatch?.soil_organic_index || targetSoil.soil_organic_index || targetSoil.organicIndex || 'Moderate';
      const soilType = munsellMatch?.soil_type || targetSoil.soil_type || targetSoil.soilType || 'Determined Soil';
      const nearMatch = targetSoil.nearMatchRating || targetSoil.confidence || '97.4%';
      const scanDate = targetSoil.scanDate || targetSoil.createdDate || targetSoil.rawDate || 'Today';
      const scannedBy = targetSoil.adminId || targetSoil.scannedBy || 'User';
      const safeCode = String(code).replace(/[\/\s]/g, '_');

      const reportContent =
        `========================================\n` +
        `       AGRICO SOIL RECORD DETAILS\n` +
        `========================================\n` +
        `Munsell Code:  ${code}\n` +
        `Color Name:    ${name}\n` +
        `Hex Code:      ${hex}\n` +
        `Organic Index: ${organic}\n` +
        `Soil Type:     ${soilType}\n` +
        `Near-Match:    ${nearMatch}\n` +
        `Scan Date:     ${scanDate}\n` +
        `Scanned By:    ${scannedBy}\n` +
        `Description:   ${resolvedDesc}\n` +
        `========================================\n` +
        `Generated by AgriCo Mobile Soil Analysis\n`;

      // 1. Write file to device storage
      const fileName = `AgriCo_Soil_Record_${safeCode}_${Date.now()}.txt`;
      const baseDir = RNFS.DownloadDirectoryPath || RNFS.DocumentDirectoryPath;
      const targetFilePath = `${baseDir}/${fileName}`;
      try {
        await RNFS.writeFile(targetFilePath, reportContent, 'utf8');
      } catch (fsErr) {
        console.log('RNFS write fallback:', fsErr?.message);
      }

      // 2. Trigger native Share/Copy sheet
      await Share.share({
        title: `AgriCo Soil Record - ${code}`,
        message: reportContent,
      });

      // 3. Visual & toast feedback
      setIsSavedCopy(true);
      setTimeout(() => setIsSavedCopy(false), 3000);
      if (Platform.OS === 'android' && ToastAndroid) {
        ToastAndroid.show(`Saved: ${fileName}`, ToastAndroid.LONG);
      }
    } catch (e) {
      console.log('Error saving record copy:', e?.message);
    }
  }, []);

  // Fetch dynamic "Most Scanned Soil" records from the database
  const loadMostScanned = useCallback(async (hue = selectedHue, search = searchQuery) => {
    try {
      setIsLoadingSoils(true);
      const params = {};
      if (hue && hue !== 'All' && hue !== 'Recommend') params.hue = hue;
      if (search && search.trim() !== '') params.search = search.trim();

      const res = await api.get('/soils/most-scanned', { params });
      if (res.data?.status === 'success' && Array.isArray(res.data.data) && res.data.data.length > 0) {
        setSoilCards(res.data.data);
        if (hue === 'Recommend' || hue === 'All') {
          setRecommendedSoils(res.data.data.slice(0, 2));
        }
        try {
          const soilsCachePath = `${RNFS.DocumentDirectoryPath}/soils_cache.json`;
          await RNFS.writeFile(soilsCachePath, JSON.stringify(res.data.data), 'utf8');
        } catch (_) {}
      }
    } catch (error) {
      console.log('API MOST SCANNED ERROR:', error?.message, error?.response?.status);
    } finally {
      setIsLoadingSoils(false);
    }
  }, [selectedHue, searchQuery]);

  // Fetch full synchronized scan history for the current account from database & local offline store
  const loadScanHistory = useCallback(async () => {
    // 1. Instantly display local store scans (offline-ready, zero lag)
    try {
      const localScans = await getLocalScans();
      if (Array.isArray(localScans) && localScans.length > 0) {
        setScanHistory(localScans);
      }
    } catch (_) {}

    // 2. If online, fetch from backend and synchronize into local store
    try {
      const net = await NetInfo.fetch();
      if (!net.isConnected) return;

      const activeUserId = user?.user_id || user?.id;
      const res = await api.get('/soil-scans', {
        params: {
          per_page: 100,
          user_id: activeUserId || undefined,
        },
      });
      const rawList = res.data?.data?.data || res.data?.data || [];
      if (Array.isArray(rawList)) {
        // Guarantee only scans belonging to this account are displayed
        const accountScans = activeUserId
          ? rawList.filter((s) => !s.user_id || String(s.user_id) === String(activeUserId))
          : rawList;

        const mapped = accountScans.map((s) => {
          const hasPin = Boolean(s.location_id || s.location || s.latitude);
          const rawNote = s.scan_note || '';
          const isRealNote = rawNote && !rawNote.startsWith('Determined Soil:') && rawNote !== '—' && rawNote !== '-';
          const fixMediaUrl = (u) => {
            if (!u) return null;
            let str = String(u).trim();
            if (str.includes('localhost:8080')) str = str.replace('localhost:8080', '10.0.2.2:8080');
            if (str.includes('127.0.0.1:8080')) str = str.replace('127.0.0.1:8080', '10.0.2.2:8080');
            if (str.startsWith('http') || str.startsWith('data:')) return str;
            if (str.length > 250 && !str.includes('/') && !str.includes('.')) {
              return `data:image/jpeg;base64,${str}`;
            }
            const activeApiHost = typeof getActiveBaseUrl === 'function' ? getActiveBaseUrl() : ENV.API_URL;
            const mediaBase = activeApiHost ? activeApiHost.replace(/\/api\/?$/, '') : 'http://10.0.2.2:8080';
            return `${mediaBase}${str.startsWith('/') ? '' : '/'}${str}`;
          };

          return {
            id: 'scan_' + s.soil_scan_id,
            soil_scan_id: s.soil_scan_id,
            soil_id: s.soil_id,
            user_id: s.user_id,
            location_id: s.location_id,
            location: s.location,
            hasPin: hasPin,
            munsellCode: s.full_code || s.soil?.full_code || '10YR 4/1',
            hue: s.hue || s.soil?.hue || '10YR',
            colorName: s.color_name || s.soil?.effective_soil_name || s.soil?.soil_name || 'Determined Soil',
            hexCode: s.hex_color || s.soil?.effective_hex_color || s.soil?.hex_color || '#8f694e',
            description: s.description || s.soil?.effective_description || 'Field scan record',
            scanNote: resolvedScanNote,
            scansCount: s.soil?.scan_count || 1,
            scanImage: fixMediaUrl(s.scan_image_url || s.scan_image),
            beforeAfterImage: fixMediaUrl(s.before_after_image_url || s.before_after_image),
            beforeAfterImageUrl: fixMediaUrl(s.before_after_image_url || s.before_after_image),
            regions: (() => {
              let raw = Array.isArray(s.regions) && s.regions.length > 0
                ? s.regions
                : (Array.isArray(s.regions_data) && s.regions_data.length > 0 ? s.regions_data : []);
              if ((!raw || raw.length === 0) && typeof s.regions === 'string') {
                try { raw = JSON.parse(s.regions); } catch (e) {}
              }
              if ((!raw || raw.length === 0) && typeof s.regions_data === 'string') {
                try { raw = JSON.parse(s.regions_data); } catch (e) {}
              }
              if (!Array.isArray(raw)) return [];
              return raw.map((r) => ({
                ...r,
                hex: r.hex || r.hex_color || (Array.isArray(r.rgb) && r.rgb.length >= 3
                  ? `#${r.rgb.map(v => Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2, '0')).join('')}`
                  : null) || null,
                munsell_code: r.munsell_code || r.munsell || r.notation || null,
                percent: r.percent !== undefined
                  ? r.percent
                  : (r.proportion !== undefined ? Math.round(r.proportion * 100) : undefined),
              }));
            })(),
            rawDate: s.scan_date || s.created_at,
            scanTime: s.scan_time || '',
            dateGroup: s.date_group || 'Earlier',
            platform: s.platform || 'website',
            syncState: 'synced',
            isOffline: false,
            soil_organic_index: s.soil?.soil_organic_index,
            soil_type: s.soil?.effective_soil_type || s.soil?.soil_type,
            nearMatchRating: s.confidence ? `${s.confidence}%` : '95%',
          };
        });

        const merged = await mergeServerScans(mapped);
        setScanHistory(merged);
      }
    } catch (e) {
      console.log('Error loading scan history from server:', e?.message);
    }
  }, [user?.user_id, user?.id]);

  // Fetch complete 207 Munsell Color Chart directory from backend
  const loadMunsellColors = useCallback(async () => {
    try {
      setIsLoadingMunsell(true);
      const res = await api.get('/munsell-colors');
      if (res.data?.status === 'success' && Array.isArray(res.data?.data)) {
        setMunsellList(res.data.data);
        if (res.data?.total_all) {
          setMunsellTotalCount(res.data.total_all);
        }
        try {
          const munsellCachePath = `${RNFS.DocumentDirectoryPath}/munsell_cache.json`;
          await RNFS.writeFile(munsellCachePath, JSON.stringify(res.data.data), 'utf8');
        } catch (_) {}
      }
    } catch (err) {
      console.log('Error loading Munsell colors:', err?.message);
    } finally {
      setIsLoadingMunsell(false);
    }
  }, []);

  // Sync Munsell colors on initial load and when switching to Munsell tab
  useEffect(() => {
    loadMunsellColors();
  }, [loadMunsellColors]);

  // Instant client-side filtering across all 207 colors
  const filteredMunsell = useMemo(() => {
    let list = munsellList;
    if (munsellSelectedHue && munsellSelectedHue !== 'All') {
      list = list.filter((c) => (c.hue || '').toUpperCase() === munsellSelectedHue.toUpperCase());
    }
    if (munsellSearch && munsellSearch.trim() !== '') {
      const q = munsellSearch.trim().toLowerCase();
      list = list.filter((c) =>
        (c.colorName || '').toLowerCase().includes(q) ||
        (c.munsellCode || '').toLowerCase().includes(q) ||
        (c.hue || '').toLowerCase().includes(q) ||
        (c.description || '').toLowerCase().includes(q)
      );
    }
    return list;
  }, [munsellList, munsellSelectedHue, munsellSearch]);

  // Munsell Directory Pagination (Synced with Reference Design)
  const munsellTotalPages = Math.max(1, Math.ceil(filteredMunsell.length / munsellRowsPerPage));
  const munsellStartItem = filteredMunsell.length === 0 ? 0 : (munsellPage - 1) * munsellRowsPerPage + 1;
  const munsellEndItem = Math.min(filteredMunsell.length, munsellPage * munsellRowsPerPage);

  // Auto-clamp page if bounds change
  useEffect(() => {
    if (munsellPage > munsellTotalPages) {
      setMunsellPage(munsellTotalPages);
    }
  }, [munsellPage, munsellTotalPages]);

  // Paginated Munsell cards slice
  const paginatedMunsell = useMemo(() => {
    const start = (munsellPage - 1) * munsellRowsPerPage;
    return filteredMunsell.slice(start, start + munsellRowsPerPage);
  }, [filteredMunsell, munsellPage, munsellRowsPerPage]);

  // Exact 3 numeric page buttons calculation matching backend & reference image design
  const munsellPageNumbers = useMemo(() => {
    if (munsellTotalPages <= 3) {
      return Array.from({ length: munsellTotalPages }, (_, i) => i + 1);
    }
    if (munsellPage === 1) {
      return [1, 2, 3];
    }
    if (munsellPage === munsellTotalPages) {
      return [munsellTotalPages - 2, munsellTotalPages - 1, munsellTotalPages];
    }
    return [munsellPage - 1, munsellPage, munsellPage + 1];
  }, [munsellTotalPages, munsellPage]);

  // Initial load
  useEffect(() => {
    loadMostScanned(selectedHue, searchQuery);
  }, [selectedHue, loadMostScanned, searchQuery]);

  // Real-time synchronization heartbeat (polls every 8 seconds)
  useEffect(() => {
    loadScanHistory();
    const interval = setInterval(() => {
      loadScanHistory();
    }, 8000);
    return () => clearInterval(interval);
  }, [loadScanHistory]);

  // Sync on navigation focus (e.g., returning from ScanSoil screen)
  useEffect(() => {
    if (!navigation || typeof navigation.addListener !== 'function') return;
    const unsubscribe = navigation.addListener('focus', () => {
      loadScanHistory();
      loadMostScanned(selectedHue, searchQuery);
    });
    return unsubscribe;
  }, [navigation, loadScanHistory, loadMostScanned, selectedHue, searchQuery]);

  // Sync on app foregrounding (e.g., user scanned on website and resumed mobile app)
  useEffect(() => {
    const subscription = AppState.addEventListener('change', (nextAppState) => {
      if (nextAppState === 'active') {
        loadScanHistory();
        loadMostScanned(selectedHue, searchQuery);
      }
    });
    return () => subscription.remove();
  }, [loadScanHistory, loadMostScanned, selectedHue, searchQuery]);

  // Pull-to-refresh handler
  const handlePullRefresh = async () => {
    setIsRefreshing(true);
    try {
      await Promise.all([
        loadMostScanned(selectedHue, searchQuery),
        loadScanHistory(),
      ]);
    } finally {
      setIsRefreshing(false);
    }
  };

  const handleSearchSubmit = () => {
    loadMostScanned(selectedHue, searchQuery);
  };

  const handleTabPress = (tab) => {
    if (tab === 'ScanSoil') {
      navigation.navigate('ScanSoil');
      return;
    }
    setActiveTab(tab);
  };

  // Local client-side filter fallback ensuring instant Hue responsiveness
  const activeMunsellSource = munsellList && munsellList.length > 0 ? munsellList : soilCards;
  const filteredSoils = (
    selectedHue === 'Recommend' || selectedHue === 'All'
      ? activeMunsellSource
      : (activeMunsellSource.filter((item) => item.hue === selectedHue).length > 0
          ? activeMunsellSource.filter((item) => item.hue === selectedHue)
          : INITIAL_SOIL_CARDS.filter((item) => item.hue === selectedHue))
  ).filter((item) => {
    const matchesSearch =
      searchQuery.trim() === '' ||
      (item.colorName || '').toLowerCase().includes(searchQuery.toLowerCase()) ||
      (item.munsellCode || '').toLowerCase().includes(searchQuery.toLowerCase()) ||
      (item.hue || '').toLowerCase().includes(searchQuery.toLowerCase());
    return matchesSearch;
  });

  // 2-card grid display by default, expandable via "See more"
  const displayedSoils = showAllCards ? filteredSoils : filteredSoils.slice(0, 2);

  return (
    <View style={styles.screenContainer}>
      <StatusBar barStyle="dark-content" backgroundColor="#f6faf7" />

      {/* Main Tab Content */}
      {activeTab === 'Home' && (
        <ScrollView
          contentContainerStyle={styles.homeScrollContent}
          showsVerticalScrollIndicator={false}
          keyboardShouldPersistTaps="handled"
          refreshControl={
            <RefreshControl
              refreshing={isRefreshing}
              onRefresh={handlePullRefresh}
              colors={['#22c55e']}
              tintColor="#22c55e"
            />
          }
        >
          {/* Header Bar matching Image 1 layout */}
          <View style={styles.headerRow}>
            <View>
              <Text style={styles.headerGreeting}>Welcome</Text>
              <Text style={styles.headerTitle}>{userFirstName}</Text>
            </View>

            <TouchableOpacity
              style={styles.bellButton}
              activeOpacity={0.7}
              onPress={() => Alert.alert('Soil Alerts', 'All field sensors and scan logs are active.')}
            >
              <BellIcon size={22} color="#0f172a" hasUnread={true} />
            </TouchableOpacity>
          </View>

          {/* Search Bar matching Image 1 */}
          <View style={styles.searchBarContainer}>
            <TextInput
              style={styles.searchInput}
              placeholder="Search Soil Color, Hue or Code..."
              placeholderTextColor="#94a3b8"
              value={searchQuery}
              onChangeText={setSearchQuery}
              onSubmitEditing={handleSearchSubmit}
              returnKeyType="search"
            />
            <TouchableOpacity
              style={styles.searchBtn}
              activeOpacity={0.85}
              onPress={handleSearchSubmit}
            >
              <SearchIcon size={16} color="#ffffff" />
            </TouchableOpacity>
          </View>

          {/* 6 Munsell Hue Filter Chips */}
          <View style={styles.categoriesWrapper}>
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.categoriesContent}
            >
              {HUE_CATEGORIES.map((hue) => {
                const isActive = selectedHue === hue;
                return (
                  <TouchableOpacity
                    key={hue}
                    style={[styles.categoryChip, isActive && styles.categoryChipActive]}
                    onPress={() => setSelectedHue(hue)}
                    activeOpacity={0.75}
                  >
                    <Text
                      style={[
                        styles.categoryChipText,
                        isActive && styles.categoryChipTextActive,
                      ]}
                    >
                      {hue}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </ScrollView>
          </View>

          {/* Section Header: "Munsell Color Chart" with "See more ->" */}
          <View style={styles.sectionHeaderRow}>
            <Text style={styles.sectionHeaderTitle}>Munsell Color Chart</Text>
            <TouchableOpacity
              activeOpacity={0.7}
              onPress={() => setActiveTab('Munsell')}
              style={styles.seeMoreHeaderBtn}
            >
              <Text style={styles.seeMoreHeaderText}>See more →</Text>
            </TouchableOpacity>
          </View>

          {/* 2-Column Grid of Soil Cards (Database-Driven, with One-by-One Carousel in Image Part) */}
          <View style={styles.gridContainer}>
            {displayedSoils.map((item) => {
              return (
                <View key={item.id} style={styles.card}>
                  {/* Munsell Color Swatch (Pure Color Only, Not Real Soil) */}
                  <TouchableOpacity
                    activeOpacity={0.88}
                    onPress={() => setDetailSoil({ ...item, isMunsellColor: true, isScanRecord: false })}
                    style={[
                      styles.soilImageContainer,
                      { backgroundColor: item.hexCode || item.hex || '#794c22' },
                    ]}
                  >
                    <View style={styles.swatchHexBadge}>
                      <View
                        style={[
                          styles.miniColorDot,
                          { backgroundColor: item.hexCode || item.hex || '#794c22' },
                        ]}
                      />
                      <Text style={styles.swatchHexText}>
                        {item.hexCode
                          ? item.hexCode.toUpperCase()
                          : item.hex
                          ? item.hex.toUpperCase()
                          : '#794C22'}
                      </Text>
                    </View>
                  </TouchableOpacity>

                  {/* Card Info */}
                  <View style={styles.cardInfo}>
                    {/* Soil Color Name */}
                    <Text style={styles.cardTitle} numberOfLines={1}>
                      {item.colorName}
                    </Text>

                    {/* Description Field */}
                    <Text style={styles.cardDescription} numberOfLines={2}>
                      {item.description}
                    </Text>

                    {/* Full Munsell Code & View Button Row */}
                    <View style={styles.cardBottomRow}>
                      <View style={styles.cardCodeBlock}>
                        <Text style={styles.munsellCodeLabel}>FULL MUNSELL CODE</Text>
                        <Text style={styles.cardMunsellCode}>{item.munsellCode}</Text>
                      </View>

                      {/* "View" Action Button */}
                      <TouchableOpacity
                        style={styles.viewBtn}
                        activeOpacity={0.8}
                        onPress={() => setDetailSoil({ ...item, isMunsellColor: true, isScanRecord: false })}
                      >
                        <Text style={styles.viewBtnText}>View</Text>
                      </TouchableOpacity>
                    </View>
                  </View>
                </View>
              );
            })}
          </View>



          {/* "Scan History" Section - Synchronized & Date-Grouped */}
          <View style={styles.recentSection}>
            <View style={styles.scanHistoryHeaderRow}>
              <Text style={styles.recentSectionTitle}>Scan History</Text>
              <Text style={styles.scanHistoryCountText}>
                {scanHistory.length} {scanHistory.length === 1 ? 'record' : 'records'}
              </Text>
            </View>

            {groupedScans.length === 0 ? (
              <View style={styles.emptyHistoryBox}>
                <Text style={styles.emptyHistoryIcon}>🌱</Text>
                <Text style={styles.emptyHistoryTitle}>No Scans Yet</Text>
                <Text style={styles.emptyHistoryDesc}>
                  Every soil scan you perform on the mobile app or website will appear here in real time.
                </Text>
              </View>
            ) : (
              <View style={styles.dateGroupedList}>
                {groupedScans.map((group) => (
                  <View key={'group_' + group.date} style={styles.dateGroupBlock}>
                    {/* Date Label Header appearing ONCE above its group (Text only, gray) */}
                    <View style={styles.dateLabelRow}>
                      <Text style={styles.dateLabelText}>{group.date}</Text>
                    </View>

                    {/* Cards for this Date Group */}
                    <View style={styles.recentList}>
                      {group.data.map((item) => {
                        const hasCameraPhoto =
                          item.scanImage &&
                          typeof item.scanImage === 'string' &&
                          item.scanImage.length > 5;
                        const itemHex = item.hexCode || item.hex || '#794c22';
                        return (
                          <TouchableOpacity
                            key={item.id}
                            style={styles.recentCard}
                            activeOpacity={0.85}
                            onPress={() => setDetailSoil({ ...item, isScanRecord: true, isMunsellColor: false })}
                          >
                            {/* Thumbnail: Camera photo if user scanned, otherwise pure Munsell color swatch */}
                            <View
                              style={[
                                styles.recentThumbBox,
                                !hasCameraPhoto && { backgroundColor: itemHex },
                              ]}
                            >
                              {hasCameraPhoto ? (
                                <FallbackImage
                                  uri={item.scanImage}
                                  style={styles.recentSoilThumb}
                                  resizeMode="cover"
                                />
                              ) : (
                                <View
                                  style={{
                                    width: '100%',
                                    height: '100%',
                                    backgroundColor: itemHex,
                                  }}
                                />
                              )}
                            </View>

                            {/* Details */}
                            <View style={styles.recentDetails}>
                              <View style={styles.recentTitleRow}>
                                <Text style={styles.recentName} numberOfLines={1}>
                                  {item.colorName}
                                </Text>
                                <Text style={styles.scanTimeText}>
                                  {item.scanTime ? `${item.scanTime} · ` : ''}
                                  {item.platform === 'mobile' ? 'App' : 'Web'}
                                </Text>
                              </View>

                              <Text style={styles.recentDesc} numberOfLines={2}>
                                {item.description}
                              </Text>

                              <View style={styles.recentCodeRow}>
                                <Text style={styles.recentCode}>{item.munsellCode}</Text>
                                {item.syncState === 'queued' || item.isOffline ? (
                                  <View style={styles.queuedBadge}>
                                    <Text style={styles.queuedBadgeText}>⚡ Queued</Text>
                                  </View>
                                ) : (
                                  <View style={styles.syncedAdminBadge}>
                                    <Text style={styles.syncedAdminBadgeText}>☁️ Saved</Text>
                                  </View>
                                )}
                              </View>
                            </View>

                            {/* Quick View Button */}
                            <View style={styles.recentViewPill}>
                              <Text style={styles.recentViewPillText}>View</Text>
                            </View>
                          </TouchableOpacity>
                        );
                      })}
                    </View>
                  </View>
                ))}
              </View>
            )}

            {/* Pagination Controls (5 rows per page) matching image design */}
            {totalPages > 1 && (
              <View style={styles.munsellPaginationCard}>
                {/* TOP ROW: ROWS PER PAGE & RANGE */}
                <View style={styles.munsellPaginationTopRow}>
                  <View style={styles.munsellPaginationLeft}>
                    <Text style={styles.munsellRowsPerPageLabel}>Rows per page:</Text>
                    <View style={styles.munsellRowsSelectBtn}>
                      <Text style={styles.munsellRowsSelectText}>5</Text>
                    </View>
                  </View>
                  <Text style={styles.munsellRangeText}>
                    {scanHistory.length === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1}–{Math.min(scanHistory.length, currentPage * rowsPerPage)} of {scanHistory.length}
                  </Text>
                </View>

                {/* SEPARATOR DIVIDER */}
                <View style={styles.munsellPaginationDivider} />

                {/* BOTTOM ROW: PREV, NUMERIC PAGES, NEXT */}
                <View style={styles.munsellPaginationRight}>
                  {/* Prev Button */}
                  <TouchableOpacity
                    style={[
                      styles.munsellNavBtn,
                      currentPage === 1 && styles.munsellNavBtnDisabled,
                    ]}
                    disabled={currentPage === 1}
                    onPress={() => setCurrentPage((p) => Math.max(1, p - 1))}
                    activeOpacity={0.7}
                  >
                    <Text
                      style={[
                        styles.munsellNavBtnText,
                        currentPage === 1 && styles.munsellNavBtnTextDisabled,
                      ]}
                    >
                      Prev
                    </Text>
                  </TouchableOpacity>

                  {/* Page Number Buttons */}
                  {pageNumbers.map((num) => {
                    const isActive = num === currentPage;
                    return (
                      <TouchableOpacity
                        key={'page_' + num}
                        style={[
                          styles.munsellPageNumBtn,
                          isActive && styles.munsellPageNumBtnActive,
                        ]}
                        onPress={() => setCurrentPage(num)}
                        activeOpacity={0.7}
                      >
                        <Text
                          style={[
                            styles.munsellPageNumText,
                            isActive && styles.munsellPageNumTextActive,
                          ]}
                        >
                          {num}
                        </Text>
                      </TouchableOpacity>
                    );
                  })}

                  {/* Next Button */}
                  <TouchableOpacity
                    style={[
                      styles.munsellNavBtn,
                      currentPage === totalPages && styles.munsellNavBtnDisabled,
                    ]}
                    disabled={currentPage === totalPages}
                    onPress={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
                    activeOpacity={0.7}
                  >
                    <Text
                      style={[
                        styles.munsellNavBtnText,
                        currentPage === totalPages && styles.munsellNavBtnTextDisabled,
                      ]}
                    >
                      Next
                    </Text>
                  </TouchableOpacity>
                </View>
              </View>
            )}
          </View>

          {/* Bottom spacing for floating navbar */}
          <View style={{ height: 100 }} />
        </ScrollView>
      )}

      {/* Tab: Munsell Color Chart Directory View */}
      {activeTab === 'Munsell' && (
        <ScrollView
          contentContainerStyle={styles.munsellScrollContent}
          showsVerticalScrollIndicator={false}
          keyboardShouldPersistTaps="handled"
        >
          {/* Top Header Row with Title and Live Total Count Badge */}
          <View style={styles.munsellHeaderRow}>
            <View style={{ flex: 1, paddingRight: 8 }}>
              <Text style={styles.munsellPageTitle}>Munsell Color Chart</Text>
              <Text style={styles.munsellPageSubtitle}>
                Official Soil Classification Directory
              </Text>
            </View>
            <View style={styles.munsellCountBadge}>
              <Text style={styles.munsellCountBadgeText}>
                {munsellTotalCount} Colors
              </Text>
            </View>
          </View>

          {/* Search Bar matching the App Design System */}
          <View style={styles.munsellSearchRow}>
            <View style={styles.munsellSearchInputWrapper}>
              <SearchIcon size={18} color="#94a3b8" />
              <TextInput
                style={styles.munsellSearchInputField}
                placeholder="Search soil color, hue, or code..."
                placeholderTextColor="#94a3b8"
                value={munsellSearch}
                onChangeText={(text) => {
                  setMunsellSearch(text);
                  setMunsellPage(1);
                }}
                autoCapitalize="none"
                returnKeyType="search"
              />
              {munsellSearch.length > 0 && (
                <TouchableOpacity
                  onPress={() => {
                    setMunsellSearch('');
                    setMunsellPage(1);
                  }}
                  hitSlop={{ top: 15, bottom: 15, left: 15, right: 15 }}
                  style={styles.munsellClearBtn}
                  activeOpacity={0.7}
                >
                  <Text style={styles.munsellClearBtnText}>✕</Text>
                </TouchableOpacity>
              )}
            </View>
          </View>

          {/* Quick Hue Filter Chips */}
          <View style={styles.munsellCategoryContainer}>
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.categoryScroll}
            >
              {['All', '10YR', '7.5YR', '5YR', '2.5YR', '10R', '2.5Y'].map((hue) => {
                const isActive = munsellSelectedHue === hue;
                return (
                  <TouchableOpacity
                    key={'munsell_tab_hue_' + hue}
                    style={[
                      styles.categoryChip,
                      isActive && styles.categoryChipActive,
                    ]}
                    activeOpacity={0.75}
                    onPress={() => {
                      setMunsellSelectedHue(hue);
                      setMunsellPage(1);
                    }}
                  >
                    <Text
                      style={[
                        styles.categoryChipText,
                        isActive && styles.categoryChipTextActive,
                      ]}
                    >
                      {hue}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </ScrollView>
          </View>

          {/* Result Count and Status */}
          <View style={styles.munsellResultMetaRow}>
            <Text style={styles.munsellResultMetaText}>
              Showing {munsellStartItem}–{munsellEndItem} of {filteredMunsell.length} colors
              {munsellSelectedHue !== 'All' ? ` • Hue: ${munsellSelectedHue}` : ''}
              {munsellSearch.trim() !== '' ? ` • "${munsellSearch.trim()}"` : ''}
            </Text>
          </View>

          {/* Loading Indicator */}
          {isLoadingMunsell && munsellList.length === 0 ? (
            <View style={styles.munsellLoadingBox}>
              <ActivityIndicator size="large" color="#22c55e" />
              <Text style={styles.munsellLoadingText}>Loading Munsell Directory...</Text>
            </View>
          ) : filteredMunsell.length === 0 ? (
            /* Empty Search State */
            <View style={styles.emptyHistoryBox}>
              <Text style={styles.emptyHistoryIcon}>🔍</Text>
              <Text style={styles.emptyHistoryTitle}>No Matching Colors</Text>
              <Text style={styles.emptyHistoryDesc}>
                No soil colors found matching "{munsellSearch}". Try searching with another hue or color name.
              </Text>
            </View>
          ) : (
            /* 2-Card Grid (Exact same design from Home page) */
            <View style={styles.gridContainer}>
              {paginatedMunsell.map((item) => (
                <View key={item.id} style={styles.card}>
                  {/* Munsell Color Swatch (Pure Color Only, Not Real Soil) */}
                  <TouchableOpacity
                    activeOpacity={0.88}
                    onPress={() => setDetailSoil({ ...item, isMunsellColor: true, isScanRecord: false })}
                    style={[
                      styles.soilImageContainer,
                      { backgroundColor: item.hexCode || item.hex || '#794c22' },
                    ]}
                  >
                    <View style={styles.swatchHexBadge}>
                      <View
                        style={[
                          styles.miniColorDot,
                          { backgroundColor: item.hexCode || item.hex || '#794c22' },
                        ]}
                      />
                      <Text style={styles.swatchHexText}>
                        {item.hexCode
                          ? item.hexCode.toUpperCase()
                          : item.hex
                          ? item.hex.toUpperCase()
                          : '#794C22'}
                      </Text>
                    </View>
                  </TouchableOpacity>

                  {/* Card Info */}
                  <View style={styles.cardInfo}>
                    {/* Soil Color Name */}
                    <Text style={styles.cardTitle} numberOfLines={1}>
                      {item.colorName}
                    </Text>

                    {/* Description */}
                    <Text style={styles.cardDescription} numberOfLines={2}>
                      {item.description}
                    </Text>

                    {/* Full Munsell Code & View Button Row */}
                    <View style={styles.cardBottomRow}>
                      <View style={styles.cardCodeBlock}>
                        <Text style={styles.munsellCodeLabel}>FULL MUNSELL CODE</Text>
                        <Text style={styles.cardMunsellCode}>{item.munsellCode}</Text>
                      </View>

                      {/* View Action Button */}
                      <TouchableOpacity
                        style={styles.viewBtn}
                        activeOpacity={0.8}
                        onPress={() => setDetailSoil({ ...item, isMunsellColor: true, isScanRecord: false })}
                      >
                        <Text style={styles.viewBtnText}>View</Text>
                      </TouchableOpacity>
                    </View>
                  </View>
                </View>
              ))}
            </View>
          )}

          {/* PAGINATION FOOTER matching reference image */}
          {filteredMunsell.length > 0 && (
            <View style={styles.munsellPaginationCard}>
              {/* TOP ROW: ROWS PER PAGE (LEFT) & TOTAL RANGE (RIGHT) */}
              <View style={styles.munsellPaginationTopRow}>
                <View style={styles.munsellPaginationLeft}>
                  <Text style={styles.munsellRowsPerPageLabel}>Rows per page:</Text>

                  {/* Rows per page dropdown trigger */}
                  <TouchableOpacity
                    style={styles.munsellRowsSelectBtn}
                    onPress={() => setShowRowsPerPageModal(true)}
                    activeOpacity={0.7}
                    hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}
                  >
                    <Text style={styles.munsellRowsSelectText}>{munsellRowsPerPage}</Text>
                    <Text style={styles.munsellRowsSelectChevron}>⌄</Text>
                  </TouchableOpacity>
                </View>

                {/* Range text */}
                <Text style={styles.munsellRangeText}>
                  {munsellStartItem}–{munsellEndItem} of {filteredMunsell.length}
                </Text>
              </View>

              {/* SEPARATOR DIVIDER */}
              <View style={styles.munsellPaginationDivider} />

              {/* BOTTOM ROW: PREV, 3 NUMERIC BUTTONS, NEXT */}
              <View style={styles.munsellPaginationRight}>
                {/* PREV BUTTON */}
                <TouchableOpacity
                  style={[
                    styles.munsellNavBtn,
                    munsellPage === 1 && styles.munsellNavBtnDisabled,
                  ]}
                  disabled={munsellPage === 1}
                  onPress={() => setMunsellPage((p) => Math.max(1, p - 1))}
                  activeOpacity={0.7}
                >
                  <Text
                    style={[
                      styles.munsellNavBtnText,
                      munsellPage === 1 && styles.munsellNavBtnTextDisabled,
                    ]}
                  >
                    Prev
                  </Text>
                </TouchableOpacity>

                {/* EXACT 3 NUMERIC PAGE BUTTONS */}
                {munsellPageNumbers.map((num) => {
                  const isActive = num === munsellPage;
                  return (
                    <TouchableOpacity
                      key={'munsell_page_' + num}
                      style={[
                        styles.munsellPageNumBtn,
                        isActive && styles.munsellPageNumBtnActive,
                      ]}
                      onPress={() => setMunsellPage(num)}
                      activeOpacity={0.7}
                    >
                      <Text
                        style={[
                          styles.munsellPageNumText,
                          isActive && styles.munsellPageNumTextActive,
                        ]}
                      >
                        {num}
                      </Text>
                    </TouchableOpacity>
                  );
                })}

                {/* NEXT BUTTON */}
                <TouchableOpacity
                  style={[
                    styles.munsellNavBtn,
                    munsellPage === munsellTotalPages && styles.munsellNavBtnDisabled,
                  ]}
                  disabled={munsellPage === munsellTotalPages}
                  onPress={() => setMunsellPage((p) => Math.min(munsellTotalPages, p + 1))}
                  activeOpacity={0.7}
                >
                  <Text
                    style={[
                      styles.munsellNavBtnText,
                      munsellPage === munsellTotalPages && styles.munsellNavBtnTextDisabled,
                    ]}
                  >
                    Next
                  </Text>
                </TouchableOpacity>
              </View>
            </View>
          )}

          <View style={{ height: 100 }} />
        </ScrollView>
      )}

      {/* Tab: Live Leaflet OpenStreetMap View with NetInfo Offline Handling */}
      {activeTab === 'Map' && (
        <View style={styles.mapScreenContainer}>
          <StatusBar barStyle="dark-content" backgroundColor="transparent" translucent={true} />

          {/* Step 3: Conditionally render the live WebView map or fallback view */}
          {isConnected || hasLoadedOnce ? (
            <WebView
              ref={webViewRef}
              originWhitelist={['*']}
              source={{
                html: leafletMapHtml,
                baseUrl: serverBase,
              }}
              userAgent="Mozilla/5.0 (Linux; Android 13; Mobile) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36 AgriCo/1.0"
              style={styles.mapWebView}
              javaScriptEnabled={true}
              domStorageEnabled={true}
              mixedContentMode="always"
              allowsInlineMediaPlayback={true}
              startInLoadingState={true}
              renderLoading={() => (
                <View style={styles.mapLoadingOverlay}>
                  <ActivityIndicator color="#22c55e" size="large" />
                </View>
              )}
              onMessage={(event) => {
                try {
                  const data = JSON.parse(event.nativeEvent.data);
                  if (data?.type === 'MAP_MODAL_STATE') {
                    setIsMapModalOpen(Boolean(data.isOpen));
                  }
                } catch (err) {}
              }}
              onLoadEnd={() => {
                setHasLoadedOnce(true);
                if (adminLocations.length > 0 && webViewRef.current) {
                  webViewRef.current.postMessage(
                    JSON.stringify({ type: 'SET_LOCATIONS', locations: adminLocations })
                  );
                }
              }}
            />
          ) : (
            /* Step 4: Offline fallback UI covering the exact screen area */
            <View style={styles.offlineFallbackContainer}>
              <View style={styles.offlineIconCircle}>
                <WifiOffIcon size={44} color="#64748b" />
              </View>
              <Text style={styles.offlineTitle}>Map requires an internet connection</Text>
              <Text style={styles.offlineSubtitle}>
                Live Leaflet tiles and satellite imagery cannot be downloaded without an active connection.
              </Text>
              <TouchableOpacity
                style={styles.offlineRetryBtn}
                onPress={handleRetryConnection}
                disabled={isCheckingConnection}
                activeOpacity={0.8}
              >
                {isCheckingConnection ? (
                  <ActivityIndicator size="small" color="#ffffff" />
                ) : (
                  <Text style={styles.offlineRetryBtnText}>Retry Connection</Text>
                )}
              </TouchableOpacity>
            </View>
          )}

          {/* Step 5: Handle connection loss mid-session (Toast / Floating warning banner) */}
          {!isConnected && hasLoadedOnce && (
            <View style={styles.offlineMidSessionBanner}>
              <Text style={styles.offlineMidSessionText}>
                ⚠️ You're offline — map may not update
              </Text>
              <TouchableOpacity
                style={styles.offlineSmallRetry}
                onPress={handleRetryConnection}
                activeOpacity={0.7}
              >
                <Text style={styles.offlineSmallRetryText}>
                  {isCheckingConnection ? '...' : 'Retry'}
                </Text>
              </TouchableOpacity>
            </View>
          )}

          {/* Floating Search Bar at the Top (Styled strictly based on the Home Screen Search Bar) */}
          {!isMapModalOpen && (
            <View style={styles.mapSearchHeader}>
              <View style={styles.mapSearchBarContainer}>
                <TextInput
                  style={styles.mapSearchInput}
                  placeholder="Search Location, Barangay, or Soil..."
                  placeholderTextColor="#94a3b8"
                  value={mapSearchText}
                  onChangeText={setMapSearchText}
                  onSubmitEditing={handleMapSearchSubmit}
                  returnKeyType="search"
                />
                <TouchableOpacity
                  style={styles.mapSearchBtn}
                  activeOpacity={0.85}
                  onPress={handleMapSearchSubmit}
                >
                  <SearchIcon size={16} color="#ffffff" />
                </TouchableOpacity>
              </View>
            </View>
          )}
        </View>
      )}

      {/* Tab: Profile View (Redesigned matching reference layout) */}
      {activeTab === 'Profile' && (
        <View style={{ flex: 1 }}>
          <ProfileView
            user={user}
            onBack={() => setActiveTab('Home')}
            logout={logout}
            scanHistory={scanHistory}
            onUpdateUser={checkAuth}
            onTriggerUpdate={(updateData) => {
              setAppUpdateInfo(updateData);
              setShowUpdateModal(true);
            }}
          />
        </View>
      )}

      {/* Soil Color Detail Modal (Soil Record Details - Matches Website Modal Exactly) */}
      {detailSoil ? (
        <Modal
          visible={detailSoil !== null}
          animationType="fade"
          transparent={true}
          onRequestClose={() => setDetailSoil(null)}
        >
          <View style={styles.modalBackdrop}>
            <Pressable
              style={StyleSheet.absoluteFill}
              onPress={() => setDetailSoil(null)}
            />
            <View style={styles.soilModalCard} pointerEvents="auto">
              {/* Modal Header: Title & Circular Close Button */}
              <View style={styles.soilModalHeaderRow}>
                <Text style={styles.soilModalTitle}>
                  {isMunsellDetail ? 'Munsell Color Details' : 'Soil Record Details'}
                </Text>
                <TouchableOpacity
                  style={styles.soilModalCloseBtn}
                  onPress={() => setDetailSoil(null)}
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
                    { backgroundColor: detailMunsellMatch?.hexCode || detailSoil.hexCode || detailSoil.hex || '#8F694E' },
                  ]}
                >
                  {/* Left Avatar Snippet (Website #modalSoilSnippetAvatarContainer) - Scan History Only */}
                  {!isMunsellDetail && (
                    <View style={styles.soilBannerAvatarContainer}>
                      {detailSoil.scanImage ? (
                        <FallbackImage
                          uri={detailSoil.scanImage}
                          style={styles.soilBannerAvatarImg}
                          resizeMode="cover"
                        />
                      ) : detailSoil.thumbnail ? (
                        <Image
                          source={
                            typeof detailSoil.thumbnail === 'number'
                              ? detailSoil.thumbnail
                              : { uri: detailSoil.thumbnail }
                          }
                          style={styles.soilBannerAvatarImg}
                          resizeMode="cover"
                        />
                      ) : (
                        <View
                          style={{
                            width: '100%',
                            height: '100%',
                            backgroundColor: detailMunsellMatch?.hexCode || detailSoil.hexCode || detailSoil.hex || '#8F694E',
                          }}
                        />
                      )}
                      {/* Detected green ROI bounding box indicating detected color region */}
                      {avatarRoiBox && (
                        <View
                          style={{
                            position: 'absolute',
                            left: `${avatarRoiBox.leftPct}%`,
                            top: `${avatarRoiBox.topPct}%`,
                            width: `${avatarRoiBox.widthPct}%`,
                            height: `${avatarRoiBox.heightPct}%`,
                            borderWidth: 1.5,
                            borderColor: detailNormBoxes[0]?.hex || '#4ade80',
                            borderRadius: 2,
                          }}
                        />
                      )}
                    </View>
                  )}

                  {/* Center Code Display & Percentage/Subtitle (Website #modalSwatchBlock) */}
                  <View style={styles.soilBannerCodeBlock}>
                    <Text style={styles.soilBannerMunsellCode}>
                      {detailMunsellMatch?.munsellCode || detailSoil.munsellCode || detailSoil.fullCode || '7.5YR 5/3'}
                    </Text>
                    {detailNormRegions.length > 1 ? (
                      <Text style={styles.soilBannerPercentageText}>
                        Soil Percentage ({(() => {
                          const first = detailNormRegions[0];
                          if (first && first.percent !== undefined) return Math.round(first.percent);
                          if (first && first.proportion !== undefined) return Math.round(first.proportion * 100);
                          return detailSoil.soilPercentage || 60;
                        })()}%)
                      </Text>
                    ) : (
                      <Text style={styles.soilBannerSubLabel} numberOfLines={1}>
                        {detailMunsellMatch?.colorName || detailSoil.colorName || detailSoil.name || 'Determined Soil'}
                      </Text>
                    )}
                  </View>

                  {/* Right Hex Pill (Website #modalHexPill) */}
                  <View style={styles.soilBannerHexPill}>
                    <Text style={styles.soilBannerHexText}>
                      {(detailMunsellMatch?.hexCode || detailSoil.hexCode || detailSoil.hex || '#8F694E').toUpperCase()}
                    </Text>
                  </View>
                </View>

                {/* Metrics Container (2-Column Paired Grids matching website .soil-modal-grid-2) */}
                {/* Row 1: Color Name & Full Code */}
                <View style={styles.soilGridRow2}>
                  <View style={styles.soilModalGridCard}>
                    <Text style={styles.soilModalLabel}>COLOR NAME</Text>
                    <Text style={styles.soilModalValue} numberOfLines={1}>
                      {detailMunsellMatch?.colorName || detailSoil.colorName || detailSoil.name || 'Brown'}
                    </Text>
                  </View>
                  <View style={styles.soilModalGridCard}>
                    <Text style={styles.soilModalLabel}>FULL CODE</Text>
                    <Text style={styles.soilModalValue} numberOfLines={1}>
                      {detailMunsellMatch?.munsellCode || detailSoil.fullCode || detailSoil.munsellCode || '7.5YR 5/3'}
                    </Text>
                  </View>
                </View>

                {/* Row 2: Soil Organic & Soil Type */}
                <View style={styles.soilGridRow2}>
                  <View style={styles.soilModalGridCard}>
                    <Text style={styles.soilModalLabel}>SOIL ORGANIC</Text>
                    <Text style={styles.soilModalValue} numberOfLines={2}>
                      {detailMunsellMatch?.soil_organic_index || detailSoil.soil_organic_index || detailSoil.organicIndex || 'Moderate (2.2% – 3.7%)'}
                    </Text>
                  </View>
                  <View style={styles.soilModalGridCard}>
                    <Text style={styles.soilModalLabel}>SOIL TYPE</Text>
                    <Text style={styles.soilModalValue} numberOfLines={2}>
                      {detailMunsellMatch?.soil_type || detailSoil.soil_type || detailSoil.soilType || 'Determined Soil'}
                    </Text>
                  </View>
                </View>

                {/* Scan Metrics (Scan Count, Near-Match, Scan Date, Scanned By, Scan Note) - Scan History Only */}
                {!isMunsellDetail && (
                  <>
                    {/* Row 3: Scan Count & Near-Match */}
                    <View style={styles.soilGridRow2}>
                      <View style={styles.soilModalGridCard}>
                        <Text style={styles.soilModalLabel}>SCAN COUNT</Text>
                        <Text style={styles.soilModalValue}>
                          {detailSoil.scanCount ? String(detailSoil.scanCount) : '1'}
                        </Text>
                      </View>
                      <View style={[styles.soilModalGridCard, styles.soilNearMatchCard]}>
                        <Text style={styles.soilModalLabel}>NEAR-MATCH</Text>
                        <Text style={styles.soilNearMatchValue}>
                          {detailSoil.nearMatchRating || detailSoil.confidence || '97.4%'}
                        </Text>
                      </View>
                    </View>

                    {/* Row 4: Scan Date & Scanned By */}
                    <View style={styles.soilGridRow2}>
                      <View style={styles.soilModalGridCard}>
                        <Text style={styles.soilModalLabel}>SCAN DATE</Text>
                        <Text style={styles.soilModalValue} numberOfLines={1}>
                          {detailSoil.scanDate || detailSoil.createdDate || detailSoil.scanTime || 'Today'}
                        </Text>
                      </View>
                      <View style={styles.soilModalGridCard}>
                        <Text style={styles.soilModalLabel}>SCANNED BY</Text>
                        <View style={styles.scannedByRow}>
                          <Text style={styles.soilModalValue} numberOfLines={1}>
                            {detailSoil.adminId || detailSoil.scannedBy || 'User'}
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
                          const hasPin = Boolean(detailSoil.hasPin || detailSoil.location_id || detailSoil.location || detailSoil.latitude);
                          const rawNote = detailSoil.scanNote || detailSoil.scan_note || '';
                          const isRealNote = rawNote && !rawNote.startsWith('Determined Soil:') && rawNote !== '—' && rawNote !== '-';
                          return (hasPin && isRealNote) ? rawNote : '-';
                        })()}
                      </Text>
                    </View>
                  </>
                )}

                {/* Description Card (Full Width - Website #modalDescCard) */}
                <View style={styles.soilModalFullCard}>
                  <Text style={styles.soilModalLabel}>DESCRIPTION</Text>
                  <Text style={styles.soilModalDescText}>
                    {detailResolvedDescription}
                  </Text>
                </View>

                {/* Soil Pin Location & Covered Area Card (Matches Website #modalSoilLocationCard - Placed AFTER Description Card) */}
                {!isMunsellDetail && (() => {
                  const hasPin = Boolean(detailSoil.hasPin || detailSoil.location_id || detailSoil.location || detailSoil.latitude);
                  if (!hasPin) return null;
                  const lat = parseFloat(detailSoil.latitude || detailSoil.lat || (detailSoil.location && detailSoil.location.latitude));
                  const lng = parseFloat(detailSoil.longitude || detailSoil.lng || (detailSoil.location && detailSoil.location.longitude));
                  const brgy = detailSoil.barangay || detailSoil.barangay_name || (detailSoil.location && detailSoil.location.barangay) || 'Poblacion';
                  const status = (detailSoil.status || (detailSoil.location && detailSoil.location.status) || 'approved').toLowerCase();
                  const coverage = detailSoil.covered_area_sqm || (detailSoil.location && detailSoil.location.covered_area_sqm)
                    ? ((detailSoil.covered_area_sqm || detailSoil.location.covered_area_sqm) / 10000).toFixed(2)
                    : null;
                  return (
                    <TouchableOpacity
                      style={styles.soilModalLocationCard}
                      onPress={() => {
                        setDetailSoil(null);
                        setActiveTab('Map');
                      }}
                      activeOpacity={0.88}
                    >
                      <View style={styles.soilLocHeaderRow}>
                        <Text style={styles.soilLocTitleText}>
                          SOIL PIN LOCATION &amp; COVERED AREA
                        </Text>
                        <View style={styles.soilLocBadgesRow}>
                          <View
                            style={[
                              styles.soilLocBadgeStatus,
                              status === 'approved' ? styles.soilLocBadgeApproved : styles.soilLocBadgePending,
                            ]}
                          >
                            <Text
                              style={[
                                styles.soilLocBadgeStatusText,
                                { color: status === 'approved' ? '#16a34a' : '#d97706' },
                              ]}
                            >
                              {status === 'approved' ? 'Approved' : 'Pending'}
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
                              {brgy} {!isNaN(lat) && !isNaN(lng) ? `· ${lat.toFixed(4)}°, ${lng.toFixed(4)}°` : ''}
                            </Text>
                          </View>
                        </View>
                        <Text style={styles.soilMapWatermark}>AgriCo Soil GPS Preview</Text>
                      </View>
                    </TouchableOpacity>
                  );
                })()}

                {/* Multi-Layer Accordion Section (Matches Website #modalMultiLayerSection) */}
                {!isMunsellDetail && detailNormRegions.length > 1 && (
                  <View style={styles.multiLayerSection}>
                    <View style={styles.multiLayerHeaderRow}>
                      <Text style={styles.multiLayerSectionTitle}>
                        ALL DETECTED SOIL LAYERS ({detailNormRegions.length})
                      </Text>
                      <TouchableOpacity
                        onPress={() => {
                          const allExpanded = detailNormRegions.slice(1).every((_, i) => expandedLayers[i]);
                          const next = {};
                          detailNormRegions.slice(1).forEach((_, i) => {
                            next[i] = !allExpanded;
                          });
                          setExpandedLayers(next);
                        }}
                        activeOpacity={0.7}
                      >
                        <Text style={styles.multiLayerToggleAllText}>
                          {detailNormRegions.slice(1).every((_, i) => expandedLayers[i]) ? 'Collapse All' : 'Expand All'}
                        </Text>
                      </TouchableOpacity>
                    </View>

                    {/* Secondary Layer Accordion Items */}
                    <View style={{ gap: 8 }}>
                      {detailNormRegions.slice(1).map((r, idx) => {
                        const isExpanded = Boolean(expandedLayers[idx]);
                        const rPct = r.percent !== undefined
                          ? Math.round(r.percent)
                          : (r.proportion !== undefined ? Math.round(r.proportion * 100) : 25);
                        const rCode = (r.munsell_code || r.munsell || r.notation || detailSoil.fullCode || '10YR 4/1').trim();
                        let rHex = r.hex || r.hex_color || null;
                        if (!rHex && Array.isArray(r.rgb) && r.rgb.length >= 3) {
                          rHex = `#${r.rgb.map(v => Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2, '0')).join('')}`;
                        }
                        rHex = rHex || detailSoil.hexColor || detailSoil.hexCode || '#8F694E';
                        if (!rHex.startsWith('#')) rHex = `#${rHex}`;

                        const matchedMunsell = OFFLINE_MUNSELL_COLORS.find(
                          c => (c.munsellCode || c.full_code || '').trim().toUpperCase() === rCode.toUpperCase()
                        );
                        const rName = r.color_name || r.name || r.label || matchedMunsell?.colorName || 'Determined Soil';
                        const rOrganic = r.organic_matter || r.soil_organic_index || matchedMunsell?.soil_organic_index || 'Moderate (2.2% – 3.7%)';
                        const rSoilType = r.soil_type || r.soil_name || matchedMunsell?.soil_type || 'Determined Soil';
                        const rConf = r.confidence ? `${r.confidence}%` : '96.8%';
                        const rDesc = matchedMunsell?.description || r.description || `${rCode} - ${rName}`;
                        const hasPin = Boolean(detailSoil.hasPin || detailSoil.location_id || detailSoil.location || detailSoil.latitude);
                        const rawNote = r.scan_note || detailSoil.scanNote || detailSoil.scan_note || '';
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
                                    {detailSoil.scanImage ? (
                                      <View style={{ width: '100%', height: '100%', position: 'relative', overflow: 'hidden' }}>
                                        <FallbackImage
                                          uri={detailSoil.scanImage}
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
                                  <View style={styles.layerBannerHexBadge}>
                                    <Text style={styles.layerBannerHexBadgeText}>{rHex.toUpperCase()}</Text>
                                  </View>
                                </View>

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
                              </View>
                            )}
                          </View>
                        );
                      })}
                    </View>
                  </View>
                )}

                {/* Soil Normalization Card (Matches Website / Mobile Specification - Scan History Only) */}
                {!isMunsellDetail && (
                  <View style={styles.soilModalNormCard}>
                    {/* Card Header: Label & Pill Badge */}
                    <View style={styles.soilModalNormHeader}>
                      <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                        <SunLightingIcon size={14} color="#10b981" />
                        <Text style={[styles.soilModalNormLabel, { color: '#10b981' }]}>
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
                      {detailSoil.beforeAfterImage || detailSoil.beforeAfterImageUrl ? (
                        <View style={{ width: '100%', height: 165, position: 'relative' }}>
                          <Image
                            source={{ uri: detailSoil.beforeAfterImage || detailSoil.beforeAfterImageUrl }}
                            style={styles.soilNormImage}
                            resizeMode="cover"
                          />
                          {/* Overlaid ROI bounding boxes — colored with each region's real detected soil color */}
                          {detailNormBoxes.map((b, idx) => (
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
                          ))}
                        </View>
                      ) : detailSoil.scanImage ? (
                        /* Simulated split Before / After frame if before_after is not yet rendered */
                        <View style={{ width: '100%', position: 'relative' }}>
                          <View style={styles.normSimulatedHeader}>
                            <Text style={styles.normHeaderBefore}>BEFORE</Text>
                            <Text style={styles.normHeaderAfter}>
                              AFTER (NORMALIZED) -- {detailSoil.munsellCode || detailSoil.fullCode || '10YR 4/1'}
                            </Text>
                          </View>
                          <View style={{ flexDirection: 'row', height: 135 }}>
                            <FallbackImage
                              uri={detailSoil.scanImage}
                              style={{ flex: 1, height: '100%' }}
                              resizeMode="cover"
                            />
                            <View style={{ width: 1.5, backgroundColor: 'rgba(255, 255, 255, 0.3)' }} />
                            <View style={{ flex: 1, height: '100%', position: 'relative', overflow: 'hidden' }}>
                              <FallbackImage
                                uri={detailSoil.scanImage}
                                style={{ width: '100%', height: '100%' }}
                                resizeMode="cover"
                              />
                              {/* Overlaid detected soil color boxes at actual detected locations */}
                              {detailNormBoxes.map((b, idx) => (
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
                              ))}
                            </View>
                          </View>
                        </View>
                      ) : (
                        /* Simulated split Before / After swatch frame when photo is not in record */
                        <View style={{ width: '100%', position: 'relative' }}>
                          <View style={styles.normSimulatedHeader}>
                            <Text style={styles.normHeaderBefore}>BEFORE</Text>
                            <Text style={styles.normHeaderAfter}>
                              AFTER (NORMALIZED) -- {detailSoil.munsellCode || detailSoil.fullCode || '10YR 4/1'}
                            </Text>
                          </View>
                          <View style={{ flexDirection: 'row', height: 135 }}>
                            <View
                              style={{
                                flex: 1,
                                height: '100%',
                                backgroundColor: detailMunsellMatch?.hexCode || detailSoil.hexCode || detailSoil.hex || '#3a2518',
                                opacity: 0.85,
                              }}
                            />
                            <View style={{ width: 1.5, backgroundColor: 'rgba(255, 255, 255, 0.3)' }} />
                            <View
                              style={{
                                flex: 1,
                                height: '100%',
                                backgroundColor: detailMunsellMatch?.hexCode || detailSoil.hexCode || detailSoil.hex || '#8F694E',
                                position: 'relative',
                                overflow: 'hidden',
                              }}
                            >
                              {detailNormBoxes.map((b, idx) => (
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

                    {/* Swatch Capsule Pills — each shows its REAL detected Stage 3/4 soil color */}
                    <View style={styles.normSwatchesRow}>
                      {detailNormRegions.map((r, idx) => {
                        // Resolve hex from region-specific fields only (do NOT fall back to global scan color)
                        let rHex = r.hex || r.hex_color || null;
                        if (!rHex && Array.isArray(r.rgb) && r.rgb.length >= 3) {
                          rHex = `#${r.rgb.map(v => Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2, '0')).join('')}`;
                        }
                        // Only fall back to global color if this is the ONLY region (dominant)
                        if (!rHex && detailNormRegions.length === 1) {
                          rHex = detailMunsellMatch?.hexCode || detailSoil.hexCode || detailSoil.hexColor || '#8F694E';
                        }
                        rHex = (rHex || '#888888');
                        if (!rHex.startsWith('#')) rHex = `#${rHex}`;

                        // Resolve munsell code from region-specific fields only
                        const rCode = r.munsell_code || r.munsell || r.notation || null;
                        // Show global munsell only if this is the single dominant region
                        const displayCode = rCode || (detailNormRegions.length === 1
                          ? (detailMunsellMatch?.munsellCode || detailSoil.munsellCode || detailSoil.fullCode || '')
                          : '');

                        const rPct = r.percent !== undefined
                          ? Math.round(r.percent)
                          : (r.proportion !== undefined
                              ? Math.round(r.proportion * 100)
                              : (detailNormRegions.length === 1 ? (detailSoil.soilPercentage || 100) : null));
                        return (
                          <View key={`swatch_pill_${idx}`} style={styles.soilSwatchCapsulePill}>
                            <View style={[styles.capsulePillSwatch, { backgroundColor: rHex }]} />
                            <Text style={styles.capsulePillHex}>{rHex.toUpperCase()}</Text>
                            {displayCode ? <Text style={styles.capsulePillCode}>({displayCode})</Text> : null}
                            {rPct != null ? <Text style={styles.capsulePillPct}>{rPct}%</Text> : null}
                          </View>
                        );
                      })}
                    </View>

                    {/* Metadata Row matching website exactly */}
                    <View style={styles.soilNormMetaRow}>
                      <Text style={styles.soilNormMetaTitle}>METADATA</Text>
                      <Text style={styles.soilNormMetaInfo}>
                        DATE: {detailNormDateTime.dateStr}  |  TIME: {detailNormDateTime.timeStr}  |  DETECTED COLORS: {Math.max(detailNormRegions.length, 1)}
                      </Text>
                    </View>
                  </View>
                )}
              </ScrollView>

              {/* Fixed Modal Footer: Close & Save Copy Buttons (Save Copy for Scan History Only) */}
              <View style={styles.soilModalFooter}>
                <TouchableOpacity
                  style={[styles.btnModalCancel, isMunsellDetail && { flex: 1 }]}
                  onPress={() => setDetailSoil(null)}
                  activeOpacity={0.8}
                  accessibilityLabel="Close details"
                >
                  <Text style={styles.btnModalCancelText}>Close</Text>
                </TouchableOpacity>

                {!isMunsellDetail && (
                  <TouchableOpacity
                    style={[
                      styles.btnSaveHardcopy,
                      isSavedCopy && { backgroundColor: '#15803d' },
                    ]}
                    onPress={() => handleSaveRecordCopy(detailSoil, detailMunsellMatch, detailResolvedDescription)}
                    activeOpacity={0.85}
                    accessibilityLabel="Save Copy"
                  >
                    <SaveTrayIcon size={14} color="#ffffff" />
                    <Text style={styles.btnSaveHardcopyText}>
                      {isSavedCopy ? 'Saved Copy ✓' : 'Save Copy'}
                    </Text>
                  </TouchableOpacity>
                )}
              </View>
            </View>
          </View>
        </Modal>
      ) : null}

      {/* Rows Per Page Selection Modal */}
      <Modal
        visible={showRowsPerPageModal}
        animationType="fade"
        transparent={true}
        onRequestClose={() => setShowRowsPerPageModal(false)}
      >
        <View style={styles.rowsModalBackdrop}>
          <Pressable
            style={StyleSheet.absoluteFill}
            onPress={() => setShowRowsPerPageModal(false)}
          />
          <View style={styles.rowsModalCard}>
            <View style={styles.rowsModalHeader}>
              <View>
                <Text style={styles.rowsModalTitle}>Rows per page</Text>
                <Text style={styles.rowsModalSubtitle}>Select cards per page</Text>
              </View>
              <TouchableOpacity
                onPress={() => setShowRowsPerPageModal(false)}
                hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}
                style={styles.rowsModalCloseBtn}
              >
                <Text style={styles.rowsModalCloseText}>✕</Text>
              </TouchableOpacity>
            </View>

            <View style={styles.rowsModalOptionsList}>
              {[5, 10, 20, 50].map((num) => {
                const isSelected = munsellRowsPerPage === num;
                return (
                  <TouchableOpacity
                    key={'rpp_' + num}
                    style={[
                      styles.rowsModalOptionItem,
                      isSelected && styles.rowsModalOptionItemSelected,
                    ]}
                    onPress={() => {
                      setMunsellRowsPerPage(num);
                      setMunsellPage(1);
                      setShowRowsPerPageModal(false);
                    }}
                    activeOpacity={0.7}
                  >
                    <Text
                      style={[
                        styles.rowsModalOptionText,
                        isSelected && styles.rowsModalOptionTextSelected,
                      ]}
                    >
                      {num} {num === 10 ? 'colors (Default)' : 'colors'}
                    </Text>
                    {isSelected && (
                      <View style={styles.rowsModalCheckmarkBadge}>
                        <Text style={styles.rowsModalCheckmark}>✓</Text>
                      </View>
                    )}
                  </TouchableOpacity>
                );
              })}
            </View>
          </View>
        </View>
      </Modal>

      {/* In-App Auto-Update Modal (Triggered when online and new version exists) */}
      <UpdateModal
        visible={showUpdateModal}
        updateInfo={appUpdateInfo}
        onClose={() => {
          if (appUpdateInfo?.latestVersion) {
            dismissedUpdateVersionRef.current = appUpdateInfo.latestVersion;
          }
          setShowUpdateModal(false);
        }}
      />

      {/* Floating Rounded Bottom Navigation Bar (Image 2 Style) */}
      {!(activeTab === 'Map' && isMapModalOpen) && (
        <BottomNavBar activeTab={activeTab} onTabPress={handleTabPress} />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  screenContainer: {
    flex: 1,
    backgroundColor: '#f6faf7',
  },
  homeScrollContent: {
    paddingHorizontal: 20,
    paddingTop: 44,
  },
  headerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 20,
  },
  headerGreeting: {
    fontSize: 16,
    fontWeight: '600',
    color: '#64748b',
    letterSpacing: -0.2,
  },
  headerTitle: {
    fontSize: 26,
    fontWeight: '900',
    color: '#0f172a',
    letterSpacing: -0.5,
    marginTop: -2,
  },
  bellButton: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: 'rgba(218, 241, 222, 0.9)',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#15803d',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.08,
    shadowRadius: 10,
    elevation: 3,
  },
  searchBarContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ffffff',
    borderRadius: 9999,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    paddingLeft: 18,
    paddingRight: 6,
    height: 48,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 2,
    marginBottom: 18,
  },
  searchInput: {
    flex: 1,
    height: 48,
    fontSize: 13.5,
    color: '#0f172a',
    fontWeight: '500',
  },
  searchBtn: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: '#22c55e',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#22c55e',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 6,
    elevation: 3,
  },
  categoriesWrapper: {
    marginBottom: 16,
  },
  categoriesContent: {
    gap: 8,
  },
  categoryChip: {
    paddingHorizontal: 18,
    paddingVertical: 8,
    borderRadius: 12,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  categoryChipActive: {
    backgroundColor: '#22c55e',
    borderColor: '#22c55e',
    shadowColor: '#22c55e',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 8,
    elevation: 3,
  },
  categoryChipText: {
    fontSize: 13,
    fontWeight: '600',
    color: '#64748b',
  },
  categoryChipTextActive: {
    color: '#ffffff',
    fontWeight: '800',
  },
  sectionHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 14,
  },
  sectionTitleWithDot: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  sectionHeaderTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#0f172a',
    letterSpacing: -0.3,
  },
  dbLiveDot: {
    width: 7,
    height: 7,
    borderRadius: 3.5,
    backgroundColor: '#22c55e',
  },
  sectionHeaderSub: {
    fontSize: 12,
    fontWeight: '600',
    color: '#64748b',
  },
  seeMoreHeaderBtn: {
    paddingVertical: 3,
    paddingHorizontal: 6,
    flexDirection: 'row',
    alignItems: 'center',
  },
  seeMoreHeaderText: {
    fontSize: 13,
    fontWeight: '700',
    color: '#22c55e',
    letterSpacing: -0.2,
  },
  gridContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    marginBottom: 26,
    rowGap: 14,
  },
  card: {
    width: (width - 40 - 12) / 2,
    backgroundColor: '#ffffff',
    borderRadius: 20,
    borderWidth: 1,
    borderColor: 'rgba(218, 241, 222, 0.85)',
    padding: 10,
    position: 'relative',
    shadowColor: '#15803d',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.07,
    shadowRadius: 20,
    elevation: 4,
  },
  soilImageContainer: {
    width: '100%',
    height: 125,
    borderRadius: 16,
    overflow: 'hidden',
    marginBottom: 10,
    backgroundColor: '#e2e8f0',
    position: 'relative',
    borderWidth: 1,
    borderColor: 'rgba(0,0,0,0.06)',
  },
  soilCardImage: {
    width: '100%',
    height: '100%',
  },
  swatchHexBadge: {
    position: 'absolute',
    bottom: 8,
    left: 8,
    backgroundColor: 'rgba(0, 0, 0, 0.55)',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
    flexDirection: 'row',
    alignItems: 'center',
  },
  miniColorDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    marginRight: 5,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.7)',
  },
  swatchHexText: {
    fontSize: 10,
    fontWeight: '700',
    color: '#ffffff',
    letterSpacing: 0.3,
  },
  cardInfo: {
    paddingHorizontal: 4,
    paddingBottom: 2,
  },
  cardTitle: {
    fontSize: 14.5,
    fontWeight: '800',
    color: '#0f172a',
    marginBottom: 3,
  },
  cardDescription: {
    fontSize: 11,
    color: '#64748b',
    lineHeight: 15,
    marginBottom: 10,
    minHeight: 30,
  },
  cardBottomRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingTop: 4,
    borderTopWidth: 1,
    borderTopColor: '#f1f5f9',
  },
  munsellCodeLabel: {
    fontSize: 9,
    fontWeight: '800',
    color: '#64748b',
    letterSpacing: 0.5,
  },
  cardMunsellCode: {
    fontSize: 13,
    fontWeight: '900',
    color: '#166534',
  },
  viewBtn: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
    backgroundColor: '#22c55e',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#22c55e',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.25,
    shadowRadius: 4,
    elevation: 2,
  },
  viewBtnText: {
    fontSize: 11.5,
    fontWeight: '800',
    color: '#ffffff',
  },
  cardCodeBlock: {
    flex: 1,
    paddingRight: 6,
  },
  recommendSection: {
    marginTop: 6,
    marginBottom: 8,
  },
  homeCarousel: {
    marginTop: 6,
    marginBottom: 14,
  },
  seeMoreRow: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 10,
    marginTop: 2,
    marginBottom: 6,
  },
  seeMoreText: {
    fontSize: 14,
    fontWeight: '700',
    color: '#22c55e',
  },
  recentSection: {
    marginTop: 4,
  },
  scanHistoryHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 14,
  },
  recentSectionTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#0f172a',
    letterSpacing: -0.3,
  },
  scanHistoryCountText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#166534',
    backgroundColor: '#f0fdf4',
    paddingHorizontal: 10,
    paddingVertical: 3,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#bbf7d0',
  },
  dateGroupedList: {
    gap: 16,
  },
  dateGroupBlock: {
    marginBottom: 4,
  },
  dateLabelRow: {
    marginTop: 8,
    marginBottom: 10,
    paddingHorizontal: 4,
  },
  dateLabelText: {
    fontSize: 13.5,
    fontWeight: '700',
    color: '#64748b',
    letterSpacing: 0.2,
  },
  recentList: {
    gap: 12,
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
    borderColor: 'rgba(0,0,0,0.08)',
    backgroundColor: '#e2e8f0',
  },
  recentSoilThumb: {
    width: '100%',
    height: '100%',
  },
  recentHueTag: {
    position: 'absolute',
    bottom: 2,
    right: 2,
    paddingHorizontal: 4,
    paddingVertical: 1,
    borderRadius: 4,
  },
  recentThumbHueText: {
    fontSize: 9,
    fontWeight: '900',
    color: '#ffffff',
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
    justifyContent: 'space-between',
    marginTop: 2,
  },
  recentCode: {
    fontSize: 12.5,
    fontWeight: '800',
    color: '#22c55e',
  },
  queuedBadge: {
    backgroundColor: '#fefce8',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 5,
    borderWidth: 1,
    borderColor: '#fef08a',
    marginLeft: 6,
  },
  queuedBadgeText: {
    color: '#ca8a04',
    fontSize: 9.5,
    fontWeight: '700',
  },
  syncedAdminBadge: {
    backgroundColor: '#f0fdf4',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 5,
    borderWidth: 1,
    borderColor: '#bbf7d0',
    marginLeft: 6,
  },
  syncedAdminBadgeText: {
    color: '#16a34a',
    fontSize: 9.5,
    fontWeight: '700',
  },
  platformText: {
    fontSize: 11,
    fontWeight: '600',
    color: '#94a3b8',
    letterSpacing: 0.2,
  },
  recentViewPill: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
    backgroundColor: '#f0fdf4',
    borderWidth: 1,
    borderColor: '#bbf7d0',
  },
  recentViewPillText: {
    fontSize: 11,
    fontWeight: '800',
    color: '#166534',
  },
  emptyHistoryBox: {
    backgroundColor: '#ffffff',
    borderRadius: 18,
    padding: 24,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: 'rgba(218, 241, 222, 0.85)',
    shadowColor: '#15803d',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.05,
    shadowRadius: 14,
    elevation: 2,
  },
  emptyHistoryIcon: {
    fontSize: 32,
    marginBottom: 8,
  },
  emptyHistoryTitle: {
    fontSize: 15,
    fontWeight: '800',
    color: '#0f172a',
    marginBottom: 4,
  },
  emptyHistoryDesc: {
    fontSize: 12,
    color: '#64748b',
    textAlign: 'center',
    lineHeight: 17,
  },
  paginationContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 20,
    marginBottom: 8,
    gap: 8,
  },
  paginationNavBtn: {
    paddingHorizontal: 16,
    height: 38,
    borderRadius: 12,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#e2e8f0',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.04,
    shadowRadius: 2,
    elevation: 1,
  },
  paginationNavBtnDisabled: {
    backgroundColor: '#f8fafc',
    borderColor: '#f1f5f9',
    elevation: 0,
    shadowOpacity: 0,
  },
  paginationNavText: {
    fontSize: 13,
    fontWeight: '700',
    color: '#334155',
  },
  paginationNavTextDisabled: {
    color: '#cbd5e1',
  },
  paginationPageBtn: {
    width: 38,
    height: 38,
    borderRadius: 12,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#e2e8f0',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.04,
    shadowRadius: 2,
    elevation: 1,
  },
  paginationPageBtnActive: {
    backgroundColor: '#22c55e',
    borderColor: '#22c55e',
    shadowColor: '#22c55e',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.3,
    shadowRadius: 5,
    elevation: 3,
  },
  paginationPageText: {
    fontSize: 14,
    fontWeight: '700',
    color: '#334155',
  },
  paginationPageTextActive: {
    color: '#ffffff',
    fontWeight: '800',
  },
  // Sub-tabs styling (Munsell, Map, Profile)
  subTabContent: {
    paddingHorizontal: 20,
    paddingTop: 50,
  },
  subTabHeader: {
    fontSize: 22,
    fontWeight: '800',
    color: '#0f172a',
    marginBottom: 6,
  },
  subTabDesc: {
    fontSize: 13,
    color: '#64748b',
    marginBottom: 20,
  },
  munsellScrollContent: {
    paddingHorizontal: 20,
    paddingTop: 50,
    paddingBottom: 24,
  },
  munsellHeaderRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    marginBottom: 16,
  },
  munsellPageTitle: {
    fontSize: 23,
    fontWeight: '900',
    color: '#0f172a',
    letterSpacing: -0.5,
  },
  munsellPageSubtitle: {
    fontSize: 12,
    fontWeight: '600',
    color: '#64748b',
    marginTop: 3,
  },
  munsellCountBadge: {
    backgroundColor: '#f0fdf4',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: '#bbf7d0',
  },
  munsellCountBadgeText: {
    fontSize: 13,
    fontWeight: '800',
    color: '#166534',
  },
  munsellSearchRow: {
    marginBottom: 12,
  },
  munsellSearchInputWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ffffff',
    borderRadius: 16,
    borderWidth: 1,
    borderColor: 'rgba(218, 241, 222, 0.95)',
    paddingHorizontal: 14,
    height: 48,
    shadowColor: '#15803d',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 2,
  },
  munsellSearchInputField: {
    flex: 1,
    fontSize: 13.5,
    fontWeight: '500',
    color: '#0f172a',
    marginLeft: 10,
    marginRight: 4,
    paddingVertical: 0,
  },
  munsellClearBtn: {
    paddingHorizontal: 12,
    paddingVertical: 10,
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 999,
  },
  munsellClearBtnText: {
    fontSize: 16,
    color: '#64748b',
    fontWeight: '800',
  },
  munsellCategoryContainer: {
    marginBottom: 12,
    marginHorizontal: -20,
    paddingHorizontal: 20,
  },
  munsellResultMetaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 14,
    paddingHorizontal: 2,
  },
  munsellResultMetaText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#64748b',
  },
  munsellLoadingBox: {
    paddingVertical: 40,
    alignItems: 'center',
    justifyContent: 'center',
  },
  munsellLoadingText: {
    fontSize: 13,
    fontWeight: '600',
    color: '#64748b',
    marginTop: 10,
  },
  munsellPaginationCard: {
    backgroundColor: '#ffffff',
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    paddingVertical: 14,
    paddingHorizontal: 16,
    marginTop: 18,
    marginBottom: 16,
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 6,
    elevation: 2,
  },
  munsellPaginationTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    width: '100%',
  },
  munsellPaginationLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  munsellRowsPerPageLabel: {
    fontSize: 13,
    fontWeight: '500',
    color: '#475569',
  },
  munsellRowsSelectBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#dbeafe',
    borderRadius: 8,
    paddingHorizontal: 10,
    paddingVertical: 5,
    gap: 5,
  },
  munsellRowsSelectText: {
    fontSize: 13,
    fontWeight: '600',
    color: '#1e293b',
  },
  munsellRowsSelectChevron: {
    fontSize: 11,
    color: '#64748b',
    fontWeight: '700',
  },
  munsellRangeText: {
    fontSize: 13,
    fontWeight: '500',
    color: '#94a3b8',
  },
  munsellPaginationDivider: {
    height: 1,
    backgroundColor: '#f1f5f9',
    marginVertical: 12,
    width: '100%',
  },
  munsellPaginationRight: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    width: '100%',
  },
  munsellNavBtn: {
    paddingHorizontal: 14,
    height: 36,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    backgroundColor: '#ffffff',
    justifyContent: 'center',
    alignItems: 'center',
  },
  munsellNavBtnDisabled: {
    borderColor: '#f1f5f9',
    backgroundColor: '#f8fafc',
  },
  munsellNavBtnText: {
    fontSize: 12.5,
    fontWeight: '600',
    color: '#475569',
  },
  munsellNavBtnTextDisabled: {
    color: '#cbd5e1',
  },
  munsellPageNumBtn: {
    width: 36,
    height: 36,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    backgroundColor: '#ffffff',
    justifyContent: 'center',
    alignItems: 'center',
  },
  munsellPageNumBtnActive: {
    backgroundColor: '#22c55e',
    borderColor: '#22c55e',
    shadowColor: '#22c55e',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.35,
    shadowRadius: 4,
    elevation: 3,
  },
  munsellPageNumText: {
    fontSize: 13,
    fontWeight: '600',
    color: '#334155',
  },
  munsellPageNumTextActive: {
    color: '#ffffff',
    fontWeight: '700',
  },
  // Rows Per Page Modal Styles
  rowsModalBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.45)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  rowsModalCard: {
    width: '100%',
    maxWidth: 320,
    backgroundColor: '#ffffff',
    borderRadius: 16,
    padding: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.15,
    shadowRadius: 16,
    elevation: 8,
  },
  rowsModalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#f1f5f9',
    paddingBottom: 12,
  },
  rowsModalTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: '#0f172a',
  },
  rowsModalSubtitle: {
    fontSize: 11.5,
    fontWeight: '500',
    color: '#64748b',
    marginTop: 2,
  },
  rowsModalCloseBtn: {
    padding: 4,
  },
  rowsModalCloseText: {
    fontSize: 16,
    fontWeight: '700',
    color: '#94a3b8',
  },
  rowsModalOptionsList: {
    gap: 8,
  },
  rowsModalOptionItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    backgroundColor: '#f8fafc',
  },
  rowsModalOptionItemSelected: {
    borderColor: '#22c55e',
    backgroundColor: '#f0fdf4',
  },
  rowsModalOptionText: {
    fontSize: 13.5,
    fontWeight: '600',
    color: '#475569',
  },
  rowsModalOptionTextSelected: {
    color: '#166534',
    fontWeight: '700',
  },
  rowsModalCheckmarkBadge: {
    width: 20,
    height: 20,
    borderRadius: 10,
    backgroundColor: '#22c55e',
    justifyContent: 'center',
    alignItems: 'center',
  },
  rowsModalCheckmark: {
    fontSize: 12,
    fontWeight: '900',
    color: '#ffffff',
  },
  // Map Screen Styles (Leaflet WebView + NetInfo Offline Fallback)
  mapScreenContainer: {
    flex: 1,
    position: 'relative',
    backgroundColor: '#e8f2f8',
    overflow: 'hidden',
  },
  mapWebView: {
    flex: 1,
    backgroundColor: '#e8f2f8',
  },
  mapLoadingOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: '#e8f2f8',
    alignItems: 'center',
    justifyContent: 'center',
  },
  mapSearchHeader: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    zIndex: 20,
    paddingTop: Platform.OS === 'ios' ? 56 : (StatusBar.currentHeight ? StatusBar.currentHeight + 14 : 42),
    paddingHorizontal: 16,
  },
  mapSearchBarContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ffffff',
    borderRadius: 9999,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    paddingLeft: 18,
    paddingRight: 6,
    height: 48,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.12,
    shadowRadius: 10,
    elevation: 6,
  },
  mapSearchInput: {
    flex: 1,
    height: 48,
    fontSize: 13.5,
    color: '#0f172a',
    fontWeight: '500',
    paddingVertical: 0,
  },
  mapSearchBtn: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: '#22c55e',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#22c55e',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 6,
    elevation: 3,
  },
  // Offline Fallback UI (Step 4)
  offlineFallbackContainer: {
    flex: 1,
    backgroundColor: '#f1f5f9',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 32,
  },
  offlineIconCircle: {
    width: 90,
    height: 90,
    borderRadius: 45,
    backgroundColor: '#ffffff',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#0f172a',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.08,
    shadowRadius: 12,
    elevation: 3,
    marginBottom: 20,
  },
  offlineTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#0f172a',
    textAlign: 'center',
    marginBottom: 8,
  },
  offlineSubtitle: {
    fontSize: 13.5,
    color: '#64748b',
    textAlign: 'center',
    lineHeight: 20,
    marginBottom: 24,
  },
  offlineRetryBtn: {
    backgroundColor: '#22c55e',
    paddingVertical: 12,
    paddingHorizontal: 28,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#22c55e',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.25,
    shadowRadius: 8,
    elevation: 3,
  },
  offlineRetryBtnText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '800',
  },
  // Offline Mid-Session Banner (Step 5)
  offlineMidSessionBanner: {
    position: 'absolute',
    top: Platform.OS === 'ios' ? 118 : (StatusBar.currentHeight ? StatusBar.currentHeight + 72 : 100),
    left: 20,
    right: 20,
    zIndex: 25,
    backgroundColor: 'rgba(254, 242, 242, 0.96)',
    borderWidth: 1,
    borderColor: '#fca5a5',
    borderRadius: 14,
    paddingVertical: 9,
    paddingHorizontal: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 6,
    elevation: 4,
  },
  offlineMidSessionText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#991b1b',
    flex: 1,
    marginRight: 8,
  },
  offlineSmallRetry: {
    backgroundColor: '#ef4444',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 8,
  },
  offlineSmallRetryText: {
    color: '#ffffff',
    fontSize: 11,
    fontWeight: '800',
  },
  statusBadge: {
    backgroundColor: '#f0fdf4',
    borderWidth: 1,
    borderColor: '#bbf7d0',
    paddingHorizontal: 14,
    paddingVertical: 6,
    borderRadius: 9999,
  },
  statusBadgeText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#166534',
  },
  profileCard: {
    backgroundColor: '#ffffff',
    borderRadius: 22,
    padding: 24,
    borderWidth: 1,
    borderColor: 'rgba(218, 241, 222, 0.9)',
    alignItems: 'center',
    shadowColor: '#15803d',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.08,
    shadowRadius: 24,
    elevation: 5,
  },
  profileAvatar: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: '#f0fdf4',
    borderWidth: 2.5,
    borderColor: '#22c55e',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 14,
  },
  profileAvatarText: {
    fontSize: 28,
    fontWeight: '900',
    color: '#22c55e',
  },
  profileName: {
    fontSize: 19,
    fontWeight: '800',
    color: '#0f172a',
    marginBottom: 4,
  },
  profileEmail: {
    fontSize: 13.5,
    color: '#64748b',
    marginBottom: 16,
  },
  sanctumBadge: {
    backgroundColor: '#f0fdf4',
    borderWidth: 1,
    borderColor: '#bbf7d0',
    paddingHorizontal: 16,
    paddingVertical: 6,
    borderRadius: 9999,
    marginBottom: 24,
  },
  sanctumText: {
    fontSize: 12.5,
    fontWeight: '700',
    color: '#166534',
  },
  signOutButton: {
    width: '100%',
    height: 44,
    backgroundColor: '#fee2e2',
    borderWidth: 1,
    borderColor: '#fca5a5',
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  signOutButtonText: {
    fontSize: 14,
    fontWeight: '700',
    color: '#dc2626',
  },
  // Modal Styling (Soil Record Details - Matches Website Exactly in Light Mode)
  modalBackdrop: {
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
    color: '#86efac',
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

  // Soil Pin Location & Covered Area Card
  soilModalLocationCard: {
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
  soilLocHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    flexWrap: 'wrap',
    gap: 8,
    marginBottom: 8,
  },
  soilLocTitleText: {
    fontSize: 10.5,
    fontWeight: '800',
    color: '#64748b',
    letterSpacing: 0.4,
    textTransform: 'uppercase',
  },
  soilLocBadgesRow: {
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
    marginTop: 6,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.2)',
  },
  soilMapCoordsCalloutText: {
    color: '#ffffff',
    fontSize: 10.5,
    fontWeight: '700',
  },
  soilMapWatermark: {
    position: 'absolute',
    bottom: 8,
    right: 10,
    color: 'rgba(255, 255, 255, 0.45)',
    fontSize: 9.5,
    fontWeight: '700',
    letterSpacing: 0.3,
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
});
