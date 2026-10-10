<x-app-layout>
    <x-slot name="title">{{ __('Leave Records History') }}</x-slot>

    @php
        $selectedLeaveTypeId = request(
            'leave_type_id',
            $leaveTypeId ?? ''
        );
    @endphp

    <div
        class="py-12"
        x-data="leaveApplicationPreviewPage()"
    >
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            {{-- Management Tabs --}}
            @include('leaves._manage-tabs')

            {{-- Filters --}}
            <form
                id="leaveApplicationFilterForm"
                method="GET"
                action="{{ route('leave-applications.all') }}"
                data-filter-url="{{ route('leave-applications.all.filter') }}"
                class="mb-4 flex min-w-0 flex-col gap-2 rounded-xl border border-gray-100 bg-gray-50/70 p-2 sm:flex-row sm:items-center"
            >
                {{-- Search --}}
                <div class="relative min-w-0 sm:flex-1">
                    <label
                        for="search"
                        class="sr-only"
                    >
                        {{ __('Search leave application') }}
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
                        id="search"
                        name="search"
                        type="search"
                        value="{{ $search ?? request('search') }}"
                        placeholder="{{ __('Search employee or leave type...') }}"
                        class="block h-9 w-full rounded-lg border-gray-300 pl-10 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>

                {{-- Status Filter --}}
                <div class="sm:w-44 sm:shrink-0">
                    <label
                        for="status"
                        class="sr-only"
                    >
                        {{ __('Status') }}
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="block h-9 w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">
                            {{ __('All Statuses') }}
                        </option>

                        @foreach([
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            'cancelled' => 'Cancelled',
                        ] as $value => $label)
                            <option
                                value="{{ $value }}"
                                {{ ($status ?? request('status')) === $value ? 'selected' : '' }}
                            >
                                {{ __($label) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Leave Type Filter --}}
                <div class="sm:w-48 sm:shrink-0">
                    <label
                        for="leave_type_id"
                        class="sr-only"
                    >
                        {{ __('Leave Types') }}
                    </label>

                    <select
                        id="leave_type_id"
                        name="leave_type_id"
                        class="block h-9 w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">
                            {{ __('Leave Types') }}
                        </option>

                        @foreach(($leaveTypes ?? collect()) as $leaveType)
                            <option
                                value="{{ $leaveType->id }}"
                                {{ (string) $selectedLeaveTypeId === (string) $leaveType->id ? 'selected' : '' }}
                            >
                                {{ $leaveType->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Reset Button --}}
                <div class="flex items-center gap-2 sm:shrink-0">
                    <a
                        href="{{ route('leave-applications.all') }}"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-xs font-bold uppercase tracking-wider text-gray-700 transition hover:bg-gray-50"
                    >
                        {{ __('Reset') }}
                    </a>
                </div>
            </form>

            {{-- Leave Records Table --}}
            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="w-full min-w-0">
                    <table class="w-full table-fixed divide-y divide-gray-100">
                        <colgroup>
                            <col class="w-[9%]">
                            <col class="w-[15%]">
                            <col class="w-[14%]">
                            <col class="w-[13%]">
                            <col class="w-[14%]">
                            <col class="w-[13%]">
                            <col class="w-[10%]">
                            <col class="w-[12%]">
                        </colgroup>
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="break-words whitespace-normal px-2 py-3 text-left text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500">
                                    {{ __('Date Filed') }}
                                </th>

                                <th class="break-words whitespace-normal px-2 py-3 text-left text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500">
                                    {{ __('Name') }}
                                </th>

                                <th class="break-words whitespace-normal px-2 py-3 text-left text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500">
                                    {{ __('Leave Type') }}
                                </th>

                                <th class="break-words whitespace-normal px-2 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500">
                                    {{ __('Leave Certification') }}
                                </th>

                                <th class="break-words whitespace-normal px-2 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500">
                                    {{ __('Recommending Approval') }}
                                </th>

                                <th class="break-words whitespace-normal px-2 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500">
                                    {{ __('Approval') }}
                                </th>

                                {{-- Status is immediately before Actions --}}
                                <th class="break-words whitespace-normal px-2 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500">
    {{ __('Status') }}
</th>


                                <th class="break-words whitespace-normal px-1 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500">
    {{ __('Actions') }}
</th>
                            </tr>
                        </thead>

                        <tbody
                            id="leaveApplicationTableBody"
                            class="divide-y divide-gray-100 bg-white"
                        >
                            @include('leaves.applications._rows', [
                                'leaves' => $leaves,
                                'actionMode' => 'view',
                                'emptyMessage' => __('No leave records found.'),
                            ])
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Leave Preview Modal --}}
        <template x-teleport="body">
            <div
                x-show="previewModalOpen"
                x-cloak
                class="fixed inset-0 z-[100000] flex items-center justify-center p-5 sm:p-8"
                style="display: none;"
                @keydown.escape.window="closePreviewModal()"
            >
                {{-- Modal Backdrop --}}
                <div
                    class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                    @click="closePreviewModal()"
                ></div>

                {{-- Modal Container --}}
                <div
                    class="relative z-10 flex w-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                    style="width: min(96vw, 1120px); height: min(92vh, 860px); max-height: calc(100dvh - 32px);"
                    @click.stop
                >
                    {{-- Modal Header --}}
                    <div class="flex shrink-0 items-center justify-between border-b border-blue-950 bg-blue-900 px-4 py-2.5 sm:px-5">
                        <div class="min-w-0">
                            <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-blue-200">
                                {{ __('Leave Form Preview') }}
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

                                    <span class="mx-1">
                                        |
                                    </span>

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

                    {{-- Preview Document --}}
                    <div class="min-h-0 flex-1 overflow-auto bg-slate-100 p-3">
                        <div class="mx-auto h-full max-w-[820px] overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm">
                            <iframe
                                x-ref="previewFrame"
                                :src="previewData.printUrl"
                                class="h-full min-h-[620px] w-full border-0 bg-white"
                                title="{{ __('Leave Print Preview') }}"
                            ></iframe>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
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

                                <span
                                    x-text="showRemarks
                                        ? 'Hide Remarks'
                                        : 'Remarks'"
                                ></span>
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
                                x-text="previewData.remarks"
                            ></div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Filter Script --}}
    @include('leaves.applications._filter-script')

    <script>
        function leaveApplicationPreviewPage() {
            return {
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

        function openLeavePreviewModal(payloadJson) {
            const root = document.querySelector(
                '[x-data="leaveApplicationPreviewPage()"]'
            );

            if (!root || !root.__x) {
                return;
            }

            root.__x.$data.openPreviewModal(
                JSON.parse(payloadJson)
            );
        }
    </script>
</x-app-layout>
