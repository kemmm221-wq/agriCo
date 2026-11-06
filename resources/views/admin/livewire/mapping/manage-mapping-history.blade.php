<div wire:poll.visible.60s class="space-y-6">

    {{-- HEADER --}}
    <div class="ui-reveal flex items-center justify-between" style="--i:0">
        <div>
            <h1 class="ui-reveal-text text-3xl font-extrabold text-gray-900" style="--i:0">
                Mapping History
            </h1>
            <p class="ui-reveal-text text-sm text-gray-400 mt-1" style="--i:0">
                {{ $mappings->total() ?? 0 }} location mapping records in database
            </p>
        </div>
    </div>

    {{-- SEARCH AND FILTER CARD --}}
    <div class="ui-reveal" style="--i:1; background:#ffffff; border-radius:20px; border:1px solid #f1f5f9; box-shadow:0 1px 3px rgba(0,0,0,0.03); padding:12px 16px; display:flex; align-items:center; gap:14px; width:100%; box-sizing:border-box; flex-wrap:wrap;">
        {{-- SEARCH INPUT --}}
        <div style="position:relative; flex:1; min-width:200px;">
            <svg style="position:absolute; left:16px; top:50%; transform:translateY(-50%); width:18px; height:18px; color:#94a3b8;" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input
                wire:model.live.debounce.300ms="search"
                @keydown.escape="$wire.set('search', ''); $el.value = '';"
                type="text"
                placeholder="Search soil name, coordinates, notes..."
                style="width:100%; border:1px solid #e2e8f0; border-radius:16px; background:#ffffff; padding:10px 44px 10px 46px; font-size:14px; font-weight:500; color:#1e293b; outline:none; transition:all 0.2s ease; box-sizing:border-box;"
                onfocus="this.style.borderColor='#22c55e'; this.style.boxShadow='0 0 0 3px rgba(68,175,53,0.15)';"
                onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none';"
            />
            @if($search)
                <x-search-clear-button wireModel="search" />
            @endif
        </div>

        {{-- FUNNEL ICON --}}
        <svg width="18" height="18" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="flex-shrink:0;">
            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
        </svg>

        {{-- FILTER DROPDOWN --}}
        <select
            wire:model.live="statusFilter"
            style="border:1px solid #e2e8f0; border-radius:14px; background:#ffffff; padding:8px 16px; font-size:13px; font-weight:600; color:#334155; outline:none; cursor:pointer; transition:all 0.2s ease; flex-shrink:0;"
            onfocus="this.style.borderColor='#22c55e';"
            onblur="this.style.borderColor='#e2e8f0';"
        >
            <option value="">All Statuses</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
        </select>

        {{-- RESULTS COUNTER --}}
        <div style="--i:1; font-size:13px; font-weight:500; color:#94a3b8; flex-shrink:0;">
            {{ $mappings->total() }} results
        </div>
    </div>

    {{-- TABLE CARD --}}
    <div class="ui-reveal bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden" style="--i:2">
        {{-- CARD HEADER --}}
        <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="ui-reveal-text text-base font-bold text-gray-900 dark:text-slate-100" style="--i:2">
                Mapping History ({{ $mappings->total() }})
            </h2>
        </div>

        <div class="overflow-x-auto mapping-table-container">
            <table class="w-full min-w-0 md:min-w-[1000px] mapping-table">
                <thead>
                    <tr class="bg-[#F1FBF1] border-b border-gray-100">

                        {{-- SOIL NAME --}}
                        <th class="col-mapping-soil px-5 py-4 text-left text-xs font-bold text-gray-500 whitespace-nowrap">
                            SOIL NAME
                        </th>

                        {{-- COORDINATES --}}
                        <th
                            wire:click="sortBy('latitude')"
                            class="col-mapping-coords soil-th-sortable px-5 py-4 text-left text-xs font-bold text-gray-500 cursor-pointer select-none group whitespace-nowrap"
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

                        {{-- SCAN DETAILS --}}
                        <th class="col-mapping-note px-5 py-4 text-left text-xs font-bold text-gray-500 whitespace-nowrap">
                            SCAN NOTE
                        </th>

                        {{-- TIMESTAMP --}}
                        <th
                            wire:click="sortBy('timestamp')"
                            class="col-mapping-time soil-th-sortable px-5 py-4 text-left text-xs font-bold text-gray-500 cursor-pointer select-none group whitespace-nowrap"
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
                        <th class="col-mapping-status px-5 py-4 text-left text-xs font-bold text-gray-500 whitespace-nowrap">
                            STATUS
                        </th>

                        {{-- ACTIONS --}}
                        <th class="col-sticky-action px-5 py-4 text-center text-xs font-bold text-gray-500 whitespace-nowrap" style="min-width: 120px; padding-right: 12px !important;">
                            ACTIONS
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($mappings as $map)
                    <tr wire:key="history-row-{{ $map->location_mapping_id ?? $map->id }}" class="border-b border-gray-100 hover:bg-green-50/40 transition">

                        {{-- SOIL NAME --}}
                        <td class="col-mapping-soil px-5 py-5 whitespace-nowrap">
                            <div class="font-bold text-sm text-gray-800">
                                {{ $map->soil->soil_name ?? '—' }}
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{ $map->soil->full_code ?? '' }}
                            </div>
                            <div class="mapping-mobile-meta text-xs text-gray-400 mt-0.5 hidden">
                                <span>{{ number_format($map->latitude, 4) }}, {{ number_format($map->longitude, 4) }}</span>
                            </div>
                        </td>

                        {{-- COORDINATES --}}
                        <td class="col-mapping-coords px-5 py-5 whitespace-nowrap">
                            <div class="flex items-center gap-1.5 text-sm text-gray-700 font-semibold">
                                <svg class="w-4 h-4 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ number_format($map->latitude, 4) }}, {{ number_format($map->longitude, 4) }}</span>
                            </div>
                        </td>

                        {{-- SCAN NOTE --}}
                        <td class="col-mapping-note px-5 py-5" style="max-width: 250px;">
                            <div class="text-sm text-gray-600 truncate" title="{{ $map->soilScan->scan_note ?? '—' }}">
                                {{ $map->soilScan->scan_note ?? '—' }}
                            </div>
                        </td>

                        {{-- TIMESTAMP --}}
                        <td class="col-mapping-time px-5 py-5 text-sm text-gray-500 whitespace-nowrap">
                            {{ $map->timestamp ? \Illuminate\Support\Carbon::parse($map->timestamp)->format('Y-m-d H:i') : $map->created_at?->format('Y-m-d H:i') }}
                        </td>

                        {{-- STATUS --}}
                        <td class="col-mapping-status px-5 py-5 whitespace-nowrap">
                            <button
                                wire:click="toggleStatus({{ $map->location_mapping_id ?? $map->id }})"
                                wire:loading.attr="disabled"
                                wire:target="toggleStatus({{ $map->location_mapping_id ?? $map->id }})"
                                type="button"
                                title="Click to set {{ ($map->status ?? 'Active') === 'Active' ? 'Inactive' : 'Active' }}"
                                style="{{ ($map->status ?? 'Active') === 'Active' ? 'background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0;' : 'background:#fee2e2; color:#ef4444; border:1px solid #fecaca;' }} display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:9999px; font-size:12px; font-weight:700; cursor:pointer; transition:all 0.2s ease;"
                                onmouseover="this.style.opacity='0.85'; this.style.transform='scale(1.03)';"
                                onmouseout="this.style.opacity='1'; this.style.transform='scale(1)';"
                            >
                                <span style="{{ ($map->status ?? 'Active') === 'Active' ? 'background:#22c55e;' : 'background:#ef4444;' }} width:7px; height:7px; border-radius:50%; display:inline-block; flex-shrink:0;"></span>
                                <span>{{ $map->status ?? 'Active' }}</span>
                            </button>
                        </td>

                        {{-- ACTIONS --}}
                        <td class="px-5 py-5 whitespace-nowrap col-sticky-action" style="min-width: 120px; padding-right: 12px !important;">
                            <div class="flex justify-end items-center gap-1.5 whitespace-nowrap">
                                {{-- VIEW --}}
                                <button
                                    wire:click="$dispatch('view-mapping',{id:{{ $map->location_mapping_id ?? $map->id }}})"
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
                                    wire:click="$dispatch('delete-mapping',{id:{{ $map->id }}})"
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
                        <td colspan="6" style="padding: 64px 24px; text-align: center; border-bottom: none;">
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px;">
                                <div style="width: 56px; height: 56px; border-radius: 16px; background: #f0fdf4; border: 1px solid #dcfce7; display: flex; align-items: center; justify-content: center; color: #22c55e; box-shadow: 0 4px 14px rgba(22,163,74,0.12);">
                                    <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                                        <polyline points="2 17 12 22 22 17"/>
                                        <polyline points="2 12 12 17 22 12"/>
                                    </svg>
                                </div>
                                <div style="font-size: 15.5px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                                    No Mapping History Found
                                </div>
                                <div style="font-size: 12.5px; font-weight: 500; color: #64748b; max-width: 360px; line-height: 1.4;">
                                    No location mapping history records matched your search query or active filter.
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

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
                    {{ $mappings->firstItem() ?? 0 }}–{{ $mappings->lastItem() ?? 0 }} of {{ $mappings->total() }}
                </span>
            </div>

            {{-- RIGHT SIDE: PREV, PAGE NUMBERS, NEXT BUTTONS --}}
            @if ($mappings->hasPages())
                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: nowrap;">
                    @if ($mappings->onFirstPage())
                        <button disabled style="padding: 5px 12px; border-radius: 8px; border: 1px solid #f1f5f9; background: #f8fafc; color: #cbd5e1; font-size: 12.5px; font-weight: 600; cursor: not-allowed;">Prev</button>
                    @else
                        <button wire:click="previousPage" style="padding: 5px 12px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all 0.15s ease;" onmouseover="this.style.borderColor='#22c55e'; this.style.color='#22c55e';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#475569';">Prev</button>
                    @endif

                    @php
                        $currentPage = $mappings->currentPage();
                        $lastPage = $mappings->lastPage();

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

                    @foreach ($mappings->getUrlRange($startPage, $endPage) as $page => $url)
                        @if ($page == $currentPage)
                            <button wire:click="gotoPage({{ $page }})" style="width: 32px; height: 32px; border-radius: 8px; background: #22c55e !important; color: #fff !important; font-size: 13px; font-weight: 700; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(34,197,94,0.35);">{{ $page }}</button>
                        @else
                            <button wire:click="gotoPage({{ $page }})" style="width: 32px; height: 32px; border-radius: 8px; background: #fff; border: 1px solid #e2e8f0; color: #334155; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.15s ease;" onmouseover="this.style.borderColor='#22c55e'; this.style.color='#22c55e';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#334155';">{{ $page }}</button>
                        @endif
                    @endforeach

                    @if ($endPage < $lastPage)
                        <button wire:click="gotoPage({{ min($lastPage, $endPage + 1) }})" style="min-width: 24px; height: 32px; background: transparent; border: none; color: #64748b; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 0 4px; transition: color 0.15s ease;" onmouseover="this.style.color='#22c55e';" onmouseout="this.style.color='#64748b';" title="Page {{ min($lastPage, $endPage + 1) }}">...</button>
                    @endif

                    @if ($mappings->hasMorePages())
                        <button wire:click="nextPage" style="padding: 5px 12px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all 0.15s ease;" onmouseover="this.style.borderColor='#22c55e'; this.style.color='#22c55e';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#475569';">Next</button>
                    @else
                        <button disabled style="padding: 5px 12px; border-radius: 8px; border: 1px solid #f1f5f9; background: #f8fafc; color: #cbd5e1; font-size: 12.5px; font-weight: 600; cursor: not-allowed;">Next</button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <style>
        .mapping-table th:first-child,
        .mapping-table td:first-child {
            padding-left: 20px !important;
        }
        @media (max-width: 679px) {
            .mapping-table-container {
                overflow-x: hidden !important;
                width: 100% !important;
            }
            .mapping-table {
                table-layout: fixed !important;
                width: 100% !important;
                min-width: 0 !important;
            }
            .col-mapping-coords,
            .col-mapping-note,
            .col-mapping-time {
                display: none !important;
            }
            .mapping-mobile-meta {
                display: block !important;
            }
            .mapping-table th:first-child,
            .mapping-table td:first-child {
                padding-left: 12px !important;
                padding-right: 4px !important;
            }
            .col-mapping-status {
                width: 84px !important;
                min-width: 84px !important;
                max-width: 84px !important;
                padding: 10px 4px !important;
                text-align: center !important;
            }
            .col-mapping-status button {
                padding: 3px 8px !important;
                font-size: 11px !important;
            }
            .col-sticky-action {
                width: 88px !important;
                min-width: 88px !important;
                max-width: 88px !important;
                padding: 10px 8px 10px 2px !important;
                text-align: center !important;
            }
            .col-sticky-action .flex {
                justify-content: center !important;
                gap: 4px !important;
            }
            .agrico-view-btn,
            .agrico-delete-btn {
                width: 32px !important;
                height: 32px !important;
                min-width: 32px !important;
                border-radius: 8px !important;
            }
        }
    </style>

    {{-- MODALS --}}
    <livewire:admin.mapping.mapping-detail-modal />
    <livewire:admin.mapping.mapping-delete-modal />
</div>
