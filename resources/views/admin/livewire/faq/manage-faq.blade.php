<div class="space-y-6">

    {{-- FLASH NOTIFICATION --}}
    @if(session()->has('message'))
        <div class="ui-reveal p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center justify-between shadow-sm" style="--i:0">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('message') }}</span>
            </div>
            <button type="button" onclick="this.closest('div').remove()" class="text-emerald-500 hover:text-emerald-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- HEADER --}}
    <div class="ui-reveal faq-header-bar" style="--i:0">
        <div>
            <h1 class="ui-reveal-text text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight" style="--i:0">
                Manage FAQ
            </h1>
            <p class="ui-reveal-text text-xs sm:text-sm text-gray-400 mt-1" style="--i:0">
                {{ $faqs->total() ?? 0 }} FAQ records in database
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <button
                wire:click="syncDefaultFaqs"
                wire:loading.attr="disabled"
                class="faq-sync-btn ui-reveal-text"
                title="Sync standard questions from system catalog"
                style="--i:0; background: #ffffff !important; color: #334155 !important; font-weight: 700; font-size: 13px; border: 1.5px solid #e2e8f0; padding: 9px 16px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); cursor: pointer; transition: all 0.2s ease; width: fit-content; flex-shrink: 0;"
                onmouseover="this.style.borderColor='#22c55e'; this.style.color='#16a34a';"
                onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#334155';"
            >
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                </svg>
                <span wire:loading.remove wire:target="syncDefaultFaqs">Sync Defaults</span>
                <span wire:loading wire:target="syncDefaultFaqs">Syncing...</span>
            </button>

            <button
                wire:click="$dispatch('create-faq')"
                class="faq-add-btn ui-reveal-text"
                style="--i:0; background: #22c55e !important; color: #ffffff !important; font-weight: 700; font-size: 13px; border: none; padding: 10px 20px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35); cursor: pointer; transition: all 0.2s ease; width: fit-content; flex-shrink: 0;"
                onmouseover="this.style.background='#16a34a';"
                onmouseout="this.style.background='#22c55e';"
            >
                <span style="font-size: 16px; font-weight: 800; line-height: 1;">+</span>
                <span>Add FAQ</span>
            </button>
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
                placeholder="Search question, answer, category..."
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
            wire:model.live="category"
            style="border:1px solid #e2e8f0; border-radius:14px; background:#ffffff; padding:8px 16px; font-size:13px; font-weight:600; color:#334155; outline:none; cursor:pointer; transition:all 0.2s ease; flex-shrink:0;"
            onfocus="this.style.borderColor='#22c55e';"
            onblur="this.style.borderColor='#e2e8f0';"
        >
            <option value="">All Categories</option>
            @foreach($faqCategories as $catOption)
                <option value="{{ $catOption }}">{{ $catOption }}</option>
            @endforeach
        </select>

        {{-- RESULTS COUNTER --}}
        <div style="--i:1; font-size:13px; font-weight:500; color:#94a3b8; flex-shrink:0;">
            {{ $faqs->total() ?? 0 }} results
        </div>
    </div>

    {{-- TABLE CARD --}}
    <div class="ui-reveal bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800 shadow-sm overflow-hidden faq-table-card w-full max-w-full min-w-0 box-border" style="--i:2">
        {{-- CARD HEADER --}}
        <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="ui-reveal-text text-base font-bold text-gray-900 dark:text-slate-100" style="--i:2">
                Frequently Asked Questions ({{ $faqs->total() ?? 0 }})
            </h2>
        </div>

        <div class="overflow-x-auto faq-table-container no-scrollbar">
            <table class="w-full faq-table min-w-0 md:min-w-[900px]">
                <thead>
                    <tr class="bg-[#F1FBF1] dark:bg-slate-800/80 border-b border-gray-100 dark:border-slate-800">

                        {{-- QUESTION & ANSWER --}}
                        <th
                            wire:click="sortBy('question')"
                            class="soil-th-sortable px-5 py-4 text-left text-xs font-bold text-gray-500 cursor-pointer select-none group whitespace-nowrap col-faq-qa"
                        >
                            <div class="flex items-center gap-1">
                                <span class="group-hover:text-[#22c55e] transition-colors duration-200">
                                    <span class="faq-title-desktop">QUESTION & ANSWER</span>
                                    <span class="faq-title-mobile">QUESTION</span>
                                </span>
                                <span class="sort-arrows">
                                    @if($sortField === 'question')
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

                        {{-- CATEGORY --}}
                        <th
                            wire:click="sortBy('category')"
                            class="soil-th-sortable px-5 py-4 text-left text-xs font-bold text-gray-500 cursor-pointer select-none group whitespace-nowrap col-faq-category"
                        >
                            <div class="flex items-center gap-1">
                                <span class="group-hover:text-[#22c55e] transition-colors duration-200">CATEGORY</span>
                                <span class="sort-arrows">
                                    @if($sortField === 'category')
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
                        <th class="px-5 py-4 text-center text-xs font-bold text-gray-500 whitespace-nowrap col-faq-status">
                            STATUS
                        </th>

                        {{-- ACTIONS --}}
                        <th class="px-3 py-4 text-center text-xs font-bold text-gray-500 whitespace-nowrap col-sticky-action">
                            <span class="faq-action-title-desktop">ACTIONS</span>
                            <span class="faq-action-title-mobile">ACTION</span>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($faqs as $faq)
                    <tr class="border-b border-gray-100 hover:bg-green-50/40 transition">

                        {{-- QUESTION & ANSWER --}}
                        <td class="px-5 py-4 col-faq-qa">
                            <div class="min-w-0" style="max-width: 100%; overflow: hidden;">
                                <div class="text-sm font-bold text-gray-800 truncate" style="font-size: 14px; font-weight: 700; color: #374151; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $faq->question }}">
                                    {{ $faq->question }}
                                </div>
                                <div class="text-xs font-medium text-slate-400 truncate mt-0.5" style="font-size: 12px; font-weight: 500; color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ strip_tags($faq->answer) }}">
                                    {{ strip_tags($faq->answer) }}
                                </div>
                                {{-- MOBILE ONLY: Show category badge under question --}}
                                <div class="faq-mobile-category mt-1.5 hidden" style="max-width: 100%;">
                                    <span class="faq-category-pill" style="display:inline-flex; align-items:center; max-width:100%; padding:2px 8px; border-radius:9999px; font-size:10.5px; font-weight:700; background:#f5f3ff; color:#7c3aed; border:1px solid #ede9fe; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; line-height:1.2;">
                                        {{ $faq->category ?? 'General' }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        {{-- CATEGORY --}}
                        <td class="px-5 py-5 whitespace-nowrap col-faq-category">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-100">
                                {{ $faq->category ?? 'General' }}
                            </span>
                        </td>

                        {{-- STATUS --}}
                        <td class="px-3 py-5 whitespace-nowrap col-faq-status text-center">
                            <button
                                wire:click="toggleStatus({{ $faq->id }})"
                                type="button"
                                class="faq-status-btn"
                                style="{{ ($faq->status ?? 'Active') === 'Active' ? 'background:#dcfce7; color:#22c55e;' : 'background:#f1f5f9; color:#475569;' }} display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:9999px; font-size:12px; font-weight:700; border:none; cursor:pointer; transition:all 0.2s ease;"
                            >
                                <span style="{{ ($faq->status ?? 'Active') === 'Active' ? 'background:#22c55e;' : 'background:#94a3b8;' }} width:7px; height:7px; border-radius:50%; display:inline-block; flex-shrink:0;"></span>
                                <span>{{ $faq->status ?? 'Active' }}</span>
                            </button>
                        </td>

                        {{-- ACTIONS --}}
                        <td class="px-3 py-5 whitespace-nowrap col-sticky-action text-center">
                            {{-- DESKTOP INLINE ACTIONS --}}
                            <div class="faq-desktop-actions">
                                {{-- VIEW --}}
                                <button
                                    wire:click="$dispatch('view-faq',{id:{{ $faq->faq_id ?? $faq->id }}})"
                                    class="agrico-view-btn"
                                    title="View Details"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>

                                {{-- EDIT --}}
                                <button
                                    wire:click="$dispatch('edit-faq',{id:{{ $faq->faq_id ?? $faq->id }}})"
                                    class="agrico-edit-btn"
                                    title="Edit FAQ"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>

                                {{-- DELETE --}}
                                <button
                                    wire:click="$dispatch('delete-faq',{id:{{ $faq->faq_id ?? $faq->id }}})"
                                    class="agrico-delete-btn"
                                    title="Delete FAQ"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>

                            {{-- MOBILE MORE ACTIONS "⋮" DROPDOWN --}}
                            <div class="faq-mobile-actions" x-data="{ open: false, dropUp: false }" @click.outside="open = false" :style="open ? 'z-index: 60;' : ''">
                                <button
                                    @click="dropUp = ($el.getBoundingClientRect().bottom + 170 > window.innerHeight) || ($el.closest('.faq-table-card')?.getBoundingClientRect().bottom - $el.getBoundingClientRect().bottom < 170); open = !open"
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
                                    {{-- VIEW DETAILS --}}
                                    <button
                                        @click="open = false"
                                        wire:click="$dispatch('view-faq',{id:{{ $faq->faq_id ?? $faq->id }}})"
                                        type="button"
                                        class="agrico-dropdown-item"
                                    >
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="color:#16a34a; flex-shrink:0;">
                                            <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span>View Details</span>
                                    </button>

                                    {{-- EDIT FAQ --}}
                                    <button
                                        @click="open = false"
                                        wire:click="$dispatch('edit-faq',{id:{{ $faq->faq_id ?? $faq->id }}})"
                                        type="button"
                                        class="agrico-dropdown-item"
                                    >
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="color:#2563eb; flex-shrink:0;">
                                            <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                        <span>Edit FAQ</span>
                                    </button>

                                    <div style="height:1px; background:#f1f5f9; margin:4px 0;"></div>

                                    {{-- DELETE FAQ --}}
                                    <button
                                        @click="open = false"
                                        wire:click="$dispatch('delete-faq',{id:{{ $faq->faq_id ?? $faq->id }}})"
                                        type="button"
                                        class="agrico-dropdown-item agrico-dropdown-item-danger"
                                    >
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="color:#ef4444; flex-shrink:0;">
                                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        <span>Delete FAQ</span>
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding: 64px 24px; text-align: center; border-bottom: none;">
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px;">
                                <div style="width: 56px; height: 56px; border-radius: 16px; background: #f0fdf4; border: 1px solid #dcfce7; display: flex; align-items: center; justify-content: center; color: #22c55e; box-shadow: 0 4px 14px rgba(22,163,74,0.12);">
                                    <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10"/>
                                        <path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/>
                                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                                    </svg>
                                </div>
                                <div style="font-size: 15.5px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                                    No FAQs Found
                                </div>
                                <div style="font-size: 12.5px; font-weight: 500; color: #64748b; max-width: 360px; line-height: 1.4;">
                                    No FAQ questions or answers matched your search query or active category filter.
                                </div>
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
                    {{ $faqs->firstItem() ?? 0 }}–{{ $faqs->lastItem() ?? 0 }} of {{ $faqs->total() }}
                </span>
            </div>

            {{-- RIGHT SIDE: PREV, PAGE NUMBERS, NEXT BUTTONS --}}
            @if ($faqs->hasPages())
                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: nowrap;">
                    @if ($faqs->onFirstPage())
                        <button disabled style="padding: 5px 12px; border-radius: 8px; border: 1px solid #f1f5f9; background: #f8fafc; color: #cbd5e1; font-size: 12.5px; font-weight: 600; cursor: not-allowed;">Prev</button>
                    @else
                        <button wire:click="previousPage" style="padding: 5px 12px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all 0.15s ease;" onmouseover="this.style.borderColor='#22c55e'; this.style.color='#22c55e';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#475569';">Prev</button>
                    @endif

                    @php
                        $currentPage = $faqs->currentPage();
                        $lastPage = $faqs->lastPage();

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

                    @foreach ($faqs->getUrlRange($startPage, $endPage) as $page => $url)
                        @if ($page == $currentPage)
                            <button wire:click="gotoPage({{ $page }})" style="width: 32px; height: 32px; border-radius: 8px; background: #22c55e !important; color: #fff !important; font-size: 13px; font-weight: 700; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(34,197,94,0.35);">{{ $page }}</button>
                        @else
                            <button wire:click="gotoPage({{ $page }})" style="width: 32px; height: 32px; border-radius: 8px; background: #fff; border: 1px solid #e2e8f0; color: #334155; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.15s ease;" onmouseover="this.style.borderColor='#22c55e'; this.style.color='#22c55e';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#334155';">{{ $page }}</button>
                        @endif
                    @endforeach

                    @if ($endPage < $lastPage)
                        <button wire:click="gotoPage({{ min($lastPage, $endPage + 1) }})" style="min-width: 24px; height: 32px; background: transparent; border: none; color: #64748b; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 0 4px; transition: color 0.15s ease;" onmouseover="this.style.color='#22c55e';" onmouseout="this.style.color='#64748b';" title="Page {{ min($lastPage, $endPage + 1) }}">...</button>
                    @endif

                    @if ($faqs->hasMorePages())
                        <button wire:click="nextPage" style="padding: 5px 12px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all 0.15s ease;" onmouseover="this.style.borderColor='#22c55e'; this.style.color='#22c55e';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#475569';">Next</button>
                    @else
                        <button disabled style="padding: 5px 12px; border-radius: 8px; border: 1px solid #f1f5f9; background: #f8fafc; color: #cbd5e1; font-size: 12.5px; font-weight: 600; cursor: not-allowed;">Next</button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- MODALS --}}
    <livewire:admin.faq.faq-detail-modal />
    <livewire:admin.faq.faq-create-modal />
    <livewire:admin.faq.faq-edit-modal />
    <livewire:admin.faq.faq-delete-modal />

    <style>
        /* ── RESPONSIVE TABLE STYLING FOR MANAGE FAQ ── */
        .faq-header-bar {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
            width: 100%;
        }
        @media (min-width: 640px) {
            .faq-header-bar {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
            }
        }

        .faq-table-container {
            width: 100% !important;
            overflow-x: auto !important;
            overflow-y: visible !important;
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }
        .faq-table-container::-webkit-scrollbar {
            display: none !important;
        }

        .faq-table {
            width: 100% !important;
            min-width: 0 !important;
            table-layout: auto !important;
            border-collapse: collapse !important;
        }

        .faq-table th,
        .faq-table td {
            box-sizing: border-box !important;
        }

        /* First column left padding: Desktop (≥ 680px) = 20px */
        .faq-table th.col-faq-id,
        .faq-table td.col-faq-id {
            padding-left: 20px !important;
        }

        /* Desktop & Tablet (≥ 680px and < 1024px): When ID is hidden, QA is first visible column -> 20px left padding */
        @media (min-width: 680px) and (max-width: 1023px) {
            .faq-table th.col-faq-qa,
            .faq-table td.col-faq-qa {
                padding-left: 20px !important;
            }
        }

        /* ── STATUS & ACTION COLUMNS: ALWAYS VISIBLE & STICKY ON RIGHT ACROSS SCREENS ── */
        .faq-table th.col-sticky-action,
        .faq-table td.col-sticky-action {
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
        .faq-table td.col-sticky-action .faq-desktop-actions {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 6px !important;
            width: 100% !important;
        }
        .faq-table th.col-sticky-action {
            z-index: 22 !important;
            background-color: #F1FBF1 !important;
            text-align: center !important;
        }

        .faq-table th.col-faq-status,
        .faq-table td.col-faq-status {
            position: sticky !important;
            right: 144px !important;
            width: 115px !important;
            min-width: 115px !important;
            max-width: 115px !important;
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
        .faq-table th.col-faq-status {
            z-index: 21 !important;
            background-color: #F1FBF1 !important;
        }
        .faq-table td.col-faq-status .faq-status-btn {
            padding: 5px 14px !important;
            font-size: 12.5px !important;
            font-weight: 700 !important;
            gap: 6px !important;
        }
        .faq-table td.col-faq-status .faq-status-btn span:first-child {
            width: 7px !important;
            height: 7px !important;
        }

        /* Hover & match row backgrounds for sticky status and action cells */
        .faq-table tbody tr:hover td.col-sticky-action,
        .faq-table tbody tr:hover td.col-faq-status,
        .faq-table tbody tr.hover\:bg-green-50\/40:hover td.col-sticky-action,
        .faq-table tbody tr.hover\:bg-green-50\/40:hover td.col-faq-status {
            background-color: #f7fdf7 !important;
        }
        .dark .faq-table th.col-sticky-action,
        .dark .faq-table th.col-faq-status {
            background-color: #1e293b !important;
        }
        .dark .faq-table td.col-sticky-action,
        .dark .faq-table td.col-faq-status {
            background-color: #0f172a !important;
        }

        /* Action buttons standard styling */
        .faq-table .agrico-view-btn,
        .faq-table .agrico-edit-btn,
        .faq-table .agrico-delete-btn {
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
        .faq-table .agrico-view-btn svg,
        .faq-table .agrico-edit-btn svg,
        .faq-table .agrico-delete-btn svg {
            width: 17px !important;
            height: 17px !important;
        }

        .faq-title-desktop {
            display: inline;
        }
        .faq-title-mobile {
            display: none;
        }
        .faq-desktop-actions {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 6px !important;
            width: 100% !important;
        }
        .faq-mobile-actions {
            display: none !important;
        }
        .faq-action-title-desktop {
            display: inline;
        }
        .faq-action-title-mobile {
            display: none;
        }

        .agrico-more-btn {
            width: 40px !important;
            height: 40px !important;
            min-width: 40px !important;
            min-height: 40px !important;
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
            .faq-title-desktop {
                display: none !important;
            }
            .faq-title-mobile {
                display: inline !important;
            }
            .faq-action-title-desktop {
                display: none !important;
            }
            .faq-action-title-mobile {
                display: inline !important;
            }
            .faq-table td.col-sticky-action .faq-desktop-actions,
            .faq-desktop-actions {
                display: none !important;
            }
            .faq-table td.col-sticky-action .faq-mobile-actions,
            .faq-mobile-actions {
                display: inline-flex !important;
                position: relative !important;
                justify-content: center !important;
                align-items: center !important;
            }
            .faq-table-card {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                box-sizing: border-box !important;
                overflow: visible !important;
            }
            .faq-table-container {
                overflow: visible !important;
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
            .faq-table {
                table-layout: fixed !important;
                width: 100% !important;
                min-width: 0 !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
            .col-faq-id,
            .col-faq-category {
                display: none !important;
            }
            .faq-mobile-category {
                display: block !important;
                margin-top: 4px !important;
            }
            /* GUARANTEED ALWAYS VISIBLE COLUMNS */
            .col-faq-status,
            .col-sticky-action {
                display: table-cell !important;
            }
            .faq-table th,
            .faq-table td {
                box-sizing: border-box !important;
            }
            .faq-table th.col-faq-qa,
            .faq-table td.col-faq-qa {
                width: auto !important;
                overflow: hidden !important;
                padding-left: 12px !important;
                padding-right: 4px !important;
            }
            /* Mobile STATUS Column: 82px width, centered */
            .faq-table th.col-faq-status,
            .faq-table td.col-faq-status {
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
            .faq-table td.col-faq-status .faq-status-btn {
                padding: 3px 8px !important;
                font-size: 11px !important;
                font-weight: 700 !important;
                gap: 4px !important;
                max-width: 100% !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            .faq-table td.col-faq-status .faq-status-btn span:first-child {
                width: 6px !important;
                height: 6px !important;
            }
            /* Mobile ACTION Column: 58px width, single "⋮" button */
            .faq-table th.col-sticky-action,
            .faq-table td.col-sticky-action {
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
            .faq-table th.col-sticky-action {
                text-align: center !important;
            }
        }
    </style>
</div>
