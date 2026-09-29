<x-app-layout>
    <x-slot name="title">{{ __('My Leave Requests') }}</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8" x-data="myLeaveApplicationTable()" x-init="init()">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @php
                $currentYear = now()->year;
                $leaveTypes = $leaves
                    ->map(fn ($leave) => (string) Str::of($leave->leaveType?->name ?? '')
                        ->replaceMatches('/\s+Leave\b/i', '')
                        ->trim())
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();
            @endphp

            {{-- Year filter and action button positioned like the Travel Authority page --}}
            <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-end">
                <div class="flex items-center gap-2">
                    <label for="my_leave_year" class="text-sm font-semibold text-gray-700">{{ __('Year') }}</label>
                    <select id="my_leave_year" x-model="year" @change="applyFilters()" class="h-10 w-28 appearance-none rounded-lg border-gray-300 bg-white pl-4 pr-10 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @for ($yearOption = $currentYear; $yearOption >= $currentYear - 4; $yearOption--)
                            <option value="{{ $yearOption }}">{{ $yearOption }}</option>
                        @endfor
                    </select>

                    <button type="button" @click="$dispatch('open-create-request-modal', { url: @js(route('leaves.create', ['modal' => 1])), title: @js(__('Leave Request')) })" class="inline-flex items-center justify-center rounded-xl bg-blue-900 px-4 py-2.5 text-xs font-black uppercase tracking-widest text-white transition hover:bg-blue-800">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        {{ __('Apply New Leave') }}
                    </button>
                </div>
            </div>

            <div x-data="{ tab: 'applications' }">
                <!-- Tabs Navigation -->
                <div class="border-b border-gray-200 mb-6">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button @click="tab = 'applications'"
                                :class="tab === 'applications' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm uppercase tracking-widest transition-colors duration-200">
                            My Leave Applications
                        </button>
                        <button @click="tab = 'credits'"
                                :class="tab === 'credits' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm uppercase tracking-widest transition-colors duration-200">
                            My Leave Credits
                        </button>
                    </nav>
                </div>

                <!-- Credits Summary Tab -->
                <div x-show="tab === 'credits'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        @foreach($credits->filter(function ($credit) {
                            $leaveName = Str::lower(trim($credit->leaveType?->name ?? ''));

                            return in_array($leaveName, [
                                'special privilege leave',
                                'sick leave',
                                'vacation leave',
                                'mandatory/force leave',
                                
                            ], true);
                        }) as $credit)
                            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm ring-1 ring-black/5 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
                                <div class="min-h-[2.5rem] text-[10px] font-black uppercase text-blue-900 tracking-widest">
                                    {{ $credit->leaveType->name }}
                                </div>
                                <div class="mt-2 flex items-end justify-between gap-3">
                                    <div>
                                        <div class="text-3xl font-black leading-none text-gray-900">{{ number_format((float) $credit->balance, 3) }}</div>
                                        <div class="mt-1 text-[10px] font-black uppercase tracking-widest text-gray-400">{{ __('Days Left') }}</div>
                                    </div>
                                </div>
                                <div class="mt-4 grid grid-cols-2 overflow-hidden rounded-xl border border-gray-100 bg-gray-50/70">
                                    <div class="border-r border-gray-100 px-3 py-2.5">
                                        <div class="text-[10px] font-black uppercase tracking-widest text-gray-400">{{ __('Earned') }}</div>
                                        <div class="mt-1 text-sm font-black text-gray-900">{{ number_format((float) $credit->earned, 3) }}</div>
                                    </div>
                                    <div class="px-3 py-2.5">
                                        <div class="text-[10px] font-black uppercase tracking-widest text-gray-400">{{ __('Used') }}</div>
                                        <div class="mt-1 text-sm font-black text-gray-900">{{ number_format((float) $credit->used, 3) }}</div>
                                    </div>
                                </div>
                                <div class="mt-4 w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                    @php
                                        $total = $credit->leaveType->days_per_year ?? 15;
                                        $percentage = $total > 0 ? ($credit->balance / $total) * 100 : 0;
                                        $color = $percentage > 50 ? 'bg-blue-900' : ($percentage > 20 ? 'bg-yellow-500' : 'bg-red-500');
                                    @endphp
                                    <div class="{{ $color }} h-full transition-all duration-1000" style="width: {{ $percentage }}%">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Leave Applications Tab -->
                <div x-show="tab === 'applications'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
                    <!-- Search and Filters -->
                    <div class="mb-4 flex w-full flex-nowrap items-center gap-2 overflow-x-auto rounded-xl border border-gray-100 bg-gray-50/70 p-2">
                        <div class="relative min-w-[260px] flex-1">
                            <label for="my_leave_search" class="sr-only">{{ __('Search leave application') }}</label>
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M21 21l-4.35-4.35M10.75 18.5a7.75 7.75 0 100-15.5 7.75 7.75 0 000 15.5z" />
                                </svg>
                            </span>
                            <input id="my_leave_search" type="search" x-model="search" @input.debounce.200ms="applyFilters()" placeholder="{{ __('Search leave type, status, or date...') }}" class="block h-9 w-full rounded-lg border-gray-300 pl-10 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>

                        <div class="w-48 shrink-0">
                            <label for="my_leave_status" class="sr-only">{{ __('Filter by status') }}</label>
                            <select id="my_leave_status" x-model="status" @change="applyFilters()" class="block h-9 w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('Status') }}</option>
                                <option value="pending">{{ __('Pending') }}</option>
                                <option value="approved">{{ __('Approved') }}</option>
                                <option value="rejected">{{ __('Rejected') }}</option>
                                <option value="cancelled">{{ __('Cancelled') }}</option>
                            </select>
                        </div>

                        <div class="w-40 shrink-0">
                            <label for="my_leave_type" class="sr-only">{{ __('Filter by leave type') }}</label>
                            <select id="my_leave_type" x-model="leaveType" @change="applyFilters()" class="block h-9 w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('Leave Types') }}</option>
                                @foreach($leaveTypes as $type)
                                    <option value="{{ Str::lower($type) }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <button type="button" @click="reset()" class="inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-xs font-bold uppercase tracking-wider text-gray-700 transition hover:bg-gray-50">
                            {{ __('Reset') }}
                        </button>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                        <div class="w-full overflow-x-auto">
                             <table class="w-full table-fixed divide-y divide-gray-100">
                                <colgroup>
                                    <col class="w-[13%]">
                                    <col class="w-[15%]">
                                    <col class="w-[17%]">
                                    <col class="w-[19%]">
                                    <col class="w-[18%]">
                                    <col class="w-[10%]">
                                    <col class="w-[8%]">
                                </colgroup>
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="break-words px-2 py-3 text-left text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500 whitespace-normal">
                                            Date Filed
                                        </th>
                                        <th scope="col" class="break-words px-2 py-3 text-left text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500 whitespace-normal">
                                            Leave Type
                                        </th>
                                        <th scope="col" class="break-words px-2 py-3 text-left text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500 whitespace-normal">
                                            Leave Certification
                                        </th>
                                        <th scope="col" class="break-words px-2 py-3 text-left text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500 whitespace-normal">
                                            Recommending Approval
                                        </th>
                                        <th scope="col" class="break-words px-2 py-3 text-left text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500 whitespace-normal">
                                            Approval
                                        </th>
                                        <th scope="col" class="break-words px-2 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500 whitespace-normal">
                                            Status
                                        </th>
                                        <th scope="col" class="break-words px-2 py-3 text-center text-[10px] font-black uppercase leading-4 tracking-wider text-gray-500 whitespace-normal">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="myLeaveApplicationTableBody" class="divide-y divide-gray-100 bg-white">
                                    @forelse($leaves as $leaf)
                                        @php
                                            $stages = [
                                                ['label' => 'HR', 'status' => $leaf->hrstaff_status],
                                                ['label' => 'Chief', 'status' => $leaf->chief_status],
                                                ['label' => 'Regional Director', 'status' => $leaf->rd_status],
                                            ];
                                            $approvedCount = collect($stages)->where('status', 'approved')->count();
                                            $hasRejected = collect($stages)->contains(fn($stage) => $stage['status'] === 'rejected');
                                            $displayStatus = $leaf->status === 'cancelled' ? 'Cancelled' : ($hasRejected ? 'Rejected' : ($approvedCount === 3 ? 'Approved' : 'Pending'));
                                            $filterStatus = $leaf->status === 'cancelled' ? 'cancelled' : ($hasRejected ? 'rejected' : ($approvedCount === 3 ? 'approved' : 'pending'));
                                            $leaveTypeName = Str::of($leaf->leaveType?->name ?? '')->replaceMatches('/\s+Leave\b/i', '')->trim();
                                            $searchText = Str::lower($leaveTypeName . ' ' . $displayStatus . ' ' . \Carbon\Carbon::parse($leaf->date_filed)->format('M d, Y'));
                                            $leaveTrackerPayload = [
                                                'id' => (string) $leaf->id,
                                                'type' => (string) $leaveTypeName,
                                                'stages' => collect($stages)->map(fn ($stage) => [
                                                    'label' => $stage['label'],
                                                    'status' => $stage['status'] ?: 'pending',
                                                ])->values(),
                                            ];
                                            $previewPayload = [
                                                'title' => (string) $leaveTypeName,
                                                'date' => \Carbon\Carbon::parse($leaf->date_filed)->format('M d, Y'),
                                                'status' => (string) $displayStatus,
                                                'remarks' => trim(collect([$leaf->rd_remarks, $leaf->hrstaff_remarks, $leaf->chief_remarks, $leaf->remarks])->filter()->implode(' ')) ?: 'No remarks available.',
                                                'printUrl' => route('leaves.print', ['leaf' => $leaf->id, 'preview' => 1]),
                                                'directPrintUrl' => route('leaves.print', $leaf->id),
                                            ];
                                            
                                            $approvalDotClass = fn (string $status) => match ($status) {
                                                'approved' => 'bg-green-500',
                                                'rejected' => 'bg-red-500',
                                                default => 'bg-gray-300',
                                            };
                                        @endphp
                                        <tr
                                            class="transition-colors hover:bg-gray-50/70"
                                            :class="selectedId === '{{ $leaf->id }}' ? 'bg-sky-50' : ''"
                                            data-approval-row="{{ $leaf->id }}"
                                            data-leave-row
                                            data-search="{{ $searchText }}"
                                            data-leave-type="{{ Str::lower($leaveTypeName) }}"
                                            data-status="{{ $filterStatus }}"
                                            data-year="{{ \Carbon\Carbon::parse($leaf->date_filed)->year }}"
                                            data-date-filed="{{ \Carbon\Carbon::parse($leaf->date_filed)->timestamp }}"
                                            data-leave-start="{{ \Carbon\Carbon::parse($leaf->start_date)->timestamp }}"
                                        >
                                            <td class="px-3 py-3 align-middle">
                                                <div class="break-words text-sm font-medium leading-5 text-gray-700 whitespace-normal">
                                                    {{ \Carbon\Carbon::parse($leaf->date_filed)->format('M d, Y') }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 align-middle">
                                                <div class="w-full min-w-0 text-left text-sm font-bold text-gray-900 break-words whitespace-normal">
                                                    <span class="block break-words whitespace-normal">{{ $leaveTypeName }}</span>
                                                </div>
                                            </td>
                                            <td class="px-2 py-3 text-center align-middle">
                                                <div class="flex min-w-0 items-center justify-center">
                                                    <span class="h-3 w-3 shrink-0 rounded-full {{ $approvalDotClass($leaf->hrstaff_status ?: 'pending') }}" title="{{ __('Leave Certification') }}: {{ ucfirst($leaf->hrstaff_status ?: 'pending') }}"></span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-center align-middle">
                                                <div class="flex min-w-0 items-center justify-center">
                                                    <span class="h-3 w-3 shrink-0 rounded-full {{ $approvalDotClass($leaf->chief_status ?: 'pending') }}" title="{{ __('Recommending Approval') }}: {{ ucfirst($leaf->chief_status ?: 'pending') }}"></span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-center align-middle">
                                                <div class="flex min-w-0 items-center justify-center">
                                                    <span class="h-3 w-3 shrink-0 rounded-full {{ $approvalDotClass($leaf->rd_status ?: 'pending') }}" title="{{ __('Approval') }}: {{ ucfirst($leaf->rd_status ?: 'pending') }}"></span>
                                                </div>
                                            </td>
                                            <td class="px-2 py-3 text-center align-middle">
                                                <span class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase {{ $filterStatus === 'approved' ? 'border-green-100 bg-green-50 text-green-700' : ($filterStatus === 'rejected' ? 'border-red-100 bg-red-50 text-red-700' : ($filterStatus === 'cancelled' ? 'border-gray-100 bg-gray-50 text-gray-600' : 'border-orange-100 bg-orange-50 text-orange-700')) }}">
                                                    {{ $displayStatus }}
                                                </span>
                                            </td>

                                            <td class="px-2 py-3 text-center align-middle">
                                                <div class="flex items-center justify-center gap-1 whitespace-nowrap">
                                                    <a
                                                        href="{{ route('leaves.show', $leaf) }}"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-blue-700 transition-colors hover:bg-blue-50 hover:text-blue-900"
                                                        title="{{ __('View details') }}"
                                                        aria-label="{{ __('View details') }}"
                                                    >
                                                        <i class="fa-solid fa-eye"></i>
                                                    </a>
                                                    <a
                                                        href="{{ route('leaves.print', $leaf) }}"
                                                        target="_blank"
                                                        rel="noopener"
                                                        data-no-transition
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900"
                                                        title="{{ __('Print leave form') }}"
                                                        aria-label="{{ __('Print leave form') }}"
                                                    >
                                                        <i class="fa-solid fa-print"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-6 py-12 text-center">
                                                <div class="text-gray-400 mb-2">
                                                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                                <p class="text-lg font-medium text-gray-400 italic">
                                                    {{ __('You haven\'t filed any leave requests yet.') }}
                                                </p>
                                                <a href="{{ route('leaves.create') }}"
                                                    class="mt-4 inline-flex items-center text-blue-600 hover:underline font-bold text-sm">
                                                    {{ __('File your first leave now') }}
                                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                                    </svg>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforelse
                                    @if($leaves->isNotEmpty())
                                        <tr x-show="noResults" style="display: none;">
                                            <td colspan="7" class="px-6 py-12 text-center">
                                                <p class="text-gray-500 italic font-medium">
                                                    {{ __('No leave applications match your search or filter.') }}
                                                </p>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <template x-teleport="body">
                <div
                    x-show="previewModalOpen"
                    x-cloak
                    class="fixed inset-0 z-[100000] flex items-center justify-center p-5 sm:p-8"
                    style="display: none;"
                    @keydown.escape.window="closePreviewModal()"
                >
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closePreviewModal()"></div>

                    <div
                        class="relative z-10 flex w-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                        style="width: min(96vw, 1120px); height: min(92vh, 860px); max-height: calc(100dvh - 32px);"
                        @click.stop
                    >
                        <div class="flex shrink-0 items-center justify-between border-b border-blue-950 bg-blue-900 px-4 py-2.5 sm:px-5">
                            <div class="min-w-0">
                                <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-blue-200">Leave Form Preview</p>
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
                                <iframe x-ref="previewFrame" :src="previewData.printUrl" class="h-full min-h-[620px] w-full border-0 bg-white" title="Leave Print Preview"></iframe>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-col gap-2 border-t border-slate-200 bg-white px-4 py-2.5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <button type="button" @click="toggleRemarks()" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2 sm:w-auto">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8M8 14h5m-8 6 3.5-3H18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h1v3Z" />
                                    </svg>
                                    <span x-text="showRemarks ? 'Hide Remarks' : 'Remarks'"></span>
                                </button>

                                <template x-if="showRemarks">
                                    <div class="mt-2 max-h-20 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs leading-5 text-slate-700" x-text="previewData.remarks"></div>
                                </template>
                            </div>

                            <div class="flex shrink-0 gap-2 sm:justify-end">
                                <button type="button" @click="closePreviewModal()" class="inline-flex flex-1 items-center justify-center rounded-lg border border-red-300 bg-white px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2 sm:flex-none">
                                    Close
                                </button>
                                <a :href="previewData.directPrintUrl" target="_blank" data-no-transition class="inline-flex flex-1 items-center justify-center rounded-lg bg-blue-900 px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:flex-none">
                                    Print
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

        </div>
    </div>
        {{-- Apply Leave modal --}}
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
            if ($event.data === 'close-create-request-modal') {
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
        aria-labelledby="leave-modal-title"
    >
        {{-- Dark overlay covering the dashboard, sidebar and topbar --}}
        <div
            class="absolute inset-0"
            style="
                z-index: 0;
                background-color: rgba(15, 23, 42, 0.62);
            "
            @click="closeCreateModal()"
            aria-hidden="true"
        ></div>

        {{-- Original modal card and size --}}
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
            {{-- Header --}}
            <div class="flex shrink-0 items-center justify-between bg-blue-900 px-5 py-4 text-white">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-blue-100">
                        {{ __('Request Form') }}
                    </p>

                    <h2
                        id="leave-modal-title"
                        class="mt-1 text-xl font-black"
                        x-text="createModalTitle"
                    ></h2>
                </div>

                <button
                    type="button"
                    @click="closeCreateModal()"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-full text-white transition hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-white/70"
                    aria-label="{{ __('Close') }}"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
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

            {{-- Leave form iframe --}}
            <div class="min-h-0 flex-1 overflow-y-auto bg-white">
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
                    class="h-full min-h-[430px] w-full border-0 bg-white"
                    :title="createModalTitle"
                ></iframe>
            </div>
        </div>
    </div>
</template>
    <script>
        function myLeaveApplicationTable(initialSelectedId = '') {
            return {
                selectedId: initialSelectedId ? String(initialSelectedId) : '',
                search: '',
                year: '{{ now()->year }}',
                leaveType: '',
                status: '',
                rows: [],
                noResults: false,
                createModalOpen: false,
                createModalUrl: '',
                createModalTitle: '',
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
                    this.rows = Array.from(document.querySelectorAll('[data-leave-row]'));
                    this.applyFilters();
                },
                selectRow(id) {
                    this.selectedId = String(id);
                },
                applyFilters() {
                    const search = this.search.trim().toLowerCase();
                    const tbody = document.getElementById('myLeaveApplicationTableBody');
                    let visibleCount = 0;

                    this.rows.forEach((row) => {
                            const matchesSearch = !search || row.dataset.search.includes(search);
                            const matchesYear = !this.year || row.dataset.year === this.year;
                            const matchesType = !this.leaveType || row.dataset.leaveType === this.leaveType;
                            const matchesStatus = !this.status || row.dataset.status === this.status;
                            const isVisible = matchesSearch && matchesYear && matchesType && matchesStatus;

                            row.classList.toggle('hidden', !isVisible);
                            if (isVisible) visibleCount++;
                        });

                    this.noResults = this.rows.length > 0 && visibleCount === 0;
                },
                reset() {
                    this.search = '';
                    this.year = '{{ now()->year }}';
                    this.leaveType = '';
                    this.status = '';
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
                    document.documentElement.classList.remove('overflow-hidden');
                    document.body.classList.remove('overflow-hidden');
                },
                toggleRemarks() {
                    this.showRemarks = !this.showRemarks;
                },
            };
        }
    </script>
</x-app-layout>
