<x-app-layout>
    <x-slot name="title">{{ __('My Travel Authorities') }}</x-slot>

    @php
        $currentYear = now()->year;
    @endphp

    <div class="py-10" x-data="travelAuthorityPage()" x-init="init()">
        <div class="mx-auto w-full max-w-[95rem] px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-xl border-l-4 border-green-500 bg-green-100 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 rounded-xl border-l-4 border-red-500 bg-red-100 px-4 py-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-end">
                

                <div class="flex items-center gap-2">
                    <label for="travel_year" class="text-sm font-semibold text-gray-700">{{ __('Year') }}</label>
                    <select id="travel_year" x-model="year" @change="applyFilters()" class="h-10 w-28 rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @for ($yearOption = $currentYear; $yearOption >= $currentYear - 4; $yearOption--)
                            <option value="{{ $yearOption }}">{{ $yearOption }}</option>
                        @endfor
                    </select>

                    <button type="button" @click="$dispatch('open-create-request-modal', { url: @js(route('travel-orders.create', ['modal' => 1])), title: @js(__('Travel Authority')) })" class="inline-flex items-center justify-center rounded-xl bg-blue-900 px-4 py-2.5 text-xs font-black uppercase tracking-widest text-white transition hover:bg-blue-800">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ __('Apply Travel Authority') }}
                    </button>
                </div>
            </div>

            {{-- Tabs styled like the Leave blade --}}
            <div class="mb-6 border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button
                        type="button"
                        @click="tab = 'my'; applyFilters()"
                        :class="tab === 'my'
                            ? 'border-blue-500 text-blue-600'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                        class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-bold uppercase tracking-widest transition-colors duration-200"
                    >
                        {{ __('My TA') }}
                    </button>
                    <button
                        type="button"
                        @click="tab = 'tagged'; applyFilters()"
                        :class="tab === 'tagged'
                            ? 'border-blue-500 text-blue-600'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                        class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-bold uppercase tracking-widest transition-colors duration-200"
                    >
                        {{ __('Tagged') }}
                    </button>
                </nav>
            </div>

            {{-- Search and filters kept on one line --}}
            <div class="mb-4 flex w-full flex-nowrap items-center gap-2 overflow-x-auto rounded-xl border border-gray-100 bg-gray-50/70 p-2">
                <div class="relative min-w-[280px] flex-1">
                    <label for="travel_authority_search" class="sr-only">{{ __('Search travel authorities') }}</label>
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M21 21l-4.35-4.35M10.75 18.5a7.75 7.75 0 100-15.5 7.75 7.75 0 000 15.5z" />
                        </svg>
                    </span>
                    <input id="travel_authority_search" type="search" x-model="search" @input.debounce.200ms="applyFilters()" placeholder="{{ __('Search TA no., destination, or approver...') }}" class="block h-9 w-full rounded-lg border-gray-300 pl-10 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>

                <div class="w-44 shrink-0">
                    <label for="travel_authority_status" class="sr-only">{{ __('Filter by status') }}</label>
                    <select id="travel_authority_status" x-model="status" @change="applyFilters()" class="block h-9 w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">{{ __('Status') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="approved">{{ __('Approved') }}</option>
                        <option value="rejected">{{ __('Rejected') }}</option>
                    </select>
                </div>

                <div class="w-44 shrink-0">
                    <label for="travel_authority_month" class="sr-only">{{ __('Filter by month') }}</label>
                    <select id="travel_authority_month" x-model="month" @change="applyFilters()" class="block h-9 w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">{{ __('Months') }}</option>
                        <option value="01">{{ __('January') }}</option>
                        <option value="02">{{ __('February') }}</option>
                        <option value="03">{{ __('March') }}</option>
                        <option value="04">{{ __('April') }}</option>
                        <option value="05">{{ __('May') }}</option>
                        <option value="06">{{ __('June') }}</option>
                        <option value="07">{{ __('July') }}</option>
                        <option value="08">{{ __('August') }}</option>
                        <option value="09">{{ __('September') }}</option>
                        <option value="10">{{ __('October') }}</option>
                        <option value="11">{{ __('November') }}</option>
                        <option value="12">{{ __('December') }}</option>
                    </select>
                </div>

                <button type="button" @click="reset()" class="inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-xs font-bold uppercase tracking-wider text-gray-700 transition hover:bg-gray-50">
                    {{ __('Reset') }}
                </button>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full table-fixed border-collapse text-left">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="w-[10%] px-3 py-3 text-[11px] font-black uppercase tracking-widest text-gray-500">{{ __('TA Stamp') }}</th>
                                <th class="w-[12%] px-3 py-3 text-[11px] font-black uppercase tracking-widest text-gray-500">{{ __('Date of Travel') }}</th>
                                <th class="w-[20%] px-3 py-3 text-[11px] font-black uppercase tracking-widest text-gray-500">{{ __('Destination') }}</th>
                                <th class="w-[22%] px-3 py-3 text-[11px] font-black uppercase tracking-widest text-gray-500">{{ __('Records Officer') }}</th>
                                <th class="w-[22%] px-3 py-3 text-[11px] font-black uppercase tracking-widest text-gray-500">{{ __('Regional Director') }}</th>
                                <th class="w-[10%] px-3 py-3 text-[11px] font-black uppercase tracking-widest text-gray-500">{{ __('AR Deadline') }}</th>
                                <th class="w-[8%] px-3 py-3 text-[11px] font-black uppercase tracking-widest text-gray-500">{{ __('AR Status') }}</th>
                                <th class="w-[7%] px-3 py-3 text-center text-[11px] font-black uppercase tracking-widest text-gray-500">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @php
                                $statusClasses = [
                                    'pending' => 'border-orange-100 bg-orange-50 text-orange-700',
                                    'approved' => 'border-green-100 bg-green-50 text-green-700',
                                    'rejected' => 'border-red-100 bg-red-50 text-red-700',
                                ];
                            @endphp

                            @foreach (['my' => $myTravelOrders, 'tagged' => $taggedTravelOrders] as $section => $orders)
                                @forelse ($orders as $order)
                                    @php
                                        $recordStatus = strtolower((string) ($order->recordofficer_status ?: 'pending'));
                                        $rdStatus = strtolower((string) ($order->rd_status ?: 'pending'));
                                        $mainStatus = strtolower((string) ($order->status ?: 'pending'));
                                        $tarDeadline = $order->tar_deadline ?: optional($order->travel_date_end)->copy()?->addDays(5);
                                        $computedTarStatus = strtolower((string) ($order->tar_status ?: 'pending'));

                                        if ($computedTarStatus !== 'submitted' && $mainStatus === 'approved' && $tarDeadline && now()->gt($tarDeadline)) {
                                            $computedTarStatus = 'overdue';
                                        }

                                        if ($mainStatus !== 'approved' && $computedTarStatus === 'pending') {
                                            $computedTarStatus = 'not_applicable';
                                        }

                                        $searchText = strtolower(implode(' ', array_filter([
                                            $order->ta_number,
                                            $order->places_of_travel,
                                            $order->purpose,
                                            $order->recordsOfficer?->firstname,
                                            $order->recordsOfficer?->lastname,
                                            $order->regionalDirector?->firstname,
                                            $order->regionalDirector?->lastname,
                                            $order->employee?->firstname,
                                            $order->employee?->lastname,
                                        ])));

                                        $taNumber = $order->ta_number ?: 'TA-' . optional($order->created_at)->format('Y-m-d') . '-' . str_pad((string) $order->id, 3, '0', STR_PAD_LEFT);
                                        $acknowledgementReportUrl = $order->tar_attachment_path
                                            ? route('travel-orders.ar.file', $order)
                                            : route('travel-orders.ar.print', $order);
                                        $previewPayload = [
                                            'title' => $taNumber,
                                            'date' => $order->created_at?->format('M d, Y') ?? 'N/A',
                                            'status' => $mainStatus,
                                            'remarks' => trim(collect([$order->rd_remarks, $order->recordofficer_remarks, $order->notes_remarks])->filter()->implode(' ')) ?: 'No remarks available.',
                                            'printUrl' => route('travel-orders.print', $order) . '#toolbar=0&navpanes=0&scrollbar=0',
                                            'directPrintUrl' => route('travel-orders.print', $order),
                                        ];
                                        
                                        $approvalDotClass = fn (string $status) => match (strtolower($status)) {
                                            'approved' => 'bg-green-500',
                                            'rejected' => 'bg-red-500',
                                            default => 'bg-gray-300',
                                        };
                                    @endphp
                                    <tr
                                        class="transition hover:bg-blue-50/40"
                                        data-ta-row
                                        data-tab="{{ $section }}"
                                        data-search="{{ $searchText }}"
                                        data-status="{{ $mainStatus }}"
                                        data-year="{{ $order->created_at?->year ?? $currentYear }}"
                                        data-month="{{ $order->travel_date_start?->format('m') ?? '' }}"
                                    >
                                        <td class="px-3 py-3 align-top text-xs font-bold text-gray-900 break-words">{{ $taNumber }}</td>
                                        <td class="px-3 py-3 align-top text-xs text-gray-700">
                                            <div class="leading-5">{{ $order->travel_date_start?->format('M d, Y') }}</div>
                                            @if($order->travel_date_end && $order->travel_date_end->ne($order->travel_date_start))
                                                <div class="leading-5">{{ $order->travel_date_end->format('M d, Y') }}</div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 align-top text-xs font-semibold text-gray-900 break-words">{{ $order->places_of_travel }}</td>
                                        <td class="px-3 py-3 align-top">
                                            <div class="flex justify-center">
                                                <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $approvalDotClass($recordStatus) }}" title="Records Officer: {{ ucfirst($recordStatus) }}"></span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 align-top">
                                            <div class="flex justify-center">
                                                <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $approvalDotClass($rdStatus) }}" title="Regional Director: {{ ucfirst($rdStatus) }}"></span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 align-top text-xs text-gray-700">{{ $tarDeadline?->format('M d, Y') ?? __('N/A') }}</td>
                                        <td class="px-3 py-3 align-top text-xs font-semibold">
                                            @if($computedTarStatus === 'submitted')
                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-100 bg-emerald-50 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-700">
                                                    <i class="fa-solid fa-file-lines"></i>
                                                    <span>{{ __('Submitted') }}</span>
                                                </span>
                                            @elseif($computedTarStatus === 'overdue')
                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-red-100 bg-red-50 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-red-700">
                                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                                    <span>{{ __('Overdue') }}</span>
                                                </span>
                                            @elseif($computedTarStatus === 'not_applicable')
                                                <span class="text-gray-400">—</span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-100 bg-slate-50 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-slate-600">
                                                    <i class="fa-regular fa-clock"></i>
                                                    <span>{{ __('Pending') }}</span>
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-center align-top">
                                            <div class="mx-auto grid w-fit grid-cols-2 place-items-center gap-x-2 gap-y-1">
                                                <a href="{{ route('travel-orders.show', $order) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-indigo-700 transition hover:bg-indigo-50 hover:text-indigo-900" title="{{ __('View') }}">
                                                    <i class="fa-solid fa-eye text-sm"></i>
                                                </a>
                                                <a href="{{ route('travel-orders.print', $order) }}" target="_blank" rel="noopener" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-700 transition hover:bg-slate-50 hover:text-slate-900" title="{{ __('Print') }}">
                                                    <i class="fa-solid fa-print text-sm"></i>
                                                </a>
                                                @if($computedTarStatus === 'submitted')
                                                    <a href="{{ $acknowledgementReportUrl }}" target="_blank" rel="noopener" data-no-transition class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-blue-700 transition hover:bg-blue-50 hover:text-blue-900" title="{{ __('View Acknowledgement Report') }}" aria-label="{{ __('View Acknowledgement Report') }}">
                                                        <i class="fa-solid fa-file-circle-check text-sm"></i>
                                                    </a>
                                                @endif
                                                @if($section === 'my' && $mainStatus === 'approved')
                                                    <button type="button" @click="openTarModal('{{ route('travel-orders.submit-tar', $order) }}', '{{ $taNumber }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-emerald-700 transition hover:bg-emerald-50 hover:text-emerald-900" title="{{ __('Submit Acknowledgement Report') }}" aria-label="{{ __('Submit Acknowledgement Report') }}">
                                                        <i class="fa-solid fa-file-arrow-up text-sm"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr data-empty-row="{{ $section }}">
                                        <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">
                                            {{ $section === 'my' ? __('No travel authorities found.') : __('No tagged travel authorities found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            @endforeach

                            <tr x-show="noResults" style="display: none;">
                                <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">{{ __('No travel authorities match your current filters.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <template x-teleport="body">
            <div x-cloak x-show="previewOpen" class="fixed inset-0 z-[100000] flex items-center justify-center p-5 sm:p-8" style="display: none;" @keydown.escape.window="closePreviewModal()">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closePreviewModal()"></div>

                <div @click.stop class="relative z-10 flex w-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl" style="width: min(96vw, 1120px); height: min(92vh, 860px); max-height: calc(100dvh - 32px);">
                    <div class="flex shrink-0 items-center justify-between border-b border-blue-950 bg-blue-900 px-4 py-2.5 sm:px-5">
                        <div class="min-w-0">
                            <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-blue-200">{{ __('Travel Authority Preview') }}</p>
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

                        <button type="button" @click="closePreviewModal()" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-white transition hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-white/70" aria-label="{{ __('Close preview') }}">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-auto bg-slate-100 p-3">
                        <div class="mx-auto h-full max-w-[820px] overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm">
                            <iframe class="h-full w-full bg-white" :src="previewData.printUrl"></iframe>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-col gap-2 border-t border-slate-200 bg-white px-4 py-2.5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <button type="button" @click="toggleRemarks()" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2 sm:w-auto">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8M8 14h5m-8 6 3.5-3H18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h1v3Z" />
                                </svg>
                                <span x-text="showRemarks ? 'Hide Remarks' : 'Remarks'"></span>
                            </button>

                            <div class="flex shrink-0 gap-2 sm:justify-end">
                            <button type="button" @click="closePreviewModal()" class="inline-flex flex-1 items-center justify-center rounded-lg border border-red-300 bg-white px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2 sm:flex-none">
                                Close
                            </button>
                            <a :href="previewData.directPrintUrl" target="_blank" rel="noopener" class="inline-flex flex-1 items-center justify-center rounded-lg bg-blue-900 px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:flex-none">
                                {{ __('Print') }}
                            </a>
                            </div>
                        </div>

                        <template x-if="showRemarks">
                            <div class="max-h-24 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs leading-5 text-slate-700" x-text="previewData.remarks"></div>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        <div x-cloak x-show="tarModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div @click.away="closeTarModal()" class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="text-sm font-black uppercase tracking-widest text-gray-900">{{ __('Submit AR') }}</h2>
                    <p class="mt-1 text-xs text-gray-500" x-text="tarTitle"></p>
                </div>
                <form :action="tarAction" method="POST" enctype="multipart/form-data" class="space-y-4 px-5 py-5">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">{{ __('Acknowledgement Report (PDF)') }}</label>
                        <input type="file" name="tar_attachment" accept="application/pdf,.pdf" class="block w-full rounded-lg border border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">{{ __('Remarks') }}</label>
                        <textarea name="tar_remarks" rows="3" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                        <button type="button" @click="closeTarModal()" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">{{ __('Cancel') }}</button>
                        <button type="submit" class="inline-flex items-center rounded-lg bg-blue-900 px-4 py-2 text-sm font-bold text-white transition hover:bg-blue-800">{{ __('Submit AR') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

        {{-- Create Travel Authority modal --}}
<template x-teleport="body">
    <div
        x-data="{
            createModalOpen: false,
            createModalUrl: '',
            createModalTitle: '',

            open(event) {
                this.createModalUrl = event.detail.url;
                this.createModalTitle = event.detail.title;
                this.createModalOpen = true;

                document.documentElement.classList.add('overflow-hidden');
                document.body.classList.add('overflow-hidden');
            },

            closeCreateModal() {
                this.createModalOpen = false;
                this.createModalUrl = '';

                document.documentElement.classList.remove('overflow-hidden');
                document.body.classList.remove('overflow-hidden');
            }
        }"
        @open-create-request-modal.window="open($event)"
        @message.window="
            if ($event.data === 'close-travel-authority-modal') {
                closeCreateModal();
            }
        "
        @keydown.escape.window="createModalOpen && closeCreateModal()"
        x-show="createModalOpen"
        x-cloak
        class="fixed inset-0 flex items-center justify-center p-4 sm:p-6"
        style="
            display: none;
            z-index: 99999;
            isolation: isolate;
        "
        role="dialog"
        aria-modal="true"
        aria-labelledby="travel-authority-modal-title"
    >
        {{-- Overlay --}}
        <div
            class="absolute inset-0"
            style="
                z-index: 0;
                background-color: rgba(15, 23, 42, 0.62);
            "
            @click="closeCreateModal()"
            aria-hidden="true"
        ></div>

        {{-- Keep original modal size --}}
        <div
            class="relative flex w-full max-w-[820px] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
            style="
                z-index: 10;
                width: min(92vw, 820px);
                height: min(84dvh, 820px);
                max-height: calc(100dvh - 40px);
            "
            @click.stop
        >
            <div class="flex shrink-0 items-center justify-between bg-blue-900 px-5 py-4 text-white">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-blue-100">
                        {{ __('Request Form') }}
                    </p>

                    <h2
                        id="travel-authority-modal-title"
                        class="mt-1 text-xl font-black"
                        x-text="createModalTitle"
                    ></h2>
                </div>

                <button
                    type="button"
                    @click="closeCreateModal()"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-full text-white transition hover:bg-white/15"
                    aria-label="{{ __('Close') }}"
                >
                    <svg
                        class="h-5 w-5"
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

            <div class="min-h-0 flex-1 overflow-hidden bg-white">
                <iframe
                    :src="createModalUrl"
                    @load="
                        try {
                            const href = $event.target.contentWindow.location.href;

                            if (
                                createModalUrl &&
                                !href.includes('modal=1') &&
                                !href.includes('/create')
                            ) {
                                window.location.href = href;
                            }
                        } catch (error) {}
                    "
                    class="h-full w-full border-0 bg-white"
                    :title="createModalTitle"
                ></iframe>
            </div>
        </div>
    </div>
</template>
    <script>
        function travelAuthorityPage() {
            return {
                tab: 'my',
                search: '',
                status: '',
                month: '',
                year: '{{ $currentYear }}',
                rows: [],
                noResults: false,
                createModalOpen: false,
                createModalUrl: '',
                createModalTitle: '',
                previewOpen: false,
                showRemarks: false,
                previewData: {
                    title: '',
                    date: '',
                    status: '',
                    remarks: '',
                    printUrl: '',
                    directPrintUrl: '',
                },
                tarModalOpen: false,
                tarAction: '',
                tarTitle: '',
                init() {
                    this.rows = Array.from(document.querySelectorAll('[data-ta-row]'));
                    this.applyFilters();
                },
                applyFilters() {
                    const search = this.search.trim().toLowerCase();
                    let visible = 0;

                    this.rows.forEach((row) => {
                        const matches = row.dataset.tab === this.tab
                            && (!search || row.dataset.search.includes(search))
                            && (!this.status || row.dataset.status === this.status)
                            && (!this.month || row.dataset.month === this.month)
                            && row.dataset.year === this.year;

                        row.classList.toggle('hidden', !matches);
                        if (matches) visible++;
                    });

                    document.querySelectorAll('[data-empty-row]').forEach((row) => {
                        row.classList.toggle('hidden', row.dataset.emptyRow !== this.tab);
                    });

                    this.noResults = this.rows.filter((row) => row.dataset.tab === this.tab).length > 0 && visible === 0;
                },
                reset() {
                    this.search = '';
                    this.status = '';
                    this.month = '';
                    this.year = '{{ $currentYear }}';
                    this.applyFilters();
                },
                openCreateModal(url, title) {
                    this.createModalUrl = url;
                    this.createModalTitle = title;
                    this.createModalOpen = true;
                    document.documentElement.classList.add('overflow-hidden');
                    document.body.classList.add('overflow-hidden');
                },
                closeCreateModal() {
                    this.createModalOpen = false;
                    this.createModalUrl = '';
                    document.documentElement.classList.remove('overflow-hidden');
                    document.body.classList.remove('overflow-hidden');
                },
                openPreviewModal(payload) {
                    this.previewData = payload;
                    this.showRemarks = false;
                    this.previewOpen = true;
                    document.documentElement.classList.add('overflow-hidden');
                    document.body.classList.add('overflow-hidden');
                },
                closePreviewModal() {
                    this.previewOpen = false;
                    this.showRemarks = false;
                    document.documentElement.classList.remove('overflow-hidden');
                    document.body.classList.remove('overflow-hidden');
                },
                toggleRemarks() {
                    this.showRemarks = !this.showRemarks;
                },
                openTarModal(action, title) {
                    this.tarAction = action;
                    this.tarTitle = title;
                    this.tarModalOpen = true;
                },
                closeTarModal() {
                    this.tarModalOpen = false;
                    this.tarAction = '';
                    this.tarTitle = '';
                },
            };
        }
    </script>
</x-app-layout>
