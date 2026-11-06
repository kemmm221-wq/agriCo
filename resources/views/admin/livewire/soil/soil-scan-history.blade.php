<div wire:poll.visible.60s class="space-y-6">

    {{-- HEADER --}}
    <div class="ui-reveal flex items-center justify-between gap-4 flex-wrap" style="--i:0">
        <div>
            <h1 class="ui-reveal-text text-3xl font-extrabold text-gray-900" style="--i:0">
                Soil Scan History
            </h1>
            <p class="ui-reveal-text text-sm text-gray-400 mt-1" style="--i:0">
                {{ $soils->total() ?? 0 }} soil records in database
            </p>
        </div>
        <a
            href="{{ route('dashboard') }}"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full text-xs font-bold text-gray-700 bg-white border border-gray-200 shadow-sm hover:bg-gray-50 hover:text-gray-900 transition"
            title="Back to Dashboard"
        >
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Dashboard
        </a>
    </div>

    {{-- SEARCH AND FILTER CARD --}}
    <div class="ui-reveal" style="--i:1; background:#ffffff; border-radius:20px; border:1px solid #f1f5f9; box-shadow:0 1px 3px rgba(0,0,0,0.03); padding:12px 16px; display:flex; align-items:center; gap:14px; width:100%; box-sizing:border-box; flex-wrap:wrap;">
        {{-- SEARCH INPUT WITH AUTO-SUGGESTIONS --}}
        <div x-data="{ open: true }" @click.outside="open = false" style="position:relative; flex:1; min-width:220px;">
            <svg style="position:absolute; left:16px; top:50%; transform:translateY(-50%); width:18px; height:18px; color:#94a3b8; z-index:2;" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input
                wire:model.live="search"
                @focus="open = true"
                @input="open = true"
                @keydown.escape="$wire.set('search', ''); $el.value = ''; open = false;"
                type="text"
                placeholder="Search soil name, full code, hue..."
                style="width:100%; border:1px solid #e2e8f0; border-radius:16px; background:#ffffff; padding:10px 44px 10px 46px; font-size:14px; font-weight:500; color:#1e293b; outline:none; transition:all 0.2s ease; box-sizing:border-box;"
                onfocus="this.style.borderColor='#22c55e'; this.style.boxShadow='0 0 0 3px rgba(68,175,53,0.15)';"
                onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none';"
            />
            @if($search)
                <x-search-clear-button wireModel="search" />
            @endif

            {{-- AUTO SUGGESTION DROPDOWN --}}
            @if(!empty($searchSuggestions) && count($searchSuggestions) > 0)
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
                        Soil Suggestions
                    </div>
                    @foreach($searchSuggestions as $s)
                        @php
                            $sCode = $s->full_code ?? $s->munsellColor?->full_code ?? '10YR 4/3';
                            $sHue = $s->hue ?? $s->munsellColor?->hue ?? '10YR';
                            $sVal = $s->munsellColor?->value ?? 4;
                            $sChr = $s->munsellColor?->chroma ?? 3;
                            $sHex = \App\Livewire\Admin\MunsellColor\ManageMunsellColor::munsellToHex($sHue, $sVal, $sChr);
                            $suggestVal = $s->soil_name ?: $sCode;
                        @endphp
                        <div
                            wire:click="selectSuggestion('{{ addslashes($suggestVal) }}')"
                            @click="open = false"
                            style="padding:8px 14px; display:flex; align-items:center; justify-content:space-between; cursor:pointer; transition:background 0.15s ease;"
                            onmouseover="this.style.background='#f8fafc';"
                            onmouseout="this.style.background='transparent';"
                        >
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span style="width:20px; height:20px; border-radius:50%; background:{{ $sHex }}; border:1.5px solid rgba(0,0,0,0.15); display:inline-block; flex-shrink:0;"></span>
                                <div>
                                    <div style="font-size:13.5px; font-weight:700; color:#1e293b;">
                                        {{ $s->soil_name ?: 'Unnamed Soil' }}
                                    </div>
                                    <div style="font-size:11.5px; color:#64748b;">
                                        Code: <strong style="color:#334155;">{{ $sCode }}</strong> &bull; Hue: {{ $sHue }}
                                    </div>
                                </div>
                            </div>
                            <span style="font-size:11px; font-weight:700; background:#f0fdf4; color:#22c55e; padding:3px 9px; border-radius:12px;">
                                Select
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- FUNNEL ICON --}}
        <svg width="18" height="18" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="flex-shrink:0;">
            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
        </svg>

        {{-- FILTER DROPDOWNS --}}
        <select
            wire:model.live="status"
            style="border:1px solid #e2e8f0; border-radius:14px; background:#ffffff; padding:8px 16px; font-size:13px; font-weight:600; color:#334155; outline:none; cursor:pointer; transition:all 0.2s ease; flex-shrink:0;"
            onfocus="this.style.borderColor='#22c55e';"
            onblur="this.style.borderColor='#e2e8f0';"
        >
            <option value="">All Status</option>
            <option value="Verified">Verified</option>
            <option value="Pending">Pending Review</option>
            <option value="Rejected">Rejected</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
        </select>

        <select
            wire:model.live="hue"
            style="border:1px solid #e2e8f0; border-radius:14px; background:#ffffff; padding:8px 16px; font-size:13px; font-weight:600; color:#334155; outline:none; cursor:pointer; transition:all 0.2s ease; flex-shrink:0;"
            onfocus="this.style.borderColor='#22c55e';"
            onblur="this.style.borderColor='#e2e8f0';"
        >
            <option value="">All Hues</option>
            @foreach($munsellHues as $hueOption)
                <option value="{{ $hueOption }}">{{ $hueOption }}</option>
            @endforeach
        </select>

        {{-- CALENDAR DATE FILTER --}}
        <div style="display:inline-flex; align-items:center; gap:8px; border:1px solid #e2e8f0; border-radius:14px; background:#ffffff; padding:6px 14px; transition:all 0.2s ease; flex-shrink:0;" onmouseover="this.style.borderColor='#22c55e';" onmouseout="if(!document.activeElement || document.activeElement!==this.querySelector('input')) this.style.borderColor='#e2e8f0';">
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
        <div style="--i:1; font-size:13px; font-weight:500; color:#94a3b8; flex-shrink:0;">
            {{ $soils->total() ?? 0 }} results
        </div>
    </div>

    {{-- STYLES FOR RESPONSIVE TABLE & BADGES --}}
    <style>
        .soil-table-container {
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: auto !important;
            overflow-y: visible !important;
            position: relative !important;
            -webkit-overflow-scrolling: touch !important;
            box-sizing: border-box !important;
        }

        .soil-table {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            border-collapse: collapse !important;
            box-sizing: border-box !important;
        }

        /* Desktop / Tablet defaults */
        .soil-table th.col-seq,
        .soil-table td.col-seq {
            width: 60px !important;
            min-width: 60px !important;
            padding-left: 20px !important;
            padding-right: 10px !important;
        }
        .soil-table th.col-detected-colors,
        .soil-table td.col-detected-colors {
            width: auto !important;
        }
        .soil-table th.col-soil-hue,
        .soil-table td.col-soil-hue {
            width: 22% !important;
            min-width: 100px !important;
        }
        .soil-table th.col-scan-date,
        .soil-table td.col-scan-date {
            width: 18% !important;
            min-width: 100px !important;
        }

        /* ── ACTION COLUMN: ALWAYS VISIBLE & STICKY ON RIGHT ACROSS ALL DESKTOP / TABLET SIZES ── */
        .soil-table th.col-sticky-action,
        .soil-table td.col-sticky-action {
            position: sticky !important;
            right: 0 !important;
            width: 110px !important;
            min-width: 110px !important;
            max-width: 110px !important;
            white-space: nowrap !important;
            box-shadow: none !important;
            border-left: none !important;
            border-right: none !important;
            z-index: 12 !important;
            background-color: #ffffff !important;
            text-align: center !important;
            padding-left: 8px !important;
            padding-right: 14px !important;
            display: table-cell !important;
        }
        .soil-table th.col-sticky-action {
            z-index: 22 !important;
            background-color: #F1FBF1 !important;
            text-align: center !important;
        }
        .soil-table tbody tr:hover td.col-sticky-action,
        .soil-table tbody tr.hover\:bg-green-50\/40:hover td.col-sticky-action {
            background-color: #f7fdf7 !important;
        }
        .soil-table tbody tr.bg-emerald-50\/80 td.col-sticky-action {
            background-color: #ecfdf5 !important;
        }

        /* Dark mode for sticky action */
        .dark .soil-table th.col-sticky-action {
            background-color: #1e293b !important;
        }
        .dark .soil-table td.col-sticky-action {
            background-color: #0f172a !important;
        }
        .dark .soil-table tbody tr:hover td.col-sticky-action {
            background-color: #1e293b !important;
        }
        .dark .soil-table tbody tr.bg-emerald-50\/80 td.col-sticky-action {
            background-color: rgba(6, 78, 59, 0.4) !important;
        }

        .soil-mobile-date {
            display: none;
        }

        .detected-badge {
            display: inline-flex;
            align-items: center;
            color: #334155;
            font-weight: 700;
            font-size: 13.5px;
            white-space: nowrap;
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
        }
        .hue-badge-pill {
            display: inline-block;
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-right: 6px;
            white-space: nowrap;
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
        }
        .dark .detected-badge {
            color: #e2e8f0 !important;
        }
        .dark .hue-badge-pill {
            color: #f1f5f9 !important;
        }

        /* Action buttons standard styling */
        .soil-action-btns {
            display: inline-flex !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 6px !important;
            white-space: nowrap !important;
        }
        .soil-table .agrico-view-btn,
        .soil-table .agrico-delete-btn {
            width: 36px !important;
            height: 36px !important;
            min-width: 36px !important;
            max-width: 36px !important;
            min-height: 36px !important;
            max-height: 36px !important;
            border-radius: 11px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .soil-table .agrico-view-btn svg,
        .soil-table .agrico-delete-btn svg {
            width: 17px !important;
            height: 17px !important;
        }

        /* ── TABLET BREAKPOINT (< 768px): date moves under Detected Colors so table fits seamlessly ── */
        @media (max-width: 767px) {
            .soil-table th.col-scan-date,
            .soil-table td.col-scan-date {
                display: none !important;
            }
            .soil-mobile-date {
                display: block !important;
                font-size: 11.5px;
                font-weight: 600;
                color: #94a3b8;
                margin-top: 2px;
            }
        }

        /* ── MOBILE BREAKPOINT (< 680px): table fits screen ── */
        @media (max-width: 679px) {
            .soil-table-container {
                overflow: visible !important;
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
            .soil-table-card-wrap {
                overflow: visible !important;
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
            .soil-table {
                table-layout: fixed !important;
                width: 100% !important;
                min-width: 0 !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
            }

            .soil-table th,
            .soil-table td {
                padding-top: 10px !important;
                padding-bottom: 10px !important;
                box-sizing: border-box !important;
            }

            .soil-table th.col-seq,
            .soil-table td.col-seq {
                width: 38px !important;
                min-width: 38px !important;
                max-width: 42px !important;
                padding-left: 10px !important;
                padding-right: 4px !important;
                font-size: 13px !important;
            }

            .soil-table th.col-detected-colors,
            .soil-table td.col-detected-colors {
                width: auto !important;
                min-width: 0 !important;
                padding-left: 6px !important;
                padding-right: 6px !important;
                overflow: hidden !important;
            }

            .soil-table th.col-soil-hue,
            .soil-table td.col-soil-hue {
                width: 82px !important;
                min-width: 82px !important;
                max-width: 88px !important;
                padding-left: 4px !important;
                padding-right: 4px !important;
            }
            .hue-badge-pill {
                font-size: 12px !important;
                margin-right: 3px !important;
            }

            /* Action column on mobile: two buttons side-by-side */
            .soil-table th.col-sticky-action,
            .soil-table td.col-sticky-action {
                display: table-cell !important;
                position: static !important;
                left: auto !important;
                right: auto !important;
                width: 92px !important;
                min-width: 92px !important;
                max-width: 96px !important;
                padding: 10px 4px !important;
                text-align: center !important;
                box-shadow: none !important;
                border-left: none !important;
                border-right: none !important;
            }
            .soil-action-btns {
                gap: 5px !important;
            }
            .soil-table .agrico-view-btn,
            .soil-table .agrico-delete-btn {
                width: 34px !important;
                height: 34px !important;
                min-width: 34px !important;
                max-width: 34px !important;
                min-height: 34px !important;
                max-height: 34px !important;
                border-radius: 10px !important;
            }
            .soil-table .agrico-view-btn svg,
            .soil-table .agrico-delete-btn svg {
                width: 16px !important;
                height: 16px !important;
            }
        }
    </style>

    {{-- TABLE CARD --}}
    <div class="ui-reveal soil-table-card-wrap bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800 shadow-sm overflow-hidden" style="--i:2">
        {{-- CARD HEADER --}}
        <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="ui-reveal-text text-base font-bold text-gray-900 dark:text-slate-100" style="--i:2">
                Soil Scan History ({{ $soils->total() ?? 0 }})
            </h2>
        </div>

        <div class="overflow-x-auto soil-table-container no-scrollbar">
            <table class="w-full soil-table">
                <thead>
                    <tr class="bg-[#F1FBF1] dark:bg-slate-800/80 border-b border-gray-100 dark:border-slate-800">
                        {{-- # / ID --}}
                        <th
                            wire:click="sortBy('id')"
                            class="px-5 py-4 text-left text-xs font-bold text-gray-500 cursor-pointer select-none group col-seq whitespace-nowrap"
                        >
                            <div class="flex items-center gap-1">
                                <span class="group-hover:text-[#22c55e] transition-colors duration-200">#</span>
                                <span class="sort-arrows">
                                    @if($sortField === 'id')
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

                        {{-- DETECTED COLORS --}}
                        <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 whitespace-nowrap col-detected-colors">
                            DETECTED COLORS
                        </th>

                        {{-- HUE --}}
                        <th
                            wire:click="sortBy('hue')"
                            class="px-5 py-4 text-left text-xs font-bold text-gray-500 cursor-pointer select-none group col-soil-hue whitespace-nowrap"
                        >
                            <div class="flex items-center gap-1">
                                <span class="group-hover:text-[#22c55e] transition-colors duration-200">HUE</span>
                                <span class="sort-arrows">
                                    @if($sortField === 'hue')
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

                        {{-- SCAN DATE --}}
                        <th
                            wire:click="sortBy('created_at')"
                            class="px-5 py-4 text-left text-xs font-bold text-gray-500 cursor-pointer select-none group col-scan-date whitespace-nowrap"
                        >
                            <div class="flex items-center gap-1">
                                <span class="group-hover:text-[#22c55e] transition-colors duration-200">SCAN DATE</span>
                                <span class="sort-arrows">
                                    @if($sortField === 'created_at')
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

                        {{-- ACTION (no sorting) --}}
                        <th class="px-3 py-4 text-center text-xs font-bold text-gray-500 whitespace-nowrap col-sticky-action">
                            ACTIONS
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($scans as $scan)
                    @php
                        $soil = $scan->soil;
                        $searchTrim = trim($search);
                        $sCode = $soil->full_code ?? $soil?->munsellColor?->full_code ?? '';
                        $sHue = $soil->hue ?? $soil?->munsellColor?->hue ?? '';
                        $sUser = $scan->user_id ?? $scan->user?->name ?? '226-0001';
                        $isSearchMatch = !empty($searchTrim) && (
                            (!empty($soil?->soil_name) && stripos($soil->soil_name, $searchTrim) !== false) ||
                            (!empty($sCode) && stripos($sCode, $searchTrim) !== false) ||
                            (!empty($sHue) && stripos($sHue, $searchTrim) !== false) ||
                            (!empty($sUser) && stripos((string)$sUser, $searchTrim) !== false) ||
                            (str_starts_with($searchTrim, '#') && '#' . $scan->soil_scan_id === $searchTrim)
                        );

                        $regionsArr = [];
                        if (!empty($scan->regions_data)) {
                            $regionsArr = is_array($scan->regions_data) ? $scan->regions_data : (json_decode($scan->regions_data, true) ?: []);
                        }
                        if (empty($regionsArr) && !empty($scan->regions)) {
                            $regionsArr = $scan->regions;
                        }
                        $detectedColorsCount = count($regionsArr) > 0 ? count($regionsArr) : 1;
                        $detectedHues = [];
                        foreach ($regionsArr as $r) {
                            $cCode = $r['munsell'] ?? ($r['munsell_code'] ?? ($r['full_code'] ?? ''));
                            $h = $cCode ? trim(explode(' ', $cCode)[0]) : ($r['hue'] ?? '');
                            if ($h && !in_array($h, $detectedHues)) {
                                $detectedHues[] = $h;
                            }
                        }
                        if (empty($detectedHues)) {
                            $detectedHues[] = $sHue ?: '10YR';
                        }

                        $rowSeqNum = $soils->total() - ($soils->firstItem() - 1 + $loop->index);
                        $scanDateFormatted = $scan->scan_date ? \Carbon\Carbon::parse($scan->scan_date)->format('m/d/Y') : $scan->created_at?->format('m/d/Y');
                    @endphp
                    <tr class="border-b border-gray-100 dark:border-slate-800 transition {{ $isSearchMatch ? 'bg-emerald-50/80 dark:bg-emerald-950/30 border-l-4 border-l-emerald-500 font-medium' : 'hover:bg-green-50/40 dark:hover:bg-slate-800/50' }}">
                        {{-- # --}}
                        <td class="px-5 py-4 whitespace-nowrap text-sm font-bold text-gray-700 dark:text-gray-300 col-seq">
                            {{ $rowSeqNum }}
                        </td>

                        {{-- DETECTED COLORS --}}
                        <td class="px-5 py-4 whitespace-nowrap col-detected-colors">
                            <span class="detected-badge inline-flex items-center font-bold text-sm text-gray-700 dark:text-gray-200">
                                {{ $detectedColorsCount === 1 ? '1 Color' : "{$detectedColorsCount} Colors" }}
                            </span>
                            <span class="soil-mobile-date">{{ $scanDateFormatted }}</span>
                        </td>

                        {{-- HUE --}}
                        <td class="px-5 py-4 whitespace-nowrap col-soil-hue">
                            @foreach($detectedHues as $h)
                                <span class="hue-badge-pill font-bold text-sm text-gray-700 dark:text-gray-200 mr-2">{{ $h }}</span>
                            @endforeach
                        </td>

                        {{-- SCAN DATE --}}
                        <td class="px-5 py-4 whitespace-nowrap text-sm font-semibold text-gray-500 dark:text-gray-400 col-scan-date">
                            {{ $scanDateFormatted }}
                        </td>

                        {{-- ACTION --}}
                        <td class="px-3 py-4 whitespace-nowrap col-sticky-action text-center">
                            <div class="soil-action-btns">
                                {{-- VIEW (Line Eye) --}}
                                <button
                                    wire:click="$dispatch('view-soil',{id:{{ $scan->soil_scan_id }}})"
                                    class="agrico-view-btn"
                                    title="View Soil Scan"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>

                                {{-- DELETE (Line Trash) --}}
                                <button
                                    wire:click="$dispatch('delete-soil',{id:{{ $scan->soil_scan_id }}})"
                                    class="agrico-delete-btn"
                                    title="Delete Soil Scan"
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
                        <td colspan="100%" style="padding: 64px 24px; text-align: center; border-bottom: none;">
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px;">
                                <div style="width: 56px; height: 56px; border-radius: 16px; background: #f0fdf4; border: 1px solid #dcfce7; display: flex; align-items: center; justify-content: center; color: #22c55e; box-shadow: 0 4px 14px rgba(22,163,74,0.12);">
                                    <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <path d="M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                                        <path d="M12 6v6l4 2"/>
                                    </svg>
                                </div>
                                <div style="font-size: 15.5px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                                    No Soil Scans Found
                                </div>
                                <div style="font-size: 12.5px; font-weight: 500; color: #64748b; max-width: 360px; line-height: 1.4;">
                                    No soil scan history records matched your search query or active date filter.
                                </div>
                                @if(\App\Models\SoilScan::count() === 0)
                                <button
                                    wire:click="seedSampleRecords"
                                    type="button"
                                    style="margin-top: 10px; display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 12px; font-size: 13px; font-weight: 700; background: #22c55e; color: #ffffff; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.28); transition: all 0.2s ease;"
                                    onmouseover="this.style.background='#16a34a';"
                                    onmouseout="this.style.background='#22c55e';"
                                >
                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <path d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Generate Sample Soil Scans
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
        <div class="px-4 py-3 sm:px-6 sm:py-4 border-t border-gray-100 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-2 text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                <span>Rows per page:</span>
                <select
                    wire:model.live="perPage"
                    class="border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-lg px-2 py-1 text-xs sm:text-sm text-gray-700 dark:text-gray-200 font-semibold outline-none cursor-pointer"
                >
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
                <span class="text-gray-400 dark:text-gray-500 ml-1">
                    {{ $soils->firstItem() ?? 0 }}–{{ $soils->lastItem() ?? 0 }} of {{ $soils->total() }}
                </span>
            </div>

            @if ($soils->hasPages())
                <div class="flex items-center gap-1.5 flex-nowrap">
                    @if ($soils->onFirstPage())
                        <button disabled class="px-3 py-1.5 rounded-lg border border-gray-100 dark:border-slate-800 bg-gray-50 dark:bg-slate-800/50 text-gray-300 dark:text-gray-600 text-xs font-semibold cursor-not-allowed">Prev</button>
                    @else
                        <button wire:click="previousPage" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-600 dark:text-gray-300 hover:border-emerald-500 hover:text-emerald-600 text-xs font-semibold cursor-pointer transition">Prev</button>
                    @endif

                    @php
                        $currentPage = $soils->currentPage();
                        $lastPage = $soils->lastPage();

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
                        <button wire:click="gotoPage({{ max(1, $startPage - 1) }})" class="w-7 h-7 bg-transparent border-0 text-gray-500 hover:text-emerald-600 text-xs font-bold cursor-pointer inline-flex items-center justify-center transition" title="Page {{ max(1, $startPage - 1) }}">...</button>
                    @endif

                    @foreach ($soils->getUrlRange($startPage, $endPage) as $page => $url)
                        @if ($page == $currentPage)
                            <button wire:click="gotoPage({{ $page }})" class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[#22c55e] text-white text-xs sm:text-sm font-bold border-0 cursor-pointer inline-flex items-center justify-center shadow-sm">{{ $page }}</button>
                        @else
                            <button wire:click="gotoPage({{ $page }})" class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-gray-700 dark:text-gray-300 hover:border-emerald-500 hover:text-emerald-600 text-xs sm:text-sm font-semibold cursor-pointer inline-flex items-center justify-center transition">{{ $page }}</button>
                        @endif
                    @endforeach

                    @if ($endPage < $lastPage)
                        <button wire:click="gotoPage({{ min($lastPage, $endPage + 1) }})" class="w-7 h-7 bg-transparent border-0 text-gray-500 hover:text-emerald-600 text-xs font-bold cursor-pointer inline-flex items-center justify-center transition" title="Page {{ min($lastPage, $endPage + 1) }}">...</button>
                    @endif

                    @if ($soils->hasMorePages())
                        <button wire:click="nextPage" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-600 dark:text-gray-300 hover:border-emerald-500 hover:text-emerald-600 text-xs font-semibold cursor-pointer transition">Next</button>
                    @else
                        <button disabled class="px-3 py-1.5 rounded-lg border border-gray-100 dark:border-slate-800 bg-gray-50 dark:bg-slate-800/50 text-gray-300 dark:text-gray-600 text-xs font-semibold cursor-not-allowed">Next</button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- MODALS --}}
    <livewire:admin.soil.soil-detail-modal />
    <livewire:admin.soil.soil-form-modal />
    <livewire:admin.soil.soil-delete-modal />
</div>
