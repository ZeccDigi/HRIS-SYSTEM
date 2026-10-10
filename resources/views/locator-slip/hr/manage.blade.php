<x-app-layout>
    <x-slot name="title">{{ __('Manage Locator Slips') }}</x-slot>

    @php
        $currentYear = now()->year;
    @endphp

    <div
        class="p-4 sm:p-5 lg:p-6"
        x-data='manageLocatorSlips(@json($tab), @json((string) $currentYear))'
        x-init="init()"
    >
        {{-- Success message --}}
        @if(session('success'))
            <div class="mb-4 rounded-lg border-l-4 border-green-500 bg-green-100 p-4 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- Error message --}}
        @if(session('error'))
            <div class="mb-4 rounded-lg border-l-4 border-red-500 bg-red-100 p-4 text-red-700">
                {{ session('error') }}
            </div>
        @endif

        {{-- Year selector --}}
        <div class="mb-4 flex items-center justify-end gap-2">
            <label
                for="locatorSlipYear"
                class="text-sm font-semibold text-gray-700"
            >
                {{ __('Year') }}
            </label>

            <select
                id="locatorSlipYear"
                x-model="year"
                @change="submitFilters()"
                class="h-10 w-28 rounded-lg border-gray-300 bg-white pl-4 pr-10 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                @foreach($availableYears as $yearOption)
                    <option value="{{ $yearOption }}">
                        {{ $yearOption }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Tabs --}}
        <div class="mb-4 border-b border-gray-200">
            <nav
                class="-mb-px flex gap-6 overflow-x-auto"
                aria-label="{{ __('Locator Slip Tabs') }}"
            >
                <button
                    type="button"
                    @click="changeTab('pending')"
                    class="shrink-0 whitespace-nowrap border-b-2 px-1 py-3 text-sm font-bold uppercase tracking-widest transition-colors duration-200"
                    :class="tab === 'pending'
                        ? 'border-indigo-500 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                >
                    {{ __('Pending Locator Slips') }}
                </button>

                <button
                    type="button"
                    @click="changeTab('all')"
                    class="shrink-0 whitespace-nowrap border-b-2 px-1 py-3 text-sm font-bold uppercase tracking-widest transition-colors duration-200"
                    :class="tab === 'all'
                        ? 'border-indigo-500 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                >
                    {{ __('All Locator Slips') }}
                </button>
            </nav>
        </div>

        {{-- Search and filters --}}
        <div class="mb-4 flex min-w-0 flex-col gap-2 rounded-xl border border-gray-100 bg-gray-50/70 p-2 sm:flex-row sm:items-center">

            {{-- Search --}}
            <div class="relative min-w-0 sm:flex-1">
                <label
                    for="locatorSlipSearch"
                    class="sr-only"
                >
                    {{ __('Search locator slip') }}
                </label>

                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.4"
                            d="M21 21l-4.35-4.35M10.75 18.5a7.75 7.75 0 100-15.5 7.75 7.75 0 000 15.5z"
                        />
                    </svg>
                </span>

                <input
                    id="locatorSlipSearch"
                    type="search"
                    x-model="search"
                    @input.debounce.400ms="submitFilters()"
                    placeholder="{{ __('Search employee, destination, type, or status...') }}"
                    class="block h-9 w-full rounded-lg border-gray-300 pl-10 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
            </div>

            {{-- Locator type --}}
            <div class="sm:w-44 sm:shrink-0">
                <label
                    for="locatorSlipType"
                    class="sr-only"
                >
                    {{ __('Locator Type') }}
                </label>

                <select
                    id="locatorSlipType"
                    x-model="type"
                    @change="submitFilters()"
                    class="block h-9 w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">
                        {{ __('Types') }}
                    </option>

                    @foreach($locatorTypes as $locatorType)
                        @php
                            $filterType = $locatorType === 'Personal'
                                ? 'Pass Slip'
                                : $locatorType;
                        @endphp

                        <option value="{{ Str::lower($filterType) }}">
                            {{ $filterType }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Status filter --}}
            <div class="sm:w-48 sm:shrink-0">
                <label
                    for="locatorSlipStatus"
                    class="sr-only"
                >
                    {{ __('Status') }}
                </label>

                <select
                    id="locatorSlipStatus"
                    x-model="status"
                    @change="submitFilters()"
                    class="block h-9 w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">
                        {{ __('Status') }}
                    </option>

                    <option value="pending">
                        {{ __('Pending') }}
                    </option>

                    <option value="approved">
                        {{ __('Approved') }}
                    </option>

                    <option value="rejected">
                        {{ __('Rejected') }}
                    </option>
                </select>
            </div>

            {{-- Reset --}}
            <div class="flex items-center sm:shrink-0">
                <button
                    type="button"
                    @click="reset()"
                    class="inline-flex h-9 w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-xs font-bold uppercase tracking-wider text-gray-700 transition hover:bg-gray-50 sm:w-auto"
                >
                    {{ __('Reset') }}
                </button>
            </div>
        </div>

        {{-- Pending Locator Slips --}}
        <div
            x-show="tab === 'pending'"
            x-cloak
        >
            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="w-full overflow-x-auto">
                    <table class="w-full table-fixed divide-y divide-gray-100">
                        <colgroup>
                            <col class="w-[17%]">
                            <col class="w-[14%]">
                            <col class="w-[11%]">
                            <col class="w-[19%]">
                            <col class="w-[18%]">
                            <col class="w-[12%]">
                            <col class="w-[9%]">
                        </colgroup>

                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    scope="col"
                                    class="break-words whitespace-normal px-3 py-3 text-left text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                >
                                    {{ __('Employee') }}
                                </th>

                                <th
                                    scope="col"
                                    class="break-words whitespace-normal px-3 py-3 text-left text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                >
                                    {{ __('Date Covered') }}
                                </th>

                                <th
                                    scope="col"
                                    class="break-words whitespace-normal px-3 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                >
                                    {{ __('Type') }}
                                </th>

                                <th
                                    scope="col"
                                    class="break-words whitespace-normal px-3 py-3 text-left text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                >
                                    {{ __('Destination') }}
                                </th>

                                <th
                                    scope="col"
                                    class="break-words whitespace-normal px-3 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                >
                                    {{ __('Approver') }}
                                </th>

                                <th
                                    scope="col"
                                    class="break-words whitespace-normal px-3 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                >
                                    {{ __('Status') }}
                                </th>

                                <th
                                    scope="col"
                                    class="break-words whitespace-normal px-1 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                >
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            id="pendingLocatorSlipRows"
                            class="divide-y divide-gray-100 bg-white"
                        >
                            @forelse($pendingLocatorSlips as $slip)
                                @php
                                    $rawStatus = Str::lower(
                                        trim((string) ($slip->status ?? 'pending'))
                                    );

                                    $displayStatus = $rawStatus === 'approved by chief'
                                        ? 'approved'
                                        : $rawStatus;

                                    $employeeName = trim(
                                        ($slip->employee?->firstname ?? '') . ' ' .
                                        ($slip->employee?->lastname ?? '')
                                    );

                                    $displayType = ($slip->type ?? '') === 'Personal'
                                        ? 'Pass Slip'
                                        : ($slip->type ?? '');

                                    $divisionChief = $slip->employee?->division ? \App\Models\Employee::where('division', $slip->employee->division)->whereIn('account_role', ['chief', 'CHIEF'])->first() : null;
                                    $chiefName = $divisionChief ? trim($divisionChief->firstname . ' ' . $divisionChief->lastname) : null;
                                    $approverName = ($slip->approved_by_chief_name && strtolower($slip->approved_by_chief_name) !== 'chief user')
                                        ? $slip->approved_by_chief_name
                                        : ($chiefName ?: ($displayStatus === 'pending' ? 'Pending Approval' : 'N/A'));

                                    $approvalStatus = $displayStatus === 'approved'
                                        ? 'approved'
                                        : ($displayStatus === 'rejected' ? 'rejected' : 'pending');

                                    $approvalDotClass = match ($approvalStatus) {
                                        'approved' => 'bg-green-500',
                                        'rejected' => 'bg-red-500',
                                        default => 'bg-gray-300',
                                    };

                                    $coveredDate = $slip->date_covered
                                        ? \Carbon\Carbon::parse($slip->date_covered)
                                        : null;

                                    $rowYear = $coveredDate
                                        ? $coveredDate->year
                                        : ($slip->created_at?->year ?? '');

                                    $searchText = Str::lower(
                                        implode(' ', array_filter([
                                            $employeeName,
                                            $slip->employee?->division,
                                            $approverName,
                                            $slip->destination,
                                            $displayType,
                                            $displayStatus,
                                        ]))
                                    );

                                    $previewRemarks = collect([
                                        $slip->chief_remarks ?? null,
                                        $slip->hrstaff_remarks ?? null,
                                        $slip->notes_remarks ?? null,
                                    ])
                                        ->filter()
                                        ->implode("\n\n");

                                    $previewPayload = [
                                        'title' => $displayType ?: 'Locator Slip',
                                        'date' => $coveredDate
                                            ? $coveredDate->format('M d, Y')
                                            : 'N/A',
                                        'status' => $displayStatus,
                                        'remarks' => $previewRemarks
                                            ?: 'No remarks available.',
                                        'printUrl' => route(
                                            'locator-slips.print',
                                            [
                                                'locatorSlip' => $slip->id,
                                                'preview' => 1,
                                            ]
                                        ),
                                        'directPrintUrl' => route(
                                            'locator-slips.print',
                                            $slip->id
                                        ),
                                    ];

                                    $statusClass = match (true) {
                                        $displayStatus === 'approved' =>
                                            'border-green-500 bg-green-500 text-white',

                                        $displayStatus === 'rejected' =>
                                            'border-red-500 bg-red-500 text-white',

                                        Str::contains($displayStatus, 'pending') =>
                                            'border-orange-100 bg-orange-50 text-orange-700',

                                        default =>
                                            'border-gray-200 bg-gray-50 text-gray-600',
                                    };
                                @endphp

                                <tr
                                    class="transition-colors duration-150 hover:bg-gray-50"
                                    data-manage-locator-row="pending"
                                    data-search="{{ $searchText }}"
                                    data-type="{{ Str::lower($displayType) }}"
                                    data-status="{{ $displayStatus }}"
                                    data-year="{{ $rowYear }}"
                                    data-employee="{{ Str::lower($employeeName) }}"
                                    data-filed="{{ $slip->created_at?->timestamp ?? 0 }}"
                                >
                                    {{-- Employee name only --}}
                                    <td class="px-3 py-3 align-middle">
                                        <div
                                            class="min-w-0 whitespace-normal break-words [overflow-wrap:anywhere] text-sm font-bold leading-5 text-gray-900"
                                            title="{{ $employeeName }}"
                                        >
                                            {{ $employeeName ?: __('N/A') }}
                                        </div>
                                    </td>

                                    {{-- Date Covered --}}
                                    <td class="px-3 py-3 align-middle">
                                        <div class="min-w-0 whitespace-normal break-words text-sm leading-5 text-gray-700">
                                            {{ $coveredDate
                                                ? $coveredDate->format('M d, Y')
                                                : __('N/A') }}
                                        </div>
                                    </td>

                                    {{-- Type --}}
                                    <td class="px-3 py-3 text-center align-middle">
                                        <div
                                            class="min-w-0 whitespace-normal break-words [overflow-wrap:anywhere] text-sm leading-5 text-gray-700"
                                            title="{{ $displayType }}"
                                        >
                                            {{ $displayType ?: __('N/A') }}
                                        </div>
                                    </td>

                                    {{-- Destination --}}
                                    <td class="px-3 py-3 align-middle">
                                        <div
                                            class="min-w-0 whitespace-normal break-words [overflow-wrap:anywhere] text-sm leading-5 text-gray-700"
                                            title="{{ $slip->destination }}"
                                        >
                                            {{ $slip->destination ?: __('N/A') }}
                                        </div>
                                    </td>

                                    {{-- Approver --}}
                                    <td class="px-3 py-3 text-center align-middle">
                                        <div class="inline-flex max-w-full items-center justify-center gap-2 text-left">
                                            <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $approvalDotClass }}"></span>
                                            <span class="min-w-0 whitespace-normal break-words [overflow-wrap:anywhere] text-xs font-semibold leading-4 text-gray-700">
                                                {{ $approverName }}
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-3 py-3 text-center align-middle">
                                        <span
                                            class="inline-flex max-w-full whitespace-normal break-words rounded-full border px-2.5 py-1 text-center text-[10px] font-semibold uppercase leading-4 {{ $statusClass }}"
                                        >
                                            {{ Str::headline($displayStatus) }}
                                        </span>
                                    </td>

                                    {{-- Actions --}}
                                    <td
                                        class="px-1 py-3 text-center align-middle"
                                        onclick="event.stopPropagation()"
                                    >
                                        <div class="mx-auto flex w-fit flex-wrap items-center justify-center gap-1">
                                            {{-- View --}}
                                            <a
                                                href="{{ route('hr.locator-slips.show', $slip) }}"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-indigo-700 transition-colors hover:bg-indigo-50 hover:text-indigo-900"
                                                title="{{ __('View details') }}"
                                                aria-label="{{ __('View details') }}"
                                            >
                                                <i class="fa-solid fa-eye text-sm"></i>
                                            </a>

                                            {{-- Print --}}
                                            <a
                                                href="{{ route('locator-slips.print', $slip->id) }}"
                                                target="_blank"
                                                rel="noopener"
                                                data-no-transition
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900"
                                                title="{{ __('Print') }}"
                                                aria-label="{{ __('Print') }}"
                                            >
                                                <i class="fa-solid fa-print text-sm"></i>
                                            </a>

                                            @if(strtolower(Auth::user()->role) === 'chief')
                                                {{-- Approve --}}
                                                <form
                                                    action="{{ route('locator-slips.approve', $slip->id) }}"
                                                    method="POST"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <button
                                                        type="submit"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-green-600 transition-colors hover:bg-green-50 hover:text-green-900"
                                                        title="{{ __('Approve') }}"
                                                        aria-label="{{ __('Approve') }}"
                                                    >
                                                        <i class="fa-solid fa-check text-sm"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if(strtolower(Auth::user()->role) === 'chief')
                                                <button
                                                    type="button"
                                                    onclick="submitManageLocatorReject({{ $slip->id }})"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-600 transition-colors hover:bg-red-50 hover:text-red-900"
                                                    title="{{ __('Reject') }}"
                                                    aria-label="{{ __('Reject') }}"
                                                >
                                                    <i class="fa-solid fa-xmark text-sm"></i>
                                                </button>

                                                {{-- Reject --}}
                                                <form
                                                    action="{{ route('locator-slips.reject', $slip->id) }}"
                                                    method="POST"
                                                    id="locatorRejectForm_{{ $slip->id }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <input
                                                        type="hidden"
                                                        name="remarks"
                                                        id="locatorRejectRemarks_{{ $slip->id }}"
                                                        value=""
                                                    >

                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td
                                        colspan="7"
                                        class="px-4 py-8 text-center text-sm font-medium italic text-gray-500"
                                    >
                                        {{ __('No pending locator slips found.') }}
                                    </td>
                                </tr>
                            @endforelse

                            @if($pendingLocatorSlips->isNotEmpty())
                                <tr
                                    x-show="pendingNoResults"
                                    x-cloak
                                >
                                    <td
                                        colspan="7"
                                        class="px-4 py-8 text-center text-sm font-medium italic text-gray-500"
                                    >
                                        {{ __('No pending locator slips match your current filters.') }}
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- Pagination temporarily disabled until the locator slip pagination flow is revisited. --}}
            </div>
        </div>

        {{-- All Locator Slips --}}
        <div
            x-show="tab === 'all'"
            x-cloak
        >
            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="w-full overflow-x-auto">
                    <table class="w-full table-fixed divide-y divide-gray-100">
                        <colgroup>
                            <col class="w-[17%]">
                            <col class="w-[14%]">
                            <col class="w-[11%]">
                            <col class="w-[19%]">
                            <col class="w-[18%]">
                            <col class="w-[12%]">
                            <col class="w-[9%]">
                        </colgroup>

                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    scope="col"
                                    class="break-words whitespace-normal px-3 py-3 text-left text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                >
                                    {{ __('Employee') }}
                                </th>

                                <th
                                    scope="col"
                                    class="break-words whitespace-normal px-3 py-3 text-left text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                >
                                    {{ __('Date Covered') }}
                                </th>

                                <th
                                    scope="col"
                                    class="break-words whitespace-normal px-3 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                >
                                    {{ __('Type') }}
                                </th>

                                <th
                                    scope="col"
                                    class="break-words whitespace-normal px-3 py-3 text-left text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                >
                                    {{ __('Destination') }}
                                </th>

                                <th
                                    scope="col"
                                    class="break-words whitespace-normal px-3 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                >
                                    {{ __('Approver') }}
                                </th>

                                <th
                                    scope="col"
                                    class="break-words whitespace-normal px-3 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                >
                                    {{ __('Status') }}
                                </th>

                                <th
                                    scope="col"
                                    class="break-words whitespace-normal px-1 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                >
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            id="allLocatorSlipRows"
                            class="divide-y divide-gray-100 bg-white"
                        >
                            @forelse($allLocatorSlips as $slip)
                                @php
                                    $rawStatus = Str::lower(
                                        trim((string) ($slip->status ?? 'pending'))
                                    );

                                    $displayStatus = $rawStatus === 'approved by chief'
                                        ? 'approved'
                                        : $rawStatus;

                                    $employeeName = trim(
                                        ($slip->employee?->firstname ?? '') . ' ' .
                                        ($slip->employee?->lastname ?? '')
                                    );

                                    $displayType = ($slip->type ?? '') === 'Personal'
                                        ? 'Pass Slip'
                                        : ($slip->type ?? '');

                                    $divisionChief = $slip->employee?->division ? \App\Models\Employee::where('division', $slip->employee->division)->whereIn('account_role', ['chief', 'CHIEF'])->first() : null;
                                    $chiefName = $divisionChief ? trim($divisionChief->firstname . ' ' . $divisionChief->lastname) : null;
                                    $approverName = ($slip->approved_by_chief_name && strtolower($slip->approved_by_chief_name) !== 'chief user')
                                        ? $slip->approved_by_chief_name
                                        : ($chiefName ?: ($displayStatus === 'pending' ? 'Pending Approval' : 'N/A'));

                                    $approvalStatus = $displayStatus === 'approved'
                                        ? 'approved'
                                        : ($displayStatus === 'rejected' ? 'rejected' : 'pending');

                                    $approvalDotClass = match ($approvalStatus) {
                                        'approved' => 'bg-green-500',
                                        'rejected' => 'bg-red-500',
                                        default => 'bg-gray-300',
                                    };

                                    $coveredDate = $slip->date_covered
                                        ? \Carbon\Carbon::parse($slip->date_covered)
                                        : null;

                                    $rowYear = $coveredDate
                                        ? $coveredDate->year
                                        : ($slip->created_at?->year ?? '');

                                    $searchText = Str::lower(
                                        implode(' ', array_filter([
                                            $employeeName,
                                            $slip->employee?->division,
                                            $approverName,
                                            $slip->destination,
                                            $displayType,
                                            $displayStatus,
                                        ]))
                                    );

                                    $previewRemarks = collect([
                                        $slip->chief_remarks ?? null,
                                        $slip->hrstaff_remarks ?? null,
                                        $slip->notes_remarks ?? null,
                                    ])
                                        ->filter()
                                        ->implode("\n\n");

                                    $previewPayload = [
                                        'title' => $displayType ?: 'Locator Slip',
                                        'date' => $coveredDate
                                            ? $coveredDate->format('M d, Y')
                                            : 'N/A',
                                        'status' => $displayStatus,
                                        'remarks' => $previewRemarks
                                            ?: 'No remarks available.',
                                        'printUrl' => route(
                                            'locator-slips.print',
                                            [
                                                'locatorSlip' => $slip->id,
                                                'preview' => 1,
                                            ]
                                        ),
                                        'directPrintUrl' => route(
                                            'locator-slips.print',
                                            $slip->id
                                        ),
                                    ];

                                    $statusClass = match (true) {
                                        $displayStatus === 'approved' =>
                                            'border-green-500 bg-green-500 text-white',

                                        $displayStatus === 'rejected' =>
                                            'border-red-500 bg-red-500 text-white',

                                        Str::contains($displayStatus, 'pending') =>
                                            'border-orange-100 bg-orange-50 text-orange-700',

                                        default =>
                                            'border-gray-200 bg-gray-50 text-gray-600',
                                    };
                                @endphp

                                <tr
                                    class="transition-colors duration-150 hover:bg-gray-50"
                                    data-manage-locator-row="all"
                                    data-search="{{ $searchText }}"
                                    data-type="{{ Str::lower($displayType) }}"
                                    data-status="{{ $displayStatus }}"
                                    data-year="{{ $rowYear }}"
                                    data-employee="{{ Str::lower($employeeName) }}"
                                    data-filed="{{ $slip->created_at?->timestamp ?? 0 }}"
                                >
                                    {{-- Employee name only --}}
                                    <td class="px-3 py-3 align-middle">
                                        <div
                                            class="min-w-0 whitespace-normal break-words [overflow-wrap:anywhere] text-sm font-bold leading-5 text-gray-900"
                                            title="{{ $employeeName }}"
                                        >
                                            {{ $employeeName ?: __('N/A') }}
                                        </div>
                                    </td>

                                    {{-- Date Covered --}}
                                    <td class="px-3 py-3 align-middle">
                                        <div class="min-w-0 whitespace-normal break-words text-sm leading-5 text-gray-700">
                                            {{ $coveredDate
                                                ? $coveredDate->format('M d, Y')
                                                : __('N/A') }}
                                        </div>
                                    </td>

                                    {{-- Type --}}
                                    <td class="px-3 py-3 text-center align-middle">
                                        <div
                                            class="min-w-0 whitespace-normal break-words [overflow-wrap:anywhere] text-sm leading-5 text-gray-700"
                                            title="{{ $displayType }}"
                                        >
                                            {{ $displayType ?: __('N/A') }}
                                        </div>
                                    </td>

                                    {{-- Destination --}}
                                    <td class="px-3 py-3 align-middle">
                                        <div
                                            class="min-w-0 whitespace-normal break-words [overflow-wrap:anywhere] text-sm leading-5 text-gray-700"
                                            title="{{ $slip->destination }}"
                                        >
                                            {{ $slip->destination ?: __('N/A') }}
                                        </div>
                                    </td>

                                    {{-- Approver --}}
                                    <td class="px-3 py-3 text-center align-middle">
                                        <div class="inline-flex max-w-full items-center justify-center gap-2 text-left">
                                            <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $approvalDotClass }}"></span>
                                            <span class="min-w-0 whitespace-normal break-words [overflow-wrap:anywhere] text-xs font-semibold leading-4 text-gray-700">
                                                {{ $approverName }}
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-3 py-3 text-center align-middle">
                                        <span
                                            class="inline-flex max-w-full whitespace-normal break-words rounded-full border px-2.5 py-1 text-center text-[10px] font-semibold uppercase leading-4 {{ $statusClass }}"
                                        >
                                            {{ Str::headline($displayStatus) }}
                                        </span>
                                    </td>

                                    {{-- Actions --}}
                                    <td
                                        class="px-1 py-3 text-center align-middle"
                                        onclick="event.stopPropagation()"
                                    >
                                        <div class="mx-auto grid w-fit grid-cols-2 place-items-center gap-1">
                                            <a
                                                href="{{ route('hr.locator-slips.show', $slip) }}"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-indigo-700 transition-colors hover:bg-indigo-50 hover:text-indigo-900"
                                                title="{{ __('View details') }}"
                                                aria-label="{{ __('View details') }}"
                                            >
                                                <i class="fa-solid fa-eye text-sm"></i>
                                            </a>

                                            <a
                                                href="{{ route('locator-slips.print', $slip->id) }}"
                                                target="_blank"
                                                rel="noopener"
                                                data-no-transition
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900"
                                                title="{{ __('Print') }}"
                                                aria-label="{{ __('Print') }}"
                                            >
                                                <i class="fa-solid fa-print text-sm"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td
                                        colspan="7"
                                        class="px-4 py-8 text-center text-sm font-medium italic text-gray-500"
                                    >
                                        {{ __('No locator slips found.') }}
                                    </td>
                                </tr>
                            @endforelse

                            @if($allLocatorSlips->isNotEmpty())
                                <tr
                                    x-show="allNoResults"
                                    x-cloak
                                >
                                    <td
                                        colspan="7"
                                        class="px-4 py-8 text-center text-sm font-medium italic text-gray-500"
                                    >
                                        {{ __('No locator slips match your current filters.') }}
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- Pagination temporarily disabled until the locator slip pagination flow is revisited. --}}
            </div>
        </div>

        {{-- Preview modal --}}
        <template x-teleport="body">
            <div
                x-show="previewModalOpen"
                x-cloak
                class="fixed inset-0 z-[100000] flex items-center justify-center p-5 sm:p-8"
                style="display: none;"
                @keydown.escape.window="closePreviewModal()"
            >
                <div
                    class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                    @click="closePreviewModal()"
                ></div>

                <div
                    class="relative z-10 flex w-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                    style="width: min(90vw, 620px); height: min(78vh, 610px); max-height: calc(100dvh - 80px);"
                    @click.stop
                >
                    {{-- Modal header --}}
                    <div class="flex shrink-0 items-center justify-between border-b border-blue-950 bg-blue-900 px-4 py-2.5 sm:px-5">
                        <div class="min-w-0">
                            <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-blue-200">
                                {{ __('Locator Slip Preview') }}
                            </p>

                            <div class="mt-0.5 flex min-w-0 flex-wrap items-center gap-x-2 gap-y-0.5">
                                <h2
                                    class="truncate text-sm font-bold text-white"
                                    x-text="previewData.title"
                                ></h2>

                                <span class="hidden text-blue-300 sm:inline">
                                    &bull;
                                </span>

                                <p class="text-[10px] font-medium text-blue-100">
                                    <span x-text="previewData.date"></span>
                                    <span class="mx-1">|</span>
                                    <span
                                        class="capitalize"
                                        x-text="previewData.status"
                                    ></span>
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="closePreviewModal()"
                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-white transition hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-white/70"
                            aria-label="{{ __('Close preview') }}"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    {{-- Preview --}}
                    <div class="min-h-0 flex-1 overflow-auto bg-slate-100 p-3">
                        <div class="mx-auto h-full max-w-[390px] overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm">
                            <iframe
                                x-ref="previewFrame"
                                :src="previewData.printUrl"
                                class="h-full min-h-[430px] w-full border-0 bg-white"
                                title="{{ __('Locator Slip Print Preview') }}"
                            ></iframe>
                        </div>
                    </div>

                    {{-- Modal footer --}}
                    <div class="flex shrink-0 flex-col gap-2 border-t border-slate-200 bg-white px-4 py-2.5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <button
                                type="button"
                                @click="toggleRemarks()"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-700 transition hover:bg-slate-50 sm:w-auto"
                            >
                                <i class="fa-regular fa-comment-dots"></i>

                                <span x-text="showRemarks ? 'Hide Remarks' : 'Remarks'"></span>
                            </button>

                            <div class="flex shrink-0 gap-2 sm:justify-end">
                            <button
                                type="button"
                                @click="closePreviewModal()"
                                class="inline-flex flex-1 items-center justify-center rounded-lg border border-red-300 bg-white px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-red-600 transition hover:bg-red-50 sm:flex-none"
                            >
                                {{ __('Close') }}
                            </button>

                            <a
                                :href="previewData.directPrintUrl"
                                target="_blank"
                                rel="noopener"
                                data-no-transition
                                class="inline-flex flex-1 items-center justify-center rounded-lg bg-blue-900 px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-white transition hover:bg-blue-800 sm:flex-none"
                            >
                                {{ __('Print') }}
                            </a>
                            </div>
                        </div>

                        <template x-if="showRemarks">
                            <div
                                class="max-h-24 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs leading-5 text-slate-700"
                                x-text="previewData.remarks"
                            ></div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <script>
        function manageLocatorSlips(initialTab, currentYear) {
            return {
                tab: initialTab || 'pending',

                search: @js($search),
                type: @js($type),
                status: @js($status),
                year: @js((string) $year),

                pendingRows: [],
                allRows: [],

                pendingNoResults: false,
                allNoResults: false,

                previewModalOpen: false,
                showRemarks: false,

                previewData: {
                    title: '',
                    date: '',
                    status: '',
                    remarks: '',
                    printUrl: '',
                    directPrintUrl: '',
                },

                init() {
                    this.pendingRows = Array.from(
                        document.querySelectorAll(
                            '[data-manage-locator-row="pending"]'
                        )
                    );

                    this.allRows = Array.from(
                        document.querySelectorAll(
                            '[data-manage-locator-row="all"]'
                        )
                    );

                },

                changeTab(selectedTab) {
                    this.tab = selectedTab;
                    this.submitFilters(selectedTab);
                },

                submitFilters(selectedTab = null) {
                    const url = new URL(@js(route('hr.locator-slips.index')));
                    const activeTab = selectedTab || this.tab || 'pending';

                    url.searchParams.set('tab', activeTab);

                    if (this.search.trim()) {
                        url.searchParams.set('search', this.search.trim());
                    }

                    if (this.type) {
                        url.searchParams.set('type', this.type);
                    }

                    if (this.status) {
                        url.searchParams.set('status', this.status);
                    }

                    if (this.year) {
                        url.searchParams.set('year', this.year);
                    }

                    window.location.href = url.toString();
                },

                reset() {
                    this.search = '';
                    this.type = '';
                    this.status = '';
                    this.year = String(currentYear);

                    window.location.href = @js(route('hr.locator-slips.index'));
                },

                openPreviewModal(payload) {
                    this.previewData = payload;
                    this.showRemarks = false;
                    this.previewModalOpen = true;

                    document.documentElement.classList.add(
                        'overflow-hidden'
                    );

                    document.body.classList.add(
                        'overflow-hidden'
                    );

                    this.$nextTick(() => {
                        if (this.$refs.previewFrame) {
                            this.$refs.previewFrame.src =
                                payload.printUrl;
                        }
                    });
                },

                closePreviewModal() {
                    this.previewModalOpen = false;
                    this.showRemarks = false;

                    document.documentElement.classList.remove(
                        'overflow-hidden'
                    );

                    document.body.classList.remove(
                        'overflow-hidden'
                    );
                },

                toggleRemarks() {
                    this.showRemarks = !this.showRemarks;
                },
            };
        }

        function openManageLocatorPreviewModal(payloadJson) {
            const root = document.querySelector(
                '[x-data^="manageLocatorSlips"]'
            );

            if (!root) {
                return;
            }

            const component = window.Alpine?.$data
                ? window.Alpine.$data(root)
                : root.__x?.$data;

            if (!component?.openPreviewModal) {
                return;
            }

            component.openPreviewModal(JSON.parse(payloadJson));
        }

        function submitManageLocatorReject(id) {
            const remarks = window.prompt(
                'Enter rejection remarks:',
                ''
            );

            if (remarks === null) {
                return;
            }

            const cleanedRemarks = remarks.trim();

            if (!cleanedRemarks) {
                alert('Remarks are required to reject.');
                return;
            }

            const input = document.getElementById(
                `locatorRejectRemarks_${id}`
            );

            const form = document.getElementById(
                `locatorRejectForm_${id}`
            );

            if (!input || !form) {
                return;
            }

            input.value = cleanedRemarks;
            form.submit();
        }
    </script>
</x-app-layout>
