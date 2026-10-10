<x-app-layout>
    <x-slot name="title">{{ __('My Locator Slips') }}</x-slot>

    <div
        class="p-4 sm:p-6 lg:p-8"
        x-data="myLocatorSlipTable()"
        x-init="init()"
    >
        <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-end">
            <div class="flex items-center gap-2">
                <label for="locator_year" class="text-sm font-semibold text-gray-700">Year</label>
                @php
                    $currentYear = now()->year;
                @endphp
                <select
                    id="locator_year"
                    name="year"
                    x-model="year"
                    @change="submitFilters()"
                    class="h-10 w-28 rounded-lg border-gray-300 bg-white pl-4 pr-10 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    @foreach($availableYears as $yearOption)
                        <option value="{{ $yearOption }}">{{ $yearOption }}</option>
                    @endforeach
                </select>

                <button type="button" @click="$dispatch('open-create-locator-slip-modal')" class="inline-flex items-center justify-center rounded-xl bg-blue-900 px-4 py-2.5 text-xs font-black uppercase tracking-widest text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Locator Slip
                </button>
            </div>
        </div>

        <div class="mb-4 flex w-full flex-nowrap items-center gap-2 overflow-x-auto rounded-xl border border-gray-100 bg-gray-50/70 p-2">
            <div class="relative min-w-[260px] flex-1">
                <label for="my_locator_search" class="sr-only">{{ __('Search locator slip') }}</label>
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M21 21l-4.35-4.35M10.75 18.5a7.75 7.75 0 100-15.5 7.75 7.75 0 000 15.5z" />
                    </svg>
                </span>
                <input id="my_locator_search" type="search" name="search" x-model="search" @input.debounce.400ms="submitFilters()" placeholder="{{ __('Search date, type, status, or destination...') }}" class="block h-9 w-full rounded-lg border-gray-300 pl-10 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            </div>

            <select name="type" x-model="type" @change="submitFilters()" class="block h-9 w-40 shrink-0 rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('Types') }}</option>
                @foreach(['Official Business', 'Pass Slip'] as $typeOption)
                    <option value="{{ $typeOption }}">{{ $typeOption }}</option>
                @endforeach
            </select>

            <select name="status" x-model="status" @change="submitFilters()" class="block h-9 w-40 shrink-0 rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('Status') }}</option>
                <option value="pending">{{ __('Pending') }}</option>
                <option value="approved">{{ __('Approved') }}</option>
                <option value="rejected">{{ __('Rejected') }}</option>
            </select>

            <button type="button" @click="reset()" class="inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-xs font-bold uppercase tracking-wider text-gray-700 transition hover:bg-gray-50">
                {{ __('Reset') }}
            </button>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white/80 shadow-lg backdrop-blur-xl">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                     <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="whitespace-nowrap px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date Covered</th>
                            <th scope="col" class="whitespace-nowrap px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                            <th scope="col" class="whitespace-nowrap px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Destination</th>
                            <th scope="col" class="whitespace-nowrap px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Approval</th>
                            <th scope="col" class="whitespace-nowrap px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th scope="col" class="whitespace-nowrap px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="myLocatorSlipTableBody" class="divide-y divide-gray-100 bg-white">
                        @forelse($locatorSlips as $slip)
                            @php
                                $displayStatus = strtolower($slip->status) === 'approved by chief' ? 'approved' : strtolower($slip->status);
                                $approvalStatus = $displayStatus === 'approved' ? 'approved' : ($displayStatus === 'rejected' ? 'rejected' : 'pending');
                                $displayType = ($slip->type ?? '') === 'Personal' ? 'Pass Slip' : ($slip->type ?? '');
                                $searchText = Str::lower(($slip->destination ?? '') . ' ' . $displayType . ' ' . $displayStatus . ' ' . \Carbon\Carbon::parse($slip->date_covered)->format('M d, Y'));
                                $statusClass = match($displayStatus) {
                                    'approved' => 'bg-[#00c950] text-white',
                                    'rejected' => 'bg-red-500 text-white',
                                    'pending' => 'border border-orange-100 bg-orange-50 text-orange-700',
                                    default => 'bg-gray-400 text-white',
                                };
                                $approvalDotClass = match($approvalStatus) {
                                    'approved' => 'bg-green-500',
                                    'rejected' => 'bg-red-500',
                                    default => 'bg-gray-300',
                                };
                                $divisionChief = $slip->employee?->division ? \App\Models\Employee::where('division', $slip->employee->division)->whereIn('account_role', ['chief', 'CHIEF'])->first() : null;
                                $chiefName = $divisionChief ? trim($divisionChief->firstname . ' ' . $divisionChief->lastname) : null;
                                $approverName = ($slip->approved_by_chief_name && strtolower($slip->approved_by_chief_name) !== 'chief user')
                                     ? $slip->approved_by_chief_name
                                     : ($chiefName ?: ($slip->recommendingApproval?->name ?: ($slip->approvedBy?->name ?: ($displayStatus === 'pending' ? 'Pending Approval' : 'N/A'))));
                                $previewPayload = [
                                    'title' => $displayType ?: 'Locator Slip',
                                    'date' => \Carbon\Carbon::parse($slip->date_covered)->format('M d, Y'),
                                    'status' => $displayStatus,
                                    'remarks' => $slip->chief_remarks ?: 'No remarks available.',
                                    'printUrl' => route('locator-slips.print', ['locatorSlip' => $slip->id, 'preview' => 1]),
                                    'directPrintUrl' => route('locator-slips.print', $slip->id),
                                ];
                                $qrPayload = [
                                    'title' => $displayType ?: 'Locator Slip',
                                    'employee' => trim(($slip->employee?->firstname ?? '') . ' ' . ($slip->employee?->lastname ?? '')),
                                    'date' => \Carbon\Carbon::parse($slip->date_covered)->format('M d, Y'),
                                    'svg' => base64_encode((string) $slip->qr_svg),
                                ];
                            @endphp
                            <tr
                                class="transition-colors duration-150 hover:bg-gray-50"
                                data-locator-row
                                data-search="{{ $searchText }}"
                                data-type="{{ Str::lower($displayType) }}"
                                data-status="{{ $displayStatus }}"
                                data-year="{{ \Carbon\Carbon::parse($slip->date_covered)->year }}"
                            >
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-gray-900">
                                    {{ \Carbon\Carbon::parse($slip->date_covered)->format('M d, Y') }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                    {{ $displayType ?: 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <div class="max-w-xs truncate" title="{{ $slip->destination }}">
                                        {{ $slip->destination ?: 'N/A' }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full {{ $approvalDotClass }}" title="Chief"></span>
                                        <span class="text-xs font-semibold text-gray-700">{{ $approverName }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span class="inline-flex rounded-full px-4 py-1 text-xs font-semibold leading-5 shadow-sm {{ $statusClass }}">
                                        {{ ucfirst($displayStatus) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        @if($slip->qr_svg)
                                            <button
                                                type="button"
                                                @click="openQrModal(@js($qrPayload))"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-emerald-700 transition-colors hover:bg-emerald-50 hover:text-emerald-900"
                                                title="{{ __('QR code') }}"
                                                aria-label="{{ __('QR code') }}"
                                            >
                                                <i class="fa-solid fa-qrcode"></i>
                                            </button>
                                        @endif

                                        <a
                                            href="{{ route('locator-slips.show', $slip) }}"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-blue-700 transition-colors hover:bg-blue-50 hover:text-blue-900"
                                            title="{{ __('View details') }}"
                                            aria-label="{{ __('View details') }}"
                                        >
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-500">
                                    No locator slips found.
                                </td>
                            </tr>
                        @endforelse

                        @if($locatorSlips->isNotEmpty())
                            <tr x-show="noResults" style="display: none;">
                                <td colspan="6" class="py-8 text-center text-gray-500">
                                    {{ __('No locator slips match your search or filter.') }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- Pagination temporarily disabled until the locator slip pagination flow is revisited. --}}
        </div>

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
                {{-- Preview Header --}}
                <div class="flex shrink-0 items-center justify-between border-b border-blue-950 bg-blue-900 px-4 py-2.5 sm:px-5">
                    <div class="min-w-0">
                        <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-blue-200">
                            Locator Slip Preview
                        </p>

                        <div class="mt-0.5 flex min-w-0 flex-wrap items-center gap-x-2 gap-y-0.5">
                            <h2 class="truncate text-sm font-bold text-white" x-text="previewData.title"></h2>

                            <span class="hidden text-blue-300 sm:inline">&bull;</span>

                            <p class="text-[10px] font-medium text-blue-100">
                                <span x-text="previewData.date"></span>
                                <span class="mx-1">|</span>
                                <span class="capitalize" x-text="previewData.status"></span>
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="closePreviewModal()"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-white transition hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-white/70"
                        aria-label="{{ __('Close preview') }}"
                        title="{{ __('Close preview') }}"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Preview Document --}}
                <div class="min-h-0 flex-1 overflow-auto bg-slate-100 p-3">
                    <div class="mx-auto h-full max-w-[390px] overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm">
                        <iframe
                            x-ref="previewFrame"
                            :src="previewData.printUrl"
                            class="h-full min-h-[430px] w-full border-0 bg-white"
                            title="Locator Slip Print Preview"
                        ></iframe>
                    </div>
                </div>

                {{-- Preview Footer --}}
                <div class="flex shrink-0 flex-col gap-2 border-t border-slate-200 bg-white px-4 py-2.5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <button
                            type="button"
                            @click="toggleRemarks()"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2 sm:w-auto"
                        >
                            
                            <span x-text="showRemarks ? 'Hide Remarks' : 'Remarks'"></span>
                        </button>

                        <div class="flex shrink-0 gap-2 sm:justify-end">
                        <button
                            type="button"
                            @click="closePreviewModal()"
                            class="inline-flex flex-1 items-center justify-center rounded-lg border border-red-300 bg-white px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2 sm:flex-none"
                        >
                            Cancel
                        </button>

                        <a
                            :href="previewData.directPrintUrl"
                            target="_blank"
                            data-no-transition
                            class="inline-flex flex-1 items-center justify-center rounded-lg bg-blue-900 px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:flex-none"
                        >
                            Print
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

        {{-- Locator Slip QR modal --}}
        <template x-teleport="body">
            <div
                x-show="qrModalOpen"
                x-cloak
                class="fixed inset-0 flex items-center justify-center p-4 sm:p-6"
                style="
                    display: none;
                    z-index: 99999;
                    isolation: isolate;
                "
                @keydown.escape.window="qrModalOpen && closeQrModal()"
                role="dialog"
                aria-modal="true"
                aria-labelledby="locator-qr-modal-title"
            >
                {{-- Same overlay as Leave and Travel Authority --}}
                <div
                    class="absolute inset-0"
                    style="
                        z-index: 0;
                        background-color: rgba(15, 23, 42, 0.62);
                    "
                    @click="closeQrModal()"
                    aria-hidden="true"
                ></div>

                {{-- QR modal card --}}
                <div
                    class="relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                    style="
                        z-index: 10;
                        width: min(92vw, 620px);
                        height: auto;
                        max-height: calc(100dvh - 40px);
                    "
                    @click.stop
                >
                    <div class="flex shrink-0 items-center justify-between bg-blue-900 px-5 py-4 text-white">
                        <div class="min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-[0.20em] text-blue-200">
                                {{ __('Locator Slip QR') }}
                            </p>

                            <h2
                                id="locator-qr-modal-title"
                                class="mt-1 truncate text-xl font-black tracking-tight"
                                x-text="qrData.title"
                            ></h2>
                        </div>

                        <button
                            type="button"
                            @click="closeQrModal()"
                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-white transition hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-white/70"
                            aria-label="{{ __('Close QR modal') }}"
                            title="{{ __('Close') }}"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-hidden bg-white px-5 py-4">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="min-w-0 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-400">
                                    {{ __('Employee') }}
                                </p>
                                <p class="mt-1 truncate text-sm font-bold text-slate-800" x-text="qrData.employee || 'N/A'"></p>
                            </div>

                            <div class="min-w-0 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-400">
                                    {{ __('Date Covered') }}
                                </p>
                                <p class="mt-1 text-sm font-bold text-slate-800" x-text="qrData.date || 'N/A'"></p>
                            </div>
                        </div>

                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3 text-center">
                            <div
                                class="qr-code-wrapper mx-auto flex items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-white p-3 shadow-sm"
                                style="width: min(100%, 300px, 44dvh); aspect-ratio: 1 / 1;"
                            >
                                <div
                                    class="qr-code-content flex h-full w-full items-center justify-center overflow-hidden"
                                    x-html="qrData.svg"
                                ></div>
                            </div>

                            <p class="mx-auto mt-2 max-w-md text-xs font-medium leading-5 text-slate-500">
                                {{ __('Scan once to record OUT time, then scan again to record IN time.') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex shrink-0 justify-end border-t border-slate-200 bg-white px-5 py-3">
                        <button
                            type="button"
                            @click="closeQrModal()"
                            class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                        >
                            {{ __('Close') }}
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <style>
        .qr-code-content svg,
        .qr-code-content img {
            display: block !important;
            width: 100% !important;
            height: 100% !important;
            max-width: 100% !important;
            max-height: 100% !important;
            object-fit: contain;
        }

        @media (max-width: 640px) {
            .qr-code-wrapper {
                max-width: 300px;
            }
        }
    </style>

    <script>
        function myLocatorSlipTable() {
            return {
                search: @js($search),
                type: @js($type),
                status: @js($status),
                year: @js((string) $year),
                rows: [],
                noResults: false,
                qrModalOpen: false,
                previewModalOpen: false,
                showRemarks: false,
                qrData: {
                    title: '',
                    employee: '',
                    date: '',
                    out: '',
                    in: '',
                    url: '',
                    svg: '',
                },
                previewData: {
                    title: '',
                    date: '',
                    status: '',
                    remarks: '',
                    printUrl: '',
                    directPrintUrl: '',
                },

                init() {
                    this.rows = Array.from(document.querySelectorAll('[data-locator-row]'));
                },

                submitFilters() {
                    const url = new URL(@js(route('locator-slips.index')));

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
                    this.year = '{{ now()->year }}';
                    window.location.href = @js(route('locator-slips.index'));
                },

                openQrModal(payload) {
                    this.qrData = {
                        ...payload,
                        svg: payload.svg ? atob(payload.svg) : '',
                    };
                    this.qrModalOpen = true;
                    document.documentElement.classList.add('overflow-hidden');
                    document.body.classList.add('overflow-hidden');
                },

                closeQrModal() {
                    this.qrModalOpen = false;
                    document.documentElement.classList.remove('overflow-hidden');
                    document.body.classList.remove('overflow-hidden');
                },

                openPreviewModal(payload) {
                    this.previewData = payload;
                    this.showRemarks = false;
                    this.previewModalOpen = true;
                    document.documentElement.classList.add('overflow-hidden');
                    document.body.classList.add('overflow-hidden');
                    this.$nextTick(() => {
                        if (this.$refs.previewFrame) {
                            this.$refs.previewFrame.src = payload.printUrl;
                        }
                    });
                },

                closePreviewModal() {
                    this.previewModalOpen = false;
                    this.showRemarks = false;
                    if (!this.qrModalOpen) {
                        document.documentElement.classList.remove('overflow-hidden');
                        document.body.classList.remove('overflow-hidden');
                    }
                },

                toggleRemarks() {
                    this.showRemarks = !this.showRemarks;
                },
            };
        }
    </script>
</x-app-layout>

@include('locator-slip.create')
