<x-app-layout>
    <x-slot name="title">{{ __('Personal Data Sheet (CS Form No. 212)') }}</x-slot>

    <div class="py-8 bg-white min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Main Content Area -->
            <div class="bg-white border-2 border-gray-100 rounded-2xl overflow-hidden shadow-lg shadow-gray-200/40">
                <div class="p-8">
                    <!-- Top Ribbon/Header -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 pb-6 border-b-2 border-gray-50 gap-4">
                        <div>
                            <h3 class="text-xl font-black text-gray-900 tracking-tight">Employee PDS Record</h3>
                            <p class="text-sm text-gray-500 font-medium italic">Civil Service Records Management Dashboard.</p>
                        </div>
                        <div class="flex gap-4">
                            <a href="{{ route('pds.print-clean') }}" target="_blank" rel="noopener noreferrer" data-no-transition
                                class="inline-flex items-center px-6 py-2.5 bg-white border-2 border-indigo-100 rounded-xl font-bold text-sm text-indigo-700 shadow-sm hover:bg-indigo-50 active:scale-95 transition-all duration-300">
                                Clean
                            </a>
                            <a href="{{ route('pds.edit') }}"
                                class="inline-flex items-center px-6 py-2.5 bg-blue-800 border-2 border-blue-900 rounded-xl font-bold text-sm text-white shadow-lg shadow-blue-200 hover:bg-blue-800 active:scale-95 transition-all duration-300">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Update Information
                            </a>
                        </div>
                    </div>

                    @if(session('error'))
                        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg text-sm">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="mb-8 p-4 bg-emerald-50 border border-emerald-100 text-emerald-900 rounded-xl flex items-center shadow-sm">
                            <svg class="w-6 h-6 mr-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm font-bold">{{ session('success') }}</span>
                        </div>
                    @endif

                    @php
                        $sections = [
                            [
                                'label' => 'Personal Information',
                                'display' => 'I. Personal Information',
                                'tab' => 'personal',
                                'filled' => $employee->pdsPersonal && (
                                    filled($employee->pdsPersonal->surname) ||
                                    filled($employee->pdsPersonal->firstname) ||
                                    filled($employee->pdsPersonal->date_of_birth) ||
                                    filled($employee->pdsPersonal->email_address)
                                ),
                            ],
                            [
                                'label' => 'Family Background',
                                'display' => 'II. Family Background',
                                'tab' => 'family',
                                'filled' => ($employee->pdsFamily && (
                                    filled($employee->pdsFamily->father_surname) ||
                                    filled($employee->pdsFamily->mother_maiden_surname) ||
                                    filled($employee->pdsFamily->spouse_surname)
                                )) || $employee->pdsChildren->count() > 0,
                            ],
                            [
                                'label' => 'Educational Background',
                                'display' => 'III. Educational Background',
                                'tab' => 'education',
                                'filled' => $employee->pdsEducation->count() > 0,
                            ],
                            [
                                'label' => 'Civil Service Eligibility',
                                'display' => 'IV. Civil Service Eligibility',
                                'tab' => 'eligibility',
                                'filled' => $employee->pdsEligibilities->count() > 0,
                            ],
                            [
                                'label' => 'Work Experience',
                                'display' => 'V. Work Experience',
                                'tab' => 'work',
                                'filled' => $employee->pdsWorkExperiences->count() > 0,
                            ],
                            [
                                'label' => 'Voluntary Work or Involvement in Civic / Non-Government / People / Voluntary Organization/s',
                                'display' => 'VI. Voluntary Work',
                                'tab' => 'voluntary',
                                'filled' => $employee->pdsVoluntaryWorks->count() > 0,
                                'review_names' => ['Voluntary Work or Involvement in Civic / Non-Government / People / Voluntary Organization/s', 'Voluntary Work'],
                            ],
                            [
                                'label' => 'Learning and Development (L&D) Interventions/Training Programs Attended',
                                'display' => 'VII. Learning and Development',
                                'tab' => 'training',
                                'filled' => $employee->pdsTrainings->count() > 0,
                                'review_names' => ['Learning and Development (L&D) Interventions/Training Programs Attended', 'Training', 'Learning and Development'],
                            ],
                            [
                                'label' => 'Other Information',
                                'display' => 'VIII. Other Information',
                                'tab' => 'otherinfo',
                                'filled' => $employee->pdsOthers->count() > 0 || $employee->pdsReferences->count() >= 3 || $employee->pdsQuestionnaire || $employee->pdsGovId,
                                'review_names' => ['Other Information', 'References', 'Questionnaire'],
                            ],
                        ];
                        $sectionReview = function ($section) use ($employee) {
                            $names = $section['review_names'] ?? [$section['label']];

                            return $employee->pdsSectionReviews->first(function ($review) use ($names) {
                                return in_array($review->section_name, $names, true);
                            });
                        };
                        $completedCount = collect($sections)->where('filled', true)->count();
                        $totalSections = count($sections);
                        $percentage = $totalSections > 0 ? round(($completedCount / $totalSections) * 100) : 0;
                        $approvedCount = collect($sections)->filter(function ($section) use ($sectionReview) {
                            return optional($sectionReview($section))->status === 'approved';
                        })->count();
                        $isAllApproved = $approvedCount === $totalSections;
                    @endphp

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                        <!-- Left Column: Profile Summary Card -->
                        <div class="lg:col-span-4">
                            <div class="bg-gray-50/50 border-2 border-gray-100 p-6 rounded-2xl shadow-sm">
                                <div class="flex flex-col items-center text-center">
                                    <x-profile-avatar :employee="$employee" size="2xl" variant="indigo" rounded="2xl" class="shadow-xl mb-4" />
                                    <div class="space-y-1">
                                        <h4 class="text-xl font-black text-gray-900">
                                            {{ $employee->firstname }} {{ $employee->lastname }}
                                        </h4>
                                        <div class="flex items-center justify-center gap-2 mt-1">
                                            <p class="inline-block px-3 py-1 bg-blue-100 text-blue-900 text-[10px] font-black uppercase tracking-widest rounded-full">
                                                {{ ucfirst($employee->position) }}
                                            </p>
                                        </div>
                                        
                                        <div class="mt-3">
                                            @if($isAllApproved)
                                                <span class="inline-flex items-center px-3 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase tracking-widest rounded-full border border-emerald-200">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                                    HR Approved
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-3 py-1 bg-amber-100 text-amber-800 text-[10px] font-black uppercase tracking-widest rounded-full border border-amber-200">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    Pending HR Approval
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-8 pt-8 border-t border-gray-200/60 space-y-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-white border border-gray-200 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 014 0"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-[9px] uppercase font-black text-gray-400 tracking-widest mb-0.5">Employee ID</p>
                                            <p class="text-base font-black text-gray-800">{{ $employee->id ?? '---' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-white border border-gray-200 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        </div>
                                        <div class="overflow-hidden">
                                            <p class="text-[9px] uppercase font-black text-gray-400 tracking-widest mb-0.5">Primary Email</p>
                                            <p class="text-sm font-bold text-gray-800 truncate tracking-tight">{{ $employee->pdsPersonal?->email_address ?? $employee->user->email }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Status & Progress -->
                        <div class="lg:col-span-8 space-y-8">
                            <div class="bg-gray-50/50 border-2 border-gray-100 p-6 rounded-2xl shadow-sm">
                                <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-6 gap-2">
                                    <div>
                                        <h4 class="text-lg font-black text-gray-900">Completion Status</h4>
                                        <p class="text-sm text-gray-500 font-medium italic">Official CS Form 212 sections saved in your PDS.</p>
                                        <p class="mt-1 text-xs text-gray-400 font-semibold">
                                            {{ $completedCount }} of {{ $totalSections }} sections saved. HR review status is shown separately on each section.
                                        </p>
                                    </div>
                                    <div class="bg-blue-900 text-white px-5 py-2 rounded-xl shadow-md border-b-2 border-blue-900 leading-none">
                                        <span class="text-2xl font-black">{{ $percentage }}%</span>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div class="w-full bg-white border-2 border-gray-50 rounded-2xl h-4 mb-8 overflow-hidden shadow-inner flex items-center p-1">
                                    <div class="bg-blue-900 h-full rounded-xl transition-all duration-1000 ease-out shadow-sm"
                                        style="width: {{ $percentage }}%">
                                    </div>
                                </div>

                                <!-- Section Grid -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    @foreach($sections as $section)
                                        @php
                                            $review = $sectionReview($section);
                                            $reviewStatus = $review ? $review->status : 'pending';
                                        @endphp
                                        <a href="{{ route('pds.edit', ['tab' => $section['tab']]) }}" class="group flex items-center p-4 bg-white rounded-xl border border-gray-100 shadow-sm transition-all duration-200 hover:border-blue-300 hover:shadow-md cursor-pointer relative overflow-hidden">
                                            <!-- Hover indicator bar -->
                                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                            
                                            <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center mr-4 transition-transform group-hover:scale-110 {{ $section['filled'] ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-400 group-hover:bg-blue-50 group-hover:text-blue-500' }}">
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
                                                <span title="{{ $section['label'] }}" class="block text-sm font-black truncate tracking-tight transition-colors {{ $section['filled'] ? 'text-gray-900 group-hover:text-emerald-700' : 'text-gray-400 group-hover:text-blue-600' }}">{{ $section['display'] }}</span>
                                                <div class="flex items-center justify-between mt-0.5">
                                                    <div class="flex items-center gap-1.5 leading-none flex-wrap">
                                                        <div class="w-1.5 h-1.5 rounded-full {{ $section['filled'] ? 'bg-emerald-500' : 'bg-gray-300 group-hover:bg-blue-400' }}"></div>
                                                        <span class="text-[9px] font-black uppercase tracking-widest transition-colors {{ $section['filled'] ? 'text-emerald-600' : 'text-gray-400 group-hover:text-blue-500' }}">
                                                            {{ $section['filled'] ? 'Saved' : 'Blank' }}
                                                        </span>
                                                        @if($section['filled'])
                                                            @if($reviewStatus === 'approved')
                                                                <span class="ml-1 text-[9px] font-black uppercase tracking-widest text-emerald-600 bg-emerald-100 px-1.5 py-0.5 rounded">Approved</span>
                                                            @elseif($reviewStatus === 'rejected')
                                                                <span class="ml-1 text-[9px] font-black uppercase tracking-widest text-red-600 bg-red-100 px-1.5 py-0.5 rounded">Rejected</span>
                                                            @else
                                                                <span class="ml-1 text-[9px] font-black uppercase tracking-widest text-amber-600 bg-amber-100 px-1.5 py-0.5 rounded">Pending Review</span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                    <svg class="w-4 h-4 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
