<div id="manage-map-root" class="space-y-6">
    {{-- DIRECT LEAFLET ASSETS INCLUSION --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" data-navigate-track />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" data-navigate-track></script>
    <script src="{{ asset('js/lallo-boundary.js') }}" data-navigate-track></script>
    <script src="{{ asset('js/agrico-map-focus.js') }}" data-navigate-track></script>
    <script src="{{ asset('js/agrico-pin.js') }}" data-navigate-track></script>

    <style>
        #manage-map-root {
            color: #0f172a !important;
        }
        #manage-map-root h1,
        #manage-map-root h2,
        #manage-map-root h3 {
            color: #0f172a !important;
        }
        #manage-map-root p {
            color: #64748b;
        }
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
            width: 255px !important;
            max-width: calc(100vw - 64px) !important;
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
    </style>

    {{-- PAGE HEADER --}}
    <div class="ui-reveal flex items-center justify-between gap-4 flex-wrap" style="--i:0">
        <div>
            <h1 class="ui-reveal-text text-3xl font-extrabold text-gray-900" style="--i:0">
                Manage Map
            </h1>
            <p class="ui-reveal-text text-sm text-gray-500 mt-1" style="--i:0">
                Interactive soil location GIS mapping & geographic distribution
            </p>
        </div>
        <a
            href="{{ route('dashboard') }}"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full text-xs font-bold shadow-sm hover:bg-gray-50 transition"
            style="color: #334155 !important; background: #ffffff !important; border: 1px solid #cbd5e1 !important; text-decoration: none;"
            title="Back to Dashboard"
        >
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            <span style="color: #334155 !important; font-weight: 700;">Back to Dashboard</span>
        </a>
    </div>

    {{-- STATS CARDS GRID --}}
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; width:100%; box-sizing:border-box;">
        <div class="ui-reveal" style="--i:1">
            <x-dashboard.stat-card
                :index="1"
                title="TOTAL SITES"
                value="{{ $stats['total'] }}"
                icon="map-pin"
                color="green"
                subtitle="All Mapped Sites" />
        </div>

        <div class="ui-reveal" style="--i:2">
            <x-dashboard.stat-card
                :index="2"
                title="ACTIVE LOCATIONS"
                value="{{ $stats['active'] }}"
                icon="document-text"
                color="emerald"
                subtitle="Active Locations" />
        </div>

        <div class="ui-reveal" style="--i:3">
            <x-dashboard.stat-card
                :index="3"
                title="INACTIVE LOCATIONS"
                value="{{ $stats['inactive'] }}"
                icon="clipboard-document"
                color="red"
                subtitle="Inactive Locations" />
        </div>
    </div>

    {{-- INTERACTIVE MAP CARD (FULL WIDTH) --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-5 flex flex-col gap-3" style="position: relative; z-index: 5;">
        <div class="flex items-center justify-between px-2 pt-1 pb-2 flex-wrap gap-2">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-[#22c55e] animate-pulse"></div>
                <h2 class="text-base font-bold text-gray-900">Geographic Location Map</h2>
            </div>
            <div class="flex items-center gap-3 text-xs font-semibold">
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-[#ef4444] border border-[#dc2626] inline-block"></span>
                    <span class="text-gray-700">Approved (Red Pin)</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-[#9ca3af] border border-[#6b7280] inline-block"></span>
                    <span class="text-gray-500">Unapproved (Gray Pin)</span>
                </div>
            </div>
        </div>

        {{-- MAP CANVAS & IN-MAP SEARCH CONTAINER --}}
        <div class="relative w-full" style="border-radius: 20px; z-index: 10;">
            {{-- FLOATING IN-MAP SEARCH BAR (INSTANT CLIENT-SIDE SEARCH - ZERO LAG) --}}
            <div
                class="map-floating-search-box"
                x-data="{
                    query: '',
                    open: false,
                    get suggestions() {
                        const q = this.query.trim().toLowerCase();
                        if (q.length < 1) return [];
                        const all = window._agricoAllLocations || [];
                        const cleanQ = q.replace(/^(brgy\.?|barangay\.?)\s*/i, '');
                        return all.filter(loc => {
                            const soil = (loc.soil?.soil_name || '').toLowerCase();
                            const code = (loc.soil?.full_code || '').toLowerCase();
                            const soilType = (loc.soil?.soil_type_label || loc.soil?.soil_type || '').toLowerCase();
                            const brgy = (loc.barangay || '').toLowerCase();
                            const note = (loc.soil_scan?.scan_note || '').toLowerCase();
                            const lat = String(loc.latitude || '');
                            const lng = String(loc.longitude || '');
                            return soil.includes(q) || code.includes(q) || soilType.includes(q) || brgy.includes(cleanQ || q) || note.includes(q) || lat.includes(q) || lng.includes(q);
                        }).slice(0, 8);
                    },
                    onInput() {
                        this.open = true;
                        const q = this.query.trim().toLowerCase();
                        const all = window._agricoAllLocations || [];
                        if (!window.agricoRenderMarkers) return;
                        if (q.length === 0) {
                            window.agricoRenderMarkers(all);
                        } else {
                            const cleanQ = q.replace(/^(brgy\.?|barangay\.?)\s*/i, '');
                            const filtered = all.filter(loc => {
                                const soil = (loc.soil?.soil_name || '').toLowerCase();
                                const code = (loc.soil?.full_code || '').toLowerCase();
                                const soilType = (loc.soil?.soil_type_label || loc.soil?.soil_type || '').toLowerCase();
                                const brgy = (loc.barangay || '').toLowerCase();
                                const note = (loc.soil_scan?.scan_note || '').toLowerCase();
                                const lat = String(loc.latitude || '');
                                const lng = String(loc.longitude || '');
                                return soil.includes(q) || code.includes(q) || soilType.includes(q) || brgy.includes(cleanQ || q) || note.includes(q) || lat.includes(q) || lng.includes(q);
                            });
                            window.agricoRenderMarkers(filtered);
                        }
                    },
                    select(loc) {
                        const soilName = loc.soil?.soil_name || 'Soil Location';
                        const rawBrgy = loc.barangay ? loc.barangay.replace(/^(brgy\.?|barangay\.?)\s*/i, '') : '';
                        this.query = rawBrgy ? ('Brgy. ' + rawBrgy) : soilName;
                        this.open = false;
                        const id = loc.location_mapping_id || loc.id;
                        window.focusMapPin(loc.latitude, loc.longitude, id);
                    },
                    clear() {
                        this.query = '';
                        this.open = false;
                        if (window._agricoAllLocations && window.agricoRenderMarkers) {
                            window.agricoRenderMarkers(window._agricoAllLocations);
                        }
                    }
                }"
                @click.outside="open = false"
                x-init="if (typeof L !== 'undefined') { L.DomEvent.disableClickPropagation($el); L.DomEvent.disableScrollPropagation($el); }"
                @mousedown.stop
                @click.stop
                @dblclick.stop
            >
                {{-- SEARCH INPUT BOX --}}
                <div style="position: relative; width: 100%; display: flex; align-items: center;">
                    <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center; pointer-events: none; z-index: 2; width: 18px; height: 18px; color: #16a34a;">
                        <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </span>

                    <input
                        x-model="query"
                        @input="onInput()"
                        @focus="open = true"
                        @keydown.escape="clear(); open = false"
                        type="text"
                        placeholder="Search map: soil, type, code, GPS, barangay..."
                        style="width: 100%; height: 44px; border: 1.5px solid rgba(226, 232, 240, 0.95); border-radius: 16px; background: rgba(255, 255, 255, 0.96); backdrop-filter: blur(8px); padding: 0 44px 0 42px; font-size: 13.5px; font-weight: 600; color: #1e293b; outline: none; box-shadow: 0 4px 18px rgba(0, 0, 0, 0.12), 0 1px 3px rgba(0, 0, 0, 0.06); transition: all 0.2s ease; box-sizing: border-box; line-height: 44px;"
                        onfocus="this.style.borderColor='#22c55e'; this.style.boxShadow='0 4px 20px rgba(34, 197, 94, 0.25), 0 0 0 3px rgba(34, 197, 94, 0.15)';"
                        onblur="this.style.borderColor='rgba(226, 232, 240, 0.95)'; this.style.boxShadow='0 4px 18px rgba(0, 0, 0, 0.12), 0 1px 3px rgba(0, 0, 0, 0.06)';"
                    />

                    <x-search-clear-button
                        x-show="query && query.length > 0"
                        x-cloak
                        click="clear()"
                    />
                </div>

                {{-- AUTO SUGGESTION DROPDOWN INSIDE MAP --}}
                <div
                    class="map-suggestions-dropdown"
                    x-show="open && suggestions.length > 0"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-[-4px]"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-[-4px]"
                >
                    <div style="padding: 8px 14px 6px 14px; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f1f5f9;">
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Location & Soil Suggestions</span>
                        <span x-text="suggestions.length + ' found'" style="font-size: 10px; color: #22c55e; font-weight: 800; white-space: nowrap; flex-shrink: 0; margin-left: 8px;"></span>
                    </div>
                    <template x-for="s in suggestions" :key="s.location_mapping_id || s.id">
                        <div
                            @click="select(s)"
                            class="map-suggestion-item"
                            style="padding: 9px 12px; display: flex; align-items: center; cursor: pointer; transition: background 0.15s ease; border-bottom: 1px solid #f8fafc;"
                            onmouseover="this.style.background='#f0fdf4';"
                            onmouseout="this.style.background='transparent';"
                        >
                            <div style="display: flex; align-items: center; gap: 10px; width: 100%; min-width: 0;">
                                <span style="width: 28px; height: 28px; border-radius: 50%; background: #dcfce7; border: 1px solid rgba(34,197,94,0.3); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg width="14" height="14" fill="#16a34a" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                </span>
                                <div style="flex: 1; min-width: 0; overflow: hidden;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; width: 100%;">
                                        <span style="font-size: 13px; font-weight: 700; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" x-text="s.barangay ? ('Brgy. ' + s.barangay.replace(/^(brgy\.?|barangay\.?)\s*/i, '')) : 'Lal-lo, Cagayan'"></span>
                                        <span style="font-size: 12.5px; font-weight: 700; color: #16a34a; white-space: nowrap; flex-shrink: 0;" x-text="s.soil?.soil_name || 'Soil Location'"></span>
                                    </div>
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 11px; color: #64748b; margin-top: 3px; width: 100%;">
                                        <div style="display: flex; align-items: center; gap: 6px; min-width: 0; overflow: hidden;">
                                            <span style="white-space: nowrap; flex-shrink: 0;">Code: <strong style="color: #334155; font-weight: 700;" x-text="s.soil?.full_code || '—'"></strong></span>
                                            <template x-if="s.soil?.soil_type_label || s.soil?.soil_type">
                                                <span style="font-size: 10px; font-weight: 700; background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; padding: 0.5px 6px; border-radius: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px;" x-text="s.soil?.soil_type_label || s.soil?.soil_type" :title="s.soil?.soil_type_label || s.soil?.soil_type"></span>
                                            </template>
                                        </div>
                                        <span style="white-space: nowrap; font-size: 10.5px; color: #94a3b8; flex-shrink: 0;">GPS: <span x-text="Number(s.latitude).toFixed(4) + ', ' + Number(s.longitude).toFixed(4)"></span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- MAP CANVAS CONTAINER --}}
            <div id="soil-map" wire:ignore style="width: 100%; height: 520px; border-radius: 20px; z-index: 1; overflow: hidden; position: relative; background: #e2e8f0;" class="border border-gray-100 shadow-inner"></div>

            {{-- HIDDEN DATA CONTAINER FOR LIVEWIRE MORPH MAP UPDATES --}}
            <div id="all-map-locations-json" data-locations='@json($allMapLocations)' style="display: none;"></div>
        </div>
    </div>

    {{-- SEARCH AND FILTER CARD (PLACED AFTER THE MAP) --}}
    <div class="ui-reveal" style="--i:4; background:#ffffff; border-radius:20px; border:1px solid #f1f5f9; box-shadow:0 1px 3px rgba(0,0,0,0.03); padding:12px 16px; display:flex; align-items:center; gap:14px; width:100%; box-sizing:border-box; flex-wrap:wrap;">
        {{-- SEARCH INPUT WITH AUTO-SUGGESTIONS --}}
        <div x-data="{ open: true }" @click.outside="open = false" style="position:relative; flex:1; min-width:240px;">
            <svg style="position:absolute; left:16px; top:50%; transform:translateY(-50%); width:18px; height:18px; color:#94a3b8; z-index:2;" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input
                wire:model.live.debounce.250ms="search"
                @focus="open = true"
                @input="open = true"
                @keydown.escape="$wire.set('search', ''); $el.value = ''; open = false;"
                type="text"
                placeholder="Search soil name, full code, coordinates, barangay..."
                style="width:100%; border:1px solid #e2e8f0; border-radius:16px; background:#ffffff; padding:10px 44px 10px 46px; font-size:14px; font-weight:500; color:#1e293b; outline:none; transition:all 0.2s ease; box-sizing:border-box;"
                onfocus="this.style.borderColor='#22c55e'; this.style.boxShadow='0 0 0 3px rgba(68,175,53,0.15)';"
                onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none';"
            />
            @if($search)
                <x-search-clear-button wireModel="search" />
            @endif

            {{-- AUTO SUGGESTION DROPDOWN --}}
            @if(!empty($tableSearchSuggestions) && count($tableSearchSuggestions) > 0)
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    style="position:absolute; top:calc(100% + 6px); left:0; right:0; z-index:50; background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; box-shadow:0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.05); max-height:300px; overflow-y:auto; padding:6px 0;"
                >
                    <div style="padding:6px 14px 4px 14px; font-size:11px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em;">
                        Location & Soil Suggestions
                    </div>
                    @foreach($tableSearchSuggestions as $s)
                        @php
                            $soilName = $s->soil->soil_name ?? 'Soil Location';
                            $fullCode = $s->soil->full_code ?? 'Munsell Code';
                            $brgy = $s->barangay ? 'Brgy. ' . $s->barangay . ', Lal-lo, Cagayan' : 'Municipality of Lal-lo, Cagayan';
                            $coords = number_format($s->latitude, 4) . ', ' . number_format($s->longitude, 4);
                            $suggestVal = $s->barangay ?: ($soilName !== 'Soil Location' ? $soilName : $fullCode);
                        @endphp
                        <div
                            wire:click="selectSuggestion('{{ addslashes($suggestVal) }}')"
                            @click="open = false"
                            style="padding:8px 14px; display:flex; align-items:center; justify-content:space-between; cursor:pointer; transition:background 0.15s ease;"
                            onmouseover="this.style.background='#f8fafc';"
                            onmouseout="this.style.background='transparent';"
                        >
                            <div style="display:flex; align-items:center; gap:10px; width:100%;">
                                <span style="width:20px; height:20px; border-radius:50%; background:#22c55e; border:1.5px solid rgba(0,0,0,0.15); display:inline-flex; align-items:center; justify-content:center; color:#fff; flex-shrink:0;">
                                    <svg width="10" height="10" fill="#ffffff" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                </span>
                                <div style="flex:1; overflow:hidden;">
                                    <div style="font-size:13.5px; font-weight:700; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        {{ $brgy }} <span style="color:#22c55e; margin-left:6px;">{{ $soilName }}</span>
                                    </div>
                                    <div style="font-size:11.5px; color:#64748b; margin-top:2px; display:flex; align-items:center; gap:10px;">
                                        <span>Code: <strong style="color:#334155;">{{ $fullCode }}</strong></span>
                                        <span>GPS: {{ $coords }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- FUNNEL ICON --}}
        <svg class="ui-reveal-text" width="18" height="18" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="flex-shrink:0;">
            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
        </svg>

        {{-- FILTER DROPDOWNS --}}
        <select
            class="ui-reveal-text"
            wire:model.live="selectedSoil"
            style="border:1px solid #e2e8f0; border-radius:14px; background:#ffffff; padding:8px 16px; font-size:13px; font-weight:600; color:#334155; outline:none; cursor:pointer; transition:all 0.2s ease; flex-shrink:0;"
            onfocus="this.style.borderColor='#22c55e';"
            onblur="this.style.borderColor='#e2e8f0';"
        >
            <option value="">All Soils</option>
            @foreach($soils as $s)
                <option value="{{ $s->id }}">{{ $s->soil_name }}</option>
            @endforeach
        </select>

        <select
            class="ui-reveal-text"
            wire:model.live="selectedStatus"
            style="border:1px solid #e2e8f0; border-radius:14px; background:#ffffff; padding:8px 16px; font-size:13px; font-weight:600; color:#334155; outline:none; cursor:pointer; transition:all 0.2s ease; flex-shrink:0;"
            onfocus="this.style.borderColor='#22c55e';"
            onblur="this.style.borderColor='#e2e8f0';"
        >
            <option value="">All Statuses</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
            <option value="Pending">Pending</option>
        </select>

        {{-- CALENDAR DATE FILTER --}}
        <div class="ui-reveal-text" style="display:inline-flex; align-items:center; gap:8px; border:1px solid #e2e8f0; border-radius:14px; background:#ffffff; padding:6px 14px; transition:all 0.2s ease; flex-shrink:0;" onmouseover="this.style.borderColor='#22c55e';" onmouseout="if(!document.activeElement || document.activeElement!==this.querySelector('input')) this.style.borderColor='#e2e8f0';">
            <svg width="16" height="16" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="flex-shrink:0;">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <input
                wire:model.live="selectedDate"
                type="date"
                style="border:none; background:transparent; font-size:13px; font-weight:600; color:#334155; outline:none; cursor:pointer; font-family:inherit;"
                title="Filter by Date"
            />
            @if($selectedDate)
                <x-close-button size="sm" click="$set('selectedDate', '')" title="Clear date filter" ariaLabel="Clear date filter" style="margin-left:4px;" />
            @endif
        </div>

        {{-- RESULTS COUNTER --}}
        <div style="font-size:13px; font-weight:500; color:#94a3b8; flex-shrink:0;">
            {{ $tableMappings->total() }} results
        </div>
    </div>

    {{-- MAPPED LOCATIONS TABLE CARD (MATCHING USER'S IMAGE DESIGN) --}}
    <div class="ui-reveal bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden" style="--i:5">
        {{-- CARD HEADER --}}
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <h2 class="ui-reveal-text text-base font-bold text-gray-900">
                Mapped Locations ({{ $tableMappings->total() }})
            </h2>
        </div>

        {{-- TABLE --}}
        <div class="overflow-x-auto map-table-container">
            <table class="w-full map-table">
                <thead>
                    <tr class="bg-[#F1FBF1] border-b border-gray-100">

                        {{-- SOIL NAME --}}
                        <th
                            wire:click="sortBy('soil_name')"
                            class="soil-th-sortable px-5 py-4 text-left text-xs font-bold text-gray-500 cursor-pointer select-none group whitespace-nowrap col-sticky-soil"
                        >
                            <div class="flex items-center gap-1">
                                <span class="group-hover:text-[#22c55e] transition-colors duration-200">SOIL NAME</span>
                                <span class="sort-arrows">
                                    @if($sortField === 'soil_name')
                                        @if($sortDirection === 'asc')
                                            <svg class="sort-chevron sort-chevron-active sort-chevron-up" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M4 9L7 5.5L10 9" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @else
                                            <svg class="sort-chevron sort-chevron-active sort-chevron-down" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M4 5L7 8.5L10 5" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @endif
                                    @else
                                        <span class="sort-chevron-pair">
                                            <svg class="sort-chevron-idle sort-chevron-idle-up" width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="M1 5L5 1L9 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            <svg class="sort-chevron-idle sort-chevron-idle-down" width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </span>
                                    @endif
                                </span>
                            </div>
                        </th>

                        {{-- COORDINATES --}}
                        <th
                            wire:click="sortBy('latitude')"
                            class="soil-th-sortable px-5 py-4 text-left text-xs font-bold text-gray-500 cursor-pointer select-none group whitespace-nowrap col-coordinates"
                        >
                            <div class="flex items-center gap-1">
                                <span class="group-hover:text-[#22c55e] transition-colors duration-200">COORDINATES</span>
                                <span class="sort-arrows">
                                    @if($sortField === 'latitude')
                                        @if($sortDirection === 'asc')
                                            <svg class="sort-chevron sort-chevron-active sort-chevron-up" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M4 9L7 5.5L10 9" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @else
                                            <svg class="sort-chevron sort-chevron-active sort-chevron-down" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M4 5L7 8.5L10 5" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @endif
                                    @else
                                        <span class="sort-chevron-pair">
                                            <svg class="sort-chevron-idle sort-chevron-idle-up" width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="M1 5L5 1L9 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            <svg class="sort-chevron-idle sort-chevron-idle-down" width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </span>
                                    @endif
                                </span>
                            </div>
                        </th>

                        {{-- SCAN NOTE --}}
                        <th
                            wire:click="sortBy('scan_note')"
                            class="soil-th-sortable px-5 py-4 text-left text-xs font-bold text-gray-500 cursor-pointer select-none group whitespace-nowrap col-scan-note"
                        >
                            <div class="flex items-center gap-1">
                                <span class="group-hover:text-[#22c55e] transition-colors duration-200">SCAN NOTE</span>
                                <span class="sort-arrows">
                                    @if($sortField === 'scan_note')
                                        @if($sortDirection === 'asc')
                                            <svg class="sort-chevron sort-chevron-active sort-chevron-up" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M4 9L7 5.5L10 9" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @else
                                            <svg class="sort-chevron sort-chevron-active sort-chevron-down" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M4 5L7 8.5L10 5" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @endif
                                    @else
                                        <span class="sort-chevron-pair">
                                            <svg class="sort-chevron-idle sort-chevron-idle-up" width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="M1 5L5 1L9 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            <svg class="sort-chevron-idle sort-chevron-idle-down" width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </span>
                                    @endif
                                </span>
                            </div>
                        </th>

                        {{-- TIMESTAMP --}}
                        <th
                            wire:click="sortBy('timestamp')"
                            class="soil-th-sortable px-5 py-4 text-left text-xs font-bold text-gray-500 cursor-pointer select-none group whitespace-nowrap col-timestamp"
                        >
                            <div class="flex items-center gap-1">
                                <span class="group-hover:text-[#22c55e] transition-colors duration-200">TIMESTAMP</span>
                                <span class="sort-arrows">
                                    @if($sortField === 'timestamp')
                                        @if($sortDirection === 'asc')
                                            <svg class="sort-chevron sort-chevron-active sort-chevron-up" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M4 9L7 5.5L10 9" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @else
                                            <svg class="sort-chevron sort-chevron-active sort-chevron-down" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M4 5L7 8.5L10 5" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @endif
                                    @else
                                        <span class="sort-chevron-pair">
                                            <svg class="sort-chevron-idle sort-chevron-idle-up" width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="M1 5L5 1L9 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            <svg class="sort-chevron-idle sort-chevron-idle-down" width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </span>
                                    @endif
                                </span>
                            </div>
                        </th>

                        {{-- STATUS --}}
                        <th
                            wire:click="sortBy('status')"
                            class="soil-th-sortable px-4 py-4 text-center text-xs font-bold text-gray-500 cursor-pointer select-none group whitespace-nowrap col-status"
                        >
                            <div class="flex items-center justify-center gap-1">
                                <span class="group-hover:text-[#22c55e] transition-colors duration-200">STATUS</span>
                                <span class="sort-arrows">
                                    @if($sortField === 'status')
                                        @if($sortDirection === 'asc')
                                            <svg class="sort-chevron sort-chevron-active sort-chevron-up" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M4 9L7 5.5L10 9" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @else
                                            <svg class="sort-chevron sort-chevron-active sort-chevron-down" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M4 5L7 8.5L10 5" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        @endif
                                    @else
                                        <span class="sort-chevron-pair">
                                            <svg class="sort-chevron-idle sort-chevron-idle-up" width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="M1 5L5 1L9 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            <svg class="sort-chevron-idle sort-chevron-idle-down" width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </span>
                                    @endif
                                </span>
                            </div>
                        </th>

                        {{-- ACTION --}}
                        <th class="px-3 py-4 text-center text-xs font-bold text-gray-500 whitespace-nowrap col-sticky-action" style="padding-right: 12px !important;">
                            ACTION
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($tableMappings as $map)
                    @php
                        $searchTrim = trim($search);
                        $cleanSearch = preg_replace('/^(brgy\.?|barangay\.?)\s*/i', '', $searchTrim);
                        $isGenericBrgy = in_array(strtolower($searchTrim), ['brgy', 'brgy.', 'barangay', 'barangay.']);
                        $sSoil = $map->soil->soil_name ?? '';
                        $sCode = $map->soil->full_code ?? '';
                        $sBrgy = $map->barangay ?? '';
                        $sLat = (string)$map->latitude;
                        $sLng = (string)$map->longitude;
                        $isSearchMatch = !empty($searchTrim) && (
                            ($isGenericBrgy && !empty($sBrgy)) ||
                            (!empty($sSoil) && stripos($sSoil, $searchTrim) !== false) ||
                            (!empty($sCode) && stripos($sCode, $searchTrim) !== false) ||
                            (!empty($sBrgy) && stripos($sBrgy, $cleanSearch ?: $searchTrim) !== false) ||
                            (!empty($sLat) && stripos($sLat, $searchTrim) !== false) ||
                            (!empty($sLng) && stripos($sLng, $searchTrim) !== false) ||
                            (str_starts_with($searchTrim, '#') && '#' . $map->id === $searchTrim)
                        );
                    @endphp
                    <tr
                        wire:key="map-row-{{ $map->location_mapping_id ?? $map->id }}-{{ strtolower($map->status ?? 'active') }}"
                        x-data="{
                            status: '{{ $map->status ?? 'Active' }}',
                            toggling: false,
                            async doToggle() {
                                if (this.toggling) return;
                                this.toggling = true;
                                const prev = this.status;
                                const nextStatus = this.status === 'Active' ? 'Inactive' : 'Active';
                                this.status = nextStatus;
                                if (window.agricoUpdateMarkerStatus) {
                                    window.agricoUpdateMarkerStatus('{{ $map->location_mapping_id ?? $map->id }}', nextStatus);
                                }
                                try {
                                    const res = await fetch('/manage-map/{{ $map->location_mapping_id ?? $map->id }}/toggle-status', {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json'
                                        }
                                    });
                                    if (!res.ok) throw new Error();
                                    if (window.Livewire) {
                                        window.Livewire.dispatch('mapping-updated');
                                    }
                                } catch (e) {
                                    this.status = prev;
                                    if (window.agricoUpdateMarkerStatus) {
                                        window.agricoUpdateMarkerStatus('{{ $map->location_mapping_id ?? $map->id }}', prev);
                                    }
                                } finally {
                                    this.toggling = false;
                                }
                            }
                        }"
                        class="border-b border-gray-100 transition cursor-pointer {{ $isSearchMatch ? 'bg-emerald-50/80 border-l-4 border-l-emerald-500 font-medium' : 'hover:bg-green-50/40' }}"
                        x-on:click="if (!$event.target.closest('button, a, input, select') && status === 'Active') focusMapPin({{ $map->latitude }}, {{ $map->longitude }}, '{{ $map->location_mapping_id ?? $map->id }}')"
                    >

                        {{-- SOIL NAME --}}
                        <td class="px-5 py-4 whitespace-nowrap col-sticky-soil">
                            <div>
                                <div class="text-sm font-bold text-[#6b7280] soil-name-title" style="font-size: 13.5px; font-weight: 700; color: #6b7280;">
                                    {{ $map->soil->soil_name ?? '—' }}
                                </div>
                                <div class="text-xs font-medium text-slate-400 soil-code-sub" style="font-size: 11.5px; font-weight: 500; color: #94a3b8;">
                                    {{ $map->soil->full_code ?? '' }}
                                </div>
                                @if($map->barangay || $map->latitude)
                                    <div class="map-mobile-coords text-xs font-semibold text-emerald-600 mt-1" style="display: none; font-size: 11px;">
                                        {{ $map->barangay ? 'Brgy. ' . $map->barangay : '' }} {{ $map->latitude ? number_format($map->latitude, 3) . ', ' . number_format($map->longitude, 3) : '' }}
                                    </div>
                                @endif
                            </div>
                        </td>

                        {{-- COORDINATES --}}
                        <td class="px-5 py-4 col-coordinates">
                            <div class="flex items-center gap-1.5 text-xs sm:text-[13px] font-bold text-[#6b7280]">
                                <svg class="w-3.5 h-3.5 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="whitespace-nowrap">{{ number_format($map->latitude, 4) }}, {{ number_format($map->longitude, 4) }}</span>
                            </div>
                            @if($map->barangay)
                                <div class="text-[11px] sm:text-xs font-medium text-slate-400 mt-0.5 ml-5 truncate" style="max-width: 240px;" title="Brgy. {{ $map->barangay }}, Lal-lo, Cagayan">
                                    Brgy. {{ $map->barangay }}
                                </div>
                            @endif
                        </td>

                        {{-- SCAN NOTE --}}
                        <td class="px-5 py-4 col-scan-note">
                            <div class="text-xs sm:text-[13px] font-bold text-[#6b7280] truncate" style="font-weight: 700; color: #6b7280;" title="{{ $map->soilScan->scan_note ?? '—' }}">
                                {{ !empty($map->soilScan->scan_note) ? \Illuminate\Support\Str::limit($map->soilScan->scan_note, 45, '...') : '—' }}
                            </div>
                        </td>

                        {{-- TIMESTAMP --}}
                        <td class="px-5 py-4 text-xs sm:text-[12.5px] font-bold text-[#6b7280] whitespace-nowrap col-timestamp">
                            {{ $map->timestamp ? \Illuminate\Support\Carbon::parse($map->timestamp)->format('Y-m-d H:i') : $map->created_at?->format('Y-m-d H:i') }}
                        </td>

                        {{-- STATUS — optimistic toggle via Alpine, Livewire saves in background --}}
                        <td class="px-3 py-4 whitespace-nowrap col-status text-center">
                            <button
                                type="button"
                                @click="doToggle()"
                                :title="'Click to set ' + (status === 'Active' ? 'Inactive' : 'Active')"
                                :style="status === 'Active'
                                    ? 'background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0; display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:4px 12px; border-radius:9999px; font-size:12px; font-weight:700; cursor:pointer; transition:all 0.15s ease;'
                                    : 'background:#f3f4f6; color:#4b5563; border:1px solid #d1d5db; display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:4px 12px; border-radius:9999px; font-size:12px; font-weight:700; cursor:pointer; transition:all 0.15s ease;'"
                                onmouseover="this.style.opacity='0.85'; this.style.transform='scale(1.03)';"
                                onmouseout="this.style.opacity='1'; this.style.transform='scale(1)';"
                            >
                                <span :style="status === 'Active' ? 'background:#22c55e; width:7px; height:7px; border-radius:50%; display:inline-block; flex-shrink:0;' : 'background:#9ca3af; width:7px; height:7px; border-radius:50%; display:inline-block; flex-shrink:0;'"></span>
                                <span x-text="status"></span>
                            </button>
                        </td>

                        {{-- ACTION --}}
                        <td class="px-3 py-4 whitespace-nowrap col-sticky-action text-center" style="padding-right: 12px !important;">
                            <div class="flex justify-end items-center gap-1.5 whitespace-nowrap">
                                {{-- VIEW --}}
                                <button
                                    type="button"
                                    @click="$dispatch('view-mapping',{id:{{ $map->location_mapping_id ?? $map->id }}})"
                                    class="agrico-view-btn"
                                    title="View Details"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>

                                {{-- DELETE --}}
                                <button
                                    type="button"
                                    @click="$dispatch('delete-mapping',{id:{{ $map->id }}})"
                                    class="agrico-delete-btn"
                                    title="Delete Mapping"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="padding: 64px 24px; text-align: center; border-bottom: none;">
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px;">
                                <div style="width: 56px; height: 56px; border-radius: 16px; background: #f0fdf4; border: 1px solid #dcfce7; display: flex; align-items: center; justify-content: center; color: #22c55e; box-shadow: 0 4px 14px rgba(22,163,74,0.12);">
                                    <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                </div>
                                <div style="font-size: 15.5px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                                    No Location Pins Found
                                </div>
                                <div style="font-size: 12.5px; font-weight: 500; color: #64748b; max-width: 360px; line-height: 1.4;">
                                    No map location pin entries matched your search query or active filter settings.
                                </div>
                                @if(\App\Models\LocationMapping::count() === 0)
                                <button
                                    wire:click="seedSamplePins"
                                    type="button"
                                    style="margin-top: 10px; display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 12px; font-size: 13px; font-weight: 700; background: #22c55e; color: #ffffff; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.28); transition: all 0.2s ease;"
                                    onmouseover="this.style.background='#16a34a';"
                                    onmouseout="this.style.background='#22c55e';"
                                >
                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <path d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Generate Sample Map Pins
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION FOOTER --}}
        <div style="padding: 16px 24px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; background: #fff;">
            {{-- LEFT SIDE: ROWS PER PAGE & RANGE --}}
            <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #64748b;">
                <span>Rows per page:</span>
                <select
                    wire:model.live="perPage"
                    style="border: 1px solid #e2e8f0; background: #fff; border-radius: 8px; padding: 4px 8px; font-size: 13px; color: #334155; font-weight: 600; outline: none; cursor: pointer;"
                >
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
                <span style="color: #94a3b8; font-size: 13px; margin-left: 4px;">
                    {{ $tableMappings->firstItem() ?? 0 }}–{{ $tableMappings->lastItem() ?? 0 }} of {{ $tableMappings->total() }}
                </span>
            </div>

            {{-- RIGHT SIDE: PREV, PAGE NUMBERS, NEXT BUTTONS --}}
            @if ($tableMappings->hasPages())
                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: nowrap;">
                    @if ($tableMappings->onFirstPage())
                        <button disabled style="padding: 5px 12px; border-radius: 8px; border: 1px solid #f1f5f9; background: #f8fafc; color: #cbd5e1; font-size: 12.5px; font-weight: 600; cursor: not-allowed;">Prev</button>
                    @else
                        <button wire:click="previousPage" style="padding: 5px 12px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all 0.15s ease;" onmouseover="this.style.borderColor='#22c55e'; this.style.color='#22c55e';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#475569';">Prev</button>
                    @endif

                    @php
                        $currentPage = $tableMappings->currentPage();
                        $lastPage = $tableMappings->lastPage();

                        if ($lastPage <= 3) {
                            $startPage = 1;
                            $endPage = $lastPage;
                        } else {
                            if ($currentPage <= 2) {
                                $startPage = 1;
                                $endPage = 3;
                            } elseif ($currentPage >= $lastPage - 1) {
                                $startPage = $lastPage - 2;
                                $endPage = $lastPage;
                            } else {
                                $startPage = $currentPage - 1;
                                $endPage = $currentPage + 1;
                            }
                        }
                    @endphp

                    @if ($startPage > 1)
                        <button wire:click="gotoPage({{ max(1, $startPage - 1) }})" style="min-width: 24px; height: 32px; background: transparent; border: none; color: #64748b; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 0 4px; transition: color 0.15s ease;" onmouseover="this.style.color='#22c55e';" onmouseout="this.style.color='#64748b';" title="Page {{ max(1, $startPage - 1) }}">...</button>
                    @endif

                    @foreach ($tableMappings->getUrlRange($startPage, $endPage) as $page => $url)
                        @if ($page == $currentPage)
                            <button wire:click="gotoPage({{ $page }})" style="width: 32px; height: 32px; border-radius: 8px; background: #22c55e !important; color: #fff !important; font-size: 13px; font-weight: 700; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(34,197,94,0.35);">{{ $page }}</button>
                        @else
                            <button wire:click="gotoPage({{ $page }})" style="width: 32px; height: 32px; border-radius: 8px; background: #fff; border: 1px solid #e2e8f0; color: #334155; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.15s ease;" onmouseover="this.style.borderColor='#22c55e'; this.style.color='#22c55e';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#334155';">{{ $page }}</button>
                        @endif
                    @endforeach

                    @if ($endPage < $lastPage)
                        <button wire:click="gotoPage({{ min($lastPage, $endPage + 1) }})" style="min-width: 24px; height: 32px; background: transparent; border: none; color: #64748b; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 0 4px; transition: color 0.15s ease;" onmouseover="this.style.color='#22c55e';" onmouseout="this.style.color='#64748b';" title="Page {{ min($lastPage, $endPage + 1) }}">...</button>
                    @endif

                    @if ($tableMappings->hasMorePages())
                        <button wire:click="nextPage" style="padding: 5px 12px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all 0.15s ease;" onmouseover="this.style.borderColor='#22c55e'; this.style.color='#22c55e';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#475569';">Next</button>
                    @else
                        <button disabled style="padding: 5px 12px; border-radius: 8px; border: 1px solid #f1f5f9; background: #f8fafc; color: #cbd5e1; font-size: 12.5px; font-weight: 600; cursor: not-allowed;">Next</button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- MODALS --}}
    <livewire:admin.mapping.mapping-detail-modal />
    <livewire:admin.mapping.mapping-delete-modal />

    <style>
        /* ── RESPONSIVE TABLE STYLING (MATCHING STUDENT RECORDS & SOIL SCAN HISTORY) ── */
        .map-table-container {
            width: 100% !important;
            overflow-x: auto !important;
            overflow-y: visible !important;
            scrollbar-width: none !important; /* Firefox */
            -ms-overflow-style: none !important;  /* IE/Edge */
        }
        .map-table-container::-webkit-scrollbar {
            display: none !important; /* Chrome/Safari */
        }

        .map-table {
            width: 100% !important;
            min-width: 0 !important;
            table-layout: auto !important;
            border-collapse: collapse !important;
        }

        /* Default cell styling */
        .map-table th,
        .map-table td {
            box-sizing: border-box !important;
        }

        /* ── STATUS & ACTION COLUMN STYLING (CLEAN & SEAMLESS - NO SEPARATING LINE) ── */
        .map-table th.col-sticky-action,
        .map-table td.col-sticky-action {
            position: sticky !important;
            right: 0 !important;
            width: 110px !important;
            min-width: 110px !important;
            max-width: 110px !important;
            white-space: nowrap !important;
            box-shadow: none !important;
            border-left: none !important;
            border-right: none !important;
            z-index: 10 !important;
            background-color: #ffffff !important;
            text-align: center !important;
            padding-right: 12px !important;
        }
        .map-table td.col-sticky-action .flex {
            justify-content: flex-end !important;
        }
        .map-table th.col-sticky-action {
            z-index: 20 !important;
            background-color: #F1FBF1 !important;
            box-shadow: none !important;
            border-left: none !important;
            border-right: none !important;
        }

        .map-table th.col-status,
        .map-table td.col-status {
            position: sticky !important;
            right: 110px !important;
            width: 110px !important;
            min-width: 110px !important;
            max-width: 110px !important;
            white-space: nowrap !important;
            box-shadow: none !important;
            border-left: none !important;
            border-right: none !important;
            z-index: 10 !important;
            background-color: #ffffff !important;
            text-align: center !important;
        }
        .map-table th.col-status {
            z-index: 20 !important;
            background-color: #F1FBF1 !important;
            box-shadow: none !important;
            border-left: none !important;
            border-right: none !important;
        }

        /* Hover & Active row backgrounds for sticky cells */
        .map-table tbody tr:hover td.col-sticky-action,
        .map-table tbody tr:hover td.col-status,
        .map-table tbody tr.hover\:bg-green-50\/40:hover td.col-sticky-action,
        .map-table tbody tr.hover\:bg-green-50\/40:hover td.col-status {
            background-color: #f7fdf7 !important;
        }
        .map-table tbody tr.bg-emerald-50\/80 td.col-sticky-action,
        .map-table tbody tr.bg-emerald-50\/80 td.col-status {
            background-color: #ecfdf5 !important;
        }
        .dark .map-table th.col-sticky-action,
        .dark .map-table th.col-status {
            background-color: #1e293b !important;
        }
        .dark .map-table td.col-sticky-action,
        .dark .map-table td.col-status {
            background-color: #0f172a !important;
        }

        /* Action buttons styling (Matching Standard 36px Desktop & Admin Global) */
        .map-table .agrico-view-btn,
        .map-table .agrico-delete-btn {
            width: 36px !important;
            height: 36px !important;
            min-width: 36px !important;
            max-width: 36px !important;
            min-height: 36px !important;
            max-height: 36px !important;
            border-radius: 11px !important;
        }
        .map-table .agrico-view-btn svg,
        .map-table .agrico-delete-btn svg {
            width: 17px !important;
            height: 17px !important;
        }

        /* In-map Floating Search & Dropdown Responsive */
        .map-floating-search-box {
            position: absolute !important;
            top: 14px !important;
            right: 14px !important;
            left: auto !important;
            width: 380px !important;
            max-width: calc(100% - 28px) !important;
            z-index: 1001 !important;
        }
        .map-suggestions-dropdown {
            position: absolute !important;
            top: calc(100% + 6px) !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 1002 !important;
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(12px) !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 16px !important;
            box-shadow: 0 12px 28px -4px rgba(0, 0, 0, 0.16), 0 4px 10px -2px rgba(0, 0, 0, 0.08) !important;
            max-height: 290px !important;
            overflow-y: auto !important;
            padding: 6px 0 !important;
            -webkit-overflow-scrolling: touch !important;
        }

        @media (max-width: 639px) {
            .map-floating-search-box {
                top: 10px !important;
                left: 10px !important;
                right: 10px !important;
                width: auto !important;
                max-width: none !important;
            }
            .map-suggestions-dropdown {
                max-height: 250px !important;
                border-radius: 14px !important;
            }
        }

        /* ══════════════════════════════════════════════════════════════════
           TIER 1. NARROW MOBILE ONLY (<= 539px - All iPhones in Portrait)
           3 Columns: SOIL NAME (with embedded coords) | STATUS | ACTION
           Fixed 100% table layout - Zero overflow, both action buttons fully visible!
        ══════════════════════════════════════════════════════════════════ */
        @media (max-width: 539px) {
            .map-table-container {
                overflow-x: hidden !important;
                width: 100% !important;
            }
            .map-table {
                table-layout: fixed !important;
                width: 100% !important;
                min-width: 0 !important;
            }
            .col-map-id,
            .col-coordinates,
            .col-scan-note,
            .col-timestamp {
                display: none !important;
            }
            .map-mobile-coords {
                display: block !important;
            }
            .map-table th,
            .map-table td {
                padding: 10px 4px !important;
                box-sizing: border-box !important;
            }
            /* 1. SOIL NAME: Takes all remaining flexible width */
            .map-table th.col-sticky-soil,
            .map-table td.col-sticky-soil {
                position: static !important;
                left: auto !important;
                right: auto !important;
                padding-left: 12px !important;
                padding-right: 4px !important;
                width: auto !important;
                overflow: hidden !important;
            }
            .map-table td.col-sticky-soil .soil-name-title,
            .map-table td.col-sticky-soil .soil-code-sub,
            .map-table td.col-sticky-soil .map-mobile-coords {
                width: 100% !important;
                max-width: 100% !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                white-space: nowrap !important;
                display: block !important;
            }
            .map-table td.col-sticky-soil .soil-name-title {
                font-size: 13.5px !important;
                font-weight: 700 !important;
                color: #0f172a !important;
            }
            .map-table td.col-sticky-soil .soil-code-sub {
                font-size: 11px !important;
                color: #94a3b8 !important;
            }
            .map-table td.col-sticky-soil .map-mobile-coords {
                font-size: 10.5px !important;
            }
            /* 2. STATUS: Fixed 110px width */
            .map-table th.col-status,
            .map-table td.col-status {
                position: static !important;
                left: auto !important;
                right: auto !important;
                display: table-cell !important;
                width: 110px !important;
                min-width: 110px !important;
                max-width: 110px !important;
                box-shadow: none !important;
                border-left: none !important;
                border-right: none !important;
                padding-left: 2px !important;
                padding-right: 2px !important;
                text-align: center !important;
            }
            .map-table td.col-status button {
                padding: 4px 10px !important;
                font-size: 11.5px !important;
                gap: 4px !important;
                max-width: 100% !important;
            }
            .map-table td.col-status button span:first-child {
                width: 6px !important;
                height: 6px !important;
            }
            /* 3. ACTION: Fixed width guaranteed to fit both buttons */
            .map-table th.col-sticky-action,
            .map-table td.col-sticky-action {
                position: static !important;
                left: auto !important;
                right: auto !important;
                display: table-cell !important;
                width: 95px !important;
                min-width: 95px !important;
                max-width: 95px !important;
                box-shadow: none !important;
                border-left: none !important;
                border-right: none !important;
                padding-left: 2px !important;
                padding-right: 12px !important;
                text-align: center !important;
            }
            .map-table td.col-sticky-action .flex {
                gap: 5px !important;
                justify-content: flex-end !important;
            }
            .map-table .agrico-view-btn,
            .map-table .agrico-delete-btn {
                width: 34px !important;
                height: 34px !important;
                min-width: 34px !important;
                max-width: 34px !important;
                min-height: 34px !important;
                max-height: 34px !important;
                border-radius: 10px !important;
                padding: 0 !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                flex-shrink: 0 !important;
            }
            .map-table .agrico-view-btn svg,
            .map-table .agrico-delete-btn svg {
                width: 16px !important;
                height: 16px !important;
            }
        }

        /* ══════════════════════════════════════════════════════════════════
           TIER 2. COMPACT & TABLET (460px - 899px)
           4 Columns: SOIL NAME | COORDINATES | STATUS | ACTION
           Fills the middle space smoothly with Coordinates & Barangay,
           eliminating large empty space with No horizontal scrollbar!
        ══════════════════════════════════════════════════════════════════ */
        @media (min-width: 540px) and (max-width: 899px) {
            .map-table-container {
                overflow-x: hidden !important;
            }
            .col-map-id,
            .col-scan-note,
            .col-timestamp {
                display: none !important;
            }
            .col-coordinates {
                display: table-cell !important;
            }
            .map-mobile-coords {
                display: none !important;
            }
            .map-table th,
            .map-table td {
                padding: 12px 10px !important;
            }
            .map-table th.col-sticky-soil,
            .map-table td.col-sticky-soil {
                position: static !important;
                left: auto !important;
                right: auto !important;
                padding-left: 12px !important;
                width: 170px !important;
                min-width: 140px !important;
                max-width: 190px !important;
            }

        /* Desktop Tier (680px - 899px): When ID is hidden, first column has 20px left padding */
        @media (min-width: 680px) and (max-width: 899px) {
            .map-table th.col-sticky-soil,
            .map-table td.col-sticky-soil {
                padding-left: 20px !important;
            }
        }
            .map-table td.col-sticky-soil .soil-name-title {
                font-size: 13.5px !important;
                font-weight: 700 !important;
                color: #6b7280 !important;
                max-width: 170px !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                white-space: nowrap !important;
            }
            .map-table td.col-sticky-soil .soil-code-sub {
                font-size: 11.5px !important;
                color: #94a3b8 !important;
            }
            .map-table th.col-coordinates,
            .map-table td.col-coordinates {
                width: auto !important;
                min-width: 140px !important;
                padding-left: 8px !important;
                padding-right: 8px !important;
            }
            .map-table th.col-status,
            .map-table td.col-status {
                position: static !important;
                left: auto !important;
                right: auto !important;
                width: 92px !important;
                min-width: 92px !important;
                max-width: 92px !important;
                box-shadow: none !important;
                border-left: none !important;
                border-right: none !important;
                padding-left: 2px !important;
                padding-right: 2px !important;
            }
            .map-table th.col-sticky-action,
            .map-table td.col-sticky-action {
                position: static !important;
                left: auto !important;
                right: auto !important;
                width: 96px !important;
                min-width: 96px !important;
                max-width: 96px !important;
                box-shadow: none !important;
                border-left: none !important;
                border-right: none !important;
                padding-left: 2px !important;
                padding-right: 12px !important;
            }
            .map-table td.col-sticky-action .flex {
                justify-content: flex-end !important;
            }
        }

        /* ══════════════════════════════════════════════════════════════════
           TIER 3. MEDIUM LAPTOPS (900px - 1149px)
           6 Columns: ID | SOIL NAME | COORDINATES | TIMESTAMP | STATUS | ACTION
           (Drops only Scan Note to ensure 100% fit with NO scrollbar!)
        ══════════════════════════════════════════════════════════════════ */
        @media (min-width: 900px) and (max-width: 1149px) {
            .col-scan-note {
                display: none !important;
            }
            .col-map-id,
            .col-coordinates,
            .col-timestamp {
                display: table-cell !important;
            }
            .map-mobile-coords {
                display: none !important;
            }
            .map-table th,
            .map-table td {
                padding: 14px 16px !important;
            }
            .map-table th.col-map-id,
            .map-table td.col-map-id {
                width: 65px !important;
                min-width: 65px !important;
                padding-left: 18px !important;
                font-size: 13px !important;
            }
            .map-table th.col-sticky-soil,
            .map-table td.col-sticky-soil {
                padding-left: 20px !important;
                width: 190px !important;
                min-width: 180px !important;
                max-width: 210px !important;
            }
            .map-table td.col-sticky-soil .soil-name-title {
                font-size: 13.5px !important;
                font-weight: 700 !important;
                color: #6b7280 !important;
                max-width: 190px !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                white-space: nowrap !important;
            }
            .map-table td.col-sticky-soil .soil-code-sub {
                font-size: 11.5px !important;
                color: #94a3b8 !important;
            }
            .map-table th.col-coordinates,
            .map-table td.col-coordinates {
                width: auto !important;
                min-width: 200px !important;
            }
            .map-table th.col-timestamp,
            .map-table td.col-timestamp {
                width: 135px !important;
                min-width: 135px !important;
                font-size: 12px !important;
                white-space: nowrap !important;
            }
        }

        /* ══════════════════════════════════════════════════════════════════
           TIER 4. WIDE DESKTOP (>= 1150px)
           ALL 7 COLUMNS: ID | SOIL NAME | COORDINATES | SCAN NOTE | TIMESTAMP | STATUS | ACTION
           No horizontal scrollbar!
        ══════════════════════════════════════════════════════════════════ */
        @media (min-width: 1150px) {
            .col-map-id,
            .col-coordinates,
            .col-scan-note,
            .col-timestamp {
                display: table-cell !important;
            }
            .map-mobile-coords {
                display: none !important;
            }
            .map-table th,
            .map-table td {
                padding: 14px 18px !important;
            }
            .map-table th.col-map-id,
            .map-table td.col-map-id {
                width: 70px !important;
                min-width: 70px !important;
                max-width: 75px !important;
                padding-left: 20px !important;
                font-size: 13px !important;
            }
            .map-table th.col-sticky-soil,
            .map-table td.col-sticky-soil {
                padding-left: 20px !important;
                width: 190px !important;
                min-width: 180px !important;
                max-width: 210px !important;
            }
            .map-table td.col-sticky-soil .soil-name-title {
                font-size: 13.5px !important;
                font-weight: 700 !important;
                color: #6b7280 !important;
                max-width: 190px !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                white-space: nowrap !important;
            }
            .map-table td.col-sticky-soil .soil-code-sub {
                font-size: 11.5px !important;
                color: #94a3b8 !important;
            }
            .map-table th.col-coordinates,
            .map-table td.col-coordinates {
                width: 230px !important;
                min-width: 210px !important;
                max-width: 260px !important;
            }
            .map-table th.col-scan-note,
            .map-table td.col-scan-note {
                width: auto !important;
                min-width: 200px !important;
            }
            .map-table th.col-timestamp,
            .map-table td.col-timestamp {
                width: 140px !important;
                min-width: 140px !important;
                max-width: 150px !important;
                font-size: 12.5px !important;
                white-space: nowrap !important;
            }
        }

        /* Custom Agrico Leaflet Popup Styling */
        .agrico-custom-popup .leaflet-popup-content-wrapper {
            background: #ffffff !important;
            border-radius: 20px !important;
            padding: 0 !important;
            overflow: hidden !important;
            box-shadow: 0 16px 36px -4px rgba(0, 0, 0, 0.16), 0 6px 12px -2px rgba(0, 0, 0, 0.08) !important;
            border: 1px solid rgba(226, 232, 240, 0.9) !important;
        }
        .agrico-custom-popup .leaflet-popup-content {
            margin: 0 !important;
            line-height: normal !important;
            width: 280px !important;
            max-width: calc(100vw - 64px) !important;
        }
        .agrico-custom-popup .leaflet-popup-tip-container {
            margin-top: -1px;
        }
        .agrico-custom-popup .leaflet-popup-tip {
            background: #ffffff !important;
            box-shadow: 0 4px 8px rgba(0,0,0,0.06) !important;
        }
        .agrico-custom-popup a.leaflet-popup-close-button {
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
            box-shadow: 0 1px 3px rgba(0,0,0,0.08) !important;
            transition: all 0.15s ease !important;
            padding: 0 !important;
            z-index: 20 !important;
            text-decoration: none !important;
        }
        .agrico-custom-popup a.leaflet-popup-close-button:hover {
            background: #f1f5f9 !important;
            color: #0f172a !important;
            border-color: #cbd5e1 !important;
        }
    </style>

    {{-- LEAFLET MAP INITIALIZATION SCRIPT --}}
    <script>
    (function() {
        // Guard: only register once per page load
        if (window._agricoMapInitialized) return;
        window._agricoMapInitialized = true;
        window._agricoMapReady = false; // NEW: true only after layout has fully stabilized

        window._agricoLeafMap = null;
        window._agricoMarkersGroup = null;
        window._agricoPolygons = [];
        const AGRICO_PIN_URL = "{{ asset('images/agrico-map-pin.png') }}";
        const AGRICO_PIN_GRAY_URL = "{{ asset('images/agrico-map-pin-gray.png') }}";

        function getRoundedBoxCoords(centerLat, centerLng, widthMeters, heightMeters) {
            const latConversion = 111320;
            const lngConversion = 111320 * Math.cos(centerLat * Math.PI / 180);
            const halfW = widthMeters / 2;
            const halfH = heightMeters / 2;

            const north = centerLat + (halfH / latConversion);
            const south = centerLat - (halfH / latConversion);
            const east  = centerLng + (halfW / lngConversion);
            const west  = centerLng - (halfW / lngConversion);

            return [
                [north, west],
                [north, east],
                [south, east],
                [south, west]
            ];
        }

        function getParcelDimensions(covStr) {
            const s = String(covStr || '').toLowerCase();
            if (s.includes('50 m')) return { w: 35, h: 35 };
            if (s.includes('250 m')) return { w: 55, h: 55 };
            if (s.includes('500 m')) return { w: 75, h: 75 };
            if (s.includes('1,000') || s.includes('1000')) return { w: 100, h: 100 };
            if (s.includes('2,500') || s.includes('2500')) return { w: 130, h: 130 };
            if (s.includes('5,000') || s.includes('5000')) return { w: 160, h: 160 };
            if (s.includes('hectare')) return { w: 190, h: 190 };
            return { w: 75, h: 75 };
        }

        function centerAdminMapOnPopup(mapInstance, lat, lng, offsetPx) {
            if (!mapInstance) return;
            offsetPx = typeof offsetPx === 'number' ? offsetPx : 175;
            var latlng = L.latLng(parseFloat(lat), parseFloat(lng));
            var zoom = mapInstance.getZoom();
            var point = mapInstance.project(latlng, zoom);
            var targetPoint = point.subtract([0, offsetPx]);
            var targetLatLng = mapInstance.unproject(targetPoint, zoom);
            mapInstance.panTo(targetLatLng, { animate: true, duration: 0.45 });
        }

        window.agricoRenderMarkers = function(mapLocations) {
            const markersGroup = window._agricoMarkersGroup;
            if (!markersGroup) return;
            markersGroup.clearLayers();
            window._agricoPolygons = [];

            if (!Array.isArray(mapLocations) || mapLocations.length === 0) return;

            const agricoIcon = L.icon({
                iconUrl: AGRICO_PIN_URL,
                iconSize: [38, 56],
                iconAnchor: [19, 56],
                popupAnchor: [0, -50]
            });

            const agricoGrayIcon = L.icon({
                iconUrl: AGRICO_PIN_GRAY_URL,
                iconSize: [38, 56],
                iconAnchor: [19, 56],
                popupAnchor: [0, -50]
            });

            // Group pins by coordinate cluster (~1m precision)
            const coordClusters = {};
            mapLocations.forEach(function(loc) {
                if (!loc.latitude || !loc.longitude) return;
                const lat = parseFloat(loc.latitude);
                const lng = parseFloat(loc.longitude);
                if (isNaN(lat) || isNaN(lng)) return;
                const key = `${lat.toFixed(5)},${lng.toFixed(5)}`;
                if (!coordClusters[key]) coordClusters[key] = [];
                coordClusters[key].push(loc);
            });

            Object.keys(coordClusters).forEach(function(coordKey) {
                const pins = coordClusters[coordKey];
                const loc = pins[0];
                const lat = parseFloat(loc.latitude);
                const lng = parseFloat(loc.longitude);
                const isCluster = pins.length > 1;

                const soil = loc.soil || {};
                const munsell = soil.munsell_color || {};
                const soilName = soil.soil_name || 'Soil Specimen';
                const soilType = loc.soil?.soil_type_label || loc.soil?.effective_soil_type || soil.soil_type || munsell.soil_type || 'Leached grayish brown';
                const fullCode = soil.full_code || 'N/A';
                const hexColor = soil.hex_color || munsell.hex_code || '#8F694E';
                let rawBrgy = (loc.barangay && loc.barangay !== '') ? loc.barangay : 'Centro (Poblacion)';
                let brgyClean = rawBrgy.replace(/^(brgy\.?|barangay\.?)\s*/i, '').replace(/,\s*lal-lo\s*$/i, '').replace(/,\s*cagayan\s*$/i, '');
                const brgy = `Brgy. ${brgyClean}, Lal-lo`;
                const pinMeta = typeof window.getAgricoPinStatusMeta === 'function'
                    ? window.getAgricoPinStatusMeta(loc.status)
                    : { isApproved: (loc.status || '').toLowerCase() === 'active' || (loc.status || '').toLowerCase() === 'approved' };
                const isActive = pins.some(p => {
                    const st = (p.status || '').toLowerCase();
                    return st === 'active' || st === 'approved';
                });
                const covStr = loc.coverage || (loc.soil_scan && loc.soil_scan.scan_note && loc.soil_scan.scan_note.match(/\[Covered Area:\s*([^\]]+)\]/i) ? loc.soil_scan.scan_note.match(/\[Covered Area:\s*([^\]]+)\]/i)[1].trim() : '500 m²');

                let regions = [];
                if (Array.isArray(loc.regions) && loc.regions.length > 0) {
                    regions = loc.regions;
                } else if (typeof loc.regions === 'string') {
                    try {
                        const parsedR = JSON.parse(loc.regions);
                        if (Array.isArray(parsedR)) regions = parsedR;
                    } catch(e) {}
                }

                const isMulti = regions.length > 1;
                const domSoil = regions[0] || {};
                const displayTitle = (isMulti && (domSoil.label || domSoil.color_name || domSoil.soil_name)) ? (domSoil.label || domSoil.color_name || domSoil.soil_name) : soilName;
                const displayCode = (isMulti && (domSoil.munsell || domSoil.munsell_code || domSoil.full_code)) ? (domSoil.munsell || domSoil.munsell_code || domSoil.full_code) : fullCode;
                let displayHex = (isMulti && (domSoil.hex || domSoil.hex_color)) ? (domSoil.hex || domSoil.hex_color) : hexColor;
                if (displayHex && !displayHex.startsWith('#')) displayHex = '#' + displayHex;

                let typeRowHtml = '';
                if (isCluster) {
                    typeRowHtml = `
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b; font-weight: 600; font-size: 11px;">Detected Soil:</span>
                            <span style="font-weight: 800; color: #0f172a; font-size: 11.5px;">${pins.length} Pinned</span>
                        </div>
                    `;
                } else if (isMulti) {
                    typeRowHtml = `
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b; font-weight: 600; font-size: 11px;">Detected Soil:</span>
                            <span style="font-weight: 800; color: #0f172a; font-size: 11.5px;">${regions.length} Detected</span>
                        </div>
                    `;
                } else {
                    typeRowHtml = `
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b; font-weight: 600; font-size: 11px;">Type:</span>
                            <span style="font-weight: 800; color: #0f172a; font-size: 11.5px; max-width: 155px; text-align: right; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${soilType}">${soilType}</span>
                        </div>
                    `;
                }

                const codeRowHtml = (!isMulti || isCluster) ? `
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #64748b; font-weight: 600; font-size: 11px;">Code:</span>
                        <span style="font-weight: 800; color: #0f172a; font-size: 11.5px;">${displayCode}</span>
                    </div>
                ` : '';

                const titleHtml = isCluster ? `
                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 2px; line-height: 1.25; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${displayTitle}">
                        ${displayTitle} <span style="font-size: 11.5px; font-weight: 700; color: #16a34a;">(+${pins.length - 1} soils)</span>
                    </div>
                ` : (!isMulti ? `
                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 2px; line-height: 1.25; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${displayTitle}">
                        ${displayTitle}
                    </div>
                ` : '');

                const locHeader = isCluster
                    ? `LOCATION #${pins.map(p => p.location_mapping_id || p.id).join(', #')} (${pins.length} PINNED)`
                    : `LOCATION #${loc.location_mapping_id}`;

                const actionButtonsHtml = isCluster ? `
                    <div style="display: flex; flex-direction: column; gap: 5px; max-height: 140px; overflow-y: auto;">
                        ${pins.map(p => `
                            <button type="button" onclick="viewPinDetails(${p.location_mapping_id || p.id})" style="width: 100%; padding: 6.5px 10px; background: #16a34a; color: #ffffff; border: none; border-radius: 8px; font-size: 11.5px; font-weight: 700; cursor: pointer; transition: background 0.15s ease; display: flex; justify-content: space-between; align-items: center;" onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
                                <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 140px;">View #${p.location_mapping_id || p.id}: ${p.soil?.soil_name || 'Soil'}</span>
                                <span style="font-size: 10px; opacity: 0.9; font-family: monospace;">${p.soil?.full_code || ''}</span>
                            </button>
                        `).join('')}
                    </div>
                ` : `
                    <div>
                        <button type="button" onclick="viewPinDetails(${loc.location_mapping_id})" style="width: 100%; padding: 8.5px 12px; background: #16a34a; color: #ffffff; border: none; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; transition: background 0.15s ease; box-shadow: 0 1px 2px rgba(22, 163, 74, 0.2);" onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
                            View Details
                        </button>
                    </div>
                `;

                const popupContent = `
                    <div style="padding: 12px 14px; font-family: inherit; width: 100%; box-sizing: border-box;">
                        <div style="margin-bottom: 3px; padding-right: 28px;">
                            <span style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">${locHeader}</span>
                        </div>
                        ${titleHtml}
                        <div style="font-size: 11.5px; color: #15803d; font-weight: 700; display: flex; align-items: center; gap: 4px;${(isMulti || isCluster) ? ' margin-top: 4px;' : ''} margin-bottom: 9px;">
                            <svg width="12" height="12" fill="#16a34a" viewBox="0 0 20 20" style="flex-shrink:0;"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                            <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${brgy}</span>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 11px; margin-bottom: 10px; font-size: 11.5px; display: flex; flex-direction: column; gap: 5px;">
                            ${codeRowHtml}
                            ${typeRowHtml}
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #64748b; font-weight: 600; font-size: 11px;">Covered Area:</span>
                                <span style="font-weight: 700; color: #0f172a; font-size: 11.5px;">${covStr}</span>
                            </div>
                            <div style="border-top: 1px dotted #cbd5e1; margin-top: 2px; padding-top: 5px; display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #94a3b8;">
                                <span>Coords:</span>
                                <span style="font-family: ui-monospace, monospace; font-size: 10.5px; font-weight: 500;">${lat.toFixed(4)}, ${lng.toFixed(4)}</span>
                            </div>
                        </div>
                        ${actionButtonsHtml}
                    </div>
                `;

                const markerIcon = typeof window.createSoilPinIcon === 'function'
                    ? window.createSoilPinIcon(loc.status)
                    : (isActive ? agricoIcon : agricoGrayIcon);

                const marker = L.marker([lat, lng], { 
                    icon: markerIcon,
                    interactive: true
                });
                const popupOpts = window.AgricoMapFocus 
                    ? window.AgricoMapFocus.getPopupOptions({ maxWidth: 300 })
                    : { className: 'agrico-custom-popup', maxWidth: 300, autoPan: false };
                marker.bindPopup(popupContent, popupOpts);
                marker.on('click', function(e) {
                    if (e && e.originalEvent) L.DomEvent.stopPropagation(e);
                    if (window.AgricoMapFocus && window._agricoLeafMap) {
                        window.AgricoMapFocus.focusPin(window._agricoLeafMap, marker, { targetZoom: 15 });
                    } else if (window._agricoLeafMap) {
                        centerAdminMapOnPopup(window._agricoLeafMap, lat, lng, 175);
                    }
                });
                marker.on('popupopen', function() {
                    if (window.AgricoMapFocus && window._agricoLeafMap && !window._agricoLeafMap._agricoIsNavigatingPins) {
                        window.AgricoMapFocus.focusPin(window._agricoLeafMap, marker, { targetZoom: 15 });
                    }
                });
                marker._agricoLocationId = loc.location_mapping_id;
                marker._agricoLocationIds = pins.map(p => String(p.location_mapping_id || p.id));
                marker._agricoIsActive = isActive;

                const dims = getParcelDimensions(covStr);
                const boxCoords = getRoundedBoxCoords(lat, lng, dims.w, dims.h);

                const polyBorderColor = pinMeta.polyBorderColor || (isActive ? '#16a34a' : '#6b7280');
                const polyFillColor   = pinMeta.polyFillColor || (isActive ? '#22c55e' : '#9ca3af');
                const polyHoverColor  = pinMeta.polyHoverColor || (isActive ? '#15803d' : '#4b5563');

                const poly = L.polygon(boxCoords, {
                    color: polyBorderColor,
                    fillColor: polyFillColor,
                    fillOpacity: 0.22,
                    weight: 2.5,
                    interactive: isActive
                });

                if (isActive) {
                    poly.bindTooltip(`<div style="font-family:inherit; font-size:11px; font-weight:700; color:#14532d;">Covered Area: <strong>${covStr}</strong></div>`, {
                        sticky: true,
                        direction: 'top',
                        offset: [0, -10]
                    });

                    poly.on('mouseover', function() {
                        this.setStyle({ fillOpacity: 0.42, weight: 3.5, color: polyHoverColor });
                    });
                    poly.on('mouseout', function() {
                        this.setStyle({ fillOpacity: 0.22, weight: 2.5, color: polyBorderColor });
                    });
                    poly.on('click', function(e) {
                        L.DomEvent.stopPropagation(e);
                        if (window.AgricoMapFocus && window._agricoLeafMap) {
                            window.AgricoMapFocus.focusPin(window._agricoLeafMap, marker, { targetZoom: 15 });
                        } else {
                            if (window._agricoLeafMap) {
                                centerAdminMapOnPopup(window._agricoLeafMap, lat, lng, 175);
                            }
                            marker.openPopup();
                        }
                    });
                }

                window._agricoPolygons.push({ poly: poly, lat: lat, lng: lng, covStr: covStr });

                markersGroup.addLayer(poly);
                markersGroup.addLayer(marker);
            });
        };

        window.agricoUpdateMarkerStatus = function(id, nextStatus) {
            id = String(id);
            if (Array.isArray(window._agricoAllLocations)) {
                for (var i = 0; i < window._agricoAllLocations.length; i++) {
                    var item = window._agricoAllLocations[i];
                    if (String(item.location_mapping_id || item.id) === id) {
                        item.status = nextStatus;
                        break;
                    }
                }
                window.agricoRenderMarkers(window._agricoAllLocations);
            }
        };

        window.agricoInitMap = function(retryCount) {
            retryCount = retryCount || 0;
            var mapContainer = document.getElementById('soil-map');
            if (!mapContainer) {
                if (retryCount < 25) {
                    setTimeout(function() { window.agricoInitMap(retryCount + 1); }, 150);
                }
                return;
            }

            if (typeof L === 'undefined') {
                if (retryCount < 30) {
                    setTimeout(function() { window.agricoInitMap(retryCount + 1); }, 150);
                }
                return;
            }

            // Always ensure clean teardown of any previous Leaflet instance on this container
            if (window._agricoLeafMap) {
                try {
                    window._agricoLeafMap.off();
                    window._agricoLeafMap.remove();
                } catch(e) {}
                window._agricoLeafMap = null;
                window._agricoMarkersGroup = null;
            }
            if (mapContainer._leaflet_id) {
                delete mapContainer._leaflet_id;
            }

            // Create new map
            var leafMap = L.map('soil-map', {
                zoomControl: false,
                scrollWheelZoom: true
            }).setView([18.1972, 121.6612], 12);
            window._agricoLeafMap = leafMap;

            if (window.AgricoMapFocus) {
                window.AgricoMapFocus.attachMapListeners(leafMap, { resetZoomOnClose: true });
            }

            L.control.zoom({ position: 'bottomright' }).addTo(leafMap);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                subdomains: ['a', 'b', 'c'],
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors | AgriCo GIS'
            }).addTo(leafMap);

            var markersGroup = L.featureGroup().addTo(leafMap);
            window._agricoMarkersGroup = markersGroup;

            // Render official Lal-lo municipal & barangay boundary overlay
            if (window.LalloBoundary) {
                window.LalloBoundary.render(leafMap, {
                    showTooltips: true,
                    bringMarkersToFront: markersGroup
                }).then(function() {
                    if (window._agricoLeafMap) window._agricoLeafMap.invalidateSize();
                }).catch(function(err) { console.log('Lal-lo boundary note:', err); });
            }

            // Load initial markers from the data element
            var dataEl = document.getElementById('all-map-locations-json');
            if (dataEl && dataEl.dataset.locations) {
                try {
                    var locs = JSON.parse(dataEl.dataset.locations);
                    window._agricoAllLocations = locs;
                    window.agricoRenderMarkers(locs);
                    if (markersGroup.getLayers().length > 0) {
                        leafMap._agricoOverviewBounds = markersGroup.getBounds();
                        leafMap.fitBounds(markersGroup.getBounds(), { padding: [40, 40], maxZoom: 13 });
                    }
                } catch(e) { console.error('Map data parse error:', e); }
            }

            // Invalidate size across multiple render intervals
            [50, 150, 300, 600, 1200].forEach(function(delay) {
                setTimeout(function() {
                    if (window._agricoLeafMap) {
                        window._agricoLeafMap.invalidateSize();
                    }
                }, delay);
            });

            // Watch for container resizes
            if (typeof ResizeObserver !== 'undefined' && !mapContainer._agricoResizeObserved) {
                mapContainer._agricoResizeObserved = true;
                var ro = new ResizeObserver(function() {
                    if (window._agricoLeafMap) {
                        window._agricoLeafMap.invalidateSize();
                    }
                });
                ro.observe(mapContainer);
            }
        };

        window.focusMapPin = function(lat, lng, id, attempt) {
            var mapEl = document.getElementById('soil-map');
            if (mapEl) {
                mapEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            var leafMap = window._agricoLeafMap;
            if (!leafMap) return;

            leafMap.invalidateSize({ animate: false, pan: false });

            var targetMarker = null;
            if (id && window._agricoMarkersGroup) {
                window._agricoMarkersGroup.eachLayer(function(layer) {
                    const matchesId = layer._agricoLocationId == id || (Array.isArray(layer._agricoLocationIds) && layer._agricoLocationIds.includes(String(id)));
                    if (matchesId && layer._agricoIsActive && typeof layer.openPopup === 'function' && layer.getPopup()) {
                        targetMarker = layer;
                    }
                });
            }

            if (targetMarker && window.AgricoMapFocus) {
                window.AgricoMapFocus.focusPin(leafMap, targetMarker, { targetZoom: 15 });
            } else {
                var targetZoom = Math.max(leafMap.getZoom(), 15);
                var latlng = L.latLng(parseFloat(lat), parseFloat(lng));
                var point = leafMap.project(latlng, targetZoom);
                var targetLatLng = leafMap.unproject(point.subtract([0, 175]), targetZoom);

                leafMap.setView(targetLatLng, targetZoom, { animate: true });

                if (targetMarker) {
                    setTimeout(function() { targetMarker.openPopup(); }, 350);
                }
            }
        };

        window.togglePinStatus = async function(id) {
            if (!id) return;
            var leafMap = window._agricoLeafMap;
            if (leafMap) leafMap.closePopup();

            try {
                const res = await fetch('/manage-map/' + id + '/toggle-status', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                if (res.ok && window.Livewire) {
                    window.Livewire.dispatch('mapping-updated');
                }
            } catch(e) {}
        };

        window.viewPinDetails = function(id) {
            if (!id) return;
            if (window.Livewire && window.Livewire.dispatch) {
                window.Livewire.dispatch('view-mapping', { id: id });
            }
        };

        // Sync markers whenever Livewire updates the DOM
        function syncMarkersFromDOM() {
            var dataEl = document.getElementById('all-map-locations-json');
            if (!dataEl || !dataEl.dataset.locations || !window._agricoLeafMap) return;
            try {
                var locs = JSON.parse(dataEl.dataset.locations);
                window._agricoAllLocations = locs;
                window.agricoRenderMarkers(locs);
            } catch(e) {}
        }

        // Livewire event: bulk refresh from toggleStatus
        function handleMapPinsRefreshed(data) {
            var locations = data;
            if (data && data.locations) locations = data.locations;
            else if (Array.isArray(data) && data[0] && data[0].locations) locations = data[0].locations;
            else if (Array.isArray(data) && data[0]) locations = data[0];
            if (locations && window._agricoLeafMap) {
                window.agricoRenderMarkers(locations);
            } else {
                syncMarkersFromDOM();
            }
        }

        function setupHooks() {
            if (window.Livewire) {
                if (window.Livewire.hook) {
                    window.Livewire.hook('morph.updated', function() {
                        syncMarkersFromDOM();
                    });
                }
                if (window.Livewire.on) {
                    window.Livewire.on('map-pins-refreshed', handleMapPinsRefreshed);
                }
            } else {
                document.addEventListener('livewire:init', function() {
                    if (window.Livewire.hook) {
                        window.Livewire.hook('morph.updated', function() { syncMarkersFromDOM(); });
                    }
                    if (window.Livewire.on) {
                        window.Livewire.on('map-pins-refreshed', handleMapPinsRefreshed);
                    }
                });
            }
        }
        setupHooks();

        window.addEventListener('resize', function() {
            if (window._agricoLeafMap) window._agricoLeafMap.invalidateSize();
        });

        // Kick off map initialization
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            setTimeout(function() { window.agricoInitMap(); }, 50);
        } else {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() { window.agricoInitMap(); }, 50);
            });
        }

        document.addEventListener('livewire:navigated', function() {
            window._agricoMapReady = false;
            setTimeout(function() { window.agricoInitMap(); }, 50);
        });

        document.addEventListener('livewire:init', function() {
            setTimeout(function() { window.agricoInitMap(); }, 50);
        });
    })();
    </script>
</div>
