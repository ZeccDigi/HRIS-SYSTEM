@php
    $isHrAdmin = $isHrAdmin ?? in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'hrstaff'], true);
@endphp
<div class="overflow-hidden rounded-xl border border-gray-200 bg-white p-3 shadow-sm" data-mfl-calendar>
    <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h4 class="text-[11px] font-black uppercase tracking-widest text-gray-400">{{ $isHrAdmin ? __('Mandatory/Force Leave Calendar') : __('My Leave Calendar') }}</h4>
            <p class="text-sm font-black text-gray-900 uppercase tracking-tight">{{ $leaveCalendarMonth->format('F Y') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard', ['month' => $previousLeaveCalendarMonth]) }}" data-calendar-link data-calendar-url="{{ route('dashboard.leave-calendar', ['month' => $previousLeaveCalendarMonth]) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 bg-white text-blue-900 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600" title="{{ __('Previous month') }}" aria-label="{{ __('Previous month') }}">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <a href="{{ route('dashboard', ['month' => $nextLeaveCalendarMonth]) }}" data-calendar-link data-calendar-url="{{ route('dashboard.leave-calendar', ['month' => $nextLeaveCalendarMonth]) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 bg-white text-blue-900 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600" title="{{ __('Next month') }}" aria-label="{{ __('Next month') }}">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>

    <div class="{{ $isHrAdmin ? 'grid gap-3 sm:grid-cols-[minmax(0,1fr)_210px]' : '' }}">
    <div class="min-w-0">
    <div class="grid grid-cols-7 rounded-lg border border-gray-200 bg-gray-50/60 p-1 text-center">
        @foreach(['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $weekday)
            <div class="py-0.5 text-[9px] font-black uppercase tracking-widest text-gray-400">{{ $weekday }}</div>
        @endforeach

        @foreach($leaveCalendarDays as $cell)
            @php
                $requests = $cell['requests'] ?? collect();
                $hasLeave = $requests->isNotEmpty();
                $isApproved = $hasLeave && $requests->contains(fn ($leave) => $leave->status === 'approved');
                $hasPending = $hasLeave && $requests->contains(fn ($leave) => $leave->status !== 'approved');
                $isPastLeave = $hasLeave && $cell['date'] && \Carbon\Carbon::parse($cell['date'])->lt(today());
                $isHoliday = (bool) ($cell['is_holiday'] ?? false);
                $holidayName = $cell['holiday_name'] ?? null;
                $titleParts = collect();

                if ($holidayName) {
                    $titleParts->push($holidayName);
                }

                if ($hasLeave) {
                    $titleParts = $titleParts->merge($requests->map(function ($leave) use ($isHrAdmin) {
                        $employeeName = trim(($leave->employee?->firstname ?? '').' '.($leave->employee?->lastname ?? ''));
                        $leaveLabel = ($leave->leaveType?->name ?? 'Leave').' - '.ucfirst($leave->status);

                        return $isHrAdmin && $employeeName !== '' ? $employeeName.' - '.$leaveLabel : $leaveLabel;
                    }));
                }

                $title = $titleParts->implode(', ');
                $schedules = $requests->map(function ($leave) {
                    $employeeName = trim(($leave->employee?->firstname ?? '').' '.($leave->employee?->lastname ?? '')) ?: 'Employee';

                    return [
                        'name' => $employeeName,
                        'status' => ucfirst((string) $leave->status),
                    ];
                })->values();
                $dayClass = match (true) {
                    $isPastLeave => 'border border-slate-200 bg-slate-100 text-slate-500 shadow-sm',
                    $isApproved => 'border border-green-200 bg-green-50 text-green-700 shadow-sm',
                    $hasPending => 'border border-amber-200 bg-amber-50 text-amber-700 shadow-sm',
                    $isHoliday => 'border border-red-100 bg-red-50 text-red-600',
                    (bool) $cell['is_today'] => 'bg-indigo-600 text-white shadow-md shadow-indigo-100',
                    (bool) $cell['is_weekend'] => 'text-gray-300',
                    default => 'text-gray-600 hover:bg-white',
                };
            @endphp
            <div class="p-0.5">
                @if($cell['day'])
                    @if($isHrAdmin && $hasLeave)
                        <button
                            type="button"
                            title="{{ $title }}"
                            aria-label="{{ $title }}"
                            data-mfl-day
                            data-mfl-display-date="{{ $cell['date']->format('F j, Y') }}"
                            data-mfl-schedules="{{ $schedules->toJson() }}"
                            class="relative flex h-8 w-full items-center justify-center rounded-md text-[11px] font-bold transition hover:ring-2 hover:ring-indigo-200 {{ $dayClass }}"
                        >
                            {{ $cell['day'] }}
                            <span class="pointer-events-none absolute right-0 top-0 inline-flex h-3.5 min-w-3.5 -translate-y-1/4 translate-x-1/4 items-center justify-center rounded-full bg-white px-0.5 text-[8px] font-black leading-none text-indigo-700 shadow-sm ring-1 ring-indigo-200">
                                {{ $requests->count() }}
                            </span>
                        </button>
                    @else
                        <div title="{{ $title }}" aria-label="{{ $title ?: $cell['date']?->format('F j, Y') }}" class="relative flex h-8 items-center justify-center rounded-md text-[11px] font-bold transition {{ $dayClass }}">
                            {{ $cell['day'] }}
                        </div>
                    @endif
                @else
                    <div class="h-8"></div>
                @endif
            </div>
        @endforeach
    </div>

    @if($isHrAdmin)
        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[9px] font-bold text-gray-500">
            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-green-500"></span>{{ __('Approved MFL') }}</span>
            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-amber-500"></span>{{ __('Pending MFL') }}</span>
            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-red-400"></span>{{ __('Holiday') }}</span>
        </div>
    @endif
    </div>

    @if($isHrAdmin)
        <aside class="rounded-lg border border-gray-200 bg-gray-50/70 p-2.5" data-mfl-panel>
            <div class="flex items-center justify-between gap-2 border-b border-gray-200 pb-1.5">
                <p class="text-[9px] font-black uppercase tracking-widest text-indigo-600">{{ __('MFL Schedule') }}</p>
                <p class="text-[10px] font-black text-gray-800" data-mfl-panel-date>{{ __('Select a date') }}</p>
            </div>
            <p class="mt-2 text-[10px] leading-4 text-gray-400" data-mfl-panel-empty>{{ __('Click a numbered calendar day to view employees.') }}</p>
            <div class="mt-1.5 max-h-48 space-y-1 overflow-y-auto pr-1" data-mfl-panel-list></div>
        </aside>
    @endif
    </div>

</div>
