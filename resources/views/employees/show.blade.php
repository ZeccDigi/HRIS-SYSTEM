<x-app-layout>
    <x-slot name="title">{{ __('Employee Details') }} - {{ $employee->firstname }} {{ $employee->lastname }}</x-slot>

    <div class="px-4 py-6 sm:px-5 lg:px-6" x-data="{ tab: 'pds', reviewModalOpen: false, currentSection: '', sectionData: null, changedFields: [], changedEntries: [], reviewStatus: 'pending', reviewRemarks: '', salnModalOpen: false, selectedSaln: null, isChangedField(key, index = null) { return this.changedFields.includes(key) || (index !== null && this.changedEntries.includes(index)); } }" x-init="if (window.location.hash === '#leave-credits') tab = 'leave-credits'">
        <div class="mx-auto w-full">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('employees.index') }}" class="inline-flex w-fit items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition-colors hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ __('Back to Records') }}
                </a>
                <a href="{{ route('employees.edit', $employee) }}" class="inline-flex w-fit items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-black uppercase tracking-widest text-white shadow-md shadow-indigo-100 transition hover:bg-indigo-700">
                    {{ __('Edit Record') }}
                </a>
            </div>

            <!-- Header Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                <div class="p-6 sm:p-8 flex flex-col md:flex-row md:items-center gap-6">
                    <div class="relative group">
                        <x-profile-avatar :employee="$employee" size="3xl" variant="indigo" rounded="2xl" class="shadow-xl" />
                        @if(auth()->user()->employee?->id === $employee->id)
                            <label for="profile_picture_upload" class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center rounded-2xl opacity-0 group-hover:opacity-100 cursor-pointer transition-opacity" title="{{ __('Upload Profile Picture') }}">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </label>
                            <form id="profile_picture_form" action="{{ route('employees.profile-picture', $employee) }}" method="POST" enctype="multipart/form-data" class="hidden">
                                @csrf
                                <input type="file" id="profile_picture_upload" name="profile_picture" accept="image/*" onchange="document.getElementById('profile_picture_form').submit()">
                            </form>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1 text-center md:text-left">
                        <h1 class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl">
                            {{ $employee->lastname }}, {{ $employee->firstname }} {{ $employee->middlename }}
                        </h1>
                        <div class="mt-2 flex min-w-0 items-center justify-center gap-3 text-sm font-normal text-gray-500 md:justify-start">
                            <span class="truncate">{{ $employee->user?->email ?? __('No linked login account') }}</span>
                            <span class="shrink-0">|</span>
                            <span class="truncate">{{ $employee->position ?: __('No position') }}</span>
                            <span class="shrink-0">|</span>
                            <span class="truncate">{{ $employee->division ?: __('No division') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Tabs Navigation -->
                <div class="flex border-t border-gray-100 px-6 sm:px-8 overflow-x-auto whitespace-nowrap scrollbar-hide">
                    <button @click="tab = 'pds'" :class="tab === 'pds' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-6 py-4 border-b-2 font-bold text-sm transition-all focus:outline-none">
                        {{ __('Personal Data Sheet (PDS)') }}
                    </button>
                    <button @click="tab = 'leave-credits'" :class="tab === 'leave-credits' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-6 py-4 border-b-2 font-bold text-sm transition-all focus:outline-none">
                        {{ __('Leave Credits') }}
                    </button>
                    <button @click="tab = 'cto-credits'" :class="tab === 'cto-credits' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-6 py-4 border-b-2 font-bold text-sm transition-all focus:outline-none">
                        {{ __('CTO Credits') }}
                    </button>
                    <button @click="tab = 'saln'" :class="tab === 'saln' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-6 py-4 border-b-2 font-bold text-sm transition-all focus:outline-none">
                        {{ __('SALN') }}
                    </button>
                </div>
            </div>

            <!-- Tab Contents -->
            <div class="space-y-6">
                <!-- PDS Tab -->
                <div x-show="tab === 'pds'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                    <div class="w-full space-y-6">
                        <div class="bg-white border border-gray-100 p-6 sm:p-8 rounded-2xl shadow-sm">
                            @php
                                $sections = [
                                    ['label' => 'Personal Information', 'filled' => (bool) $employee->pdsPersonal, 'icon' => 'user', 'data' => $employee->pdsPersonal],
                                    ['label' => 'Family Background', 'filled' => (bool) $employee->pdsFamily, 'icon' => 'users', 'data' => $employee->pdsFamily],
                                    ['label' => 'Educational Background', 'filled' => $employee->pdsEducation->count() > 0, 'icon' => 'academic-cap', 'data' => $employee->pdsEducation],
                                    ['label' => 'Civil Service Eligibility', 'filled' => $employee->pdsEligibilities->count() > 0, 'icon' => 'badge-check', 'data' => $employee->pdsEligibilities],
                                    ['label' => 'Work Experience', 'filled' => $employee->pdsWorkExperiences->count() > 0, 'icon' => 'briefcase', 'data' => $employee->pdsWorkExperiences],
                                    ['label' => 'Questionnaire', 'filled' => (bool) $employee->pdsQuestionnaire, 'icon' => 'question-mark-circle', 'data' => $employee->pdsQuestionnaire],
                                    ['label' => 'References', 'filled' => $employee->pdsReferences->count() >= 3, 'icon' => 'identification', 'data' => $employee->pdsReferences],
                                ];
                                $completedCount = collect($sections)->where('filled', true)->count();
                                $totalSections = count($sections);
                                $percentage = $totalSections > 0 ? round(($completedCount / $totalSections) * 100) : 0;
                            @endphp

                            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-6 gap-2">
                                <div>
                                    <h4 class="text-xl font-black text-gray-900">PDS Completion Status</h4>
                                    <p class="text-sm text-gray-500 font-medium italic">Civil Service record sections compliance.</p>
                                </div>
                                <div class="bg-indigo-600 text-white px-5 py-2 rounded-xl shadow-md leading-none">
                                    <span class="text-2xl font-black">{{ $percentage }}%</span>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="w-full bg-gray-100 rounded-full h-3 mb-8 overflow-hidden shadow-inner">
                                <div class="bg-indigo-600 h-full rounded-full transition-all duration-1000 ease-out"
                                    style="width: {{ $percentage }}%">
                                </div>
                            </div>

                            <!-- Section Grid (Full Width Responsive) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                @foreach($sections as $section)
                                    @php
                                        $review = $employee->pdsSectionReviews->where('section_name', $section['label'])->first();
                                        $reviewStatus = $review ? $review->status : 'pending';
                                        $reviewRemarks = $review ? $review->remarks : '';
                                        $changed = $pdsReviewChangedFields[$section['label']] ?? ['fields' => [], 'entries' => []];
                                    @endphp
                                    <button @click="reviewModalOpen = true; currentSection = '{{ $section['label'] }}'; sectionData = {{ json_encode($section['data']) }}; changedFields = @js($changed['fields']); changedEntries = @js($changed['entries']); reviewStatus = '{{ $reviewStatus === 'rejected' ? 'rejected' : 'approved' }}'; reviewRemarks = '{{ addslashes($reviewRemarks) }}'" class="group flex items-center p-4 bg-gray-50 rounded-xl border border-gray-100 shadow-sm transition-all duration-200 hover:border-indigo-300 hover:shadow-md cursor-pointer relative overflow-hidden text-left focus:outline-none">
                                        <!-- Hover indicator bar -->
                                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                        
                                        <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center mr-4 transition-transform group-hover:scale-110 {{ $section['filled'] ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-400 group-hover:bg-indigo-50 group-hover:text-indigo-500' }}">
                                            @if($section['filled'])
                                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                            @else
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                            @endif
                                        </div>
                                        <div class="overflow-hidden flex-1">
                                            <span class="block text-sm font-black truncate tracking-tight transition-colors {{ $section['filled'] ? 'text-gray-900 group-hover:text-emerald-700' : 'text-gray-400 group-hover:text-indigo-600' }}">{{ $section['label'] }}</span>
                                            <div class="flex items-center justify-between mt-0.5">
                                                <div class="flex items-center gap-1.5 leading-none">
                                                    <div class="w-1.5 h-1.5 rounded-full {{ $section['filled'] ? 'bg-emerald-500' : 'bg-gray-300 group-hover:bg-indigo-400' }}"></div>
                                                    <span class="text-[9px] font-black uppercase tracking-widest transition-colors {{ $section['filled'] ? 'text-emerald-600' : 'text-gray-400 group-hover:text-indigo-500' }}">
                                                        {{ $section['filled'] ? 'Saved' : 'Blank' }}
                                                    </span>
                                                    @if($reviewStatus === 'approved')
                                                        <span class="ml-2 text-[9px] font-black uppercase tracking-widest text-emerald-600 bg-emerald-100 px-1 rounded">Approved</span>
                                                    @elseif($reviewStatus === 'rejected')
                                                        <span class="ml-2 text-[9px] font-black uppercase tracking-widest text-red-600 bg-red-100 px-1 rounded">Rejected</span>
                                                    @endif
                                                    @if(count($changed['fields']) || count($changed['entries']))
                                                        <span class="ml-2 inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-widest text-red-600">
                                                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                                            Updated
                                                        </span>
                                                    @endif
                                                </div>
                                                <svg class="w-4 h-4 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </div>
                                        </div>
                                    </button>
                                @endforeach
                            </div>

                            <div class="mt-8 flex justify-center">
                                <a href="{{ route('employees.pds.show', $employee) }}" target="_blank" rel="noopener noreferrer" data-no-transition class="inline-flex items-center gap-2 rounded-xl bg-indigo-50 px-5 py-2.5 text-sm font-bold text-indigo-700 hover:bg-indigo-100 transition shadow-xs">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    {{ __('View Full PDS Record (Review)') }}
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Leave Credits Tab -->
                <div x-show="tab === 'leave-credits'" x-data="{ leaveCreditSearch: '', adjustModalOpen: {{ $errors->has('leave_credit_id') || $errors->has('operation') || $errors->has('amount') ? 'true' : 'false' }} }" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
                        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h3 class="text-xl font-black text-gray-900 tracking-tight">{{ __('Leave Credits') }}</h3>
                            </div>
                            <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center lg:w-auto">
                                <div class="relative w-full sm:w-[25rem] lg:w-[28rem]">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M21 21l-4.35-4.35M10.75 18.5a7.75 7.75 0 100-15.5 7.75 7.75 0 000 15.5z" />
                                        </svg>
                                    </span>
                                    <input
                                        type="search"
                                        x-model.debounce.150ms="leaveCreditSearch"
                                        placeholder="{{ __('Search leave type...') }}"
                                        class="block h-11 w-full rounded-xl border-gray-300 bg-white pl-10 pr-3 text-sm font-medium shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                </div>
                                <button type="button" @click="adjustModalOpen = true" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-indigo-600 px-5 text-xs font-black uppercase tracking-widest text-white shadow-sm transition hover:bg-indigo-700">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M12 5v14m7-7H5" />
                                    </svg>
                                    {{ __('Adjust Credits') }}
                                </button>
                            </div>
                        </div>

                        <div class="max-h-[34rem] overflow-y-auto overflow-x-hidden rounded-xl border border-gray-100">
                            <table class="w-full table-fixed divide-y divide-gray-100">
                                <colgroup>
                                    <col class="w-[42%]">
                                    <col class="w-[15%]">
                                    <col class="w-[12%]">
                                    <col class="w-[11%]">
                                    <col class="w-[10%]">
                                    <col class="w-[10%]">
                                </colgroup>
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('Leave Type') }}</th>
                                        <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-4 text-left text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('Available Balance') }}</th>
                                        <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-4 text-left text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('Earned') }}</th>
                                        <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-4 text-left text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('Used') }}</th>
                                        <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-4 text-left text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('Adjustment') }}</th>
                                        <th scope="col" class="sticky top-0 z-10 bg-gray-50 px-4 py-4 text-left text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('As Of') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @forelse($employee->leaveCredits->sortBy(fn($credit) => $credit->leaveType?->name ?? '') as $credit)
                                        @php
                                            $searchText = strtolower(collect([
                                                $credit->leaveType?->name,
                                                $credit->leaveType?->legal_basis,
                                                number_format((float) $credit->earned, 3),
                                                number_format((float) $credit->used, 3),
                                                number_format((float) $credit->adjusted, 3),
                                                number_format((float) $credit->balance, 3),
                                                $credit->accrued_as_of?->format('M d, Y'),
                                            ])->filter()->implode(' '));
                                        @endphp
                                        <tr
                                            x-show="@js($searchText).includes(leaveCreditSearch.toLowerCase().trim())"
                                            class="hover:bg-gray-50/70 transition-colors"
                                        >
                                            <td class="px-6 py-4 align-middle">
                                                <div class="text-sm font-bold text-gray-900">{{ $credit->leaveType?->name ?? __('Leave Type') }}</div>
                                                @if($credit->leaveType?->legal_basis)
                                                    <div
                                                        class="mt-1 text-xs italic leading-5 text-gray-400"
                                                        style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"
                                                        title="{{ $credit->leaveType->legal_basis }}"
                                                    >
                                                        {{ $credit->leaveType->legal_basis }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 align-middle text-sm font-black leading-5 text-gray-900">
                                                {{ number_format((float) $credit->balance, 3) }} {{ Str::plural('day', $credit->balance) }}
                                            </td>
                                            <td class="px-4 py-4 align-middle text-sm font-bold text-gray-700">
                                                {{ number_format((float) $credit->earned, 3) }}
                                            </td>
                                            <td class="px-4 py-4 align-middle text-sm font-bold text-gray-700">
                                                {{ number_format((float) $credit->used, 3) }}
                                            </td>
                                            <td class="px-4 py-4 align-middle text-sm font-bold {{ (float) $credit->adjusted < 0 ? 'text-red-600' : ((float) $credit->adjusted > 0 ? 'text-emerald-600' : 'text-gray-700') }}">
                                                {{ (float) $credit->adjusted > 0 ? '+' : '' }}{{ number_format((float) $credit->adjusted, 3) }}
                                            </td>
                                            <td class="px-4 py-4 align-middle text-sm font-medium text-gray-500">
                                                {{ $credit->accrued_as_of?->format('M d, Y') ?? '-' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-6 py-10 text-center text-sm font-medium text-gray-400">
                                                {{ __('No leave credits found for this employee.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div x-show="adjustModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="leave-credit-adjustment-title" role="dialog" aria-modal="true">
                            <div class="flex min-h-screen items-center justify-center px-4 py-6 text-center">
                                <div x-show="adjustModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/50" @click="adjustModalOpen = false"></div>

                                <div x-show="adjustModalOpen" x-transition class="relative w-full max-w-xl overflow-hidden rounded-2xl bg-white text-left shadow-xl">
                                    <div class="border-b border-gray-100 px-6 py-5">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <p class="text-[10px] font-black uppercase tracking-[0.25em] text-indigo-600">{{ __('Leave Credits') }}</p>
                                                <h3 id="leave-credit-adjustment-title" class="mt-1 text-xl font-black text-gray-900">{{ __('Adjust Credits') }}</h3>
                                            </div>
                                            <button type="button" @click="adjustModalOpen = false" class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <form method="POST" action="{{ route('employees.leave-credit-adjustments.store', $employee) }}" class="space-y-5 px-6 py-6">
                                        @csrf

                                        <div>
                                            <x-input-label for="leave_credit_id" :value="__('Leave Type')" />
                                            <select id="leave_credit_id" name="leave_credit_id" required class="mt-1 block w-full rounded-xl border-gray-300 text-sm font-semibold text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <option value="">{{ __('Select leave type') }}</option>
                                                @foreach($employee->leaveCredits->sortBy(fn($credit) => $credit->leaveType?->name ?? '') as $creditOption)
                                                    <option value="{{ $creditOption->id }}" @selected((string) old('leave_credit_id') === (string) $creditOption->id)>
                                                        {{ $creditOption->leaveType?->name ?? __('Leave Type') }} - {{ number_format((float) $creditOption->balance, 3) }} {{ Str::plural('day', $creditOption->balance) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <x-input-error class="mt-2" :messages="$errors->get('leave_credit_id')" />
                                        </div>

                                        <div class="grid gap-4 sm:grid-cols-[0.9fr_1.1fr]">
                                            <div>
                                                <x-input-label for="operation" :value="__('Action')" />
                                                <select id="operation" name="operation" required class="mt-1 block w-full rounded-xl border-gray-300 text-sm font-semibold text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option value="add" @selected(old('operation', 'add') === 'add')>{{ __('Add Credits') }}</option>
                                                    <option value="deduct" @selected(old('operation') === 'deduct')>{{ __('Deduct Credits') }}</option>
                                                </select>
                                                <x-input-error class="mt-2" :messages="$errors->get('operation')" />
                                            </div>
                                            <div>
                                                <x-input-label for="amount" :value="__('Amount')" />
                                                <x-text-input id="amount" name="amount" type="number" step="0.001" min="0.001" max="365" required class="mt-1 block w-full" :value="old('amount')" placeholder="0.000" />
                                                <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                                            </div>
                                        </div>

                                        <div>
                                            <x-input-label for="remarks" :value="__('Remarks')" />
                                            <textarea id="remarks" name="remarks" rows="3" class="mt-1 block w-full rounded-xl border-gray-300 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ __('Optional note for this adjustment') }}">{{ old('remarks') }}</textarea>
                                            <x-input-error class="mt-2" :messages="$errors->get('remarks')" />
                                        </div>

                                        <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                                            <button type="button" @click="adjustModalOpen = false" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-xs font-black uppercase tracking-widest text-gray-600 transition hover:bg-gray-50">
                                                {{ __('Cancel') }}
                                            </button>
                                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-black uppercase tracking-widest text-white shadow-sm transition hover:bg-indigo-700">
                                                {{ __('Save Adjustment') }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CTO Credits Tab -->
                <div x-show="tab === 'cto-credits'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                    @php
                        $ctoRequests = $employee->ctoRequests->sortByDesc('created_at');
                        $approvedCtoRequests = $employee->ctoRequests->where('status', 'approved');
                        $earnedCtoHours = $approvedCtoRequests->where('type', 'earn')->sum(fn ($request) => (float) $request->hours);
                        $usedCtoHours = $approvedCtoRequests->where('type', 'use')->sum(fn ($request) => (float) $request->hours);
                        $pendingCtoCount = $employee->ctoRequests->where('status', 'pending')->count();
                    @endphp

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
                        <div class="mb-6">
                            <h3 class="text-xl font-black text-gray-900 tracking-tight">{{ __('CTO Credits') }}</h3>
                            <p class="text-sm font-medium text-gray-500">{{ __('Current compensatory time-off balance and activity for this employee.') }}</p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-5">
                                <p class="text-[10px] font-black uppercase tracking-widest text-indigo-600">{{ __('Available Balance') }}</p>
                                <p class="mt-2 text-3xl font-black text-gray-900">{{ number_format((float) $employee->cto_balance, 2) }}</p>
                                <p class="mt-1 text-xs font-bold text-gray-500">{{ __('hours') }}</p>
                            </div>

                            <div class="rounded-xl border border-emerald-100 bg-white p-5">
                                <p class="text-[10px] font-black uppercase tracking-widest text-emerald-600">{{ __('Approved Earned') }}</p>
                                <p class="mt-2 text-2xl font-black text-gray-900">{{ number_format($earnedCtoHours, 2) }}</p>
                                <p class="mt-1 text-xs font-bold text-gray-500">{{ __('hours') }}</p>
                            </div>

                            <div class="rounded-xl border border-amber-100 bg-white p-5">
                                <p class="text-[10px] font-black uppercase tracking-widest text-amber-600">{{ __('Approved Used') }}</p>
                                <p class="mt-2 text-2xl font-black text-gray-900">{{ number_format($usedCtoHours, 2) }}</p>
                                <p class="mt-1 text-xs font-bold text-gray-500">{{ __('hours') }}</p>
                            </div>

                            <div class="rounded-xl border border-slate-100 bg-white p-5">
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">{{ __('Pending Requests') }}</p>
                                <p class="mt-2 text-2xl font-black text-gray-900">{{ $pendingCtoCount }}</p>
                                <p class="mt-1 text-xs font-bold text-gray-500">{{ __('requests') }}</p>
                            </div>
                        </div>

                        <div class="mt-6 overflow-x-auto rounded-xl border border-gray-100">
                            <table class="min-w-full divide-y divide-gray-100">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('Date') }}</th>
                                        <th scope="col" class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('Type') }}</th>
                                        <th scope="col" class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('Hours') }}</th>
                                        <th scope="col" class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('Status') }}</th>
                                        <th scope="col" class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('Balance After') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @forelse($ctoRequests->take(8) as $ctoRequest)
                                        @php
                                            $statusClass = match ($ctoRequest->status) {
                                                'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                                'rejected' => 'bg-red-50 text-red-700 border-red-100',
                                                default => 'bg-amber-50 text-amber-700 border-amber-100',
                                            };
                                        @endphp
                                        <tr class="hover:bg-gray-50/70 transition-colors">
                                            <td class="px-6 py-4 text-sm font-semibold text-gray-700">
                                                {{ $ctoRequest->date_start?->format('M d, Y') ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 text-sm font-bold text-gray-900">
                                                {{ $ctoRequest->type_label }}
                                            </td>
                                            <td class="px-6 py-4 text-sm font-bold text-gray-700">
                                                {{ number_format((float) $ctoRequest->hours, 2) }}
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-wider {{ $statusClass }}">
                                                    {{ ucfirst($ctoRequest->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm font-bold text-gray-700">
                                                {{ $ctoRequest->cto_balance_after !== null ? number_format((float) $ctoRequest->cto_balance_after, 2).' hrs' : '-' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-6 py-10 text-center text-sm font-medium text-gray-400">
                                                {{ __('No CTO requests found for this employee.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SALN Tab -->
                <div
                    x-show="tab === 'saln'"
                    x-data="{ salnReturnOpen: false, salnReturnAction: '', salnReturnRemarks: '' }"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    style="display: none;"
                >
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5">
                        <div class="flex justify-between items-center mb-5">
                            <h3 class="text-xl font-black text-gray-900 tracking-tight">{{ __('SALN Records') }}</h3>
                        </div>

                        @if($employee->salns && $employee->salns->count() > 0)
                            <div class="overflow-hidden rounded-xl border border-gray-100">
                                <table class="w-full table-fixed divide-y divide-gray-200">
                                    <colgroup>
                                        <col class="w-[13%]">
                                        <col class="w-[13%]">
                                        <col class="w-[17%]">
                                        <col class="w-[17%]">
                                        <col class="w-[17%]">
                                        <col class="w-[13%]">
                                        <col class="w-[10%]">
                                    </colgroup>
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-3 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">As of Date</th>
                                            <th scope="col" class="px-3 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Filing Type</th>
                                            <th scope="col" class="px-3 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Total Assets</th>
                                            <th scope="col" class="px-3 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Total Liabilities</th>
                                            <th scope="col" class="px-3 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Net Worth</th>
                                            <th scope="col" class="px-3 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Status</th>
                                            <th scope="col" class="px-3 py-3 text-center text-[10px] font-black text-gray-500 uppercase tracking-widest">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-100 text-sm">
                                        @foreach($employee->salns->sortByDesc('as_of_date') as $saln)
                                            @php
                                                $salnReviewStatus = strtolower((string) ($saln->review_status ?? 'pending'));
                                                $salnStatusClass = match ($salnReviewStatus) {
                                                    'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                                    'returned' => 'bg-rose-50 text-rose-700 border-rose-100',
                                                    default => 'bg-amber-50 text-amber-700 border-amber-100',
                                                };
                                                $salnStatusLabel = match ($salnReviewStatus) {
                                                    'approved' => 'Approved',
                                                    'returned' => 'Returned',
                                                    default => 'Pending',
                                                };
                                            @endphp
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="px-3 py-4 font-bold text-gray-900">{{ $saln->as_of_date->format('M d, Y') }}</td>
                                                <td class="px-3 py-4 text-gray-600">{{ str_replace('_', ' ', $saln->type_of_filing) }}</td>
                                                <td class="px-3 py-4 text-gray-600">₱ {{ number_format($saln->total_assets, 2) }}</td>
                                                <td class="px-3 py-4 text-gray-600">₱ {{ number_format($saln->total_liabilities, 2) }}</td>
                                                <td class="px-3 py-4 font-bold text-emerald-600">₱ {{ number_format($saln->net_worth, 2) }}</td>
                                                <td class="px-3 py-4">
                                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-wider {{ $salnStatusClass }}">{{ $salnStatusLabel }}</span>
                                                </td>
                                                <td class="px-3 py-4 text-right text-sm font-medium">
                                                    <div class="grid grid-cols-2 gap-2">
                                                    <a href="{{ route('salns.show', ['saln' => $saln, 'back' => route('employees.show', ['employee' => $employee, 'tab' => 'saln'])]) }}" data-no-transition class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700 transition-colors hover:bg-indigo-100 hover:text-indigo-900" title="View SALN Preview" aria-label="View SALN Preview">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        </svg>
                                                        <span class="sr-only">View SALN Preview</span>
                                                    </a>
                                                    @if($salnReviewStatus !== 'approved')
                                                        <form method="POST" action="{{ route('salns.review', $saln) }}" class="inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="review_status" value="approved">
                                                            <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100" title="Approve SALN" aria-label="Approve SALN">
                                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <button
                                                        type="button"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100"
                                                        title="Return SALN"
                                                        aria-label="Return SALN"
                                                        @click="salnReturnAction = '{{ route('salns.review', $saln) }}'; salnReturnRemarks = ''; salnReturnOpen = true"
                                                    >
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div x-show="salnReturnOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4">
                                <form method="POST" :action="salnReturnAction" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="review_status" value="returned">
                                    <h4 class="text-lg font-black text-slate-900">Return SALN</h4>
                                    <p class="mt-1 text-sm font-medium text-slate-500">Enter the remarks for the employee.</p>
                                    <textarea name="review_remarks" x-model="salnReturnRemarks" rows="4" class="mt-4 w-full rounded-xl border-gray-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required></textarea>
                                    <div class="mt-5 flex justify-end gap-3">
                                        <button type="button" @click="salnReturnOpen = false" class="rounded-lg px-4 py-2 text-xs font-black uppercase tracking-widest text-slate-500 hover:bg-slate-50">Cancel</button>
                                        <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-xs font-black uppercase tracking-widest text-white hover:bg-rose-700">Return</button>
                                    </div>
                                </form>
                            </div>
                        @else
                            <div class="text-center py-12 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
                                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400 shadow-sm">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <h3 class="text-lg font-black text-gray-900 mb-1">No SALN Records</h3>
                                <p class="text-gray-500 text-sm">This employee has not submitted any Statement of Assets, Liabilities, and Net Worth.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <!-- PDS Review Modal -->
        <div x-show="reviewModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-3 pb-16 pt-4 text-center sm:block sm:p-0">
                <div x-show="reviewModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="reviewModalOpen = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="reviewModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:w-[92vw] sm:max-w-5xl border border-gray-100">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 max-h-[80vh] overflow-y-auto">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-black text-gray-900" id="modal-title" x-text="'Review ' + currentSection"></h3>
                                
                                <!-- Data Viewer -->
                                <div class="mt-4 bg-gray-50 p-4 rounded-lg border border-gray-200 text-sm">
                                    <div class="mb-2 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                        <h4 class="font-bold text-gray-700">Section Data:</h4>
                                        <div class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-wider text-red-600" x-show="changedFields.length || changedEntries.length">
                                            <span class="h-2 w-2 rounded-full bg-red-500"></span>
                                            Changed after last review
                                        </div>
                                    </div>
                                    <template x-if="sectionData">
                                        <div class="space-y-1">
                                            <template x-if="Array.isArray(sectionData) && sectionData.length > 0">
                                                <div>
                                                    <template x-for="(item, index) in sectionData" :key="index">
                                                        <div class="mb-4 pb-2 border-b border-gray-200 last:border-0 last:mb-0 last:pb-0" :class="changedEntries.includes(index) ? 'border-l-4 border-red-400 bg-red-50/60 pl-3 pr-2' : ''">
                                                            <div class="mb-1 flex items-center gap-2">
                                                                <div class="font-bold text-gray-500 text-xs uppercase tracking-wider" :class="changedEntries.includes(index) ? 'text-red-700' : ''" x-text="'Entry ' + (index + 1)"></div>
                                                                <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-red-700" x-show="changedEntries.includes(index)">
                                                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                                                    Updated
                                                                </span>
                                                            </div>
                                                            <template x-for="(value, key) in item" :key="key">
                                                                <div class="grid grid-cols-3 gap-2 py-1" x-show="key !== 'id' && key !== 'employee_id' && key !== 'created_at' && key !== 'updated_at'">
                                                                    <div class="col-span-1 font-bold text-gray-600 capitalize truncate" :class="isChangedField(key, index) ? 'text-red-700' : ''">
                                                                        <span class="mr-1 inline-block h-2 w-2 rounded-full bg-red-500" x-show="isChangedField(key, index)"></span>
                                                                        <span x-text="key.replace(/_/g, ' ')"></span>
                                                                    </div>
                                                                    <div class="col-span-2 text-gray-900" :class="isChangedField(key, index) ? 'font-bold text-red-700' : ''" x-text="value === null ? '---' : value"></div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="!Array.isArray(sectionData)">
                                                <div>
                                                    <template x-for="(value, key) in sectionData" :key="key">
                                                        <div class="grid grid-cols-3 gap-2 py-1 border-b border-gray-100 last:border-0" :class="isChangedField(key) ? 'bg-red-50/70' : ''" x-show="key !== 'id' && key !== 'employee_id' && key !== 'created_at' && key !== 'updated_at'">
                                                            <div class="col-span-1 font-bold text-gray-600 capitalize truncate" :class="isChangedField(key) ? 'text-red-700' : ''">
                                                                <span class="mr-1 inline-block h-2 w-2 rounded-full bg-red-500" x-show="isChangedField(key)"></span>
                                                                <span x-text="key.replace(/_/g, ' ')"></span>
                                                            </div>
                                                            <div class="col-span-2 text-gray-900" :class="isChangedField(key) ? 'font-bold text-red-700' : ''" x-text="value === null || value === '' ? '---' : value"></div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!sectionData || (Array.isArray(sectionData) && sectionData.length === 0)">
                                        <div class="text-gray-500 italic">No data inputted for this section yet.</div>
                                    </template>
                                </div>

                                <!-- Review Form -->
                                <form id="review-form" action="{{ route('pds-reviews.store', $employee) }}" method="POST" class="mt-6 space-y-4" onsubmit="const remarks = this.elements['remarks']; if (this.elements['status'].value === 'rejected' && !remarks.value.trim()) { remarks.setCustomValidity('Remarks are required when rejecting this section.'); remarks.reportValidity(); event.preventDefault(); return false; } remarks.setCustomValidity('');">
                                    @csrf
                                    <input type="hidden" name="section_name" x-model="currentSection">
                                    
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-1">Status</label>
                                        <select name="status" x-model="reviewStatus" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm">
                                            <option value="approved">Approve</option>
                                            <option value="rejected">Reject</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-1">Remarks</label>
                                        <textarea name="remarks" x-model="reviewRemarks" rows="3" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"></textarea>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100">
                        <button type="submit" form="review-form" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-bold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Save Review
                        </button>
                        <button type="button" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" @click="reviewModalOpen = false">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- SALN Details Modal -->
        <div x-show="salnModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-3 pb-16 pt-4 text-center sm:block sm:p-0">
                <div x-show="salnModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="salnModalOpen = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="salnModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:w-[92vw] sm:max-w-6xl border border-gray-100">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 max-h-[80vh] overflow-y-auto">
                        <div class="flex items-center justify-between mb-6 border-b border-gray-100 pb-4">
                            <h3 class="text-2xl font-black text-gray-900 flex items-center gap-3" id="modal-title">
                                <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                SALN Details
                            </h3>
                            <button @click="salnModalOpen = false" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        
                        <template x-if="selectedSaln">
                            <div class="space-y-8 text-sm">
                                <!-- Filing Info -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-6 rounded-xl border border-gray-200">
                                    <div>
                                        <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">As of Date</p>
                                        <p class="text-lg font-black text-gray-900" x-text="new Date(selectedSaln.as_of_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Filing Type & Status</p>
                                        <p class="text-lg font-black text-gray-900">
                                            <span x-text="selectedSaln.type_of_filing"></span>
                                            <span class="text-gray-400 text-sm font-medium ml-2" x-text="'(' + selectedSaln.filing_status + ')'"></span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Summary -->
                                <div>
                                    <h4 class="font-black text-gray-900 text-base uppercase tracking-widest border-b-2 border-gray-100 pb-2 mb-4">Financial Summary</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="bg-indigo-50 p-4 rounded-xl border border-indigo-100">
                                            <p class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-1">Total Assets</p>
                                            <p class="text-xl font-black text-indigo-900" x-text="'₱ ' + Number(selectedSaln.total_assets).toLocaleString('en-US', {minimumFractionDigits: 2})"></p>
                                        </div>
                                        <div class="bg-red-50 p-4 rounded-xl border border-red-100">
                                            <p class="text-xs font-bold text-red-600 uppercase tracking-widest mb-1">Total Liabilities</p>
                                            <p class="text-xl font-black text-red-900" x-text="'₱ ' + Number(selectedSaln.total_liabilities).toLocaleString('en-US', {minimumFractionDigits: 2})"></p>
                                        </div>
                                        <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-100">
                                            <p class="text-xs font-bold text-emerald-600 uppercase tracking-widest mb-1">Net Worth</p>
                                            <p class="text-xl font-black text-emerald-900" x-text="'₱ ' + Number(selectedSaln.net_worth).toLocaleString('en-US', {minimumFractionDigits: 2})"></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Real Properties -->
                                <div>
                                    <h4 class="font-black text-gray-900 text-base uppercase tracking-widest border-b-2 border-gray-100 pb-2 mb-4">Real Properties</h4>
                                    <template x-if="selectedSaln.real_properties && selectedSaln.real_properties.length > 0">
                                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th scope="col" class="px-4 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Description</th>
                                                        <th scope="col" class="px-4 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Kind</th>
                                                        <th scope="col" class="px-4 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Location</th>
                                                        <th scope="col" class="px-4 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Year</th>
                                                        <th scope="col" class="px-4 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Mode</th>
                                                        <th scope="col" class="px-4 py-3 text-right text-[10px] font-black text-gray-500 uppercase tracking-widest">Acquisition Cost</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-100">
                                                    <template x-for="(prop, index) in selectedSaln.real_properties" :key="index">
                                                        <tr>
                                                            <td class="px-4 py-3 whitespace-nowrap text-gray-900 font-bold" x-text="prop.description"></td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-gray-600" x-text="prop.kind"></td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-gray-600" x-text="prop.location || prop.exact_location || ''"></td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-gray-600" x-text="prop.acquisition_year"></td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-gray-600" x-text="prop.acquisition_mode"></td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-right text-gray-900 font-bold" x-text="'₱ ' + Number(prop.acquisition_cost).toLocaleString('en-US', {minimumFractionDigits: 2})"></td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </template>
                                    <template x-if="!selectedSaln.real_properties || selectedSaln.real_properties.length === 0">
                                        <p class="text-gray-500 italic p-4 bg-gray-50 rounded-xl border border-dashed border-gray-200">No real properties declared.</p>
                                    </template>
                                </div>

                                <!-- Personal Properties -->
                                <div>
                                    <h4 class="font-black text-gray-900 text-base uppercase tracking-widest border-b-2 border-gray-100 pb-2 mb-4">Personal Properties</h4>
                                    <template x-if="selectedSaln.personal_properties && selectedSaln.personal_properties.length > 0">
                                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th scope="col" class="px-4 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Description</th>
                                                        <th scope="col" class="px-4 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Year Acquired</th>
                                                        <th scope="col" class="px-4 py-3 text-right text-[10px] font-black text-gray-500 uppercase tracking-widest">Acquisition Cost</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-100">
                                                    <template x-for="(prop, index) in selectedSaln.personal_properties" :key="index">
                                                        <tr>
                                                            <td class="px-4 py-3 whitespace-nowrap text-gray-900 font-bold" x-text="prop.description"></td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-gray-600" x-text="prop.year_acquired"></td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-right text-gray-900 font-bold" x-text="'₱ ' + Number(prop.acquisition_cost).toLocaleString('en-US', {minimumFractionDigits: 2})"></td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </template>
                                    <template x-if="!selectedSaln.personal_properties || selectedSaln.personal_properties.length === 0">
                                        <p class="text-gray-500 italic p-4 bg-gray-50 rounded-xl border border-dashed border-gray-200">No personal properties declared.</p>
                                    </template>
                                </div>

                                <!-- Liabilities -->
                                <div>
                                    <h4 class="font-black text-gray-900 text-base uppercase tracking-widest border-b-2 border-gray-100 pb-2 mb-4">Liabilities</h4>
                                    <template x-if="selectedSaln.liabilities && selectedSaln.liabilities.length > 0">
                                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th scope="col" class="px-4 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Nature</th>
                                                        <th scope="col" class="px-4 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest">Name of Creditor</th>
                                                        <th scope="col" class="px-4 py-3 text-right text-[10px] font-black text-gray-500 uppercase tracking-widest">Outstanding Balance</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-100">
                                                    <template x-for="(liability, index) in selectedSaln.liabilities" :key="index">
                                                        <tr>
                                                            <td class="px-4 py-3 whitespace-nowrap text-gray-900 font-bold" x-text="liability.nature"></td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-gray-600" x-text="liability.name_of_creditors"></td>
                                                            <td class="px-4 py-3 whitespace-nowrap text-right text-red-600 font-bold" x-text="'₱ ' + Number(liability.outstanding_balance).toLocaleString('en-US', {minimumFractionDigits: 2})"></td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </template>
                                    <template x-if="!selectedSaln.liabilities || selectedSaln.liabilities.length === 0">
                                        <p class="text-gray-500 italic p-4 bg-gray-50 rounded-xl border border-dashed border-gray-200">No liabilities declared.</p>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="bg-gray-50 px-4 py-4 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100">
                        <button type="button" class="w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-6 py-2.5 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors" @click="salnModalOpen = false">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
