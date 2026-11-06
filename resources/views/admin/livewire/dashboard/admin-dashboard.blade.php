<div wire:poll.visible.60s class="w-full">
    {{-- Header --}}
    <div class="ui-reveal" style="--i:0; margin-bottom: 24px;">
        <h1 class="ui-reveal-text" style="--i:0; font-size: 32px; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2; letter-spacing: -0.02em;">
            Dashboard
        </h1>
        <p class="ui-reveal-text" style="--i:0; font-size: 13.5px; font-weight: 500; color: #94a3b8; margin: 4px 0 0 0; line-height: 1.4;">
            {{ $lastUpdated->format('l, d F Y') }} — Last updated {{ $lastUpdated->format('h:i A') }}
        </p>
    </div>



    {{-- Statistic Cards --}}
    <div style="margin-top: 24px;">
    @include('livewire.dashboard.stat-cards', [
        'totalSoils' => $totalSoils,
        'activeSoils' => $activeSoils,
        'inactiveSoils' => $inactiveSoils,
        'todaySoils' => $todaySoils,
        'weeklySoils' => $weeklySoils,
        'totalScans' => $totalScans,
        'todayScans' => $todayScans,
        'weeklyScans' => $weeklyScans,
        'totalUsers' => $totalUsers,
        'weeklyUsers' => $weeklyUsers,
        'totalAdmins' => $totalAdmins,
        'pendingApplications' => $pendingApplications,
        'weeklyApplications' => $weeklyApplications,
        'pendingRequests' => $pendingRequests,
        'weeklyRequests' => $weeklyRequests,
        'totalMappingReports' => $totalMappingReports,
        'weeklyMappingReports' => $weeklyMappingReports
    ])
    </div>{{-- /stat-cards --}}


    {{-- Hue Monitor --}}
    <div class="ui-reveal mt-6" style="--i:5">
        <x-dashboard.hue-monitor
            :munsellHues="$munsellHues" />
    </div>



    {{-- Soil Ranking --}}
    <div class="mt-6">
        @include('livewire.dashboard.soil-ranking')
    </div>




</div>
