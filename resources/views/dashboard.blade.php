<x-app-layout>
    <x-slot name="header">
        <h3 class="px-3 pt-2 sm:px-4 lg:px-5 text-xl font-black text-gray-900 tracking-tight uppercase">{{ __('Welcome back,') }} {{ Auth::user()->display_name }}!</h3>
    </x-slot>


    <div class="py-2">
        <div class="mx-auto w-full px-3 sm:px-4 lg:px-5">
            <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-[minmax(0,1fr)_280px]">
                <!-- Leave Calendar and Recent Requests -->
                <div class="min-w-0 space-y-3">
                    @if($employee)
                        @if(!$isHrAdmin)
                            @php
                                $formatRange = function ($start, $end = null) {
                                    $startDate = $start ? \Carbon\Carbon::parse($start) : null;
                                    $endDate = $end ? \Carbon\Carbon::parse($end) : $startDate;

                                    if (! $startDate) {
                                        return 'N/A';
                                    }

                                    if ($endDate && $startDate->isSameDay($endDate)) {
                                        return $startDate->format('M d, Y');
                                    }

                                    return $startDate->format('M d').' - '.$endDate->format('M d, Y');
                                };

                                $statusClass = function ($status) {
                                    return match (strtolower((string) $status)) {
                                        'approved', 'submitted' => 'border-green-100 bg-green-50 text-green-700',
                                        'rejected' => 'border-red-100 bg-red-50 text-red-700',
                                        'cancelled' => 'border-gray-100 bg-gray-50 text-gray-600',
                                        default => 'border-amber-100 bg-amber-50 text-amber-700',
                                    };
                                };

                                $recentItemGroups = [
                                    'left' => collect([
                                        $leaveRecentRequest ? [
                                            'label' => 'Leave',
                                            'title' => $leaveRecentRequest->leaveType?->name ?? 'Leave',
                                            'date' => $formatRange($leaveRecentRequest->start_date, $leaveRecentRequest->end_date),
                                            'status' => strtolower((string) $leaveRecentRequest->status) ?: 'pending',
                                            'url' => route('leaves.index'),
                                        ] : null,
                                        $locatorRecentSlip ? [
                                            'label' => 'Locator Slip',
                                            'title' => $locatorRecentSlip->destination ?: ($locatorRecentSlip->type ?: 'Locator Slip'),
                                            'date' => $formatRange($locatorRecentSlip->date_covered),
                                            'status' => strtolower((string) $locatorRecentSlip->status) ?: 'pending',
                                            'url' => route('locator-slips.show', $locatorRecentSlip),
                                        ] : null,
                                    ])->filter(),
                                    'right' => collect([
                                        $travelRecentOrder ? [
                                            'label' => 'Travel Authority',
                                            'title' => $travelRecentOrder->places_of_travel ?: ($travelRecentOrder->ta_number ?: 'Travel Authority'),
                                            'date' => $formatRange($travelRecentOrder->travel_date_start, $travelRecentOrder->travel_date_end),
                                            'status' => strtolower((string) $travelRecentOrder->status) ?: 'pending',
                                            'url' => route('travel-orders.show', $travelRecentOrder),
                                        ] : null,
                                        $ctoRecentRequest ? [
                                            'label' => 'CTO',
                                            'title' => $ctoRecentRequest->type_label,
                                            'date' => $formatRange($ctoRecentRequest->date_start, $ctoRecentRequest->date_end),
                                            'status' => strtolower((string) $ctoRecentRequest->status) ?: 'pending',
                                            'url' => route('my-cto.show', $ctoRecentRequest),
                                        ] : null,
                                    ])->filter(),
                                ];

                                $hasRecentItems = $recentItemGroups['left']->isNotEmpty() || $recentItemGroups['right']->isNotEmpty();
                            @endphp

                            <div class="space-y-3">
                                @if($hasRecentItems)
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        @foreach($recentItemGroups as $group)
                                            <div class="space-y-2">
                                                @foreach($group as $item)
                                                    <div class="min-h-[88px] rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm">
                                                        <div class="flex h-full items-start justify-between gap-3">
                                                            <div class="min-w-0">
                                                                <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">{{ $item['label'] }}</p>
                                                                <p class="mt-1.5 truncate text-base font-black text-gray-900">{{ $item['title'] }}</p>
                                                                <p class="mt-1 text-xs font-semibold text-gray-400">{{ $item['date'] }}</p>
                                                            </div>
                                                            <div class="flex shrink-0 items-center gap-2">
                                                                <span class="rounded-full border px-2.5 py-1 text-[9px] font-black uppercase tracking-widest {{ $statusClass($item['status']) }}">
                                                                    {{ $item['status'] }}
                                                                </span>
                                                                <a href="{{ $item['url'] }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-blue-900 transition hover:bg-blue-50 hover:text-blue-700" aria-label="{{ __('View') }}">
                                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M5 12h14m-6-6 6 6-6 6" />
                                                                    </svg>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div id="dashboardLeaveCalendar" class="w-full" style="width: 470px; max-width: 100%;" data-calendar-container>
                                    @include('dashboard._leave-calendar')
                                </div>
                            </div>
                        @else
                            <div class="space-y-3">
                                @if($latestApprovals->isNotEmpty())
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        @foreach($latestApprovals->chunk(2) as $group)
                                            <div class="space-y-2">
                                                @foreach($group as $approval)
                                                    <div class="min-h-[88px] rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm">
                                                        <div class="flex h-full items-start justify-between gap-3">
                                                            <div class="min-w-0">
                                                                <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">{{ $approval['label'] }}</p>
                                                                <p class="mt-1.5 truncate text-base font-black text-gray-900">{{ $approval['title'] }}</p>
                                                                <p class="mt-1 truncate text-xs font-semibold text-gray-400">{{ $approval['employee'] }} · {{ $approval['date'] }}</p>
                                                            </div>
                                                            <div class="flex shrink-0 items-center gap-2">
                                                                <span class="rounded-full border border-amber-100 bg-amber-50 px-2.5 py-1 text-[9px] font-black uppercase tracking-widest text-amber-700">
                                                                    {{ __('Pending') }}
                                                                </span>
                                                                <a href="{{ $approval['url'] }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-blue-900 transition hover:bg-blue-50 hover:text-blue-700" aria-label="{{ __('View') }}">
                                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M5 12h14m-6-6 6 6-6 6" />
                                                                    </svg>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div id="dashboardLeaveCalendar" class="w-full" style="width: 760px; max-width: 100%;" data-calendar-container>
                                    @include('dashboard._leave-calendar')
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                <!-- Dashboard Actions -->
                <div class="min-w-0 space-y-3 lg:self-start">
                    @if(in_array(auth()->user()->role, ['admin', 'hrstaff', 'chief', 'regionaldirector']))
                        <div>
                            <a href="{{ route('announcements.index', ['openCreate' => 1]) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-xs font-black uppercase tracking-widest text-white shadow-md shadow-indigo-100 transition hover:bg-indigo-700">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5" />
                                </svg>
                                {{ __('New Announcement') }}
                            </a>
                        </div>
                    @endif

                    @if($latestAnnouncement)
                        <div class="bg-white rounded-xl p-5 shadow-lg shadow-indigo-50 border border-gray-200 flex flex-col">
                            <div class="mb-3">
                                <span class="inline-flex w-full items-center justify-center rounded-lg border border-indigo-100 bg-indigo-50 px-5 py-2.5 text-xs font-black uppercase tracking-widest text-indigo-600">
                                    {{ __('Latest Updates') }}
                                </span>
                            </div>

                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">{{ $latestAnnouncement->published_at->diffForHumans() }}</p>
                                <h3 class="text-base font-black text-gray-900 mb-3 tracking-tight leading-5 line-clamp-2 uppercase">
                                    {{ $latestAnnouncement->title }}
                                </h3>
                            </div>

                            <div>
                                <a href="{{ route('announcements.show', $latestAnnouncement) }}" class="flex items-center justify-center w-full px-5 py-2.5 bg-gray-900 text-white rounded-lg font-black text-xs uppercase tracking-widest hover:bg-indigo-600 transition-all duration-300 shadow-lg shadow-gray-200">
                                    {{ __('Read Announcement') }}
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                    </svg>
                                </a>
                                <a href="{{ route('announcements.view') }}" class="block text-center mt-3 text-[10px] font-black text-gray-400 hover:text-indigo-600 uppercase tracking-widest transition-colors">
                                    {{ __('View All') }}
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-xl p-6 text-center flex flex-col items-center justify-center">
                            <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-sm mb-4">
                                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                                </svg>
                            </div>
                            <p class="text-sm font-bold text-gray-400 uppercase tracking-widest">{{ __('No new announcements') }}</p>
                        </div>
                    @endif

                    @if($approvalCounts->isEmpty())
                        <div class="grid gap-2">
                            <a href="{{ route('pds.index') }}" class="rounded-lg border border-blue-800 bg-blue-900 p-3 text-white shadow-md shadow-blue-100 transition hover:bg-blue-800">
                                <span class="block text-sm font-bold tracking-tight">{{ __('View PDS') }}</span>
                                <span class="mt-2 inline-flex items-center text-[10px] font-black bg-white text-blue-900 px-3 py-1.5 rounded-md uppercase tracking-widest">
                                    {{ __('Open PDS') }}
                                </span>
                            </a>
                            <a href="{{ route('salns.index') }}" class="rounded-lg border border-blue-800 bg-blue-900 p-3 text-white shadow-md shadow-blue-100 transition hover:bg-blue-800">
                                <span class="block text-sm font-bold tracking-tight">{{ __('View SALN') }}</span>
                                <span class="mt-2 inline-flex items-center text-[10px] font-black bg-white text-blue-900 px-3 py-1.5 rounded-md uppercase tracking-widest">
                                    {{ __('Open SALN') }}
                                </span>
                            </a>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('click', async (event) => {
            const mflDay = event.target.closest('[data-mfl-day]');

            if (mflDay) {
                const calendar = mflDay.closest('[data-mfl-calendar]');
                const dateLabel = calendar?.querySelector('[data-mfl-panel-date]');
                const emptyState = calendar?.querySelector('[data-mfl-panel-empty]');
                const list = calendar?.querySelector('[data-mfl-panel-list]');
                let schedules = [];

                try {
                    schedules = JSON.parse(mflDay.dataset.mflSchedules || '[]');
                } catch (error) {
                    schedules = [];
                }

                calendar?.querySelectorAll('[data-mfl-day]').forEach((button) => {
                    button.classList.remove('outline', 'outline-2', 'outline-offset-1', 'outline-indigo-500');
                });
                mflDay.classList.add('outline', 'outline-2', 'outline-offset-1', 'outline-indigo-500');

                if (dateLabel) {
                    dateLabel.textContent = mflDay.dataset.mflDisplayDate || '';
                }

                if (emptyState) {
                    emptyState.hidden = schedules.length > 0;
                }

                if (list) {
                    list.replaceChildren();

                    schedules.forEach((schedule) => {
                        const item = document.createElement('div');
                        const heading = document.createElement('div');
                        const name = document.createElement('p');
                        const status = document.createElement('span');
                        const approved = String(schedule.status).toLowerCase() === 'approved';

                        item.className = 'rounded-md border border-gray-200 bg-white px-2 py-1.5 shadow-sm';
                        heading.className = 'flex items-center justify-between gap-2';
                        name.className = 'min-w-0 truncate text-[10px] font-black text-gray-800';
                        status.className = approved
                            ? 'shrink-0 rounded-full bg-green-50 px-1.5 py-0.5 text-[8px] font-black uppercase text-green-700'
                            : 'shrink-0 rounded-full bg-amber-50 px-1.5 py-0.5 text-[8px] font-black uppercase text-amber-700';
                        name.textContent = schedule.name || 'Employee';
                        status.textContent = schedule.status || 'Pending';

                        heading.append(name, status);
                        item.append(heading);
                        list.append(item);
                    });
                }

                return;
            }

            const link = event.target.closest('[data-calendar-link]');
            const container = document.querySelector('[data-calendar-container]');

            if (!link || !container) {
                return;
            }

            event.preventDefault();

            try {
                const response = await fetch(link.dataset.calendarUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('Calendar request failed.');
                }

                const payload = await response.json();
                container.innerHTML = payload.html;

                const url = new URL(window.location.href);
                url.searchParams.set('month', payload.month);
                window.history.replaceState({}, '', url);
            } catch (error) {
                window.location.href = link.href;
            }
        });
    </script>
</x-app-layout>
