<x-app-layout>
    <x-slot name="title">{{ __('Manage Travel Authorities') }}</x-slot>

    @php
        $currentYear = now()->year;
        $approvalRole = strtolower((string) auth()->user()->role);
    @endphp

    <div
        class="py-8"
        x-data='manageTravelAuthorities(@json($tab), @json($currentYear), @json($approvalRole))'
        x-init="init()"
    >
        <div class="mx-auto w-full max-w-[95rem] px-4 sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 rounded-xl border-l-4 border-green-500 bg-green-100 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Year selector --}}
            <div class="mb-4 flex items-center justify-end gap-2">
                <label
                    for="travelYear"
                    class="text-sm font-semibold text-gray-700"
                >
                    {{ __('Year') }}
                </label>

                <select
                    id="travelYear"
                    x-model="year"
                    @change="applyFilters()"
                    class="h-10 w-28 rounded-lg border-gray-300 bg-white pl-4 pr-10 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    @for($yearOption = $currentYear; $yearOption >= $currentYear - 5; $yearOption--)
                        <option value="{{ $yearOption }}">
                            {{ $yearOption }}
                        </option>
                    @endfor
                </select>
            </div>

            {{-- Tabs --}}
            <div class="mb-4 border-b border-gray-200">
                <nav
                    class="-mb-px flex gap-6 overflow-x-auto"
                    aria-label="{{ __('Travel Authority Tabs') }}"
                >
                    <button
                        type="button"
                        @click="changeTab('pending')"
                        class="shrink-0 border-b-2 px-1 pb-3 pt-1 text-sm font-bold transition"
                        :class="tab === 'pending'
                            ? 'border-indigo-600 text-indigo-700'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                    >
                        {{ __('Pending') }}
                    </button>

                    <button
                        type="button"
                        @click="changeTab('all')"
                        class="shrink-0 border-b-2 px-1 pb-3 pt-1 text-sm font-bold transition"
                        :class="tab === 'all'
                            ? 'border-indigo-600 text-indigo-700'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                    >
                        {{ __('All TA') }}
                    </button>
                </nav>
            </div>

            {{-- Filters --}}
            <div class="mb-4 flex min-w-0 flex-col gap-2 rounded-xl border border-gray-100 bg-gray-50/70 p-2 lg:flex-row lg:items-center">

                {{-- Search --}}
                <div class="relative min-w-0 lg:flex-1">
                    <label for="travelSearch" class="sr-only">
                        {{ __('Search travel authority') }}
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
                        id="travelSearch"
                        type="search"
                        x-model="search"
                        @input.debounce.200ms="applyFilters()"
                        placeholder="{{ __('Search TA no., employee, destination...') }}"
                        class="block h-9 w-full rounded-lg border-gray-300 pl-10 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>

                {{-- Travel type --}}
                <div class="lg:w-48 lg:shrink-0">
                    <label for="travelType" class="sr-only">
                        {{ __('Travel Type') }}
                    </label>

                    <select
                        id="travelType"
                        x-model="travelType"
                        @change="applyFilters()"
                        class="block h-9 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">
                            {{ __('All Types') }}
                        </option>

                        @foreach($travelTypes as $value => $label)
                            <option value="{{ $value }}">
                                {{ __($label) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Month filter --}}
                <div class="lg:w-44 lg:shrink-0">
                    <label for="travelMonth" class="sr-only">
                        {{ __('Month Filed') }}
                    </label>

                    <select
                        id="travelMonth"
                        x-model="month"
                        @change="applyFilters()"
                        class="block h-9 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">
                            {{ __('All Months') }}
                        </option>

                        <option value="1">{{ __('January') }}</option>
                        <option value="2">{{ __('February') }}</option>
                        <option value="3">{{ __('March') }}</option>
                        <option value="4">{{ __('April') }}</option>
                        <option value="5">{{ __('May') }}</option>
                        <option value="6">{{ __('June') }}</option>
                        <option value="7">{{ __('July') }}</option>
                        <option value="8">{{ __('August') }}</option>
                        <option value="9">{{ __('September') }}</option>
                        <option value="10">{{ __('October') }}</option>
                        <option value="11">{{ __('November') }}</option>
                        <option value="12">{{ __('December') }}</option>
                    </select>
                </div>

                {{-- Reset --}}
                <div class="flex items-center lg:shrink-0">
                    <button
                        type="button"
                        @click="reset()"
                        class="inline-flex h-9 w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-xs font-bold uppercase tracking-wider text-gray-700 transition hover:bg-gray-50 lg:w-auto"
                    >
                        {{ __('Reset') }}
                    </button>
                </div>
            </div>

            {{-- Travel authorities table --}}
            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                @foreach([
                    'pending' => $pendingTravelOrders,
                    'all' => $allTravelOrders
                ] as $section => $orders)

                    <div
                        x-show="tab === '{{ $section }}'"
                        x-cloak
                        class="overflow-x-auto"
                    >
                        <table class="min-w-full table-fixed border-collapse text-left">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="w-[9%] px-3 py-3 text-[11px] font-black uppercase tracking-widest text-gray-500">
                                        {{ __('Date Filed') }}
                                    </th>

                                    <th class="w-[12%] px-3 py-3 text-[11px] font-black uppercase tracking-widest text-gray-500">
                                        {{ __('TA Stamp') }}
                                    </th>

                                    <th class="w-[12%] px-3 py-3 text-[11px] font-black uppercase tracking-widest text-gray-500">
                                        {{ __('Employee') }}
                                    </th>

                                    <th class="w-[11%] px-3 py-3 text-[11px] font-black uppercase tracking-widest text-gray-500">
                                        {{ __('Date of Travel') }}
                                    </th>

                                    <th class="w-[12%] px-3 py-3 text-[11px] font-black uppercase tracking-widest text-gray-500">
                                        {{ __('Destination') }}
                                    </th>

                                    <th class="w-[12%] px-3 py-3 text-[11px] font-black uppercase tracking-widest text-gray-500">
                                        {{ __('Record Officer') }}
                                    </th>

                                    <th class="w-[12%] px-3 py-3 text-[11px] font-black uppercase tracking-widest text-gray-500">
                                        {{ __('Regional Director') }}
                                    </th>

                                    <th class="w-[8%] px-3 py-3 text-[11px] font-black uppercase tracking-widest text-gray-500">
                                        {{ __('Status') }}
                                    </th>

                                    <th class="w-[7%] px-3 py-3 text-[11px] font-black uppercase tracking-widest text-gray-500">
                                        {{ __('AR Deadline') }}
                                    </th>

                                    <th class="w-[5%] px-3 py-3 text-[11px] font-black uppercase tracking-widest text-gray-500">
                                        {{ __('AR Status') }}
                                    </th>

                                    <th class="w-[5%] px-3 py-3 text-right text-[11px] font-black uppercase tracking-widest text-gray-500">
                                        {{ __('Actions') }}
                                    </th>
                                </tr>
                            </thead>

                            <tbody
                                id="{{ $section }}TravelOrderRows"
                                class="divide-y divide-gray-100 bg-white"
                            >
                                @include('travel-orders._admin-rows', [
                                    'orders' => $orders,
                                    'emptyMessage' => $section === 'pending'
                                        ? __('No pending travel authorities.')
                                        : __('No travel authorities found.'),
                                    'actionMode' => $section === 'pending'
                                        ? 'review'
                                        : 'view',
                                ])

                                @if($orders->isNotEmpty())
                                    <tr
                                        x-show="{{ $section }}NoResults"
                                        x-cloak
                                    >
                                        <td
                                            colspan="11"
                                            class="px-6 py-10 text-center"
                                        >
                                            <div class="flex flex-col items-center justify-center">
                                                <svg
                                                    class="mb-3 h-8 w-8 text-gray-300"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="1.7"
                                                        d="M21 21l-4.35-4.35M10.75 18.5a7.75 7.75 0 100-15.5 7.75 7.75 0 000 15.5z"
                                                    />
                                                </svg>

                                                <p class="text-sm font-semibold text-gray-600">
                                                    {{ __('No matching travel authorities found.') }}
                                                </p>

                                                <p class="mt-1 text-xs text-gray-400">
                                                    {{ __('Try changing the search, year, month, or travel type.') }}
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Preview modal --}}
        <template x-teleport="body">
            <div
                x-cloak
                x-show="previewOpen"
                class="fixed inset-0 z-[100000] flex items-center justify-center p-5 sm:p-8"
                style="display: none;"
                @keydown.escape.window="closePreviewModal()"
            >
                <div
                    class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                    @click="closePreviewModal()"
                ></div>

                <div
                    @click.stop
                    class="relative z-10 flex w-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                    style="width: min(96vw, 1120px); height: min(92vh, 860px); max-height: calc(100dvh - 32px);"
                >
                    {{-- Modal header --}}
                    <div class="flex shrink-0 items-center justify-between border-b border-blue-950 bg-blue-900 px-4 py-2.5 sm:px-5">
                        <div class="min-w-0">
                            <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-blue-200">
                                {{ __('Travel Authority Preview') }}
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

                    {{-- Document preview --}}
                    <div class="min-h-0 flex-1 overflow-auto bg-slate-100 p-3">
                        <div class="mx-auto h-full max-w-[820px] overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm">
                            <iframe
                                class="h-full min-h-[620px] w-full border-0 bg-white"
                                :src="previewData.printUrl"
                                title="{{ __('Travel Authority Preview') }}"
                            ></iframe>
                        </div>
                    </div>

                    {{-- Modal footer --}}
                    <div class="flex shrink-0 flex-col gap-2 border-t border-slate-200 bg-white px-4 py-2.5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <button
                                type="button"
                                @click="toggleRemarks()"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2 sm:w-auto"
                            >
                                <svg
                                    class="h-3.5 w-3.5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 10h8M8 14h5m-8 6 3.5-3H18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h1v3Z"
                                    />
                                </svg>

                                <span x-text="showRemarks ? 'Hide Remarks' : 'Remarks'"></span>
                            </button>

                            <div class="flex shrink-0 gap-2 sm:justify-end">
                            <button
                                type="button"
                                @click="closePreviewModal()"
                                class="inline-flex flex-1 items-center justify-center rounded-lg border border-red-300 bg-white px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2 sm:flex-none"
                            >
                                {{ __('Close') }}
                            </button>

                            <a
                                :href="previewData.directPrintUrl"
                                target="_blank"
                                rel="noopener"
                                data-no-transition
                                class="inline-flex flex-1 items-center justify-center rounded-lg bg-blue-900 px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:flex-none"
                            >
                                {{ __('Print') }}
                            </a>
                            </div>
                        </div>

                        <template x-if="showRemarks">
                            <div
                                class="max-h-24 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs leading-5 text-slate-700"
                                x-text="previewData.remarks || 'No remarks provided.'"
                            ></div>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        <template x-teleport="body">
            <div
                x-cloak
                x-show="approveModalOpen"
                class="fixed inset-0 z-[100000] flex items-center justify-center p-4"
                style="display: none;"
                @keydown.escape.window="closeApproveModal()"
            >
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeApproveModal()"></div>

                <div @click.stop class="relative z-10 w-full max-w-xs overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <div>
                            <h2 class="text-base font-black text-slate-900">{{ __('Approve this travel authority?') }}</h2>
                            <p class="mt-0.5 text-xs text-slate-500" x-text="approveModalTitle"></p>
                        </div>
                    </div>

                    <div class="px-4 py-3 text-center">
                        <p class="whitespace-pre-line text-xs leading-5 text-slate-600" x-text="approveModalDescription"></p>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50 px-4 py-3 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            @click="closeApproveModal()"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-[11px] font-black uppercase tracking-widest text-slate-600 transition hover:bg-slate-100"
                        >
                            {{ __('Cancel') }}
                        </button>

                        <button
                            type="button"
                            @click="submitApproveForm()"
                            class="inline-flex items-center justify-center rounded-lg bg-blue-900 px-3 py-2 text-[11px] font-black uppercase tracking-widest text-white transition hover:bg-blue-800"
                        >
                            {{ __('Approve') }}
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <template x-teleport="body">
            <div
                x-cloak
                x-show="rejectModalOpen"
                class="fixed inset-0 z-[100000] flex items-center justify-center p-4"
                style="display: none;"
                @keydown.escape.window="closeRejectModal()"
            >
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeRejectModal()"></div>

                <div @click.stop class="relative z-10 w-full max-w-xs overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <div>
                            <h2 class="text-base font-black text-slate-900">{{ __('Reject this travel authority?') }}</h2>
                            <p class="mt-0.5 text-xs text-slate-500" x-text="rejectModalTitle"></p>
                        </div>
                    </div>

                    <div class="px-4 py-3">
                        <label
                            for="travelRejectModalRemarks"
                            class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500"
                        >
                            {{ __('Remarks / Notes') }}
                        </label>

                        <textarea
                            id="travelRejectModalRemarks"
                            x-model="rejectModalRemarks"
                            rows="4"
                            class="block w-full resize-y rounded-lg border-slate-300 bg-slate-50 text-xs text-slate-800 shadow-sm transition focus:border-blue-900 focus:bg-white focus:ring-blue-900"
                            placeholder="{{ __('Enter remarks or notes here...') }}"
                        ></textarea>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50 px-4 py-3 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            @click="closeRejectModal()"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-[11px] font-black uppercase tracking-widest text-slate-600 transition hover:bg-slate-100"
                        >
                            {{ __('Cancel') }}
                        </button>

                        <button
                            type="button"
                            @click="submitRejectForm()"
                            class="inline-flex items-center justify-center rounded-lg bg-blue-900 px-3 py-2 text-[11px] font-black uppercase tracking-widest text-white transition hover:bg-blue-800"
                        >
                            {{ __('Reject') }}
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <script>
        function manageTravelAuthorities(initialTab, currentYear, approvalRole) {
            return {
                tab: initialTab || 'pending',

                search: '',
                travelType: '',
                year: String(currentYear),
                month: '',

                pendingRows: [],
                allRows: [],

                pendingNoResults: false,
                allNoResults: false,

                previewOpen: false,
                showRemarks: false,
                approveModalOpen: false,
                approveModalFormId: '',
                approveModalTitle: '',
                approveModalDescription: '',
                rejectModalOpen: false,
                rejectModalFormId: '',
                rejectModalTitle: '',
                rejectModalRemarks: '',

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
                            '[data-manage-travel-row="pending"]'
                        )
                    );

                    this.allRows = Array.from(
                        document.querySelectorAll(
                            '[data-manage-travel-row="all"]'
                        )
                    );

                    this.applyFilters();
                },

                changeTab(selectedTab) {
                    this.tab = selectedTab;
                    this.applyFilters();

                    const url = new URL(window.location.href);

                    url.searchParams.set(
                        'tab',
                        selectedTab
                    );

                    window.history.replaceState(
                        {},
                        '',
                        url.toString()
                    );
                },

                applyFilters() {
                    const pendingVisibleCount = this.filterRows(
                        this.pendingRows,
                        document.getElementById(
                            'pendingTravelOrderRows'
                        )
                    );

                    const allVisibleCount = this.filterRows(
                        this.allRows,
                        document.getElementById(
                            'allTravelOrderRows'
                        )
                    );

                    this.pendingNoResults =
                        pendingVisibleCount === 0 &&
                        this.pendingRows.length > 0;

                    this.allNoResults =
                        allVisibleCount === 0 &&
                        this.allRows.length > 0;
                },

                filterRows(rows, tbody) {
                    if (!tbody) {
                        return 0;
                    }

                    const searchValue = this.search
                        .trim()
                        .toLowerCase();

                    let visibleCount = 0;

                    /*
                     * Always place newest filed records first.
                     */
                    rows.sort((firstRow, secondRow) => {
                        const firstFiled = Number(
                            firstRow.dataset.filed || 0
                        );

                        const secondFiled = Number(
                            secondRow.dataset.filed || 0
                        );

                        return secondFiled - firstFiled;
                    });

                    rows.forEach((row) => {
                        const filedDate = this.getFiledDate(row);

                        const filedYear = filedDate
                            ? String(filedDate.getFullYear())
                            : '';

                        const filedMonth = filedDate
                            ? String(filedDate.getMonth() + 1)
                            : '';

                        const rowSearch = (
                            row.dataset.search || ''
                        ).toLowerCase();

                        const matchesSearch =
                            !searchValue ||
                            rowSearch.includes(searchValue);

                        const matchesType =
                            !this.travelType ||
                            row.dataset.type === this.travelType;

                        const matchesYear =
                            !this.year ||
                            filedYear === String(this.year);

                        const matchesMonth =
                            !this.month ||
                            filedMonth === String(this.month);

                        const visible =
                            matchesSearch &&
                            matchesType &&
                            matchesYear &&
                            matchesMonth;

                        row.classList.toggle(
                            'hidden',
                            !visible
                        );

                        if (visible) {
                            visibleCount++;
                        }

                        tbody.appendChild(row);
                    });

                    return visibleCount;
                },

                getFiledDate(row) {
                    const rawFiledDate = row.dataset.filed;

                    if (!rawFiledDate) {
                        return null;
                    }

                    /*
                     * Handle a Unix timestamp.
                     */
                    if (/^\d+$/.test(rawFiledDate)) {
                        const timestamp = Number(rawFiledDate);

                        const milliseconds =
                            timestamp < 1000000000000
                                ? timestamp * 1000
                                : timestamp;

                        const date = new Date(milliseconds);

                        return Number.isNaN(date.getTime())
                            ? null
                            : date;
                    }

                    /*
                     * Handle a date string.
                     */
                    const date = new Date(rawFiledDate);

                    return Number.isNaN(date.getTime())
                        ? null
                        : date;
                },

                reset() {
                    this.search = '';
                    this.travelType = '';
                    this.year = String(currentYear);
                    this.month = '';

                    this.applyFilters();
                },

                openPreviewModal(payload) {
                    this.previewData = payload;
                    this.showRemarks = false;
                    this.previewOpen = true;

                    document.documentElement.classList.add(
                        'overflow-hidden'
                    );

                    document.body.classList.add(
                        'overflow-hidden'
                    );
                },

                closePreviewModal() {
                    this.previewOpen = false;
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

                openApproveModal(id, title) {
                    this.approveModalFormId = `travelApproveForm_${id}`;
                    this.approveModalTitle = title ? `ID: ${title}` : '';
                    this.approveModalDescription = approvalRole === 'recordofficer'
                        ? 'Approving will forward this request\nto Regional Director for final review.'
                        : (approvalRole === 'regionaldirector'
                            ? 'Approving will mark this as\nfully approved.'
                            : 'Approving will move this request to the next step\nor mark it as fully approved.');
                    this.approveModalOpen = true;

                    document.documentElement.classList.add('overflow-hidden');
                    document.body.classList.add('overflow-hidden');
                },

                closeApproveModal() {
                    this.approveModalOpen = false;
                    this.approveModalFormId = '';
                    this.approveModalTitle = '';
                    this.approveModalDescription = '';

                    document.documentElement.classList.remove('overflow-hidden');
                    document.body.classList.remove('overflow-hidden');
                },

                submitApproveForm() {
                    const form = document.getElementById(this.approveModalFormId);

                    if (form) {
                        form.submit();
                    }
                },

                openRejectModal(id, title) {
                    this.rejectModalFormId = `travelRejectForm_${id}`;
                    this.rejectModalTitle = title ? `ID: ${title}` : '';
                    this.rejectModalRemarks = '';
                    this.rejectModalOpen = true;

                    document.documentElement.classList.add('overflow-hidden');
                    document.body.classList.add('overflow-hidden');
                },

                closeRejectModal() {
                    this.rejectModalOpen = false;
                    this.rejectModalFormId = '';
                    this.rejectModalTitle = '';
                    this.rejectModalRemarks = '';

                    document.documentElement.classList.remove('overflow-hidden');
                    document.body.classList.remove('overflow-hidden');
                },

                submitRejectForm() {
                    const form = document.getElementById(this.rejectModalFormId);
                    const input = form?.querySelector('input[name="remarks"]');

                    if (input) {
                        input.value = this.rejectModalRemarks;
                    }

                    if (form) {
                        form.submit();
                    }
                },
            };
        }

        function openTravelPreviewModal(payloadJson) {
            const root = document.querySelector(
                '[x-data^="manageTravelAuthorities"]'
            );

            const component =
                window.Alpine?.$data?.(root) ||
                root?.__x?.$data;

            if (!component) {
                return;
            }

            component.openPreviewModal(
                JSON.parse(payloadJson)
            );
        }

        function openTravelApproveModal(id, title) {
            const root = document.querySelector(
                '[x-data^="manageTravelAuthorities"]'
            );

            const component =
                window.Alpine?.$data?.(root) ||
                root?.__x?.$data;

            if (!component) {
                return;
            }

            component.openApproveModal(id, title);
        }

        function submitTravelReject(button) {
            const id = button?.dataset?.travelId;
            const title = button?.dataset?.travelTitle || '';
            const root = document.querySelector(
                '[x-data^="manageTravelAuthorities"]'
            );

            const component =
                window.Alpine?.$data?.(root) ||
                root?.__x?.$data;

            if (!component) {
                return;
            }

            component.openRejectModal(
                id,
                title
            );
        }
    </script>
</x-app-layout>
