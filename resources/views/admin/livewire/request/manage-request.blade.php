<div class="space-y-6">

    {{-- 1. PAGE HEADER --}}
    <div class="ui-reveal flex flex-col sm:flex-row sm:items-center justify-between gap-3" style="--i:0">
        <div>
            <h1 class="ui-reveal-text" style="--i:0; font-size:24px; font-weight:900; color:#0f172a; margin:0; line-height:1; letter-spacing:-0.5px;">
                User Requests
            </h1>
            <p class="ui-reveal-text" style="--i:0; font-size:14px; color:#64748b; font-weight:500; margin-top:6px; margin-bottom:0;">
                Manage and respond to user requests
            </p>
        </div>

        {{-- TOP RIGHT AMBER BADGE --}}
        <div class="ui-reveal-text self-start sm:self-auto" style="--i:0; background:#fffbeb; color:#b45309; font-weight:700; border-radius:9999px; padding:7px 18px; font-size:13px; border:1px solid #fde68a; display:inline-flex; align-items:center; gap:8px; box-shadow:0 1px 2px rgba(0,0,0,0.03); flex-shrink:0; white-space:nowrap; width:fit-content;">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="flex-shrink:0;">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 6v6l4 2"/>
            </svg>
            <span class="ui-reveal-num" style="--i:0; white-space:nowrap;" x-data x-init="animateCount($el, {{ (int) $pendingCount }}, { suffix: ' Pending', delay: 400 })">{{ $pendingCount }} Pending</span>
        </div>
    </div>

    {{-- 2. STATS CARDS GRID --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        {{-- CARD 1: PENDING --}}
        <div class="ui-reveal" style="--i:1" wire:key="req-stat-pending-wrap">
            <div
                wire:click="$set('statusFilter', '{{ $statusFilter === 'Pending' ? '' : 'Pending' }}')"
                wire:key="req-stat-pending-card"
                title="Filter by Pending"
                style="background:#ffffff; border-radius:20px; padding:18px 20px; border:1.5px solid {{ $statusFilter === 'Pending' ? '#d97706' : 'rgba(241, 245, 249, 0.9)' }}; box-shadow:{{ $statusFilter === 'Pending' ? '0 4px 18px rgba(217, 119, 6, 0.15)' : '0 2px 12px rgba(0,0,0,0.03), 0 1px 3px rgba(0,0,0,0.02)' }}; display:flex; align-items:center; gap:14px; width:100%; height:100%; box-sizing:border-box; transition:transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease; cursor:pointer;"
                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.06)';"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='{{ $statusFilter === 'Pending' ? '0 4px 18px rgba(217, 119, 6, 0.15)' : '0 2px 12px rgba(0,0,0,0.03)' }}';"
            >
                <div class="ui-reveal-text" style="width:46px; height:46px; border-radius:14px; background:#fef3c7; color:#d97706; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                </div>
                <div style="flex:1; min-width:0; display:flex; flex-direction:column; justify-content:center;">
                    <div class="ui-reveal-num" style="font-size:28px; font-weight:800; color:#0f172a; line-height:1.1; letter-spacing:-0.02em; margin-bottom:2px;" x-data x-init="animateCount($el, {{ (int) $pendingCount }}, { delay: 600 })">{{ $pendingCount }}</div>
                    <div class="ui-reveal-text" style="font-size:12px; font-weight:600; color:#94a3b8; line-height:1.2;">Pending</div>
                </div>
            </div>
        </div>

        {{-- CARD 2: APPROVED --}}
        <div class="ui-reveal" style="--i:2" wire:key="req-stat-approved-wrap">
            <div
                wire:click="$set('statusFilter', '{{ $statusFilter === 'Approved' ? '' : 'Approved' }}')"
                wire:key="req-stat-approved-card"
                title="Filter by Approved"
                style="background:#ffffff; border-radius:20px; padding:18px 20px; border:1.5px solid {{ $statusFilter === 'Approved' ? '#22c55e' : 'rgba(241, 245, 249, 0.9)' }}; box-shadow:{{ $statusFilter === 'Approved' ? '0 4px 18px rgba(34, 197, 94, 0.15)' : '0 2px 12px rgba(0,0,0,0.03), 0 1px 3px rgba(0,0,0,0.02)' }}; display:flex; align-items:center; gap:14px; width:100%; height:100%; box-sizing:border-box; transition:transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease; cursor:pointer;"
                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.06)';"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='{{ $statusFilter === 'Approved' ? '0 4px 18px rgba(34, 197, 94, 0.15)' : '0 2px 12px rgba(0,0,0,0.03)' }}';"
            >
                <div class="ui-reveal-text" style="width:46px; height:46px; border-radius:14px; background:#dcfce7; color:#22c55e; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div style="flex:1; min-width:0; display:flex; flex-direction:column; justify-content:center;">
                    <div class="ui-reveal-num" style="font-size:28px; font-weight:800; color:#0f172a; line-height:1.1; letter-spacing:-0.02em; margin-bottom:2px;" x-data x-init="animateCount($el, {{ (int) $approvedCount }}, { delay: 800 })">{{ $approvedCount }}</div>
                    <div class="ui-reveal-text" style="font-size:12px; font-weight:600; color:#94a3b8; line-height:1.2;">Approved</div>
                </div>
            </div>
        </div>

        {{-- CARD 3: DISAPPROVED --}}
        <div class="ui-reveal" style="--i:3" wire:key="req-stat-disapproved-wrap">
            <div
                wire:click="$set('statusFilter', '{{ $statusFilter === 'Disapproved' ? '' : 'Disapproved' }}')"
                wire:key="req-stat-disapproved-card"
                title="Filter by Disapproved"
                style="background:#ffffff; border-radius:20px; padding:18px 20px; border:1.5px solid {{ $statusFilter === 'Disapproved' ? '#dc2626' : 'rgba(241, 245, 249, 0.9)' }}; box-shadow:{{ $statusFilter === 'Disapproved' ? '0 4px 18px rgba(220, 38, 38, 0.15)' : '0 2px 12px rgba(0,0,0,0.03), 0 1px 3px rgba(0,0,0,0.02)' }}; display:flex; align-items:center; gap:14px; width:100%; height:100%; box-sizing:border-box; transition:transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease; cursor:pointer;"
                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.06)';"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='{{ $statusFilter === 'Disapproved' ? '0 4px 18px rgba(220, 38, 38, 0.15)' : '0 2px 12px rgba(0,0,0,0.03)' }}';"
            >
                <div class="ui-reveal-text" style="width:46px; height:46px; border-radius:14px; background:#fee2e2; color:#dc2626; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                </div>
                <div style="flex:1; min-width:0; display:flex; flex-direction:column; justify-content:center;">
                    <div class="ui-reveal-num" style="font-size:28px; font-weight:800; color:#0f172a; line-height:1.1; letter-spacing:-0.02em; margin-bottom:2px;" x-data x-init="animateCount($el, {{ (int) $disapprovedCount }}, { delay: 1000 })">{{ $disapprovedCount }}</div>
                    <div class="ui-reveal-text" style="font-size:12px; font-weight:600; color:#94a3b8; line-height:1.2;">Disapproved</div>
                </div>
            </div>
        </div>
    </div>

    {{-- SEARCH AND FILTER CARD --}}
    <div class="ui-reveal" style="--i:4; background:#ffffff; border-radius:20px; border:1px solid #f1f5f9; box-shadow:0 1px 3px rgba(0,0,0,0.03); padding:12px 16px; display:flex; align-items:center; gap:14px; width:100%; box-sizing:border-box; flex-wrap:wrap;">
        {{-- SEARCH INPUT --}}
        <div style="position:relative; flex:1; min-width:200px;">
            <svg style="position:absolute; left:16px; top:50%; transform:translateY(-50%); width:18px; height:18px; color:#94a3b8;" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                @keydown.escape="$wire.set('search', ''); $el.value = '';"
                placeholder="Search name, email, or request type..."
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
            <option value="">All Status</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Disapproved">Disapproved</option>
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
        <div style="font-size:13px; font-weight:500; color:#94a3b8; flex-shrink:0;">
            {{ $userRequests->total() }} results
        </div>
    </div>

    {{-- FLASH NOTIFICATIONS --}}
    @if (session()->has('message') || session()->has('success'))
        <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; padding:12px 16px; border-radius:14px; font-size:14px; font-weight:600; display:flex; align-items:center; gap:8px;">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <span>{{ session('message') ?? session('success') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:12px 16px; border-radius:14px; font-size:14px; font-weight:600; display:flex; align-items:center; gap:8px;">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- 4. USER REQUESTS TABLE CARD --}}
    <div class="ui-reveal bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden request-table-card w-full max-w-full min-w-0 box-border" style="--i:5">
        {{-- CARD HEADER --}}
        <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between request-card-header">
            <h2 class="ui-reveal-text text-base font-bold text-gray-900 dark:text-slate-100" style="--i:5">
                User Requests (<span>{{ $userRequests->total() }}</span>)
            </h2>
        </div>

        <div class="overflow-x-auto request-table-container no-scrollbar" style="width:100%;">
            <table class="w-full request-table min-w-0 md:min-w-[980px]" style="width:100%; text-align:left; border-collapse:separate; border-spacing:0;">
                <thead>
                    <tr class="bg-[#F1FBF1] dark:bg-slate-800/80 border-b border-gray-100 dark:border-slate-800">
                        <th class="col-request-id" style="padding:16px 20px; font-size:11px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.05em; border-bottom:1px solid #e2e8f0; text-align:center;">Request ID</th>
                        <th class="col-request-user" style="padding:16px 24px; font-size:11px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.05em; border-bottom:1px solid #e2e8f0;">User</th>
                        <th class="col-request-submitted" style="padding:16px 24px; font-size:11px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.05em; border-bottom:1px solid #e2e8f0;">Submitted</th>
                        <th class="col-request-status text-center" style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.05em; border-bottom:1px solid #e2e8f0; text-align:center;">Status</th>
                        <th class="col-request-type text-center" style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.05em; border-bottom:1px solid #e2e8f0; text-align:center;">Type</th>
                        <th class="col-sticky-action text-center" style="font-size:11px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.05em; border-bottom:1px solid #e2e8f0; text-align:center;">
                            <span class="request-action-title-desktop">ACTIONS</span>
                            <span class="request-action-title-mobile">ACTION</span>
                        </th>
                    </tr>
                </thead>
                <tbody style="font-size:14px; font-weight:500;">
                    @forelse ($userRequests as $req)
                        <tr
                            wire:key="req-{{ $req->id }}"
                            x-data="{
                                status: '{{ $req->status }}',
                                csrfToken: '{{ csrf_token() }}',
                                async doApprove() {
                                    if (this.status !== 'Pending') return;
                                    this.status = 'Approved';
                                    try {
                                        const res = await fetch('/user-requests/{{ $req->user_request_id ?? $req->id }}/approve', {
                                            method: 'POST',
                                            headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                                        });
                                        if (!res.ok) { this.status = 'Pending'; return; }
                                        $wire.$refresh();
                                        window.dispatchEvent(new CustomEvent('request-updated'));
                                    } catch(e) { this.status = 'Pending'; }
                                },
                                async doDisapprove() {
                                    if (this.status !== 'Pending') return;
                                    this.status = 'Disapproved';
                                    try {
                                        const res = await fetch('/user-requests/{{ $req->user_request_id ?? $req->id }}/disapprove', {
                                            method: 'POST',
                                            headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                                        });
                                        if (!res.ok) { this.status = 'Pending'; return; }
                                        $wire.$refresh();
                                        window.dispatchEvent(new CustomEvent('request-updated'));
                                    } catch(e) { this.status = 'Pending'; }
                                }
                            }"
                            style="transition:background 0.15s ease;" onmouseover="this.style.background='#f8fafc';" onmouseout="this.style.background='transparent';"
                        >
                            {{-- REQUEST ID --}}
                            <td class="col-request-id" style="padding:18px 20px; font-family:monospace; font-size:14px; font-weight:700; color:#6b7280; border-bottom:1px solid #f1f5f9; white-space:nowrap; text-align:center;">
                                {{ $req->request_code ?: ('REQ-' . str_pad($req->id, 3, '0', STR_PAD_LEFT)) }}
                            </td>

                            {{-- USER DETAILS --}}
                            <td class="col-request-user" style="padding:18px 24px; border-bottom:1px solid #f1f5f9;">
                                <div style="min-width: 0; max-width: 100%; overflow: hidden;">
                                    <div class="text-sm font-bold text-[#6b7280]" style="font-size: 14px; font-weight: 700; color: #6b7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $req->user_name }}">
                                        {{ $req->user_name }}
                                    </div>
                                    <div class="text-xs font-medium text-slate-400" style="font-size: 12px; font-weight: 500; color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $req->user_email }}">
                                        {{ $req->user_email }}
                                    </div>
                                    {{-- MOBILE ONLY: Show Request Type Badge under user details if needed --}}
                                    <div class="request-mobile-type mt-1.5 hidden">
                                        @if(str_contains(strtolower($req->type), 'delet'))
                                            <span style="background:#fee2e2; color:#b91c1c; font-size:10px; font-weight:700; border-radius:9999px; padding:2px 8px; display:inline-block; border:1px solid #fecaca; line-height: 1.3;">
                                                Account Deletion
                                            </span>
                                        @elseif(str_contains(strtolower($req->type), 'reactivat'))
                                            <span style="background:#fef3c7; color:#b45309; font-size:10px; font-weight:700; border-radius:9999px; padding:2px 8px; display:inline-block; border:1px solid #fde68a; line-height: 1.3;">
                                                Reactivation
                                            </span>
                                        @elseif(str_contains(strtolower($req->type), 'account'))
                                            <span style="background:#eefbe7; color:#22c55e; font-size:10px; font-weight:700; border-radius:9999px; padding:2px 8px; display:inline-block; border:1px solid #dcfce7; line-height: 1.3;">
                                                Account
                                            </span>
                                        @elseif(str_contains(strtolower($req->type), 'coordinate') || str_contains(strtolower($req->type), 'location') || str_contains(strtolower($req->type), 'map') || str_contains(strtolower($req->type), 'pin') || str_contains(strtolower($req->type), 'soil'))
                                            <span style="background:#eff6ff; color:#1d4ed8; font-size:10px; font-weight:700; border-radius:9999px; padding:2px 8px; display:inline-block; border:1px solid #dbeafe; line-height: 1.3;">
                                                Soil Coordinates
                                            </span>
                                        @elseif(str_contains(strtolower($req->type), 'munsell'))
                                            <span style="background:#f5f3ff; color:#6d28d9; font-size:10px; font-weight:700; border-radius:9999px; padding:2px 8px; display:inline-block; border:1px solid #ddd6fe; line-height: 1.3;">
                                                Add Munsell
                                            </span>
                                        @else
                                            <span style="background:#f1f5f9; color:#475569; font-size:10px; font-weight:700; border-radius:9999px; padding:2px 8px; display:inline-block; border:1px solid #e2e8f0; line-height: 1.3;">
                                                {{ $req->type }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- SUBMITTED AT --}}
                            <td class="col-request-submitted" style="padding:18px 24px; font-size:14px; font-weight:700; color:#6b7280; border-bottom:1px solid #f1f5f9; white-space:nowrap;">
                                {{ $req->submitted_at ? $req->submitted_at->format('m/d/Y h:i A') : $req->created_at->format('m/d/Y h:i A') }}
                            </td>

                            {{-- STATUS BADGE — driven by Alpine `status` for instant optimistic update --}}
                            <td class="col-request-status text-center" style="padding:18px 20px; border-bottom:1px solid #f1f5f9; white-space:nowrap; text-align:center;">
                                {{-- Pending --}}
                                <template x-if="status === 'Pending'">
                                    <span class="agrico-status-pill" style="background:#fffbeb; color:#b45309; border:1px solid #fde68a;">
                                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
                                        </svg>
                                        Pending
                                    </span>
                                </template>
                                {{-- Approved --}}
                                <template x-if="status === 'Approved'">
                                    <span class="agrico-status-pill" style="background:#dcfce7; color:#22c55e; border:1px solid #bbf7d0;">
                                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                                        </svg>
                                        Approved
                                    </span>
                                </template>
                                {{-- Disapproved --}}
                                <template x-if="status === 'Disapproved'">
                                    <span class="agrico-status-pill" style="background:#fee2e2; color:#b91c1c; border:1px solid #fecaca;">
                                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                                        </svg>
                                        Disapproved
                                    </span>
                                </template>
                            </td>

                            {{-- TYPE BADGE (STICKY WITH ACTION) --}}
                            <td class="col-request-type text-center" style="padding:18px 20px; border-bottom:1px solid #f1f5f9; white-space:nowrap; text-align:center;">
                                @if(str_contains(strtolower($req->type), 'delet'))
                                    <span style="background:#fee2e2; color:#b91c1c; font-size:11px; font-weight:700; border-radius:9999px; padding:4px 12px; display:inline-block; border:1px solid #fecaca;">
                                        Account Deletion
                                    </span>
                                @elseif(str_contains(strtolower($req->type), 'reactivat'))
                                    <span style="background:#fef3c7; color:#b45309; font-size:11px; font-weight:700; border-radius:9999px; padding:4px 12px; display:inline-block; border:1px solid #fde68a;">
                                        Reactivation
                                    </span>
                                @elseif(str_contains(strtolower($req->type), 'account'))
                                    <span style="background:#eefbe7; color:#22c55e; font-size:11px; font-weight:700; border-radius:9999px; padding:4px 12px; display:inline-block; border:1px solid #dcfce7;">
                                        Account
                                    </span>
                                @elseif(str_contains(strtolower($req->type), 'coordinate') || str_contains(strtolower($req->type), 'location') || str_contains(strtolower($req->type), 'map') || str_contains(strtolower($req->type), 'pin') || str_contains(strtolower($req->type), 'soil'))
                                    <span style="background:#eff6ff; color:#1d4ed8; font-size:11px; font-weight:700; border-radius:9999px; padding:4px 12px; display:inline-block; border:1px solid #dbeafe;">
                                        Soil Coordinates
                                    </span>
                                @elseif(str_contains(strtolower($req->type), 'munsell'))
                                    <span style="background:#f5f3ff; color:#6d28d9; font-size:11px; font-weight:700; border-radius:9999px; padding:4px 12px; display:inline-block; border:1px solid #ddd6fe;">
                                        Add Munsell
                                    </span>
                                @else
                                    <span style="background:#f1f5f9; color:#475569; font-size:11px; font-weight:700; border-radius:9999px; padding:4px 12px; display:inline-block; border:1px solid #e2e8f0;">
                                        {{ $req->type }}
                                    </span>
                                @endif
                            </td>

                            {{-- ACTIONS — optimistic: buttons disappear instantly on click --}}
                            <td class="col-sticky-action text-center" style="border-bottom:1px solid #f1f5f9; white-space:nowrap;">
                                {{-- DESKTOP INLINE ACTIONS --}}
                                <div class="request-desktop-actions">
                                    {{-- VIEW / REVIEW ICON BUTTON --}}
                                    <button
                                        wire:click="viewRequest({{ $req->id }})"
                                        type="button"
                                        class="agrico-view-btn"
                                        title="Review Request Details"
                                    >
                                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>

                                    {{-- Show Approve/Disapprove buttons only when status is still Pending (optimistic) --}}
                                    <template x-if="status === 'Pending'">
                                        <span style="display:inline-flex;align-items:center;gap:6px;">
                                            <button
                                                @click="doApprove()"
                                                type="button"
                                                class="agri-btn agri-btn--primary agri-btn--sm"
                                                style="font-size:12px; font-weight:700; border-radius:8px; padding:7px 14px;"
                                            >
                                                Approve
                                            </button>
                                            <button
                                                @click="doDisapprove()"
                                                type="button"
                                                class="agri-btn agri-btn--danger agri-btn--sm"
                                                style="font-size:12px; font-weight:700; border-radius:8px; padding:7px 14px;"
                                            >
                                                Disapprove
                                            </button>
                                        </span>
                                    </template>
                                    <template x-if="status !== 'Pending'">
                                        <span style="color:#94a3b8; font-size:12px; font-weight:500; font-style:italic;">Resolved</span>
                                    </template>
                                </div>

                                {{-- MOBILE MORE ACTIONS "⋮" DROPDOWN --}}
                                <div class="request-mobile-actions" x-data="{ open: false, dropUp: false }" @click.outside="open = false" :style="open ? 'z-index: 60;' : ''">
                                    <button
                                        @click="dropUp = ($el.getBoundingClientRect().bottom + 190 > window.innerHeight) || ($el.closest('.request-table-card')?.getBoundingClientRect().bottom - $el.getBoundingClientRect().bottom < 190); open = !open"
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
                                        {{-- REVIEW DETAILS --}}
                                        <button
                                            @click="open = false"
                                            wire:click="viewRequest({{ $req->id }})"
                                            type="button"
                                            class="agrico-dropdown-item"
                                        >
                                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="color:#16a34a; flex-shrink:0;">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                            <span>Review Details</span>
                                        </button>

                                        <template x-if="status === 'Pending'">
                                            <span>
                                                <div style="height:1px; background:#f1f5f9; margin:4px 0;"></div>

                                                {{-- APPROVE --}}
                                                <button
                                                    @click="open = false; doApprove()"
                                                    type="button"
                                                    class="agrico-dropdown-item"
                                                >
                                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="color:#22c55e; flex-shrink:0;">
                                                        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                                                        <polyline points="22 4 12 14.01 9 11.01"/>
                                                    </svg>
                                                    <span>Approve</span>
                                                </button>

                                                {{-- DISAPPROVE --}}
                                                <button
                                                    @click="open = false; doDisapprove()"
                                                    type="button"
                                                    class="agrico-dropdown-item agrico-dropdown-item-danger"
                                                >
                                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="color:#ef4444; flex-shrink:0;">
                                                        <circle cx="12" cy="12" r="10"/>
                                                        <line x1="15" y1="9" x2="9" y2="15"/>
                                                        <line x1="9" y1="9" x2="15" y2="15"/>
                                                    </svg>
                                                    <span>Disapprove</span>
                                                </button>
                                            </span>
                                        </template>
                                        <template x-if="status !== 'Pending'">
                                            <span>
                                                <div style="height:1px; background:#f1f5f9; margin:4px 0;"></div>
                                                <div style="padding: 6px 12px; font-size: 11.5px; font-weight: 600; color: #94a3b8; font-style: italic; display: flex; align-items: center; gap: 6px;">
                                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #94a3b8; display: inline-block;"></span>
                                                    <span>Resolved</span>
                                                </div>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="6" style="padding: 64px 24px; text-align: center; border-bottom: none;">
                                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px;">
                                    <div style="width: 56px; height: 56px; border-radius: 16px; background: #f0fdf4; border: 1px solid #dcfce7; display: flex; align-items: center; justify-content: center; color: #22c55e; box-shadow: 0 4px 14px rgba(22,163,74,0.12);">
                                        <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                            <polyline points="14 2 14 8 20 8"/>
                                            <line x1="16" y1="13" x2="8" y2="13"/>
                                            <line x1="16" y1="17" x2="8" y2="17"/>
                                        </svg>
                                    </div>
                                    <div style="font-size: 15.5px; font-weight: 800; color: #0f172a; margin-top: 4px;">
                                        No User Requests Found
                                    </div>
                                    <div style="font-size: 12.5px; font-weight: 500; color: #64748b; max-width: 360px; line-height: 1.4;">
                                        New account, map pin, or color requests submitted by users will automatically appear here for your authorization.
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION FOOTER (MATCHING ALL OTHER TABLES) --}}
        <div class="request-pagination-footer" style="padding: 16px 24px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; background: #fff;">
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
                    {{ $userRequests->firstItem() ?? 0 }}–{{ $userRequests->lastItem() ?? 0 }} of {{ $userRequests->total() }}
                </span>
            </div>

            {{-- RIGHT SIDE: PREV, PAGE NUMBERS, NEXT BUTTONS --}}
            @if ($userRequests->hasPages())
                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: nowrap;">
                    @if ($userRequests->onFirstPage())
                        <button disabled style="padding: 5px 12px; border-radius: 8px; border: 1px solid #f1f5f9; background: #f8fafc; color: #cbd5e1; font-size: 12.5px; font-weight: 600; cursor: not-allowed;">Prev</button>
                    @else
                        <button wire:click="previousPage" style="padding: 5px 12px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all 0.15s ease;" onmouseover="this.style.borderColor='#22c55e'; this.style.color='#22c55e';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#475569';">Prev</button>
                    @endif

                    @php
                        $currentPage = $userRequests->currentPage();
                        $lastPage = $userRequests->lastPage();

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

                    @foreach ($userRequests->getUrlRange($startPage, $endPage) as $page => $url)
                        @if ($page == $currentPage)
                            <button wire:click="gotoPage({{ $page }})" style="width: 32px; height: 32px; border-radius: 8px; background: #22c55e !important; color: #fff !important; font-size: 13px; font-weight: 700; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(34,197,94,0.35);">{{ $page }}</button>
                        @else
                            <button wire:click="gotoPage({{ $page }})" style="width: 32px; height: 32px; border-radius: 8px; background: #fff; border: 1px solid #e2e8f0; color: #334155; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.15s ease;" onmouseover="this.style.borderColor='#22c55e'; this.style.color='#22c55e';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#334155';">{{ $page }}</button>
                        @endif
                    @endforeach

                    @if ($endPage < $lastPage)
                        <button wire:click="gotoPage({{ min($lastPage, $endPage + 1) }})" style="min-width: 24px; height: 32px; background: transparent; border: none; color: #64748b; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 0 4px; transition: color 0.15s ease;" onmouseover="this.style.color='#22c55e';" onmouseout="this.style.color='#64748b';" title="Page {{ min($lastPage, $endPage + 1) }}">...</button>
                    @endif

                    @if ($userRequests->hasMorePages())
                        <button wire:click="nextPage" style="padding: 5px 12px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all 0.15s ease;" onmouseover="this.style.borderColor='#22c55e'; this.style.color='#22c55e';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#475569';">Next</button>
                    @else
                        <button disabled style="padding: 5px 12px; border-radius: 8px; border: 1px solid #f1f5f9; background: #f8fafc; color: #cbd5e1; font-size: 12.5px; font-weight: 600; cursor: not-allowed;">Next</button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- REVIEW REQUEST DETAIL MODAL --}}
    @if ($showViewModal && $selectedRequest)

        <div style="position:fixed; inset:0; z-index:100002; display:flex; align-items:center; justify-content:center; background:rgba(15,23,42,0.45); backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px); padding: 12px; box-sizing: border-box; overflow:hidden;" class="animate-in fade-in duration-200">
            <div style="background:#ffffff; border-radius:24px; max-width:680px; width:100%; height:auto; max-height:calc(100dvh - 24px); margin:auto; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.3); border:1px solid #f1f5f9; position:relative; box-sizing: border-box;" class="admin-review-request-modal agrico-standard-popup animate-in zoom-in-95 duration-200">

                {{-- MODAL HEADER --}}
                <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; background: #ffffff; flex-shrink: 0; position: relative;">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div style="width:40px; height:40px; border-radius:12px; background:#dcfce7; color:#22c55e; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </div>
                        <div>
                            <h2 style="font-size:17px; font-weight:800; color:#0f172a; margin:0; line-height:1.2;">
                                Review User Request
                            </h2>
                            <span style="font-family:monospace; font-size:12px; font-weight:700; color:#64748b; margin-top:2px; display:inline-block;">
                                {{ $selectedRequest->request_code ?: ('REQ-' . str_pad($selectedRequest->id, 3, '0', STR_PAD_LEFT)) }}
                            </span>
                        </div>
                    </div>
                    <x-close-button wire:click="closeViewModal" />
                </div>

                {{-- MODAL BODY --}}
                <div class="admin-review-request-body" style="padding: 16px 20px; overflow-y: auto; flex: 1 1 auto; min-height: 0; box-sizing: border-box; scrollbar-width: thin; scrollbar-color: #cbd5e1 #f1f5f9; -webkit-overflow-scrolling: touch; overscroll-behavior: contain;">

                    {{-- REQUEST DETAILS GRID --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" style="background:#f8fafc; border-radius:16px; border:1px solid #f1f5f9; padding:16px; margin-bottom:16px;">
                        <div>
                            <span style="font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em; display:block;">Requested By</span>
                            <span style="font-size:14px; font-weight:800; color:#0f172a; display:block; margin-top:4px;">{{ $selectedRequest->user_name }}</span>
                            <span style="font-size:12px; color:#64748b; font-weight:500; display:block;">{{ $selectedRequest->user_email }}</span>
                        </div>

                        <div>
                            <span style="font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em; display:block;">Request Type</span>
                            <div style="margin-top:4px;">
                                @if(str_contains(strtolower($selectedRequest->type), 'delet'))
                                    <span style="background:#fee2e2; color:#b91c1c; font-size:11px; font-weight:700; border-radius:9999px; padding:4px 12px; display:inline-block; border:1px solid #fecaca;">
                                        Account Deletion Request
                                    </span>
                                @elseif(str_contains(strtolower($selectedRequest->type), 'reactivat'))
                                    <span style="background:#fef3c7; color:#b45309; font-size:11px; font-weight:700; border-radius:9999px; padding:4px 12px; display:inline-block; border:1px solid #fde68a;">
                                        Reactivation Request
                                    </span>
                                @elseif(str_contains(strtolower($selectedRequest->type), 'account'))
                                    <span style="background:#eefbe7; color:#22c55e; font-size:11px; font-weight:700; border-radius:9999px; padding:4px 12px; display:inline-block; border:1px solid #dcfce7;">
                                        Account
                                    </span>
                                @elseif(str_contains(strtolower($selectedRequest->type), 'coordinate') || str_contains(strtolower($selectedRequest->type), 'location') || str_contains(strtolower($selectedRequest->type), 'map') || str_contains(strtolower($selectedRequest->type), 'pin') || str_contains(strtolower($selectedRequest->type), 'soil'))
                                    <span style="background:#eff6ff; color:#1d4ed8; font-size:11px; font-weight:700; border-radius:9999px; padding:4px 12px; display:inline-block; border:1px solid #dbeafe;">
                                        Soil Coordinates
                                    </span>
                                @elseif(str_contains(strtolower($selectedRequest->type), 'munsell'))
                                    <span style="background:#f5f3ff; color:#6d28d9; font-size:11px; font-weight:700; border-radius:9999px; padding:4px 12px; display:inline-block; border:1px solid #ddd6fe;">
                                        Add Munsell
                                    </span>
                                @else
                                    <span style="background:#f1f5f9; color:#475569; font-size:11px; font-weight:700; border-radius:9999px; padding:4px 12px; display:inline-block; border:1px solid #e2e8f0;">
                                        {{ $selectedRequest->type }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <span style="font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em; display:block;">Submitted Date</span>
                            <span style="font-size:13px; font-weight:600; color:#334155; display:block; margin-top:4px;">
                                {{ $selectedRequest->submitted_at ? $selectedRequest->submitted_at->format('M d, Y h:i A') : $selectedRequest->created_at->format('M d, Y h:i A') }}
                            </span>
                        </div>

                        <div>
                            <span style="font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em; display:block;">Current Status</span>
                            <div style="margin-top:4px;">
                                @if($selectedRequest->status === 'Pending')
                                    <span style="background:#fffbeb; color:#b45309; font-size:11px; font-weight:700; border-radius:9999px; padding:4px 12px; display:inline-flex; align-items:center; gap:5px; border:1px solid #fde68a;">
                                        Pending
                                    </span>
                                @elseif($selectedRequest->status === 'Approved')
                                    <span style="background:#dcfce7; color:#22c55e; font-size:11px; font-weight:700; border-radius:9999px; padding:4px 12px; display:inline-flex; align-items:center; gap:5px; border:1px solid #bbf7d0;">
                                        Approved
                                    </span>
                                @else
                                    <span style="background:#fee2e2; color:#b91c1c; font-size:11px; font-weight:700; border-radius:9999px; padding:4px 12px; display:inline-flex; align-items:center; gap:5px; border:1px solid #fecaca;">
                                        Disapproved
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if($selectedRequest->admin_id)
                            <div style="grid-column: span 2; border-top: 1px dashed #e2e8f0; padding-top: 12px; margin-top: 4px;">
                                <span style="font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em; display:block;">Reviewed By Admin</span>
                                <div style="margin-top:4px;">
                                    <span style="background:#eef2ff; color:#4f46e5; font-size:12px; font-weight:700; border-radius:8px; padding:4px 10px; font-family:monospace; display:inline-block; border:1px solid #e0e7ff;">
                                        {{ $selectedRequest->admin_id }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- SOIL SAMPLE LOCATION MAP (IF SOIL COORDINATES REQUEST) --}}
                    @if($selectedRequestLocation)
                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; flex-wrap: wrap; gap: 6px;">
                                <span style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 6px;">
                                    <svg width="14" height="14" fill="none" stroke="#16a34a" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Soil Sample Location Map
                                </span>
                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    <span style="font-family: monospace; font-size: 11px; font-weight: 700; color: #1e293b; background: #e2e8f0; padding: 2px 8px; border-radius: 6px;">
                                        {{ number_format($selectedRequestLocation['lat'], 4) }}, {{ number_format($selectedRequestLocation['lng'], 4) }}
                                    </span>
                                    @if(!empty($selectedRequestLocation['barangay']))
                                        <span style="font-size: 11px; font-weight: 700; color: #1e40af; background: #dbeafe; padding: 2px 8px; border-radius: 6px;">
                                            {{ $selectedRequestLocation['barangay'] }}
                                        </span>
                                    @endif
                                    @if(!empty($selectedRequestLocation['coverage']))
                                        <span style="font-size: 11px; font-weight: 700; color: #065f46; background: #d1fae5; padding: 2px 8px; border-radius: 6px;">
                                            {{ $selectedRequestLocation['coverage'] }}
                                        </span>
                                    @endif
                                    <span id="reqMapGeoStatus-{{ $selectedRequest->user_request_id }}" style="font-size: 11px; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 2px 8px; border-radius: 6px; display: none;"></span>
                                </div>
                            </div>

                            <div
                                x-data
                                x-init="$nextTick(() => { window.renderAgricoRequestMap('review-request-map-{{ $selectedRequest->user_request_id }}', {{ $selectedRequestLocation['lat'] }}, {{ $selectedRequestLocation['lng'] }}, @js($selectedRequestLocation['barangay']), @js($selectedRequestLocation['soil_name']), @js($selectedRequestLocation['munsell_code']), @js($selectedRequestLocation['coverage'] ?? ''), @js($selectedRequest->status)) })"
                                style="position: relative; width: 100%; border-radius: 16px; overflow: hidden; border: 1px solid #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.06);"
                            >
                                <div id="review-request-map-{{ $selectedRequest->user_request_id }}" style="height: 220px; width: 100%; background: #e2e8f0;"></div>
                                <div style="position: absolute; bottom: 8px; right: 8px; z-index: 1000; background: rgba(255,255,255,0.92); backdrop-filter: blur(4px); padding: 3px 8px; border-radius: 6px; font-size: 10.5px; color: #475569; font-weight: 700; border: 1px solid #e2e8f0; pointer-events: none;">
                                    AgriCo Soil GPS Preview
                                </div>
                            </div>
                        </div>

                        {{-- SOIL SPECIMEN & NORMALIZED VIEW (AFTER NORMALIZED ONLY) --}}
                        @php
                            $renderRegions = $selectedRequestLocation['render_regions'] ?? [];
                            $isAllPinned = !empty($selectedRequestLocation['is_all_pinned']);
                            $normImg = $selectedRequestLocation['normalized_image_url'] ?? null;
                            $thumbImg = $selectedRequestLocation['pinned_soil_image'] ?? null;
                            $mCode = $selectedRequestLocation['munsell_code'] ?? 'Soil Specimen';
                            $sName = $selectedRequestLocation['soil_name'] ?? 'Soil Specimen';
                            $hex = $selectedRequestLocation['hex_color'] ?? '#8a7757';
                            $pct = $selectedRequestLocation['pinned_percentage'] ?? '100%';
                            $conf = $selectedRequestLocation['pinned_confidence'] ?? '95%';
                            $ordinalNames = ['1ST', '2ND', '3RD', '4TH', '5TH', '6TH', '7TH', '8TH', '9TH', '10TH'];

                            $normalizeBox = function($r) {
                                if (!$r) {
                                    return ['left' => 25, 'top' => 25, 'width' => 50, 'height' => 50];
                                }
                                $box = $r['box'] ?? ($r['bounding_box'] ?? null);
                                if (!$box && isset($r['x'], $r['y'], $r['w'], $r['h'])) {
                                    $box = ['x' => $r['x'], 'y' => $r['y'], 'w' => $r['w'], 'h' => $r['h']];
                                }
                                if (!$box && isset($r['norm_x'], $r['norm_y'], $r['norm_w'], $r['norm_h'])) {
                                    $box = ['x' => $r['norm_x'], 'y' => $r['norm_y'], 'w' => $r['norm_w'], 'h' => $r['norm_h']];
                                }
                                if (is_string($box)) {
                                    $box = json_decode($box, true) ?: null;
                                }
                                if (!$box) {
                                    return ['left' => 25, 'top' => 25, 'width' => 50, 'height' => 50];
                                }
                                $bx = floatval($box['norm_x'] ?? ($box['x'] ?? 0.25));
                                $by = floatval($box['norm_y'] ?? ($box['y'] ?? 0.25));
                                $bw = floatval($box['norm_w'] ?? ($box['w'] ?? 0.5));
                                $bh = floatval($box['norm_h'] ?? ($box['h'] ?? 0.5));

                                if ($bx > 1.0 || $by > 1.0 || $bw > 1.0 || $bh > 1.0) {
                                    $bx = $bx / 400; $by = $by / 400; $bw = $bw / 400; $bh = $bh / 400;
                                }
                                $bx = max(0.01, min(0.92, $bx));
                                $by = max(0.01, min(0.92, $by));
                                $bw = max(0.05, min(0.98 - $bx, $bw));
                                $bh = max(0.05, min(0.98 - $by, $bh));

                                return [
                                    'left'   => round($bx * 100, 2),
                                    'top'    => round($by * 100, 2),
                                    'width'  => round($bw * 100, 2),
                                    'height' => round($bh * 100, 2),
                                ];
                            };
                        @endphp

                        <div style="margin-bottom: 20px;">
                            {{-- Header Title Row --}}
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; flex-wrap: wrap; gap: 6px;">
                                <span style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 6px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm-1 15-4-4 1.4-1.4L11 14.2l6.6-6.6L19 9z"/></svg>
                                    Soil Specimen (Normalized)
                                </span>
                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    @if($isAllPinned)
                                        <span style="font-size: 10.5px; font-weight: 700; color: #065f46; background: #d1fae5; padding: 2px 9px; border-radius: 6px; border: 1px solid #a7f3d0;">
                                            ✨ All {{ count($renderRegions) }} Soils Pinned (Batch Pin)
                                        </span>
                                    @else
                                        <span style="font-size: 10.5px; font-weight: 700; color: #0f766e; background: #ccfbf1; padding: 2px 9px; border-radius: 6px; border: 1px solid #99f6e4;">
                                            📌 Pinned Soil Layer Only
                                        </span>
                                    @endif
                                    <span style="font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 6px; background: rgba(16, 185, 129, 0.15); color: #10b981; text-transform: uppercase; letter-spacing: 0.04em; border: 1px solid rgba(16, 185, 129, 0.3);">
                                        AFTER (NORMALIZED)
                                    </span>
                                </div>
                            </div>

                            {{-- Main Card Container --}}
                            <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.06); padding: 14px;">

                                {{-- Hero Swatch Banner --}}
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 12px; flex-wrap: wrap;">
                                    <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                                        {{-- Thumbnail or Fallback --}}
                                        <div style="width: 48px; height: 48px; border-radius: 10px; overflow: hidden; flex-shrink: 0; background: {{ $hex }}; border: 2px solid #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.15); position: relative;">
                                            @if($thumbImg)
                                                <img src="{{ $thumbImg }}" alt="{{ $mCode }}" style="width: 100%; height: 100%; object-fit: cover; display: block;" onerror="this.style.display='none';" />
                                            @else
                                                <div style="width: 100%; height: 100%; background: {{ $hex }};"></div>
                                            @endif
                                        </div>

                                        {{-- Soil Info --}}
                                        <div style="min-width: 0;">
                                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                                <span style="font-size: 15px; font-weight: 800; color: #0f172a; letter-spacing: -0.01em;">
                                                    {{ $mCode }}
                                                </span>
                                                <span style="font-size: 13px; font-weight: 600; color: #475569;">
                                                    {{ $sName }}
                                                </span>
                                            </div>
                                            <div style="font-size: 11.5px; font-weight: 600; color: #64748b; margin-top: 2px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                                <span>Soil Coverage: <strong style="color: #0f172a;">{{ $pct }}</strong></span>
                                                <span>•</span>
                                                <span>Confidence: <strong style="color: #10b981;">{{ $conf }}</strong></span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Right HEX Pill --}}
                                    <span style="font-size: 12px; font-weight: 800; color: #ffffff; background: {{ $hex }}; padding: 5px 12px; border-radius: 9999px; font-family: monospace; letter-spacing: 0.05em; text-shadow: 0 1px 2px rgba(0,0,0,0.35); box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        {{ strtoupper($hex) }}
                                    </span>
                                </div>

                                {{-- Normalized Photo Frame with Overlaid ROI Bounding Boxes (AFTER NORMALIZED ONLY) --}}
                                <div style="position: relative; width: 100%; border-radius: 12px; overflow: hidden; background: #0c1a17; border: 1px solid rgba(142, 182, 155, 0.25); text-align: center;">
                                    @if($normImg)
                                        <div style="position: relative; width: 100%; display: inline-block;">
                                            <img
                                                src="{{ $normImg }}"
                                                alt="Soil Specimen - After (Normalized)"
                                                loading="lazy"
                                                style="width: 100%; height: auto; max-height: 320px; object-fit: contain; display: block; margin: 0 auto;"
                                                onload="var p = this.parentElement; if(p) { var o = p.querySelector('.soil-norm-overlays'); if(o) o.style.display='block'; }"
                                                onerror="this.style.display='none'; var fb = this.parentElement.querySelector('.soil-photo-missing-fb'); if(fb) fb.style.display='flex';"
                                            />

                                            {{-- Bounding Box Overlays (Only Pinned Soil if Single Pin; All Soils if Pin All) --}}
                                            <div class="soil-norm-overlays" style="display: none; position: absolute; inset: 0; pointer-events: none; z-index: 10;">
                                                @foreach($renderRegions as $idx => $r)
                                                    @php
                                                        $bData = $normalizeBox($r);
                                                        $rHex = $r['hex'] ?? $hex;
                                                        $rCode = $r['munsell'] ?? ($r['munsell_code'] ?? ($r['code'] ?? $mCode));
                                                        $rPct = isset($r['percent']) ? $r['percent'] : (isset($r['proportion']) ? round($r['proportion'] * 100, 1) : null);
                                                        if ($isAllPinned) {
                                                            $roleLabel = ($ordinalNames[$idx] ?? ($idx + 1) . 'TH') . ' COLOR · ' . $rCode . ($rPct !== null ? ' (' . $rPct . '%)' : '');
                                                        } else {
                                                            $roleLabel = 'PINNED SOIL · ' . $rCode . ($rPct !== null ? ' (' . $rPct . '%)' : '');
                                                        }
                                                    @endphp
                                                    <div style="position: absolute; left: {{ $bData['left'] }}%; top: {{ $bData['top'] }}%; width: {{ $bData['width'] }}%; height: {{ $bData['height'] }}%; border: 2.5px solid #4ade80; pointer-events: none; border-radius: 4px; box-sizing: border-box; box-shadow: 0 0 8px rgba(74, 222, 128, 0.45);">
                                                        <span style="position: absolute; top: -14px; left: 50%; transform: translateX(-50%); font-size: 8.5px; font-weight: 800; background: #4ade80; color: #000000; padding: 1.5px 7px; border-radius: 4px; white-space: nowrap; line-height: 1.2; letter-spacing: 0.03em; box-shadow: 0 2px 4px rgba(0,0,0,0.35);">
                                                            {{ $roleLabel }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <div class="soil-photo-missing-fb" style="display: none; flex-direction: column; align-items: center; justify-content: center; padding: 32px 16px; background: rgba(12, 26, 23, 0.95); text-align: center;">
                                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 8px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                                <span style="font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.85);">Image preview unavailable</span>
                                            </div>
                                        </div>
                                    @else
                                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 36px 16px; background: rgba(12, 26, 23, 0.95); text-align: center;">
                                            <div style="width: 56px; height: 56px; border-radius: 12px; background: {{ $hex }}; border: 2px solid rgba(255,255,255,0.3); margin-bottom: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.25);"></div>
                                            <span style="font-size: 13px; font-weight: 800; color: #ffffff;">{{ $mCode }} — {{ $sName }}</span>
                                            <span style="font-size: 11px; color: rgba(255,255,255,0.65); margin-top: 4px;">Normalized photo was not attached to this legacy coordinate pin</span>
                                        </div>
                                    @endif

                                    {{-- Watermark Tag --}}
                                    <div style="position: absolute; bottom: 8px; right: 8px; z-index: 15; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); padding: 2px 7px; border-radius: 5px; font-size: 9.5px; color: rgba(255,255,255,0.8); font-weight: 700; border: 1px solid rgba(255,255,255,0.15); pointer-events: none;">
                                        AgriCo Normalized View
                                    </div>
                                </div>

                                {{-- Detected Soil Swatch Pills Below Photo (Only Pinned Soil if Single Pin; All if Pin All) --}}
                                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px;">
                                    @foreach($renderRegions as $idx => $r)
                                        @php
                                            $rHex = $r['hex'] ?? $hex;
                                            $rCode = $r['munsell'] ?? ($r['munsell_code'] ?? ($r['code'] ?? $mCode));
                                            $rLabel = $r['label'] ?? ($r['color_name'] ?? ($r['name'] ?? $sName));
                                            $rawPct = isset($r['percent']) ? $r['percent'] : (isset($r['proportion']) ? round($r['proportion'] * 100, 1) : null);
                                            $rConf = $r['confidence'] ?? ($r['near_match'] ?? null);
                                            if (is_numeric($rConf)) $rConf = round((float)$rConf, 1) . '%';
                                        @endphp
                                        <div style="display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 9999px; padding: 4px 12px 4px 6px;">
                                            <span style="width: 14px; height: 14px; border-radius: 9999px; background: {{ $rHex }}; border: 1px solid rgba(0,0,0,0.15); flex-shrink: 0;"></span>
                                            <span style="font-size: 11.5px; font-weight: 800; color: #0f172a; font-family: monospace;">{{ $rCode }}</span>
                                            <span style="font-size: 11.5px; font-weight: 600; color: #475569;">{{ $rLabel }}</span>
                                            @if($rawPct !== null)
                                                <span style="font-size: 10.5px; font-weight: 700; color: #0369a1; background: #e0f2fe; padding: 1px 6px; border-radius: 6px;">{{ $rawPct }}%</span>
                                            @endif
                                            @if($rConf)
                                                <span style="font-size: 10.5px; font-weight: 700; color: #15803d; background: #dcfce7; padding: 1px 6px; border-radius: 6px;">{{ $rConf }} Match</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                            </div>
                        </div>
                    @endif

                    {{-- DETAILED DESCRIPTION BOX --}}
                    <div style="margin-bottom:12px;">
                        <span style="font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em; display:block; margin-bottom:6px;">Full Request Description</span>
                        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:16px; font-size:14px; color:#334155; line-height:1.5; font-weight:500;">
                            {{ $selectedRequest->description }}
                        </div>
                    </div>

                </div>

                {{-- MODAL FOOTER ACTION BUTTONS --}}
                <div style="padding: 14px 20px 16px; background: #ffffff; border-top: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px; flex-shrink: 0; width: 100%; box-sizing: border-box; flex-wrap: wrap;">
                    <button
                        type="button"
                        wire:click="closeViewModal"
                        class="flex-1 min-w-[90px] py-2.5 sm:py-3 rounded-full border border-gray-200 text-gray-600 font-bold text-sm hover:bg-gray-50 transition cursor-pointer text-center"
                        title="Cancel"
                    >
                        Cancel
                    </button>
                    @if($selectedRequest->status === 'Pending')
                        <button
                            type="button"
                            wire:click="disapprove({{ $selectedRequest->user_request_id ?? $selectedRequest->id }})"
                            wire:loading.attr="disabled"
                            class="flex-1 min-w-[110px] py-2.5 sm:py-3 rounded-full bg-red-500 hover:bg-red-600 text-white font-bold text-sm shadow-md shadow-red-500/35 transition cursor-pointer text-center disabled:opacity-50"
                            style="box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35);"
                            title="Disapprove"
                        >
                            <span wire:loading.remove wire:target="disapprove">Disapprove</span>
                            <span wire:loading wire:target="disapprove">Processing...</span>
                        </button>
                        <button
                            type="button"
                            wire:click="approve({{ $selectedRequest->user_request_id ?? $selectedRequest->id }})"
                            wire:loading.attr="disabled"
                            class="flex-1 min-w-[140px] py-2.5 sm:py-3 rounded-full bg-green-500 hover:bg-green-600 text-white font-bold text-sm shadow-md shadow-green-500/35 transition cursor-pointer text-center flex items-center justify-center gap-2 disabled:opacity-50"
                            style="box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35);"
                            title="Approve Request"
                        >
                            <span wire:loading.remove wire:target="approve">Approve Request</span>
                            <span wire:loading wire:target="approve">Processing...</span>
                        </button>
                    @endif
                </div>

            </div>
        </div>
    @endif

    <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
        .request-table-container {
            width: 100% !important;
            overflow-x: auto !important;
            scrollbar-width: none !important;
        }
        .request-table-container::-webkit-scrollbar {
            display: none !important;
        }
        /* First column left padding: Desktop (≥ 680px) = 20px */
        .request-table th.col-request-id,
        .request-table td.col-request-id {
            padding-left: 20px !important;
        }
        .request-table {
            width: 100% !important;
            min-width: 0 !important;
            border-collapse: collapse !important;
        }
        .request-table th,
        .request-table td {
            box-sizing: border-box !important;
        }

        /* ── STATUS PILL BASE STYLING ── */
        .agrico-status-pill {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 104px !important;
            height: 25px !important;
            border-radius: 9999px !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            gap: 5px !important;
            white-space: nowrap !important;
            box-sizing: border-box !important;
        }

        /* ── CLEAN STICKY ACTION COLUMN GROUP (DESKTOP: TYPE & ACTION) ── */
        .request-table th.col-sticky-action,
        .request-table td.col-sticky-action {
            position: sticky !important;
            right: 0 !important;
            width: 220px !important;
            min-width: 220px !important;
            max-width: 220px !important;
            white-space: nowrap !important;
            box-shadow: none !important;
            border-left: none !important;
            border-right: none !important;
            z-index: 10 !important;
            background-color: #ffffff !important;
            text-align: center !important;
            padding-left: 8px !important;
            padding-right: 14px !important;
        }
        .request-table th.col-sticky-action {
            z-index: 20 !important;
            background-color: #F1FBF1 !important;
            text-align: center !important;
        }

        .request-table th.col-request-type,
        .request-table td.col-request-type {
            position: sticky !important;
            right: 220px !important;
            width: 140px !important;
            min-width: 140px !important;
            max-width: 140px !important;
            white-space: nowrap !important;
            box-shadow: none !important;
            border-left: none !important;
            border-right: none !important;
            z-index: 10 !important;
            background-color: #ffffff !important;
            text-align: center !important;
            padding: 14px 6px !important;
        }
        .request-table th.col-request-type {
            z-index: 20 !important;
            background-color: #F1FBF1 !important;
            text-align: center !important;
        }

        /* Status is a regular scrolling column (not sticky) */
        .request-table th.col-request-status,
        .request-table td.col-request-status {
            position: static !important;
            width: 130px !important;
            min-width: 130px !important;
            white-space: nowrap !important;
            text-align: center !important;
            padding: 14px 12px !important;
        }

        .request-table tbody tr:hover td.col-sticky-action,
        .request-table tbody tr:hover td.col-request-type {
            background-color: #f8fafc !important;
        }

        .dark .request-table th.col-sticky-action,
        .dark .request-table th.col-request-type {
            background-color: #1e293b !important;
        }
        .dark .request-table td.col-sticky-action,
        .dark .request-table td.col-request-type {
            background-color: #0f172a !important;
        }
        .dark .request-table tbody tr:hover td.col-sticky-action,
        .dark .request-table tbody tr:hover td.col-request-type {
            background-color: #1e293b !important;
        }
        .request-action-title-desktop {
            display: inline;
        }
        .request-action-title-mobile {
            display: none;
        }
        .request-table td.col-sticky-action .request-desktop-actions {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 8px !important;
            width: 100% !important;
        }
        .request-mobile-actions {
            display: none !important;
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

        /* Mobile (<= 768px): User, Type, Status and Action fit seamlessly with no lines between them */
        @media (max-width: 768px) {
            .request-action-title-desktop {
                display: none !important;
            }
            .request-action-title-mobile {
                display: inline !important;
            }
            .request-table td.col-sticky-action .request-desktop-actions,
            .request-desktop-actions {
                display: none !important;
            }
            .request-table td.col-sticky-action .request-mobile-actions,
            .request-mobile-actions {
                display: inline-flex !important;
                position: relative !important;
                justify-content: center !important;
                align-items: center !important;
            }
            .request-table-card {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                box-sizing: border-box !important;
                overflow: visible !important;
            }
            .request-card-header {
                padding: 14px 14px !important;
            }
            .request-table-container {
                overflow: visible !important;
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
            .request-table {
                table-layout: fixed !important;
                width: 100% !important;
                min-width: 0 !important;
                max-width: 100% !important;
                border-collapse: collapse !important;
                box-sizing: border-box !important;
            }
            .col-request-id,
            .col-request-submitted,
            .col-request-status {
                display: none !important;
            }
            .request-mobile-type {
                display: none !important;
            }
            .request-table th.col-request-user,
            .request-table td.col-request-user {
                width: auto !important;
                overflow: hidden !important;
                padding-left: 12px !important;
                padding-right: 4px !important;
            }
            /* Mobile TYPE Column: 96px width, centered */
            .request-table th.col-request-type,
            .request-table td.col-request-type {
                display: table-cell !important;
                position: static !important;
                left: auto !important;
                right: auto !important;
                width: 96px !important;
                min-width: 96px !important;
                max-width: 96px !important;
                padding: 10px 4px !important;
                text-align: center !important;
                border-left: none !important;
                border-right: none !important;
                box-shadow: none !important;
            }
            .request-table td.col-request-type span {
                font-size: 10px !important;
                padding: 3px 8px !important;
                max-width: 92px !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                white-space: nowrap !important;
                display: inline-block !important;
                box-sizing: border-box !important;
            }
            /* Mobile ACTION Column: 58px width, single "⋮" button, perfectly centered */
            .request-table th.col-sticky-action,
            .request-table td.col-sticky-action {
                display: table-cell !important;
                position: static !important;
                left: auto !important;
                right: auto !important;
                width: 58px !important;
                min-width: 58px !important;
                max-width: 58px !important;
                padding: 10px 4px !important;
                text-align: center !important;
                border-left: none !important;
                border-right: none !important;
                box-shadow: none !important;
                overflow: visible !important;
            }
            .request-table th.col-sticky-action {
                text-align: center !important;
                font-size: 10px !important;
                letter-spacing: 0.04em !important;
                padding: 12px 4px !important;
            }
            .request-action-title-mobile {
                display: inline-block !important;
                text-align: center !important;
                width: 100% !important;
            }
            .request-table td.col-sticky-action .request-mobile-actions {
                display: inline-flex !important;
                justify-content: center !important;
                align-items: center !important;
                margin: 0 auto !important;
            }
            .request-pagination-footer {
                padding: 12px 14px !important;
                justify-content: center !important;
                gap: 10px !important;
            }
        }
    </style>

    <script>
        if (typeof window.renderAgricoRequestMap === 'undefined') {
            window.renderAgricoRequestMap = function(containerId, lat, lng, barangay, soilName, munsellCode, coverage, status) {
                let attempts = 0;
                const maxAttempts = 30;
                const interval = setInterval(function() {
                    attempts++;
                    const mapEl = document.getElementById(containerId);
                    if (!mapEl || typeof L === 'undefined') {
                        if (attempts >= maxAttempts) clearInterval(interval);
                        return;
                    }
                    clearInterval(interval);

                    if (mapEl._leaflet_id) {
                        try { mapEl._leaflet_id = null; } catch(e) {}
                    }

                    const pinMeta = typeof window.getAgricoPinStatusMeta === 'function'
                        ? window.getAgricoPinStatusMeta(status)
                        : { isApproved: String(status || '').toLowerCase() === 'approved' || String(status || '').toLowerCase() === 'active' };
                    const isApproved = pinMeta.isApproved;

                    const reqMap = L.map(mapEl, {
                        zoomControl: true,
                        attributionControl: false
                    }).setView([lat, lng], 15);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19
                    }).addTo(reqMap);

                    const pinIcon = typeof window.createSoilPinIcon === 'function'
                        ? window.createSoilPinIcon(status)
                        : L.icon({
                            iconUrl: isApproved ? '{{ asset('images/agrico-map-pin.png') }}' : '{{ asset('images/agrico-map-pin-gray.png') }}',
                            iconSize: [38, 56],
                            iconAnchor: [19, 56],
                            popupAnchor: [0, -50]
                        });

                    const marker = L.marker([lat, lng], { icon: pinIcon }).addTo(reqMap);

                    const popupHtml = `
                        <div style='font-family:inherit; min-width:170px; padding:3px;'>
                            <div style='font-size:11px; font-weight:800; color:${isApproved ? '#16a34a' : '#64748b'}; text-transform:uppercase;'>${barangay || 'Lal-lo Overall'}</div>
                            <strong style='font-size:13.5px; color:#0f172a; display:block; margin:2px 0;'>${soilName || 'Soil Specimen'}</strong>
                            ${munsellCode ? `<div style='font-size:11px; color:#64748b; font-family:monospace; margin-bottom:2px;'>Munsell: <strong>${munsellCode}</strong></div>` : ''}
                            <div style='font-size:10.5px; color:#94a3b8;'>${parseFloat(lat).toFixed(5)}, ${parseFloat(lng).toFixed(5)}</div>
                        </div>
                    `;
                    marker.bindPopup(popupHtml, { closeButton: false }).openPopup();

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

                    const covStr = coverage || '500 m²';
                    let boxW = 85, boxH = 85, cornerR = 20;
                    if (covStr.includes('50 m')) { boxW = 35; boxH = 35; cornerR = 8; }
                    else if (covStr.includes('250 m')) { boxW = 55; boxH = 55; cornerR = 13; }
                    else if (covStr.includes('500 m')) { boxW = 75; boxH = 75; cornerR = 18; }
                    else if (covStr.includes('1,000') || covStr.includes('1000')) { boxW = 100; boxH = 100; cornerR = 24; }
                    else if (covStr.includes('2,500') || covStr.includes('2500')) { boxW = 130; boxH = 130; cornerR = 30; }
                    else if (covStr.includes('5,000') || covStr.includes('5000')) { boxW = 160; boxH = 160; cornerR = 36; }
                    else if (covStr.toLowerCase().includes('hectare')) { boxW = 190; boxH = 190; cornerR = 42; }

                    const roundedBoxCoords = getRoundedBoxCoords(lat, lng, boxW, boxH, cornerR);
                    const poly = L.polygon(roundedBoxCoords, {
                        color: pinMeta.polyBorderColor || (isApproved ? '#16a34a' : '#6b7280'),
                        fillColor: pinMeta.polyFillColor || (isApproved ? '#22c55e' : '#9ca3af'),
                        fillOpacity: 0.22,
                        weight: 2.5
                    }).addTo(reqMap);

                    if (covStr) {
                        const areaPrefix = pinMeta.isApproved ? 'Covered Area' : (pinMeta.isRejected ? 'Unapproved Covered Area' : 'Pending Covered Area');
                        poly.bindTooltip(`${areaPrefix} (Sakop): ${covStr}`, { sticky: true });
                    }

                    // Render official Lal-lo municipal & barangay boundary overlay
                    if (window.LalloBoundary) {
                        window.LalloBoundary.render(reqMap, {
                            showTooltips: true,
                            bringMarkersToFront: [marker, poly]
                        }).catch(function(err) { console.log('Lal-lo boundary note:', err); });

                        window.LalloBoundary.findBarangayAt(lat, lng).then(function(res) {
                            const badge = document.getElementById('reqMapGeoStatus-' + containerId.replace('review-request-map-', ''));
                            if (badge) {
                                if (res.inside) {
                                    badge.style.display = 'inline-block';
                                    badge.style.color = '#065f46';
                                    badge.style.background = '#d1fae5';
                                    badge.textContent = '✓ Inside Lal-lo' + (res.barangay ? ' (' + res.barangay + ')' : '');
                                } else {
                                    badge.style.display = 'inline-block';
                                    badge.style.color = '#b91c1c';
                                    badge.style.background = '#fee2e2';
                                    badge.textContent = '⚠️ Outside Lal-lo Coverage';
                                }
                            }
                        }).catch(function() {});
                    }

                    setTimeout(() => { reqMap.invalidateSize(); }, 150);
                    setTimeout(() => { reqMap.invalidateSize(); }, 350);
                    setTimeout(() => { reqMap.invalidateSize(); }, 600);
                    setTimeout(() => { reqMap.invalidateSize(); }, 1000);
                }, 50);
            };
        }
    </script>
</div>
