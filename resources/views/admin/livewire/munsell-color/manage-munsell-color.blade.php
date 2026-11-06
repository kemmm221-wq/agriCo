<div class="space-y-6">



    {{-- HEADER --}}
    <div class="ui-reveal munsell-header-bar" style="--i:0">
        <div>
            <h1 class="ui-reveal-text text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight" style="--i:0">
                Manage Munsell Color
            </h1>
            <p class="ui-reveal-text text-xs sm:text-sm text-gray-400 mt-1" style="--i:0">
                {{ $colors->total() ?? 0 }} Munsell color records in database
            </p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <a
                href="{{ route('dashboard') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full text-xs font-bold text-gray-700 bg-white border border-gray-200 shadow-sm hover:bg-gray-50 hover:text-gray-900 transition"
                title="Back to Dashboard"
            >
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to Dashboard
            </a>

            <button
                wire:click="$dispatch('create-munsell')"
                class="munsell-add-btn ui-reveal-text"
                style="--i:0; background: #22c55e !important; color: #ffffff !important; font-weight: 700; font-size: 13px; border: none; padding: 10px 20px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35); cursor: pointer; transition: all 0.2s ease; width: fit-content; flex-shrink: 0;"
                onmouseover="this.style.background='#16a34a';"
                onmouseout="this.style.background='#22c55e';"
            >
                <span style="font-size: 16px; font-weight: 800; line-height: 1;">+</span>
                <span>Add Munsell Color</span>
            </button>
        </div>
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
                placeholder="Search color name, full code, hue, User ID..."
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
                        Color Suggestions
                    </div>
                    @foreach($searchSuggestions as $s)
                        @php
                            $sHex = !empty($s->hex_code)
                                ? (str_starts_with($s->hex_code, '#') ? strtolower($s->hex_code) : '#' . strtolower($s->hex_code))
                                : \App\Livewire\Admin\MunsellColor\ManageMunsellColor::munsellToHex($s->hue, $s->value, $s->chroma, $s->hex_code ?? null);
                            $suggestVal = $s->color_name ?: $s->full_code;
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
                                        {{ $s->color_name ?: 'Unnamed Color' }}
                                    </div>
                                    <div style="font-size:11.5px; color:#64748b;">
                                        Code: <strong style="color:#334155;">{{ $s->full_code }}</strong> | Hue: {{ $s->hue }}
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

        {{-- FILTER DROPDOWN --}}
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
            {{ $colors->total() ?? 0 }} results
        </div>
    </div>











    {{-- TABLE CARD --}}
    <div
        class="ui-reveal bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800 shadow-sm overflow-hidden munsell-table-card w-full max-w-full min-w-0 box-border"
        style="--i:2"
    >
        {{-- CARD HEADER --}}
        <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="ui-reveal-text text-base font-bold text-gray-900 dark:text-slate-100" style="--i:2">
                Munsell Soil Colors ({{ $colors->total() ?? 0 }})
            </h2>
        </div>

        <div class="overflow-x-auto munsell-table-container no-scrollbar">
            <table class="w-full munsell-table min-w-0 md:min-w-[900px]">






                <thead>

                    <tr class="
bg-[#F1FBF1]
dark:bg-slate-800/80
border-b
border-gray-100
dark:border-slate-800
">




                        {{-- COLOR NAME / CHARACTERISTICS --}}
                        <th
                            wire:click="sortBy('color_name')"
                            class="soil-th-sortable px-5 py-4 text-left text-xs font-bold text-gray-500 cursor-pointer select-none group whitespace-nowrap col-munsell-name"
                        >
                            <div class="flex items-center gap-1">
                                <span class="group-hover:text-[#22c55e] transition-colors duration-200">
                                    <span class="munsell-title-desktop">COLOR NAME & CHARACTERISTICS</span>
                                    <span class="munsell-title-mobile">COLOR NAME</span>
                                </span>
                                <span class="sort-arrows">
                                    @if($sortField === 'color_name')
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


                        {{-- IMAGE COLOR --}}
                        <th class="px-4 py-4 text-center text-xs font-bold text-gray-500 whitespace-nowrap col-munsell-image">
                            IMAGE COLOR
                        </th>


                        {{-- FULL CODE --}}
                        <th
                            wire:click="sortBy('full_code')"
                            class="soil-th-sortable px-5 py-4 text-left text-xs font-bold text-gray-500 cursor-pointer select-none group whitespace-nowrap col-munsell-code"
                        >
                            <div class="flex items-center gap-1">
                                <span class="group-hover:text-[#22c55e] transition-colors duration-200">FULL CODE</span>
                                <span class="sort-arrows">
                                    @if($sortField === 'full_code')
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


                        {{-- SOIL ORGANIC INDEX --}}
                        <th
                            wire:click="sortBy('soil_organic_index')"
                            class="soil-th-sortable px-5 py-4 text-left text-xs font-bold text-gray-500 cursor-pointer select-none group whitespace-nowrap col-munsell-organic"
                        >
                            <div class="flex items-center gap-1">
                                <span class="group-hover:text-[#22c55e] transition-colors duration-200">SOIL ORGANIC INDEX</span>
                                <span class="sort-arrows">
                                    @if($sortField === 'soil_organic_index')
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


                        {{-- SOIL TYPE --}}
                        <th
                            wire:click="sortBy('soil_type')"
                            class="soil-th-sortable px-5 py-4 text-left text-xs font-bold text-gray-500 cursor-pointer select-none group whitespace-nowrap col-munsell-soiltype"
                        >
                            <div class="flex items-center gap-1">
                                <span class="group-hover:text-[#22c55e] transition-colors duration-200">SOIL TYPE</span>
                                <span class="sort-arrows">
                                    @if($sortField === 'soil_type')
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


                        {{-- CREATED --}}
                        <th
                            wire:click="sortBy('created_at')"
                            class="soil-th-sortable px-5 py-4 text-left text-xs font-bold text-gray-500 cursor-pointer select-none group whitespace-nowrap col-munsell-created"
                        >
                            <div class="flex items-center gap-1">
                                <span class="group-hover:text-[#22c55e] transition-colors duration-200">CREATED</span>
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

                        {{-- STATUS --}}
                        <th class="px-5 py-4 text-center text-xs font-bold text-gray-500 whitespace-nowrap col-munsell-status">
                            STATUS
                        </th>


                        {{-- ACTIONS --}}
                        <th class="px-3 py-4 text-center text-xs font-bold text-gray-500 whitespace-nowrap col-sticky-action">
                            <span class="munsell-action-title-desktop">ACTIONS</span>
                            <span class="munsell-action-title-mobile">ACTION</span>
                        </th>


                    </tr>

                </thead>









                <tbody>


                    @forelse($colors as $color)

                    @php
                        $swatch = !empty($color->hex_code)
                            ? (str_starts_with($color->hex_code, '#') ? strtolower($color->hex_code) : '#' . strtolower($color->hex_code))
                            : \App\Livewire\Admin\MunsellColor\ManageMunsellColor::munsellToHex($color->hue, $color->value, $color->chroma, $color->hex_code ?? null);
                        $searchTrim = trim($search);
                        $isSearchMatch = !empty($searchTrim) && (
                            (!empty($color->color_name) && stripos($color->color_name, $searchTrim) !== false) ||
                            (!empty($color->full_code) && stripos($color->full_code, $searchTrim) !== false) ||
                            (!empty($color->soil_organic_index) && stripos($color->soil_organic_index, $searchTrim) !== false) ||
                            (!empty($color->soil_type) && stripos($color->soil_type, $searchTrim) !== false) ||
                            (!empty($color->hue) && stripos($color->hue, $searchTrim) !== false) ||
                            (!empty($color->admin_id) && stripos((string)$color->admin_id, $searchTrim) !== false) ||
                            (str_starts_with($searchTrim, '#') && '#' . $color->id === $searchTrim)
                        );
                    @endphp

                    <tr class="border-b border-gray-100 transition {{ $isSearchMatch ? 'bg-emerald-50/80 border-l-4 border-l-emerald-500 font-medium' : 'hover:bg-green-50/40' }}">




                        {{-- SOIL CHARACTERISTICS: Color Name + Description --}}
                        <td class="px-5 py-4 col-munsell-name">
                            <div class="min-w-0">
                                <div class="text-sm font-bold truncate" style="font-size: 14px; font-weight: 700; color: #374151;" title="{{ $color->color_name ?: '—' }}">
                                    {{ $color->color_name ?: '—' }}
                                </div>
                                <div class="text-xs font-medium text-slate-400 truncate mt-0.5" style="font-size: 12px; font-weight: 500; color: #94a3b8;" title="{{ $color->description ?: ('Munsell ' . $color->full_code . ' Soil-Color Chart') }}">
                                    {{ $color->description ?: ('Munsell ' . $color->full_code . ' Soil-Color Chart') }}
                                </div>
                            </div>
                        </td>


                        {{-- IMAGE COLOR --}}
                        <td class="px-4 py-5 col-munsell-image text-center">
                            <div class="flex items-center justify-center gap-2">
                                @php
                                    $imgSrc = !empty($color->image_path) ? $color->image_path : (!empty($color->image) ? $color->image : null);
                                @endphp
                                @if($imgSrc)
                                    <img
                                        src="{{ asset('storage/' . $imgSrc) }}"
                                        class="w-8 h-8 rounded-lg border border-gray-200 shadow-sm object-cover inline-block"
                                        title="{{ $color->full_code }}"
                                    >
                                @else
                                    <span
                                        class="w-8 h-8 rounded-lg border border-gray-200 shadow-sm inline-block"
                                        style="background-color: {{ $swatch }};"
                                        title="{{ $color->full_code }} ({{ $swatch }})"
                                    ></span>
                                @endif
                            </div>
                        </td>


                        {{-- FULL CODE --}}
                        <td class="px-5 py-5 whitespace-nowrap col-munsell-code">
                            <span class="font-bold text-[#6b7280] text-sm" style="font-size: 14px; font-weight: 700; color: #6b7280;">{{ $color->full_code }}</span>
                        </td>


                        {{-- SOIL ORGANIC INDEX --}}
                        <td class="px-5 py-5 text-sm text-[#6b7280] font-bold whitespace-nowrap col-munsell-organic" style="font-size: 14px; font-weight: 700; color: #6b7280;">
                            {{ $color->soil_organic_index ?: '—' }}
                        </td>


                        {{-- SOIL TYPE --}}
                        <td class="px-5 py-5 text-sm text-[#6b7280] font-bold whitespace-nowrap col-munsell-soiltype" style="font-size: 14px; font-weight: 700; color: #6b7280;">
                            {{ $color->soil_type ?: '—' }}
                        </td>


                        {{-- CREATED --}}
                        <td class="px-5 py-5 text-sm text-[#6b7280] font-bold whitespace-nowrap col-munsell-created" style="font-size: 14px; font-weight: 700; color: #6b7280;">
                            {{ $color->created_at?->format('m/d/Y') ?: '08/02/2026' }}
                        </td>

                        {{-- STATUS (ALWAYS VISIBLE) --}}
                        <td class="px-3 py-5 whitespace-nowrap col-munsell-status text-center">
                            <button
                                wire:click="toggleStatus({{ $color->id }})"
                                type="button"
                                class="munsell-status-btn"
                                style="{{ ($color->status ?? 'Active') === 'Active' ? 'background:#dcfce7; color:#22c55e;' : 'background:#f1f5f9; color:#475569;' }} display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:9999px; font-size:12px; font-weight:700; border:none; cursor:pointer; transition:all 0.2s ease;"
                            >
                                <span style="{{ ($color->status ?? 'Active') === 'Active' ? 'background:#22c55e;' : 'background:#94a3b8;' }} width:7px; height:7px; border-radius:50%; display:inline-block; flex-shrink:0;"></span>
                                <span>{{ $color->status ?? 'Active' }}</span>
                            </button>
                        </td>


                        {{-- ACTIONS (ALWAYS VISIBLE) --}}
                        <td class="px-3 py-5 whitespace-nowrap col-sticky-action text-center">
                            {{-- DESKTOP INLINE ACTIONS --}}
                            <div class="munsell-desktop-actions flex justify-center items-center gap-2 whitespace-nowrap">

                                {{-- VIEW --}}
                                <button
                                    wire:click="$dispatch('view-munsell',{id:{{ $color->munsell_color_id ?? $color->id }}})"
                                    class="agrico-view-btn"
                                    title="View Color"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>

                                {{-- EDIT --}}
                                <button
                                    wire:click="$dispatch('edit-munsell',{id:{{ $color->munsell_color_id ?? $color->id }}})"
                                    class="agrico-edit-btn"
                                    title="Edit Color"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>

                                {{-- DELETE --}}
                                <button
                                    wire:click="$dispatch('delete-munsell',{id:{{ $color->munsell_color_id ?? $color->id }}})"
                                    class="agrico-delete-btn"
                                    title="Delete Color"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>

                            </div>

                            {{-- MOBILE MORE ACTIONS "⋮" DROPDOWN --}}
                            <div class="munsell-mobile-actions" x-data="{ open: false, dropUp: false }" @click.outside="open = false" :style="open ? 'z-index: 60;' : ''">
                                <button
                                    @click="dropUp = ($el.getBoundingClientRect().bottom + 170 > window.innerHeight) || ($el.closest('.munsell-table-card')?.getBoundingClientRect().bottom - $el.getBoundingClientRect().bottom < 170); open = !open"
                                    type="button"
                                    class="agrico-more-btn"
                                    title="More Actions"
                                    aria-label="More Actions"
                                >
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="flex-shrink:0;">
                                        <circle cx="12" cy="5" r="2"/>
                                        <circle cx="12" cy="12" r="2"/>
                                        <circle cx="12" cy="19" r="2"/>
                                    </svg>
                                </button>

                                {{-- DROPDOWN MENU --}}
                                <div
                                    x-show="open"
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="agrico-mobile-dropdown"
                                    :style="dropUp ? 'bottom: calc(100% + 6px); top: auto;' : 'top: calc(100% + 6px); bottom: auto;'"
                                >
                                    {{-- VIEW COLOR --}}
                                    <button
                                        @click="open = false"
                                        wire:click="$dispatch('view-munsell',{id:{{ $color->munsell_color_id ?? $color->id }}})"
                                        type="button"
                                        class="agrico-dropdown-item"
                                    >
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="color:#16a34a; flex-shrink:0;">
                                            <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span>View Color</span>
                                    </button>

                                    {{-- EDIT COLOR --}}
                                    <button
                                        @click="open = false"
                                        wire:click="$dispatch('edit-munsell',{id:{{ $color->munsell_color_id ?? $color->id }}})"
                                        type="button"
                                        class="agrico-dropdown-item"
                                    >
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="color:#2563eb; flex-shrink:0;">
                                            <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                        <span>Edit Color</span>
                                    </button>

                                    <div style="height:1px; background:#f1f5f9; margin:4px 0;"></div>

                                    {{-- DELETE COLOR --}}
                                    <button
                                        @click="open = false"
                                        wire:click="$dispatch('delete-munsell',{id:{{ $color->munsell_color_id ?? $color->id }}})"
                                        type="button"
                                        class="agrico-dropdown-item agrico-dropdown-item-danger"
                                    >
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="color:#ef4444; flex-shrink:0;">
                                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        <span>Delete Color</span>
                                    </button>
                                </div>
                            </div>
                        </td>


                    </tr>


                    @empty
                    <tr>
                        <td colspan="8" style="padding: 64px 24px; text-align: center; border-bottom: none;">
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px;">
                                <div style="width: 56px; height: 56px; border-radius: 16px; background: #f0fdf4; border: 1px solid #dcfce7; display: flex; align-items: center; justify-content: center; color: #22c55e; box-shadow: 0 4px 14px rgba(22,163,74,0.12);">
                                    <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10"/>
                                        <path d="M12 8v8M8 12h8"/>
                                    </svg>
                                </div>
                                <div style="font-size: 15.5px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                                    No Munsell Colors Found
                                </div>
                                <div style="font-size: 12.5px; font-weight: 500; color: #64748b; max-width: 360px; line-height: 1.4;">
                                    No soil Munsell color entries matched your search term or active color filters.
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse

                </tbody>
            </table>


        </div>





        {{-- PAGINATION --}}


        {{-- PAGINATION FOOTER (MATCHING IMAGE 1) --}}
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
                    {{ $colors->firstItem() ?? 0 }}–{{ $colors->lastItem() ?? 0 }} of {{ $colors->total() }}
                </span>
            </div>

            {{-- RIGHT SIDE: PREV, 3 PAGE NUMBERS, NEXT BUTTONS --}}
            @if ($colors->hasPages())
                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: nowrap;">
                    {{-- PREVIOUS BUTTON --}}
                    @if ($colors->onFirstPage())
                        <button
                            disabled
                            style="padding: 5px 12px; border-radius: 8px; border: 1px solid #f1f5f9; background: #f8fafc; color: #cbd5e1; font-size: 12.5px; font-weight: 600; cursor: not-allowed;"
                        >
                            Prev
                        </button>
                    @else
                        <button
                            wire:click="previousPage"
                            style="padding: 5px 12px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all 0.15s ease;"
                            onmouseover="this.style.borderColor='#22c55e'; this.style.color='#22c55e';"
                            onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#475569';"
                        >
                            Prev
                        </button>
                    @endif

                    {{-- EXACT 3 NUMERIC PAGE BUTTONS --}}
                    @php
                        $currentPage = $colors->currentPage();
                        $lastPage = $colors->lastPage();

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

                    @foreach ($colors->getUrlRange($startPage, $endPage) as $page => $url)
                        @if ($page == $currentPage)
                            <button
                                wire:click="gotoPage({{ $page }})"
                                style="width: 32px; height: 32px; border-radius: 8px; background: #22c55e !important; color: #ffffff !important; font-size: 13px; font-weight: 700; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(34,197,94,0.35);"
                            >
                                {{ $page }}
                            </button>
                        @else
                            <button
                                wire:click="gotoPage({{ $page }})"
                                style="width: 32px; height: 32px; border-radius: 8px; background: #fff; border: 1px solid #e2e8f0; color: #334155; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.15s ease;"
                                onmouseover="this.style.borderColor='#22c55e'; this.style.color='#22c55e';"
                                onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#334155';"
                            >
                                {{ $page }}
                            </button>
                        @endif
                    @endforeach

                    @if ($endPage < $lastPage)
                        <button wire:click="gotoPage({{ min($lastPage, $endPage + 1) }})" style="min-width: 24px; height: 32px; background: transparent; border: none; color: #64748b; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 0 4px; transition: color 0.15s ease;" onmouseover="this.style.color='#22c55e';" onmouseout="this.style.color='#64748b';" title="Page {{ min($lastPage, $endPage + 1) }}">...</button>
                    @endif

                    {{-- NEXT BUTTON --}}
                    @if ($colors->hasMorePages())
                        <button
                            wire:click="nextPage"
                            style="padding: 5px 12px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all 0.15s ease;"
                            onmouseover="this.style.borderColor='#22c55e'; this.style.color='#22c55e';"
                            onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#475569';"
                        >
                            Next
                        </button>
                    @else
                        <button
                            disabled
                            style="padding: 5px 12px; border-radius: 8px; border: 1px solid #f1f5f9; background: #f8fafc; color: #cbd5e1; font-size: 12.5px; font-weight: 600; cursor: not-allowed;"
                        >
                            Next
                        </button>
                    @endif
                </div>
            @endif
        </div>



    </div>

    {{-- MODALS --}}
    <livewire:admin.munsell-color.munsell-detail-modal />
    <livewire:admin.munsell-color.munsell-create-modal />
    <livewire:admin.munsell-color.munsell-edit-modal />
    <livewire:admin.munsell-color.munsell-delete-modal />

    <style>
        /* ── RESPONSIVE TABLE STYLING FOR MANAGE MUNSELL COLOR ── */
        .munsell-table-container {
            width: 100% !important;
            overflow-x: auto !important;
            overflow-y: visible !important;
            scrollbar-width: none !important; /* Firefox */
            -ms-overflow-style: none !important;  /* IE/Edge */
        }
        .munsell-table-container::-webkit-scrollbar {
            display: none !important; /* Chrome/Safari */
        }

        .munsell-table {
            width: 100% !important;
            min-width: 0 !important;
            table-layout: auto !important;
            border-collapse: collapse !important;
        }

        .munsell-table th,
        .munsell-table td {
            box-sizing: border-box !important;
        }

        /* First column left padding: Desktop (≥ 680px) = 20px */
        .munsell-table th.col-munsell-id,
        .munsell-table td.col-munsell-id {
            padding-left: 20px !important;
        }

        /* ── STATUS & ACTION COLUMNS: ALWAYS VISIBLE & STICKY ON RIGHT ACROSS ALL SCREEN SIZES ── */
        .munsell-table th.col-sticky-action,
        .munsell-table td.col-sticky-action {
            position: sticky !important;
            right: 0 !important;
            width: 144px !important;
            min-width: 144px !important;
            max-width: 144px !important;
            white-space: nowrap !important;
            box-shadow: none !important;
            border-left: none !important;
            border-right: none !important;
            z-index: 12 !important;
            background-color: #ffffff !important;
            text-align: center !important;
            padding-left: 12px !important;
            padding-right: 12px !important;
            display: table-cell !important;
        }
        .munsell-table td.col-sticky-action .munsell-desktop-actions {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 6px !important;
            width: 100% !important;
        }
        .munsell-table th.col-sticky-action {
            z-index: 22 !important;
            background-color: #F1FBF1 !important;
            text-align: center !important;
        }

        .munsell-table th.col-munsell-status,
        .munsell-table td.col-munsell-status {
            position: sticky !important;
            right: 144px !important;
            width: 110px !important;
            min-width: 110px !important;
            max-width: 110px !important;
            white-space: nowrap !important;
            box-shadow: none !important;
            border-left: none !important;
            border-right: none !important;
            z-index: 11 !important;
            background-color: #ffffff !important;
            text-align: center !important;
            padding-left: 8px !important;
            padding-right: 8px !important;
            display: table-cell !important;
        }
        .munsell-table th.col-munsell-status {
            z-index: 21 !important;
            background-color: #F1FBF1 !important;
        }

        .munsell-table th.col-munsell-image,
        .munsell-table td.col-munsell-image {
            width: 115px !important;
            min-width: 115px !important;
            text-align: center !important;
            white-space: nowrap !important;
        }

        /* Hover & match row backgrounds for sticky status and action cells */
        .munsell-table tbody tr:hover td.col-sticky-action,
        .munsell-table tbody tr:hover td.col-munsell-status,
        .munsell-table tbody tr.hover\:bg-green-50\/40:hover td.col-sticky-action,
        .munsell-table tbody tr.hover\:bg-green-50\/40:hover td.col-munsell-status {
            background-color: #f7fdf7 !important;
        }
        .munsell-table tbody tr.bg-emerald-50\/80 td.col-sticky-action,
        .munsell-table tbody tr.bg-emerald-50\/80 td.col-munsell-status {
            background-color: #ecfdf5 !important;
        }
        .dark .munsell-table th.col-sticky-action,
        .dark .munsell-table th.col-munsell-status {
            background-color: #1e293b !important;
        }
        .dark .munsell-table td.col-sticky-action,
        .dark .munsell-table td.col-munsell-status {
            background-color: #0f172a !important;
        }

        /* Action buttons standard styling (Standard 36px Desktop) */
        .munsell-table .agrico-view-btn,
        .munsell-table .agrico-edit-btn,
        .munsell-table .agrico-delete-btn {
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
        .munsell-table .agrico-view-btn svg,
        .munsell-table .agrico-edit-btn svg,
        .munsell-table .agrico-delete-btn svg {
            width: 17px !important;
            height: 17px !important;
        }

        /* Medium Desktop / Small Laptop (< 1200px): Hide Soil Type and Created */
        @media (max-width: 1199px) {
            .col-munsell-soiltype,
            .col-munsell-created {
                display: none !important;
            }
        }

        /* Tablet (< 1024px): Hide ID and Soil Organic Index */
        @media (max-width: 1023px) {
            .col-munsell-id,
            .col-munsell-organic {
                display: none !important;
            }
        }

        /* Desktop & Tablet (≥ 680px and < 1024px): When ID is hidden, Color Name is first visible column -> 20px left padding */
        @media (min-width: 680px) and (max-width: 1023px) {
            .munsell-table th.col-munsell-name,
            .munsell-table td.col-munsell-name {
                padding-left: 20px !important;
            }
        }

        /* Header responsive bar */
        .munsell-header-bar {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
            width: 100%;
        }
        @media (min-width: 640px) {
            .munsell-header-bar {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
            }
        }

        .munsell-title-desktop {
            display: inline;
        }
        .munsell-title-mobile {
            display: none;
        }
        .munsell-action-title-desktop {
            display: inline;
        }
        .munsell-action-title-mobile {
            display: none;
        }
        .munsell-desktop-actions {
            display: flex;
        }
        .munsell-mobile-actions {
            display: none;
        }

        /* Agrico Mobile More Actions Button & Dropdown Menu */
        .agrico-more-btn {
            width: 40px !important;
            height: 40px !important;
            min-width: 40px !important;
            max-width: 40px !important;
            min-height: 40px !important;
            max-height: 40px !important;
            border-radius: 12px !important;
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            color: #64748b !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04) !important;
        }
        .agrico-more-btn:hover {
            background: #f8fafc !important;
            color: #0f172a !important;
            border-color: #cbd5e1 !important;
        }
        .dark .agrico-more-btn {
            background: #1e293b !important;
            border-color: #334155 !important;
            color: #94a3b8 !important;
        }
        .dark .agrico-more-btn:hover {
            background: #334155 !important;
            color: #f8fafc !important;
        }
        .agrico-mobile-dropdown {
            position: absolute !important;
            right: 0 !important;
            width: 160px !important;
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 16px !important;
            box-shadow: 0 12px 28px -4px rgba(0,0,0,0.14), 0 8px 12px -6px rgba(0,0,0,0.06) !important;
            padding: 6px !important;
            z-index: 90 !important;
            text-align: left !important;
        }
        .dark .agrico-mobile-dropdown {
            background: #1e293b !important;
            border-color: #334155 !important;
            box-shadow: 0 12px 28px -4px rgba(0,0,0,0.4) !important;
        }
        .agrico-dropdown-item {
            width: 100% !important;
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            padding: 8px 12px !important;
            border-radius: 10px !important;
            border: none !important;
            background: transparent !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            color: #334155 !important;
            cursor: pointer !important;
            transition: all 0.15s ease !important;
            text-align: left !important;
            box-sizing: border-box !important;
        }
        .agrico-dropdown-item:hover {
            background: #f8fafc !important;
            color: #0f172a !important;
        }
        .dark .agrico-dropdown-item {
            color: #cbd5e1 !important;
        }
        .dark .agrico-dropdown-item:hover {
            background: #334155 !important;
            color: #ffffff !important;
        }
        .agrico-dropdown-item-danger {
            color: #ef4444 !important;
        }
        .agrico-dropdown-item-danger:hover {
            background: #fef2f2 !important;
            color: #b91c1c !important;
        }
        .dark .agrico-dropdown-item-danger:hover {
            background: rgba(239, 68, 68, 0.15) !important;
            color: #fca5a5 !important;
        }

        /* Mobile (< 680px): Table fits 100% of phone screen. Status and Action are NEVER HIDDEN! */
        @media (max-width: 679px) {
            .munsell-title-desktop {
                display: none !important;
            }
            .munsell-title-mobile {
                display: inline !important;
            }
            .munsell-action-title-desktop {
                display: none !important;
            }
            .munsell-action-title-mobile {
                display: inline !important;
            }
            .munsell-table td.col-sticky-action .munsell-desktop-actions,
            .munsell-desktop-actions {
                display: none !important;
            }
            .munsell-table td.col-sticky-action .munsell-mobile-actions,
            .munsell-mobile-actions {
                display: inline-flex !important;
                position: relative !important;
                justify-content: center !important;
                align-items: center !important;
            }
            .munsell-table-card {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                box-sizing: border-box !important;
                overflow: visible !important;
            }
            .munsell-table-container {
                overflow: visible !important;
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
            .munsell-table {
                table-layout: fixed !important;
                width: 100% !important;
                min-width: 0 !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
            .col-munsell-id,
            .col-munsell-image,
            .col-munsell-code,
            .col-munsell-organic,
            .col-munsell-soiltype,
            .col-munsell-created {
                display: none !important;
            }
            /* GUARANTEED ALWAYS VISIBLE COLUMNS */
            .col-munsell-status,
            .col-sticky-action {
                display: table-cell !important;
            }
            .munsell-table th,
            .munsell-table td {
                box-sizing: border-box !important;
            }
            .munsell-table th.col-munsell-name,
            .munsell-table td.col-munsell-name {
                width: auto !important;
                overflow: hidden !important;
                padding-left: 12px !important;
                padding-right: 4px !important;
            }
            /* Mobile STATUS Column: 82px width, centered */
            .munsell-table th.col-munsell-status,
            .munsell-table td.col-munsell-status {
                position: static !important;
                left: auto !important;
                right: auto !important;
                width: 82px !important;
                min-width: 82px !important;
                max-width: 82px !important;
                padding: 10px 2px !important;
                text-align: center !important;
                display: table-cell !important;
            }
            .munsell-table td.col-munsell-status .munsell-status-btn {
                padding: 3px 8px !important;
                font-size: 11px !important;
                font-weight: 700 !important;
                gap: 4px !important;
                max-width: 100% !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            .munsell-table td.col-munsell-status .munsell-status-btn span:first-child {
                width: 6px !important;
                height: 6px !important;
            }
            /* Mobile ACTION Column: 58px width, single "⋮" button */
            .munsell-table th.col-sticky-action,
            .munsell-table td.col-sticky-action {
                position: static !important;
                left: auto !important;
                right: auto !important;
                width: 58px !important;
                min-width: 58px !important;
                max-width: 58px !important;
                padding: 10px 4px !important;
                text-align: center !important;
                display: table-cell !important;
            }
            .munsell-table th.col-sticky-action {
                text-align: center !important;
            }
        }
    </style>
</div>
