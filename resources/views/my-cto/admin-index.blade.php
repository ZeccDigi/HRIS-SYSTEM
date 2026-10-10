<x-app-layout>
    <x-slot name="title">{{ __('Manage Compensatory Time-Off') }}</x-slot>

    @php
        $currentYear = now()->year;
        $approvalRole = strtolower(auth()->user()->role ?? '');
    @endphp

    <div
        class="py-8"
        x-data='manageCtoRequests(@json($tab), @json($currentYear))'
        x-init="init()"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

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
                    for="ctoYear"
                    class="text-sm font-semibold text-gray-700"
                >
                    {{ __('Year') }}
                </label>

                <select
                    id="ctoYear"
                    x-model="year"
                    @change="applyFilters()"
                    class="h-10 w-28 rounded-lg border-gray-300 bg-white pl-4 pr-10 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    @for(
                        $yearOption = $currentYear;
                        $yearOption >= $currentYear - 5;
                        $yearOption--
                    )
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
                    aria-label="{{ __('CTO tabs') }}"
                >
                    <button
                        type="button"
                        @click="changeTab('pending')"
                        class="shrink-0 border-b-2 px-1 pb-3 pt-1 text-sm font-bold transition"
                        :class="tab === 'pending'
                            ? 'border-indigo-600 text-indigo-700'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                    >
                        {{ __('Pending CTO') }}
                    </button>

                    <button
                        type="button"
                        @click="changeTab('all')"
                        class="shrink-0 border-b-2 px-1 pb-3 pt-1 text-sm font-bold transition"
                        :class="tab === 'all'
                            ? 'border-indigo-600 text-indigo-700'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                    >
                        {{ __('All CTO') }}
                    </button>
                </nav>
            </div>

            {{-- Search and filters --}}
            <div class="mb-4 flex min-w-0 flex-col gap-2 rounded-xl border border-gray-100 bg-gray-50/70 p-2 sm:flex-row sm:items-center">

                {{-- Search --}}
                <div class="relative min-w-0 sm:flex-1">
                    <label
                        for="ctoSearch"
                        class="sr-only"
                    >
                        {{ __('Search CTO request') }}
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
                        id="ctoSearch"
                        type="search"
                        x-model="search"
                        @input.debounce.200ms="applyFilters()"
                        placeholder="{{ __('Search employee, type, status, or approver...') }}"
                        class="block h-9 w-full rounded-lg border-gray-300 pl-10 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>

                {{-- CTO type --}}
                <div class="sm:w-44 sm:shrink-0">
                    <label
                        for="ctoType"
                        class="sr-only"
                    >
                        {{ __('CTO Type') }}
                    </label>

                    <select
                        id="ctoType"
                        x-model="type"
                        @change="applyFilters()"
                        class="block h-9 w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">
                            {{ __('Types') }}
                        </option>

                        <option value="earn">
                            {{ __('Earn CTO') }}
                        </option>

                        <option value="use">
                            {{ __('Use CTO') }}
                        </option>
                    </select>
                </div>

                {{-- Status filter --}}
                <div class="sm:w-44 sm:shrink-0">
                    <label
                        for="ctoStatus"
                        class="sr-only"
                    >
                        {{ __('Status') }}
                    </label>

                    <select
                        id="ctoStatus"
                        x-model="status"
                        @change="applyFilters()"
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

            {{-- Pending CTO table --}}
            <div
                x-show="tab === 'pending'"
                x-cloak
            >
                <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                    <div class="w-full overflow-x-auto">
                        <table class="w-full table-fixed divide-y divide-gray-100">
                            <colgroup>
                                <col class="w-[11%]">
                                <col class="w-[19%]">
                                <col class="w-[11%]">
                                <col class="w-[11%]">
                                <col class="w-[13%]">
                                <col class="w-[13%]">
                                <col class="w-[13%]">
                                <col class="w-[9%]">
                            </colgroup>

                            <thead class="bg-gray-50/80">
                                <tr>
                                    <th
                                        scope="col"
                                        class="break-words whitespace-normal px-2 py-3 text-left text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                    >
                                        {{ __('Date Filed') }}
                                    </th>

                                    <th
                                        scope="col"
                                        class="break-words whitespace-normal px-2 py-3 text-left text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                    >
                                        {{ __('Employee') }}
                                    </th>

                                    <th
                                        scope="col"
                                        class="break-words whitespace-normal px-2 py-3 text-left text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                    >
                                        {{ __('Type') }}
                                    </th>

                                    <th
                                        scope="col"
                                        class="break-words whitespace-normal px-2 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                    >
                                        {{ __('Status') }}
                                    </th>

                                    <th
                                        scope="col"
                                        class="break-words whitespace-normal px-2 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                    >
                                        {{ __('Human Resource') }}
                                    </th>

                                    <th
                                        scope="col"
                                        class="break-words whitespace-normal px-2 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                    >
                                        {{ __('Chief') }}
                                    </th>

                                    <th
                                        scope="col"
                                        class="break-words whitespace-normal px-2 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                    >
                                        {{ __('Regional Director') }}
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
                                id="pendingCtoRows"
                                class="divide-y divide-gray-100 bg-white"
                            >
                                @include('my-cto._admin-rows', [
                                    'requests' => $pendingCtoRequests,
                                    'emptyMessage' => __('No pending CTO requests.'),
                                    'actionMode' => strtolower(auth()->user()->role ?? '') === 'admin' ? 'view' : 'review',
                                ])

                                @if($pendingCtoRequests->isNotEmpty())
                                    <tr
                                        x-show="pendingNoResults"
                                        x-cloak
                                    >
                                        <td
                                            colspan="8"
                                            class="px-4 py-10 text-center text-sm font-medium italic text-gray-500"
                                        >
                                            {{ __('No pending CTO requests match your current filters.') }}
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- All CTO table --}}
            <div
                x-show="tab === 'all'"
                x-cloak
            >
                <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                    <div class="w-full overflow-x-auto">
                        <table class="w-full table-fixed divide-y divide-gray-100">
                            <colgroup>
                                <col class="w-[11%]">
                                <col class="w-[19%]">
                                <col class="w-[11%]">
                                <col class="w-[11%]">
                                <col class="w-[13%]">
                                <col class="w-[13%]">
                                <col class="w-[13%]">
                                <col class="w-[9%]">
                            </colgroup>

                            <thead class="bg-gray-50/80">
                                <tr>
                                    <th
                                        scope="col"
                                        class="break-words whitespace-normal px-2 py-3 text-left text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                    >
                                        {{ __('Date Filed') }}
                                    </th>

                                    <th
                                        scope="col"
                                        class="break-words whitespace-normal px-2 py-3 text-left text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                    >
                                        {{ __('Employee') }}
                                    </th>

                                    <th
                                        scope="col"
                                        class="break-words whitespace-normal px-2 py-3 text-left text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                    >
                                        {{ __('Type') }}
                                    </th>

                                    <th
                                        scope="col"
                                        class="break-words whitespace-normal px-2 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                    >
                                        {{ __('Status') }}
                                    </th>

                                    <th
                                        scope="col"
                                        class="break-words whitespace-normal px-2 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                    >
                                        {{ __('Human Resource') }}
                                    </th>

                                    <th
                                        scope="col"
                                        class="break-words whitespace-normal px-2 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                    >
                                        {{ __('Chief') }}
                                    </th>

                                    <th
                                        scope="col"
                                        class="break-words whitespace-normal px-2 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500"
                                    >
                                        {{ __('Regional Director') }}
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
                                id="allCtoRows"
                                class="divide-y divide-gray-100 bg-white"
                            >
                                @include('my-cto._admin-rows', [
                                    'requests' => $allCtoRequests,
                                    'emptyMessage' => __('No approved or rejected CTO requests yet.'),
                                    'actionMode' => 'view',
                                ])

                                @if($allCtoRequests->isNotEmpty())
                                    <tr
                                        x-show="allNoResults"
                                        x-cloak
                                    >
                                        <td
                                            colspan="8"
                                            class="px-4 py-10 text-center text-sm font-medium italic text-gray-500"
                                        >
                                            {{ __('No CTO requests match your current filters.') }}
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Approval confirmation modal --}}
        <template x-teleport="body">
            <div
                x-show="approveModalOpen"
                x-cloak
                class="fixed inset-0 z-[100000] flex items-center justify-center p-4"
                style="display: none;"
                @keydown.escape.window="closeApproveModal()"
            >
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeApproveModal()"></div>

                <div @click.stop class="relative z-10 w-full max-w-xs overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <h2 class="text-base font-black text-slate-900" x-text="approveModalHeading"></h2>
                        <p class="mt-0.5 text-xs text-slate-500" x-text="approveModalTitle"></p>
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
                            x-text="approveModalAction"
                        ></button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Rejection confirmation modal --}}
        <template x-teleport="body">
            <div
                x-show="rejectModalOpen"
                x-cloak
                class="fixed inset-0 z-[100000] flex items-center justify-center p-4"
                style="display: none;"
                @keydown.escape.window="closeRejectModal()"
            >
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeRejectModal()"></div>

                <div @click.stop class="relative z-10 w-full max-w-xs overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <h2 class="text-base font-black text-slate-900">{{ __('Reject this CTO request?') }}</h2>
                        <p class="mt-0.5 text-xs text-slate-500" x-text="rejectModalTitle"></p>
                    </div>

                    <div class="px-4 py-3">
                        <label
                            for="ctoRejectModalRemarks"
                            class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500"
                        >
                            {{ __('Remarks / Notes') }}
                        </label>

                        <textarea
                            id="ctoRejectModalRemarks"
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

        {{-- Preview modal --}}
        <template x-teleport="body">
            <div
                x-show="previewModalOpen"
                x-cloak
                class="fixed inset-0 z-[100000] flex items-center justify-center p-5 sm:p-8"
                style="display: none;"
                @keydown.escape.window="closePreviewModal()"
            >
                {{-- Backdrop --}}
                <div
                    class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                    @click="closePreviewModal()"
                ></div>

                {{-- Modal --}}
                <div
                    class="relative z-10 flex w-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                    style="width: min(96vw, 1120px); height: min(92vh, 860px); max-height: calc(100dvh - 32px);"
                    @click.stop
                >
                    {{-- Header --}}
                    <div class="flex shrink-0 items-center justify-between border-b border-blue-950 bg-blue-900 px-4 py-2.5 sm:px-5">
                        <div class="min-w-0">
                            <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-blue-200">
                                {{ __('CTO Form Preview') }}
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

                    {{-- Document --}}
                    <div class="min-h-0 flex-1 overflow-auto bg-slate-100 p-3">
                        <div class="mx-auto h-full max-w-[820px] overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm">
                            <iframe
                                x-ref="previewFrame"
                                :src="previewData.printUrl"
                                class="h-full min-h-[620px] w-full border-0 bg-white"
                                title="{{ __('CTO Print Preview') }}"
                            ></iframe>
                        </div>
                    </div>

                    {{-- Footer --}}
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
                                x-text="previewData.remarks || 'No remarks available.'"
                            ></div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <script>
        function manageCtoRequests(initialTab, currentYear) {
            const approvalRole = @js($approvalRole);

            return {
                tab: initialTab || 'pending',

                search: '',
                type: '',
                status: '',
                year: String(currentYear),

                pendingRows: [],
                allRows: [],

                pendingNoResults: false,
                allNoResults: false,

                previewModalOpen: false,
                showRemarks: false,
                approveModalOpen: false,
                approveModalFormId: '',
                approveModalHeading: '',
                approveModalTitle: '',
                approveModalDescription: '',
                approveModalAction: '',
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
                            '[data-manage-cto-row="pending"]'
                        )
                    );

                    this.allRows = Array.from(
                        document.querySelectorAll(
                            '[data-manage-cto-row="all"]'
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
                        document.getElementById('pendingCtoRows')
                    );

                    const allVisibleCount = this.filterRows(
                        this.allRows,
                        document.getElementById('allCtoRows')
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
                     * Newest CTO requests are always displayed first.
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

                        const rowSearch = (
                            row.dataset.search || ''
                        ).toLowerCase();

                        const rowType = (
                            row.dataset.type || ''
                        ).toLowerCase();

                        const rowStatus = (
                            row.dataset.status || ''
                        ).toLowerCase();

                        const matchesSearch =
                            !searchValue ||
                            rowSearch.includes(searchValue);

                        const matchesType =
                            !this.type ||
                            rowType === String(this.type).toLowerCase();

                        const matchesStatus =
                            !this.status ||
                            rowStatus === String(this.status).toLowerCase();

                        const matchesYear =
                            !this.year ||
                            filedYear === String(this.year);

                        const visible =
                            matchesSearch &&
                            matchesType &&
                            matchesStatus &&
                            matchesYear;

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
                     * Handle Unix timestamps.
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
                     * Handle normal date strings.
                     */
                    const date = new Date(rawFiledDate);

                    return Number.isNaN(date.getTime())
                        ? null
                        : date;
                },

                reset() {
                    this.search = '';
                    this.type = '';
                    this.status = '';
                    this.year = String(currentYear);

                    this.applyFilters();
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

                openApproveModal(id, title, isHR) {
                    this.approveModalFormId = `ctoApproveRowForm_${id}`;
                    this.approveModalHeading = isHR
                        ? 'Verify this CTO request?'
                        : 'Approve this CTO request?';
                    this.approveModalTitle = title ? `ID: ${title}` : '';
                    this.approveModalAction = isHR ? 'Verify' : 'Approve';
                    this.approveModalDescription = isHR
                        ? 'Verifying will forward this request\nto the next approval stage.'
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
                    this.approveModalHeading = '';
                    this.approveModalTitle = '';
                    this.approveModalDescription = '';
                    this.approveModalAction = '';

                    document.documentElement.classList.remove('overflow-hidden');
                    document.body.classList.remove('overflow-hidden');
                },

                submitApproveForm() {
                    const form = document.getElementById(this.approveModalFormId);

                    if (form) {
                        form.submit();
                    }
                },

                openRejectModal(id, title, isHR) {
                    this.rejectModalFormId = `ctoRejectRowForm_${id}`;
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

                    if (!form) {
                        return;
                    }

                    const remarksInput = form.querySelector('input[name="remarks"]');

                    if (remarksInput) {
                        remarksInput.value = this.rejectModalRemarks.trim();
                    }

                    form.submit();
                },
            };
        }

        function openCtoPreviewModal(payloadJson) {
            const root = document.querySelector('[x-data^="manageCtoRequests"]');
            const manager = window.getCtoManagerData
                ? window.getCtoManagerData()
                : (window.Alpine && typeof window.Alpine.$data === 'function'
                    ? window.Alpine.$data(root)
                    : root?.__x?.$data || root?._x_dataStack?.[0] || null);

            if (!manager) {
                return;
            }

            manager.openPreviewModal(JSON.parse(payloadJson));
        }
    </script>
</x-app-layout>
