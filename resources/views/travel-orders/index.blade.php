<x-app-layout>
    <x-slot name="title">{{ __('My Travel Authorities') }}</x-slot>

    @php
        $currentYear = now()->year;
    @endphp

    <div class="px-1 py-3 sm:px-2 lg:px-3" x-data="travelAuthorityPage()" x-init="init()">
        <div class="w-full">
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

            <section class="rounded-2xl border border-slate-100 bg-white p-2 shadow-sm sm:p-3">
            {{-- Tabs styled like the Leave blade --}}
            <div class="mb-3 border-b border-gray-200 px-1">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button
                        type="button"
                        @click="setTab('my')"
                        :class="tab === 'my'
                            ? 'border-blue-500 text-blue-600'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                        class="whitespace-nowrap border-b-2 px-1 py-3 text-sm font-bold uppercase tracking-widest transition-colors duration-200"
                    >
                        {{ __('My TA') }}
                    </button>
                    <button
                        type="button"
                        @click="setTab('tagged')"
                        :class="tab === 'tagged'
                            ? 'border-blue-500 text-blue-600'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                        class="whitespace-nowrap border-b-2 px-1 py-3 text-sm font-bold uppercase tracking-widest transition-colors duration-200"
                    >
                        {{ __('Tagged') }}
                    </button>
                </nav>
            </div>

            {{-- Search and filters kept on one line --}}
            <div class="mb-3 flex w-full flex-nowrap items-center gap-2">
                <div class="relative min-w-0 flex-1">
                    <label for="travel_authority_search" class="sr-only">{{ __('Search travel authorities') }}</label>
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M21 21l-4.35-4.35M10.75 18.5a7.75 7.75 0 100-15.5 7.75 7.75 0 000 15.5z" />
                        </svg>
                    </span>
                    <input id="travel_authority_search" type="search" x-model="search" @input.debounce.200ms="applyFilters()" placeholder="{{ __('Search TA no., destination, or approver...') }}" class="block h-9 w-full rounded-lg border-gray-300 pl-10 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>

                <div class="w-40 shrink-0">
                    <label for="travel_authority_status" class="sr-only">{{ __('Filter by status') }}</label>
                    <select id="travel_authority_status" x-model="status" @change="applyFilters()" class="block h-9 w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">{{ __('Status') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="approved">{{ __('Approved') }}</option>
                        <option value="rejected">{{ __('Rejected') }}</option>
                    </select>
                </div>

                <div class="w-40 shrink-0">
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

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div>
                    <table class="w-full table-fixed border-collapse text-left">
                        <colgroup>
                            <col class="w-[13%]">
                            <col class="w-[14%]">
                            <col class="w-[13%]">
                            <col class="w-[10%]">
                            <col class="w-[10%]">
                            <col class="w-[12%]">
                            <col class="w-[11%]">
                            <col class="w-[17%]">
                        </colgroup>
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-2 py-3 text-[10px] font-black uppercase tracking-wider text-gray-500">{{ __('TA Stamp') }}</th>
                                <th class="px-2 py-3 text-[10px] font-black uppercase tracking-wider text-gray-500">{{ __('Date of Travel') }}</th>
                                <th class="px-2 py-3 text-[10px] font-black uppercase tracking-wider text-gray-500">{{ __('Destination') }}</th>
                                <th class="px-2 py-3 text-center text-[10px] font-black uppercase tracking-wider text-gray-500">{{ __('Records Officer') }}</th>
                                <th class="px-2 py-3 text-center text-[10px] font-black uppercase tracking-wider text-gray-500">{{ __('Regional Director') }}</th>
                                <th class="px-2 py-3 text-[10px] font-black uppercase tracking-wider text-gray-500">{{ __('TAR Deadline') }}</th>
                                <th class="px-2 py-3 text-[10px] font-black uppercase tracking-wider text-gray-500">{{ __('TAR Status') }}</th>
                                <th class="px-2 py-3 text-center text-[10px] font-black uppercase tracking-wider text-gray-500">{{ __('Actions') }}</th>
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

                                @forelse ($travelOrders as $order)
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
                                        $acknowledgementReportUrl = route('travel-orders.ar.show', $order);
                                        $dmwParticipantNames = collect([$order->employee])
                                            ->merge($order->companions)
                                            ->filter()
                                            ->map(fn ($participant) => trim(($participant->firstname ?? '') . ' ' . ($participant->middlename ?? '') . ' ' . ($participant->lastname ?? '') . ' ' . ($participant->suffix ?? '')))
                                            ->filter()
                                            ->implode(', ');
                                        $arDefaults = [
                                            'activity' => $order->tar_activity ?: $order->purpose,
                                            'activity_date' => $order->tar_activity_date?->format('Y-m-d') ?: $order->travel_date_end?->format('Y-m-d'),
                                            'venue' => $order->tar_venue ?: $order->places_of_travel,
                                            'participants_present' => $order->tar_participants_present ?: '',
                                            'dmw_participants' => $order->tar_dmw_participants ?: $dmwParticipantNames,
                                            'objectives' => $order->tar_objectives ?: '',
                                            'highlights' => $order->tar_highlights ?: '',
                                            'results' => $order->tar_results ?: '',
                                            'recommendations' => $order->tar_recommendations ?: '',
                                            'commitments' => $order->tar_commitments ?: '',
                                            'attachments' => $order->tar_attachments ?: [],
                                            'other_attachment' => $order->tar_other_attachment ?: '',
                                            'remarks' => $order->tar_remarks ?: '',
                                        ];
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
                                        data-tab="{{ $tab }}"
                                        data-search="{{ $searchText }}"
                                        data-status="{{ $mainStatus }}"
                                        data-year="{{ $order->created_at?->year ?? $currentYear }}"
                                        data-month="{{ $order->travel_date_start?->format('m') ?? '' }}"
                                    >
                                        <td class="px-2 py-2.5 align-middle text-xs font-bold text-gray-900 break-words">{{ $taNumber }}</td>
                                        <td class="px-2 py-2.5 align-middle text-xs text-gray-700">
                                            <div class="leading-5">{{ $order->travel_date_start?->format('M d, Y') }}</div>
                                            @if($order->travel_date_end && $order->travel_date_end->ne($order->travel_date_start))
                                                <div class="leading-5">{{ $order->travel_date_end->format('M d, Y') }}</div>
                                            @endif
                                        </td>
                                        <td class="px-2 py-2.5 align-middle text-xs font-semibold text-gray-900 break-words">{{ $order->places_of_travel }}</td>
                                        <td class="px-2 py-2.5 align-middle">
                                            <div class="flex justify-center">
                                                <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $approvalDotClass($recordStatus) }}" title="Records Officer: {{ ucfirst($recordStatus) }}"></span>
                                            </div>
                                        </td>
                                        <td class="px-2 py-2.5 align-middle">
                                            <div class="flex justify-center">
                                                <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $approvalDotClass($rdStatus) }}" title="Regional Director: {{ ucfirst($rdStatus) }}"></span>
                                            </div>
                                        </td>
                                        <td class="px-2 py-2.5 align-middle text-xs text-gray-700">{{ $tarDeadline?->format('M d, Y') ?? __('N/A') }}</td>
                                        <td class="px-2 py-2.5 align-middle text-xs font-semibold">
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
                                        <td class="px-2 py-2.5 text-center align-middle">
                                            <div class="mx-auto grid w-fit grid-cols-2 place-items-center gap-x-2 gap-y-1">
                                                <a href="{{ route('travel-orders.show', $order) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-indigo-700 transition hover:bg-indigo-50 hover:text-indigo-900" title="{{ __('View') }}">
                                                    <i class="fa-solid fa-eye text-sm"></i>
                                                </a>
                                                <a href="{{ route('travel-orders.print', $order) }}" target="_blank" rel="noopener" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-700 transition hover:bg-slate-50 hover:text-slate-900" title="{{ __('Print') }}">
                                                    <i class="fa-solid fa-print text-sm"></i>
                                                </a>
                                                @if($computedTarStatus === 'submitted')
                                                    <a href="{{ $acknowledgementReportUrl }}" target="_blank" rel="noopener" data-no-transition class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-blue-700 transition hover:bg-blue-50 hover:text-blue-900" title="{{ __('View TAR') }}" aria-label="{{ __('View TAR') }}">
                                                        <i class="fa-solid fa-file-circle-check text-sm"></i>
                                                    </a>
                                                @endif
                                                @if($tab === 'my' && $mainStatus === 'approved')
                                                    <button type="button" @click='openTarModal(@js(route("travel-orders.submit-tar", $order)), @js($taNumber), @js($arDefaults))' class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-emerald-700 transition hover:bg-emerald-50 hover:text-emerald-900" title="{{ __('Submit TAR') }}" aria-label="{{ __('Submit TAR') }}">
                                                        <i class="fa-solid fa-file-arrow-up text-sm"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">
                                            {{ $tab === 'my' ? __('No travel authorities found.') : __('No tagged travel authorities found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                        </tbody>
                    </table>
                </div>
                @include('partials.table-pagination', ['paginator' => $travelOrders, 'label' => __('travel authorities')])
            </div>
            </section>
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

        <div x-cloak x-show="tarModalOpen" class="fixed inset-0 z-[100000] flex items-center justify-center bg-slate-900/60 p-3 sm:p-5" @keydown.escape.window="closeTarModal()">
            <div @click.outside="closeTarModal()" class="flex max-h-[94vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex shrink-0 items-center justify-between border-b border-blue-950 bg-blue-900 px-5 py-4 text-white">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-blue-200">{{ __('Travel Authority Report (TAR)') }}</p>
                        <h2 class="mt-1 text-lg font-black">{{ __('Submit TAR') }}</h2>
                        <p class="mt-0.5 text-xs text-blue-100" x-text="tarTitle"></p>
                    </div>
                    <button type="button" @click="closeTarModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-full hover:bg-white/15" aria-label="{{ __('Close') }}">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form :action="tarAction" method="POST" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col">
                    @csrf
                    <div class="min-h-0 flex-1 space-y-5 overflow-y-auto bg-slate-50 px-5 py-5 sm:px-7">
                        <section class="rounded-xl border border-slate-200 bg-white p-4">
                            <h3 class="mb-4 text-xs font-black uppercase tracking-widest text-blue-900">{{ __('Activity Information') }}</h3>
                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="md:col-span-2">
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">{{ __('Activity') }} <span class="text-red-500">*</span></label>
                                    <input type="text" name="tar_activity" x-model="tarForm.activity" required maxlength="255" class="block w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">{{ __('Date') }} <span class="text-red-500">*</span></label>
                                    <input type="date" name="tar_activity_date" x-model="tarForm.activity_date" required class="block w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">{{ __('Venue') }} <span class="text-red-500">*</span></label>
                                    <input type="text" name="tar_venue" x-model="tarForm.venue" required maxlength="255" class="block w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">{{ __('Participants Present') }} <span class="text-red-500">*</span></label>
                                    <textarea name="tar_participants_present" x-model="tarForm.participants_present" rows="3" required class="block w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600"></textarea>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">{{ __('Participants from DMW') }} <span class="text-red-500">*</span></label>
                                    <textarea name="tar_dmw_participants" x-model="tarForm.dmw_participants" rows="3" required class="block w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600"></textarea>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-xl border border-slate-200 bg-white p-4">
                            <h3 class="mb-4 text-xs font-black uppercase tracking-widest text-blue-900">{{ __('Activity Report Details') }}</h3>
                            <div class="grid gap-4 lg:grid-cols-2">
                                @foreach([
                                    ['objectives', 'I. Objective/s of the Activity'],
                                    ['highlights', 'II. Facts/Highlights of the Activity'],
                                    ['results', 'III. Result/s of the Activity'],
                                    ['recommendations', 'IV. Recommendations/Resolutions'],
                                ] as [$field, $label])
                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold text-slate-700">{{ __($label) }} <span class="text-red-500">*</span></label>
                                        <textarea name="tar_{{ $field }}" x-model="tarForm.{{ $field }}" rows="4" required class="block w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600"></textarea>
                                    </div>
                                @endforeach
                                <div class="lg:col-span-2">
                                    <label class="mb-1.5 block text-xs font-bold text-slate-700">{{ __('V. Commitments/Requested Action from DMW') }} <span class="text-red-500">*</span></label>
                                    <textarea name="tar_commitments" x-model="tarForm.commitments" rows="4" required class="block w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600"></textarea>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-xl border border-slate-200 bg-white p-4">
                            <h3 class="mb-3 text-xs font-black uppercase tracking-widest text-blue-900">{{ __('VI. Attachments (check applicable)') }}</h3>
                            <div class="grid gap-x-6 gap-y-2 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach([
                                    'photo_documentation' => 'Photo documentation',
                                    'lecture_materials' => 'Lecture material/s',
                                    'activity_materials' => 'Activity material/s',
                                    'certificate' => 'Certificate of participation/attendance',
                                    'program_flow' => 'Program flow',
                                    'others' => 'Others (specify)',
                                ] as $value => $label)
                                    <label class="flex items-start gap-2 text-sm text-slate-700">
                                        <input type="checkbox" name="tar_attachments[]" value="{{ $value }}" x-model="tarForm.attachments" class="mt-0.5 rounded border-slate-300 text-blue-900 focus:ring-blue-600">
                                        <span>{{ __($label) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div x-show="tarForm.attachments.includes('others')" class="mt-3" x-cloak>
                                <input type="text" name="tar_other_attachment" x-model="tarForm.other_attachment" :required="tarForm.attachments.includes('others')" maxlength="255" placeholder="{{ __('Specify other attachment') }}" class="block w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600">
                            </div>
                        </section>

                        <section class="grid gap-4 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">{{ __('Supporting PDF (optional)') }}</label>
                                <input type="file" name="tar_attachment" accept="application/pdf,.pdf" class="block w-full rounded-lg border border-slate-300 bg-white text-sm shadow-sm file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:font-semibold">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">{{ __('Remarks (optional)') }}</label>
                                <textarea name="tar_remarks" x-model="tarForm.remarks" rows="2" class="block w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-blue-600"></textarea>
                            </div>
                        </section>
                    </div>

                    <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 bg-white px-5 py-3 sm:px-7">
                        <button type="button" @click="closeTarModal()" class="inline-flex min-w-28 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-xs font-black uppercase tracking-wider text-slate-700 transition hover:bg-slate-50">{{ __('Cancel') }}</button>
                        <button type="submit" class="inline-flex min-w-40 items-center justify-center gap-2 rounded-lg bg-blue-900 px-5 py-2.5 text-xs font-black uppercase tracking-wider text-white transition hover:bg-blue-800">
                            <i class="fa-solid fa-paper-plane"></i>
                            {{ __('Submit TAR') }}
                        </button>
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
                tab: @js($tab),
                search: @js($search),
                status: @js($status),
                month: @js($month),
                year: @js((string) $year),
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
                tarForm: {
                    activity: '', activity_date: '', venue: '', participants_present: '', dmw_participants: '',
                    objectives: '', highlights: '', results: '', recommendations: '', commitments: '',
                    attachments: [], other_attachment: '', remarks: '',
                },
                init() {
                    this.rows = Array.from(document.querySelectorAll('[data-ta-row]'));
                },
                setTab(tab) {
                    this.tab = tab;
                    this.applyFilters();
                },
                applyFilters() {
                    const url = new URL(@js(route('travel-orders.index')));

                    if (this.tab) {
                        url.searchParams.set('tab', this.tab);
                    }

                    if (this.search.trim()) {
                        url.searchParams.set('search', this.search.trim());
                    }

                    if (this.status) {
                        url.searchParams.set('status', this.status);
                    }

                    if (this.month) {
                        url.searchParams.set('month', this.month);
                    }

                    if (this.year) {
                        url.searchParams.set('year', this.year);
                    }

                    window.location.href = url.toString();
                },
                reset() {
                    window.location.href = @js(route('travel-orders.index'));
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
                openTarModal(action, title, defaults = {}) {
                    this.tarAction = action;
                    this.tarTitle = title;
                    this.tarForm = {
                        activity: defaults.activity || '',
                        activity_date: defaults.activity_date || '',
                        venue: defaults.venue || '',
                        participants_present: defaults.participants_present || '',
                        dmw_participants: defaults.dmw_participants || '',
                        objectives: defaults.objectives || '',
                        highlights: defaults.highlights || '',
                        results: defaults.results || '',
                        recommendations: defaults.recommendations || '',
                        commitments: defaults.commitments || '',
                        attachments: Array.isArray(defaults.attachments) ? defaults.attachments : [],
                        other_attachment: defaults.other_attachment || '',
                        remarks: defaults.remarks || '',
                    };
                    this.tarModalOpen = true;
                    document.documentElement.classList.add('overflow-hidden');
                    document.body.classList.add('overflow-hidden');
                },
                closeTarModal() {
                    this.tarModalOpen = false;
                    this.tarAction = '';
                    this.tarTitle = '';
                    document.documentElement.classList.remove('overflow-hidden');
                    document.body.classList.remove('overflow-hidden');
                },
            };
        }
    </script>
</x-app-layout>
