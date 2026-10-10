<x-app-layout>
    <x-slot name="title">{{ __('Update Personal Data Sheet (CS Form No. 212)') }}</x-slot>

    @php
        $isReadOnly = $isReadOnly ?? false;
        $pdsReviewChangedInputNames = $pdsReviewChangedInputNames ?? [];
    @endphp

    <style>
        /* Always-visible field background only */
        .pds-form-body input:not([type="radio"]):not([type="checkbox"]):not([type="hidden"]):not([type="file"]),
        .pds-form-body select,
        .pds-form-body textarea {
            background-color: #eef2ff !important;
        }

        /* Radio button styling for high contrast and clear visibility */
        .pds-line-page input[type="radio"] {
            appearance: auto !important;
            -webkit-appearance: auto !important;
            accent-color: #3730a3 !important;
            width: 17px !important;
            height: 17px !important;
            cursor: pointer;
            margin: 0 !important;
            vertical-align: middle;
        }
        .pds-line-page input[type="radio"]:checked {
            accent-color: #3730a3 !important;
        }

        @if($isReadOnly)
        .pds-line-page .pds-form-body input:not([type="radio"]):not([type="checkbox"]):not([type="file"]),
        .pds-line-page .pds-form-body select,
        .pds-line-page .pds-form-body textarea {
            pointer-events: none !important;
            user-select: text !important;
            background-color: #f8fafc !important;
            border-bottom-style: solid !important;
            border-bottom-color: #cbd5e1 !important;
            color: #1e293b !important;
            cursor: default !important;
        }
        .pds-line-page .pds-form-body input[type="radio"],
        .pds-line-page .pds-form-body input[type="checkbox"] {
            pointer-events: none !important;
            cursor: default !important;
            accent-color: #3730a3 !important;
        }
        .pds-line-page .pds-form-body button:not([type="submit"]),
        .pds-line-page .pds-form-body input[type="file"] {
            display: none !important;
        }
        @endif
    </style>

    @php
        $allowedTabs = ['personal', 'family', 'education', 'eligibility', 'work', 'voluntary', 'training', 'otherinfo', 'declaration'];
        $initialTab = in_array(request('tab'), $allowedTabs, true) ? request('tab') : 'personal';
    @endphp

    @php
        $yearOptions = range(now()->year, 1950);
    @endphp

    <div class="pds-line-page min-h-screen bg-slate-50 py-4" x-data="{ 
        init() {
            this.normalizeMobileNumber();
        },
        tab: @js($initialTab),
        children: {{ $employee->pdsChildren->count() > 0 ? $employee->pdsChildren->toJson() : '[{fullname: \'\', date_of_birth: \'\'}]' }},
        education: {{ $employee->pdsEducation->count() > 0 ? $employee->pdsEducation->toJson() : '[{level: \'\', school_name: \'\', course: \'\', period_from: \'\', period_to: \'\', highest_level: \'\', year_graduated: \'\', honors: \'\'}]' }},
        eligibility: {{ $employee->pdsEligibilities->count() > 0 ? $employee->pdsEligibilities->toJson() : '[{title: \'\', rating: \'\', date_of_exam: \'\', place_of_exam: \'\', license_number: \'\', license_validity: \'\'}]' }},
        work: {{ $employee->pdsWorkExperiences->count() > 0 ? $employee->pdsWorkExperiences->toJson() : '[{date_from: \'\', date_to: \'\', position_title: \'\', company: \'\', monthly_salary: \'\', salary_grade: \'\', appointment_status: \'\', is_gov_service: 0}]' }},
        training: {{ $employee->pdsTrainings->count() > 0 ? $employee->pdsTrainings->toJson() : '[{title: \'\', date_from: \'\', date_to: \'\', number_of_hours: \'\', attachment_path: \'\', type: \'\', conducted_by: \'\'}]' }},
        voluntary: {{ $employee->pdsVoluntaryWorks->count() > 0 ? $employee->pdsVoluntaryWorks->toJson() : '[{organization_name: \'\', date_from: \'\', date_to: \'\', number_of_hours: \'\', position: \'\'}]' }},
        others: {{ $employee->pdsOthers->count() > 0 ? $employee->pdsOthers->toJson() : '[{type: \'Skill\', description: \'\'}]' }},
        references: {{ $employee->pdsReferences->count() > 0 ? $employee->pdsReferences->toJson() : '[{name: \'\', address: \'\', telephone_no: \'\'}, {name: \'\', address: \'\', telephone_no: \'\'}, {name: \'\', address: \'\', telephone_no: \'\'}]' }},
        addresses: {
            res_region: @js(old('personal.res_region', $employee->pdsPersonal?->res_region)),
            res_province: @js(old('personal.res_province', $employee->pdsPersonal?->res_province)),
            res_city: @js(old('personal.res_city', $employee->pdsPersonal?->res_city)),
            res_barangay: @js(old('personal.res_barangay', $employee->pdsPersonal?->res_barangay)),
            res_zip_code: @js(old('personal.res_zip_code', $employee->pdsPersonal?->res_zip_code)),
            perm_region: @js(old('personal.perm_region', $employee->pdsPersonal?->perm_region)),
            perm_province: @js(old('personal.perm_province', $employee->pdsPersonal?->perm_province)),
            perm_city: @js(old('personal.perm_city', $employee->pdsPersonal?->perm_city)),
            perm_barangay: @js(old('personal.perm_barangay', $employee->pdsPersonal?->perm_barangay)),
            perm_zip_code: @js(old('personal.perm_zip_code', $employee->pdsPersonal?->perm_zip_code))
        },
        mobile_no: @js(old('personal.mobile_no', $employee->pdsPersonal?->mobile_no)),
        locationOptions: {
            regions: @js($locationOptionSets['ph_regions']),
            provinces: @js($locationOptionSets['ph_provinces']),
            cities: @js($locationOptionSets['ph_cities']),
            barangays: @js($locationOptionSets['ph_barangays'])
        },
        cityZipCodes: @js($cityZipCodes),
        optionsFor(options, parentValue) {
            if (!parentValue) return options;

            const linkedOptions = options.filter((option) => String(option.parent_value || '') === String(parentValue));

            return linkedOptions.length ? linkedOptions : options.filter((option) => !option.parent_value);
        },
        provinceOptions(prefix) {
            return this.optionsFor(this.locationOptions.provinces, this.addresses[`${prefix}_region`]);
        },
        cityOptions(prefix) {
            return this.optionsFor(this.locationOptions.cities, this.addresses[`${prefix}_province`]);
        },
        barangayOptions(prefix) {
            return this.optionsFor(this.locationOptions.barangays, this.addresses[`${prefix}_city`]);
        },
        resetAddress(prefix, level) {
            if (level === 'region') {
                this.addresses[`${prefix}_province`] = '';
                this.addresses[`${prefix}_city`] = '';
                this.addresses[`${prefix}_barangay`] = '';
                this.addresses[`${prefix}_zip_code`] = '';
            }

            if (level === 'province') {
                this.addresses[`${prefix}_city`] = '';
                this.addresses[`${prefix}_barangay`] = '';
                this.addresses[`${prefix}_zip_code`] = '';
            }

            if (level === 'city') {
                this.addresses[`${prefix}_barangay`] = '';
                this.fillZipCode(prefix);
            }
        },
        fillZipCode(prefix) {
            const province = this.addresses[`${prefix}_province`] || '';
            const city = this.addresses[`${prefix}_city`] || '';
            const selectedKey = this.normalizeLocationKey(`${province}|${city}`);
            const zipMatch = Object.entries(this.cityZipCodes)
                .find(([key]) => this.normalizeLocationKey(key) === selectedKey);
            const zip = zipMatch ? zipMatch[1] : '';

            this.addresses[`${prefix}_zip_code`] = zip;
        },
        normalizeLocationKey(value) {
            return String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
        },
        copyResidentialAddress() {
            ['house_no', 'street', 'subdivision'].forEach((key) => {
                const source = document.querySelector(`[name=&quot;personal[res_${key}]&quot;]`);
                const target = document.querySelector(`[name=&quot;personal[perm_${key}]&quot;]`);

                if (source && target) target.value = source.value;
            });

            ['region', 'province', 'city', 'barangay', 'zip_code'].forEach((key) => {
                this.addresses[`perm_${key}`] = this.addresses[`res_${key}`] || '';
            });
        },
        formatMobileNumber(value) {
            const digits = String(value || '').replace(/\D/g, '').slice(0, 11);
            const first = digits.slice(0, 4);
            const second = digits.slice(4, 7);
            const third = digits.slice(7, 11);

            return [first, second, third].filter(Boolean).join('-');
        },
        normalizeMobileNumber() {
            this.mobile_no = this.formatMobileNumber(this.mobile_no);
        },
        signaturePreview: @js($employee->effective_signature_url),
        photoPreview: @js($employee->profile_picture_url)
    }">
        <div class="mx-auto w-full px-1 sm:px-2 lg:px-3">
            <div class="mb-6 flex flex-col gap-5 print:hidden">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    @if(! $isReadOnly)
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-600 shadow-sm transition hover:bg-gray-50 hover:text-indigo-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                            </svg>
                            {{ __('Back') }}
                        </a>
                    @else
                        <a href="{{ route('employees.show', $employee) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-600 shadow-sm transition hover:bg-gray-50 hover:text-indigo-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                            </svg>
                            {{ __('Back') }}
                        </a>
                    @endif
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('pds.download') }}" data-no-transition class="inline-flex h-11 min-w-[8.5rem] items-center justify-center rounded-lg bg-white px-4 text-xs font-black uppercase tracking-widest text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-50">Download PDF</a>
                        {{--
                        <a href="{{ route('pds.download-excel') }}" data-no-transition class="inline-flex h-11 min-w-[8.5rem] items-center justify-center rounded-lg bg-white px-4 text-xs font-black uppercase tracking-widest text-emerald-700 ring-1 ring-emerald-100 hover:bg-emerald-50">Download Excel</a>
                        --}}
                        <a href="{{ route('pds.print') }}" data-no-transition target="_blank" rel="noopener noreferrer" class="inline-flex h-11 min-w-[8.5rem] items-center justify-center rounded-lg bg-indigo-700 px-4 text-xs font-black uppercase tracking-widest text-white hover:bg-indigo-800">Preview PDS</a>
                    </div>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Personal Data Sheet</h2>
                    <p class="mt-2 text-sm font-medium text-slate-600">Please put N/A for fields if not applicable.</p>
                </div>
            </div>

            <div class="pds-paper w-full bg-white">
                <div class="pds-official-header hidden border-b border-slate-200 px-6 py-5 text-center">
                    <p class="text-[10px] font-bold">CS Form No. 212</p>
                    <h1 class="text-2xl font-black tracking-widest text-black">PERSONAL DATA SHEET</h1>
                    <p class="text-[10px] font-bold">Revised 2017</p>
                    <p class="mx-auto mt-3 max-w-3xl text-[10px] font-bold leading-relaxed text-left">
                        WARNING: Any misrepresentation made in the Personal Data Sheet and the Work Experience Sheet shall cause the filing of administrative/criminal case/s against the person concerned. Print legibly. Tick appropriate boxes and indicate N/A if not applicable.
                    </p>
                </div>
                
                <!-- Tab Navigation -->
                <div class="border-b border-slate-200 print:hidden">
                    <nav class="flex items-center gap-4 overflow-x-auto px-3 pt-4 sm:px-4 lg:px-5" aria-label="PDS sections">
                        <button @click="tab = 'personal'" :class="tab === 'personal' ? 'border-indigo-700 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-800'" class="shrink-0 whitespace-nowrap border-b-2 px-1 pb-3 text-xs font-black uppercase tracking-[0.16em] transition-all duration-200">
                            I. Personal Information
                        </button>
                        <button @click="tab = 'family'" :class="tab === 'family' ? 'border-indigo-700 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-800'" class="shrink-0 whitespace-nowrap border-b-2 px-1 pb-3 text-xs font-black uppercase tracking-[0.16em] transition-all duration-200">
                            II. Family Background
                        </button>
                        <button @click="tab = 'education'" :class="tab === 'education' ? 'border-indigo-700 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-800'" class="shrink-0 whitespace-nowrap border-b-2 px-1 pb-3 text-xs font-black uppercase tracking-[0.16em] transition-all duration-200">
                            III. Education
                        </button>
                        <button @click="tab = 'eligibility'" :class="tab === 'eligibility' ? 'border-indigo-700 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-800'" class="shrink-0 whitespace-nowrap border-b-2 px-1 pb-3 text-xs font-black uppercase tracking-[0.16em] transition-all duration-200">
                            IV. Eligibility
                        </button>
                        <button @click="tab = 'work'" :class="tab === 'work' ? 'border-indigo-700 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-800'" class="shrink-0 whitespace-nowrap border-b-2 px-1 pb-3 text-xs font-black uppercase tracking-[0.16em] transition-all duration-200">
                            V. Work Experience
                        </button>
                        <button @click="tab = 'voluntary'" :class="tab === 'voluntary' ? 'border-indigo-700 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-800'" class="shrink-0 whitespace-nowrap border-b-2 px-1 pb-3 text-xs font-black uppercase tracking-[0.16em] transition-all duration-200">
                            VI. Voluntary Work
                        </button>
                        <button @click="tab = 'training'" :class="tab === 'training' ? 'border-indigo-700 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-800'" class="shrink-0 whitespace-nowrap border-b-2 px-1 pb-3 text-xs font-black uppercase tracking-[0.16em] transition-all duration-200">
                            VII. L&D
                        </button>
                        <button @click="tab = 'otherinfo'" :class="tab === 'otherinfo' ? 'border-indigo-700 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-800'" class="shrink-0 whitespace-nowrap border-b-2 px-1 pb-3 text-xs font-black uppercase tracking-[0.16em] transition-all duration-200">
                            VIII. Other Information
                        </button>
                        <button @click="tab = 'declaration'" :class="tab === 'declaration' ? 'border-indigo-700 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-800'" class="shrink-0 whitespace-nowrap border-b-2 px-1 pb-3 text-xs font-black uppercase tracking-[0.16em] transition-all duration-200">
                            IX. Declaration
                        </button>
                    </nav>
                </div>

                <!-- Validation Errors -->
                @if ($errors->any())
                    <div class="p-6 bg-red-50 border-b-2 border-red-100">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="text-sm font-black text-red-800 uppercase tracking-widest">Saving Failed</h3>
                        </div>
                        <ul class="list-disc list-inside text-xs font-bold text-red-600 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!($isReadOnly ?? false))
                    <form action="{{ route('pds.update') }}" method="POST" class="pds-form-body px-3 pb-8 pt-6 sm:px-4 lg:px-5" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                @else
                    <div class="pds-form-body px-3 pb-8 pt-6 sm:px-4 lg:px-5">
                @endif

                    <!-- Section I: Personal Information -->
                    <div x-show="tab === 'personal'" x-cloak class="space-y-8 animate-fade-in">
                        <!-- SECTION I: PERSONAL INFORMATION -->
                        <div>
                            <div class="pds-section-heading flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-base font-black text-gray-900 uppercase tracking-tight">Personal Information</h3>
                                </div>
                                @php $personalReview = $employee->pdsSectionReviews->where('section_name', 'Personal Information')->first(); @endphp

    @php
        $yearOptions = range(now()->year, 1950);
    @endphp
                                @if($personalReview && $personalReview->remarks)
                                    <span class="bg-yellow-100 text-yellow-800 text-[10px] px-2 py-1 rounded font-bold border border-yellow-200">Has HR Remarks</span>
                                @endif
                            </div>
                            @if($personalReview && $personalReview->remarks)
                                <div class="mb-6 flex items-start gap-3 border-l-4 border-yellow-300 bg-yellow-50/60 py-3 pl-4 pr-3">
                                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <div>
                                        <span class="block text-[10px] font-black text-yellow-800 uppercase tracking-widest mb-1">HR Remarks ({{ ucfirst($personalReview->status) }})</span>
                                        <p class="text-sm font-medium text-yellow-900 leading-relaxed">{{ $personalReview->remarks }}</p>
                                    </div>
                                </div>
                            @endif
                            
                            <div class="space-y-8">
                                <div class="pds-field-table pds-field-table-4">
                                    <!-- 1. Surname -->
                                    <div class="space-y-1">
                                        <x-input-label value="Surname" :required="true" class="text-[10px] font-black uppercase text-gray-500 tracking-wider" />
                                        <x-text-input class="block w-full text-sm font-bold border-gray-100 bg-gray-50/30" name="personal[surname]" :value="old('personal.surname', $employee->pdsPersonal?->surname ?? $employee->lastname)" />
                                    </div>
                                    <!-- 2. First Name & Extension -->
                                    <div class="contents">
                                        <div class="space-y-1">
                                            <x-input-label value="First Name" :required="true" class="text-[10px] font-black uppercase text-gray-500 tracking-wider" />
                                            <x-text-input class="block w-full text-sm font-bold border-gray-100 bg-gray-50/30" name="personal[firstname]" :value="old('personal.firstname', $employee->pdsPersonal?->firstname ?? $employee->firstname)" />
                                        </div>
                                        <div class="space-y-1">
                                            <x-input-label value="Middle Name" :required="true" class="text-[10px] font-black uppercase text-gray-500 tracking-wider" />
                                            <x-text-input class="block w-full text-sm font-bold border-gray-100 bg-gray-50/30" name="personal[middlename]" :value="old('personal.middlename', $employee->pdsPersonal?->middlename ?? $employee->middlename)" />
                                        </div>
                                        <div class="space-y-1">
                                            <x-input-label value="Extension (JR., SR)" class="text-[9px] font-black uppercase text-gray-400 tracking-tighter" />
                                            <select name="personal[name_extension]" class="w-full rounded-xl border-gray-100 text-sm font-bold">
                                                <option value="">Select extension</option>
                                                @foreach($utilityOptionSets['name_extensions'] as $option)
                                                    <option value="{{ $option->value }}" {{ old('personal.name_extension', $employee->pdsPersonal?->name_extension) == $option->value ? 'selected' : '' }}>{{ $option->label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- 3. DOB & 4. POB -->
                                    <div class="contents">
                                        <div class="space-y-1">
                                            <x-input-label value="Date of Birth" :required="true" class="text-[10px] font-black uppercase text-gray-500 tracking-wider" />
                                            <x-text-input type="date" class="block w-full text-sm font-bold border-gray-100" name="personal[date_of_birth]" :value="old('personal.date_of_birth', $employee->pdsPersonal?->date_of_birth)" />
                                        </div>
                                        <div class="space-y-1">
                                            <x-input-label value="Place of Birth" :required="true" class="text-[10px] font-black uppercase text-gray-500 tracking-wider" />
                                            <x-text-input class="block w-full text-sm font-bold border-gray-100" name="personal[place_of_birth]" :value="old('personal.place_of_birth', $employee->pdsPersonal?->place_of_birth)" />
                                        </div>
                                    </div>

                                    <!-- 5. Sex & 6. Civil Status -->
                                    <div class="contents">
                                        <div class="space-y-1">
                                            <x-input-label value="Sex" :required="true" class="text-[10px] font-black uppercase text-gray-500 tracking-wider" />
                                            <select name="personal[sex]" class="w-full border-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm text-sm font-bold">
                                                <option value="">Select</option>
                                                <option value="Male" {{ old('personal.sex', $employee->pdsPersonal?->sex) == 'Male' ? 'selected' : '' }}>Male</option>
                                                <option value="Female" {{ old('personal.sex', $employee->pdsPersonal?->sex) == 'Female' ? 'selected' : '' }}>Female</option>
                                            </select>
                                        </div>
                                        <div class="space-y-1">
                                            <x-input-label value="Civil Status" :required="true" class="text-[10px] font-black uppercase text-gray-500 tracking-wider" />
                                            @php
                                                $currCivilStatus = strtolower(old('personal.civil_status', $employee->pdsPersonal?->civil_status ?? ''));
                                            @endphp

    @php
        $yearOptions = range(now()->year, 1950);
    @endphp
                                            <select name="personal[civil_status]" class="w-full border-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm text-sm font-bold">
                                                <option value="">Select</option>
                                                @foreach($utilityOptionSets['civil_statuses'] as $option)
                                                    <option value="{{ $option->value }}" {{ old('personal.civil_status', $employee->pdsPersonal?->civil_status) == $option->value ? 'selected' : '' }}>{{ $option->label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- 7. Height, 8. Weight, 9. Blood -->
                                    <div class="contents">
                                        <div class="space-y-1">
                                            <x-input-label value="Height (cm)" :required="true" class="text-[10px] font-black uppercase text-gray-500 tracking-wider" />
                                            <x-text-input class="block w-full text-sm font-bold border-gray-100" name="personal[height_m]" :value="old('personal.height_m', \App\Support\PdsFormatter::heightCm($employee->pdsPersonal?->height_m, ''))" />
                                        </div>
                                        <div class="space-y-1">
                                            <x-input-label value="Weight (kg)" :required="true" class="text-[10px] font-black uppercase text-gray-500 tracking-wider" />
                                            <x-text-input class="block w-full text-sm font-bold border-gray-100" name="personal[weight_kg]" :value="old('personal.weight_kg', $employee->pdsPersonal?->weight_kg)" />
                                        </div>
                                        <div class="space-y-1">
                                            <x-input-label value="Blood Type" :required="true" class="text-[10px] font-black uppercase text-gray-500 tracking-wider" />
                                            <select name="personal[blood_type]" class="w-full rounded-xl border-gray-100 text-sm font-bold">
                                                <option value="">Select blood type</option>
                                                @foreach($utilityOptionSets['blood_types'] as $option)
                                                    <option value="{{ $option->value }}" {{ old('personal.blood_type', $employee->pdsPersonal?->blood_type) == $option->value ? 'selected' : '' }}>{{ $option->label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- IDs 10-15 -->
                                    <div class="col-span-full border-t border-slate-100 pt-4">
                                        <div class="pds-field-table pds-field-table-4">
                                        <div class="space-y-1"><x-input-label value="UMID ID NO." class="text-[9px] font-black text-gray-400" /><x-text-input class="w-full text-xs font-bold border-gray-100" name="personal[umid_no]" :value="old('personal.umid_no', $employee->pdsPersonal?->umid_no)" /></div>
                                        <div class="space-y-1"><x-input-label value="PAG-IBIG ID NO." class="text-[9px] font-black text-gray-400" /><x-text-input class="w-full text-xs font-bold border-gray-100" name="personal[pagibig_id_no]" :value="old('personal.pagibig_id_no', $employee->pdsPersonal?->pagibig_id_no)" /></div>
                                        <div class="space-y-1"><x-input-label value="PHILHEALTH NO." class="text-[9px] font-black text-gray-400" /><x-text-input class="w-full text-xs font-bold border-gray-100" name="personal[philhealth_no]" :value="old('personal.philhealth_no', $employee->pdsPersonal?->philhealth_no)" /></div>
                                        <div class="space-y-1"><x-input-label value="SSS NO." class="text-[9px] font-black text-gray-400" /><x-text-input class="w-full text-xs font-bold border-gray-100" name="personal[sss_no]" :value="old('personal.sss_no', $employee->pdsPersonal?->sss_no)" /></div>
                                        <div class="space-y-1"><x-input-label value="PhilSys No. (PSN)" class="text-[9px] font-black text-gray-400" /><x-text-input class="w-full text-xs font-bold border-gray-100" name="personal[philsys_no]" :value="old('personal.philsys_no', $employee->pdsPersonal?->philsys_no)" /></div>
                                        <div class="space-y-1"><x-input-label value="TIN NO." class="text-[9px] font-black text-gray-400" /><x-text-input class="w-full text-xs font-bold border-gray-100" name="personal[tin_no]" :value="old('personal.tin_no', $employee->pdsPersonal?->tin_no)" /></div>
                                            <div class="space-y-1"><x-input-label value="AGENCY EMPLOYEE NO." class="text-[9px] font-black text-gray-400" /><x-text-input class="w-full text-xs font-bold border-gray-100" name="personal[agency_employee_no]" :value="old('personal.agency_employee_no', $employee->pdsPersonal?->agency_employee_no)" /></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-8">
                                    <!-- 16. Citizenship -->
                                    <div class="space-y-4 border-t border-slate-100 pt-5">
                                        <x-input-label value="Citizenship" :required="true" class="text-[10px] font-black uppercase text-indigo-700 tracking-wider" />
                                        <div class="flex gap-6 items-center">
                                            <label class="inline-flex items-center">
                                                <input type="radio" name="personal[citizenship]" value="Filipino" class="text-indigo-600 focus:ring-indigo-500" {{ (old('personal.citizenship', $employee->pdsPersonal?->citizenship) == 'Filipino' || !old('personal.citizenship', $employee->pdsPersonal?->citizenship)) ? 'checked' : '' }}>
                                                <span class="ml-2 text-xs font-bold text-gray-700">Filipino</span>
                                            </label>
                                            <label class="inline-flex items-center">
                                                <input type="radio" name="personal[citizenship]" value="Dual Citizenship" class="text-indigo-600 focus:ring-indigo-500" {{ old('personal.citizenship', $employee->pdsPersonal?->citizenship) == 'Dual Citizenship' ? 'checked' : '' }}>
                                                <span class="ml-2 text-xs font-bold text-gray-700">Dual Citizenship</span>
                                            </label>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <select name="personal[citizenship_type]" class="w-full border-gray-100 rounded-xl text-[10px] font-bold">
                                                <option value="">By Birth / Naturalization</option>
                                                @foreach($utilityOptionSets['citizenship_types'] as $option)
                                                    <option value="{{ $option->value }}" {{ old('personal.citizenship_type', $employee->pdsPersonal?->citizenship_type) == $option->value ? 'selected' : '' }}>{{ $option->label }}</option>
                                                @endforeach
                                            </select>
                                            <select name="personal[citizenship_country]" class="w-full rounded-xl border-gray-100 text-[10px] font-bold">
                                                <option value="">Country</option>
                                                @foreach($utilityOptionSets['countries'] as $option)
                                                    <option value="{{ $option->value }}" {{ old('personal.citizenship_country', $employee->pdsPersonal?->citizenship_country) == $option->value ? 'selected' : '' }}>{{ $option->label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- 17. Residential Address -->
                                    <div class="border-t border-slate-100 pt-5">
                                        <x-input-label value="Residential Address" :required="true" class="text-[10px] font-black uppercase text-gray-500 tracking-wider" />
                                        <div class="pds-field-table pds-field-table-4 mt-5">
                                            <x-text-input placeholder="House/Block/Lot No." class="text-xs font-bold" name="personal[res_house_no]" :value="old('personal.res_house_no', $employee->pdsPersonal?->res_house_no)" />
                                            <x-text-input placeholder="Street" class="text-xs font-bold" name="personal[res_street]" :value="old('personal.res_street', $employee->pdsPersonal?->res_street)" />
                                            <x-text-input placeholder="Subdivision/Village" class="text-xs font-bold" name="personal[res_subdivision]" :value="old('personal.res_subdivision', $employee->pdsPersonal?->res_subdivision)" />
                                            <select name="personal[res_region]" x-model="addresses.res_region" @change="resetAddress('res', 'region')" class="rounded-xl border-gray-100 text-xs font-bold">
                                                <option value="">Region</option>
                                                <template x-for="option in locationOptions.regions" :key="`res-region-${option.value}`">
                                                    <option :value="option.value" :selected="String(addresses.res_region || '') === String(option.value)" x-text="option.label"></option>
                                                </template>
                                            </select>
                                            <select name="personal[res_province]" x-model="addresses.res_province" @change="resetAddress('res', 'province')" class="rounded-xl border-gray-100 text-xs font-bold">
                                                <option value="">Province</option>
                                                <template x-for="option in provinceOptions('res')" :key="`res-province-${option.value}`">
                                                    <option :value="option.value" :selected="String(addresses.res_province || '') === String(option.value)" x-text="option.label"></option>
                                                </template>
                                            </select>
                                            <select name="personal[res_city]" x-model="addresses.res_city" @change="resetAddress('res', 'city')" class="rounded-xl border-gray-100 text-xs font-bold">
                                                <option value="">City / Municipality</option>
                                                <template x-for="option in cityOptions('res')" :key="`res-city-${option.value}`">
                                                    <option :value="option.value" :selected="String(addresses.res_city || '') === String(option.value)" x-text="option.label"></option>
                                                </template>
                                            </select>
                                            <select name="personal[res_barangay]" x-model="addresses.res_barangay" class="rounded-xl border-gray-100 text-xs font-bold">
                                                <option value="">Barangay</option>
                                                <template x-for="option in barangayOptions('res')" :key="`res-barangay-${option.value}`">
                                                    <option :value="option.value" :selected="String(addresses.res_barangay || '') === String(option.value)" x-text="option.label"></option>
                                                </template>
                                            </select>
                                            <input type="text" placeholder="Zip Code" class="address-combobox text-xs font-bold" name="personal[res_zip_code]" x-model="addresses.res_zip_code" />
                                        </div>
                                    </div>

                                    <!-- 18. Permanent Address -->
                                    <div class="border-t border-slate-100 pt-5">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <x-input-label value="Permanent Address" :required="true" class="text-[10px] font-black uppercase text-gray-500 tracking-wider" />
                                            <label class="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                                <input type="checkbox" id="sameAsResidentialAddress" @change="if ($event.target.checked) copyResidentialAddress()" class="rounded border-slate-300 text-indigo-700 focus:ring-indigo-500">
                                                Same as residential address
                                            </label>
                                        </div>
                                        <div class="pds-field-table pds-field-table-4 mt-5">
                                            <x-text-input placeholder="House/Block/Lot No." class="text-xs font-bold" name="personal[perm_house_no]" :value="old('personal.perm_house_no', $employee->pdsPersonal?->perm_house_no)" />
                                            <x-text-input placeholder="Street" class="text-xs font-bold" name="personal[perm_street]" :value="old('personal.perm_street', $employee->pdsPersonal?->perm_street)" />
                                            <x-text-input placeholder="Subdivision/Village" class="text-xs font-bold" name="personal[perm_subdivision]" :value="old('personal.perm_subdivision', $employee->pdsPersonal?->perm_subdivision)" />
                                            <select name="personal[perm_region]" x-model="addresses.perm_region" @change="resetAddress('perm', 'region')" class="rounded-xl border-gray-100 text-xs font-bold">
                                                <option value="">Region</option>
                                                <template x-for="option in locationOptions.regions" :key="`perm-region-${option.value}`">
                                                    <option :value="option.value" :selected="String(addresses.perm_region || '') === String(option.value)" x-text="option.label"></option>
                                                </template>
                                            </select>
                                            <select name="personal[perm_province]" x-model="addresses.perm_province" @change="resetAddress('perm', 'province')" class="rounded-xl border-gray-100 text-xs font-bold">
                                                <option value="">Province</option>
                                                <template x-for="option in provinceOptions('perm')" :key="`perm-province-${option.value}`">
                                                    <option :value="option.value" :selected="String(addresses.perm_province || '') === String(option.value)" x-text="option.label"></option>
                                                </template>
                                            </select>
                                            <select name="personal[perm_city]" x-model="addresses.perm_city" @change="resetAddress('perm', 'city')" class="rounded-xl border-gray-100 text-xs font-bold">
                                                <option value="">City / Municipality</option>
                                                <template x-for="option in cityOptions('perm')" :key="`perm-city-${option.value}`">
                                                    <option :value="option.value" :selected="String(addresses.perm_city || '') === String(option.value)" x-text="option.label"></option>
                                                </template>
                                            </select>
                                            <select name="personal[perm_barangay]" x-model="addresses.perm_barangay" class="rounded-xl border-gray-100 text-xs font-bold">
                                                <option value="">Barangay</option>
                                                <template x-for="option in barangayOptions('perm')" :key="`perm-barangay-${option.value}`">
                                                    <option :value="option.value" :selected="String(addresses.perm_barangay || '') === String(option.value)" x-text="option.label"></option>
                                                </template>
                                            </select>
                                            <input type="text" placeholder="Zip Code" class="address-combobox text-xs font-bold" name="personal[perm_zip_code]" x-model="addresses.perm_zip_code" />
                                        </div>
                                    </div>

                                    <!-- 19-21 Contact -->
                                    <div class="pds-field-table pds-field-table-3 border-t border-slate-100 pt-4">
                                        <div class="space-y-1"><x-input-label value="Telephone No." class="text-[9px] font-black text-gray-400" /><x-text-input class="w-full text-xs font-bold border-gray-100" name="personal[telephone_no]" :value="old('personal.telephone_no', $employee->pdsPersonal?->telephone_no)" /></div>
                                        <div class="space-y-1">
                                            <x-input-label value="Mobile No." :required="true" class="text-[9px] font-black text-gray-400" />
                                            <input
                                                type="text"
                                                class="w-full text-xs font-bold border-gray-100"
                                                name="personal[mobile_no]"
                                                x-model="mobile_no"
                                                @input="normalizeMobileNumber()"
                                                inputmode="numeric"
                                                maxlength="13"
                                                placeholder="09XX-XXX-XXXX"
                                            />
                                        </div>
                                        <div class="space-y-1"><x-input-label value="E-mail Address" :required="true" class="text-[9px] font-black text-gray-400" /><x-text-input class="w-full text-xs font-bold border-gray-100" name="personal[email_address]" :value="old('personal.email_address', $employee->pdsPersonal?->email_address)" /></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section II: Family Background -->
                    <div x-show="tab === 'family'" x-cloak class="space-y-8 animate-fade-in">
                        <div>
                            <div class="pds-section-heading flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-base font-black text-gray-900 uppercase tracking-tight">Family Background</h3>
                                </div>
                                @php $familyReview = $employee->pdsSectionReviews->where('section_name', 'Family Background')->first(); @endphp

    @php
        $yearOptions = range(now()->year, 1950);
    @endphp
                                @if($familyReview && $familyReview->remarks)
                                    <span class="bg-yellow-100 text-yellow-800 text-[10px] px-2 py-1 rounded font-bold border border-yellow-200">Has HR Remarks</span>
                                @endif
                            </div>
                            @if($familyReview && $familyReview->remarks)
                                <div class="mb-6 flex items-start gap-3 border-l-4 border-yellow-300 bg-yellow-50/60 py-3 pl-4 pr-3">
                                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <div>
                                        <span class="block text-[10px] font-black text-yellow-800 uppercase tracking-widest mb-1">HR Remarks ({{ ucfirst($familyReview->status) }})</span>
                                        <p class="text-sm font-medium text-yellow-900 leading-relaxed">{{ $familyReview->remarks }}</p>
                                    </div>
                                </div>
                            @endif
                            
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                <div class="space-y-1"><x-input-label value="Spouse Surname" class="text-[9px] font-black uppercase text-gray-400 tracking-[0.2em]" /><x-text-input class="block w-full text-sm font-bold border-gray-100" name="family[spouse_surname]" :value="$employee->pdsFamily?->spouse_surname" /></div>
                                <div class="space-y-1"><x-input-label value="Spouse First Name" class="text-[9px] font-black uppercase text-gray-400 tracking-[0.2em]" /><x-text-input class="block w-full text-sm font-bold border-gray-100" name="family[spouse_firstname]" :value="$employee->pdsFamily?->spouse_firstname" /></div>
                                <div class="space-y-1"><x-input-label value="Spouse Extension" class="text-[9px] font-black uppercase text-gray-400 tracking-[0.2em]" /><select name="family[spouse_extension]" class="block w-full rounded-xl border-gray-100 text-sm font-bold"><option value="">Select extension</option>@foreach($utilityOptionSets['name_extensions'] as $option)<option value="{{ $option->value }}" {{ old('family.spouse_extension', $employee->pdsFamily?->spouse_extension) == $option->value ? 'selected' : '' }}>{{ $option->label }}</option>@endforeach</select></div>
                                <div class="space-y-1"><x-input-label value="Occupation" class="text-[9px] font-black uppercase text-gray-400 tracking-[0.2em]" /><x-text-input class="block w-full text-sm font-bold border-gray-100" name="family[spouse_occupation]" :value="$employee->pdsFamily?->spouse_occupation" /></div>
                            </div>

                            <!-- Dynamic Children Rows -->
                            <div class="mt-7 border-t border-slate-100 pt-5">
                                <div class="flex justify-between items-center mb-4">
                                    <h4 class="font-black text-gray-700 uppercase text-[10px] tracking-widest">Children</h4>
                                    <button type="button" @click="children.push({fullname: '', date_of_birth: ''})" class="text-[9px] font-black uppercase tracking-[0.1em] bg-white border border-indigo-200 text-indigo-700 px-3 py-1.5 rounded-lg hover:bg-indigo-50 transition">+ Add Child</button>
                                </div>
                                <div class="overflow-x-auto border-b border-slate-100">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="text-left text-[9px] font-black uppercase text-gray-400 bg-gray-50">
                                                <th class="px-4 py-2">Full Name</th>
                                                <th class="px-4 py-2">Date of Birth</th>
                                                <th class="px-4 py-2 w-10"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            <template x-for="(child, index) in children" :key="index">
                                                <tr>
                                                    <td class="px-2 py-1"><x-text-input placeholder="Juan Dela Cruz" class="w-full text-xs font-bold border-transparent focus:border-indigo-100 focus:ring-0" x-model="child.fullname" ::name="`children[${index}][fullname]`" /></td>
                                                    <td class="px-2 py-1"><x-text-input type="date" class="w-full text-xs font-bold border-transparent focus:border-indigo-100 focus:ring-0" x-model="child.date_of_birth" ::name="`children[${index}][date_of_birth]`" /></td>
                                                    <td class="px-2 py-1"><button type="button" @click="children.splice(index, 1)" class="text-red-400 hover:text-red-600 font-bold transition">&times;</button></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section III: Education -->
                    <div x-show="tab === 'education'" x-cloak class="space-y-8 animate-fade-in">
                         <div>
                            <div class="pds-section-heading flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-base font-black text-gray-900 uppercase tracking-tight">Educational Background</h3>
                                </div>
                                <div class="flex items-center gap-2">
                                    @php $educationReview = $employee->pdsSectionReviews->where('section_name', 'Educational Background')->first(); @endphp

    @php
        $yearOptions = range(now()->year, 1950);
    @endphp
                                    @if($educationReview && $educationReview->remarks)
                                        <span class="bg-yellow-100 text-yellow-800 text-[10px] px-2 py-1 rounded font-bold border border-yellow-200">Has HR Remarks</span>
                                    @endif
                                    <button type="button" @click="education.push({level: '', school_name: '', course: '', period_from: '', period_to: '', highest_level: '', year_graduated: '', honors: ''})" class="text-[9px] font-black uppercase tracking-[0.1em] bg-indigo-700 text-white px-3 py-1.5 rounded-lg hover:bg-indigo-800 transition">+ Add Row</button>
                                </div>
                            </div>
                            @if($educationReview && $educationReview->remarks)
                                <div class="mb-6 flex items-start gap-3 border-l-4 border-yellow-300 bg-yellow-50/60 py-3 pl-4 pr-3">
                                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <div>
                                        <span class="block text-[10px] font-black text-yellow-800 uppercase tracking-widest mb-1">HR Remarks ({{ ucfirst($educationReview->status) }})</span>
                                        <p class="text-sm font-medium text-yellow-900 leading-relaxed">{{ $educationReview->remarks }}</p>
                                    </div>
                                </div>
                            @endif
                            <div class="overflow-x-auto border-b border-slate-100">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="text-left text-[9px] font-black uppercase text-gray-400 bg-gray-50">
                                            <th class="px-2 py-3 border-r border-gray-50">Level <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-2 py-3 border-r border-gray-50">School Name <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-2 py-3 border-r border-gray-50">Course <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-2 py-3 border-r border-gray-50">From</th>
                                            <th class="px-2 py-3 border-r border-gray-50">To</th>
                                            <th class="px-2 py-3 border-r border-gray-50">Year</th>
                                            <th class="px-2 py-3 border-r border-gray-50">Honors</th>
                                            <th class="px-2 py-3 w-8"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 font-bold text-gray-800">
                                        <template x-for="(edu, index) in education" :key="index">
                                            <tr>
                                                <td class="p-0.5"><select class="w-full text-[10px] font-bold border-transparent focus:border-indigo-100 bg-transparent px-2" x-model="edu.level" :name="`education[${index}][level]`"><option value="">Select</option>@foreach($utilityOptionSets['education_levels'] as $option)<option value="{{ $option->value }}">{{ $option->label }}</option>@endforeach</select></td>
                                                <td class="p-0.5"><x-text-input class="w-full text-[10px] font-bold border-transparent focus:border-indigo-100 bg-transparent px-2" x-model="edu.school_name" ::name="`education[${index}][school_name]`" /></td>
                                                <td class="p-0.5"><x-text-input class="w-full text-[10px] font-bold border-transparent focus:border-indigo-100 bg-transparent px-2" x-model="edu.course" ::name="`education[${index}][course]`" /></td>
                                                <td class="p-0.5"><x-text-input class="w-full text-[10px] font-bold border-transparent focus:border-indigo-100 bg-transparent px-2" x-model="edu.period_from" ::name="`education[${index}][period_from]`" /></td>
                                                <td class="p-0.5"><x-text-input class="w-full text-[10px] font-bold border-transparent focus:border-indigo-100 bg-transparent px-2" x-model="edu.period_to" ::name="`education[${index}][period_to]`" /></td>
                                                <td class="p-0.5"><select class="w-full text-[10px] font-bold border-transparent focus:border-indigo-100 bg-transparent px-2" x-model="edu.year_graduated" :name="`education[${index}][year_graduated]`"><option value="">Year</option>@foreach($yearOptions as $year)<option value="{{ $year }}">{{ $year }}</option>@endforeach</select></td>
                                                <td class="p-0.5"><x-text-input class="w-full text-[10px] font-bold border-transparent focus:border-indigo-100 bg-transparent px-2" x-model="edu.honors" ::name="`education[${index}][honors]`" /></td>
                                                <td class="p-0.5 text-center"><button type="button" @click="education.splice(index, 1)" class="text-red-400 hover:text-red-600">&times;</button></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Section IV: Civil Service Eligibility -->
                    <div x-show="tab === 'eligibility'" x-cloak class="space-y-8 animate-fade-in">
                        <div>
                            <div class="pds-section-heading flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-base font-black text-gray-900 uppercase tracking-tight">Civil Service Eligibility</h3>
                                </div>
                                <div class="flex items-center gap-2">
                                    @php $eligibilityReview = $employee->pdsSectionReviews->where('section_name', 'Civil Service Eligibility')->first(); @endphp

    @php
        $yearOptions = range(now()->year, 1950);
    @endphp
                                    @if($eligibilityReview && $eligibilityReview->remarks)
                                        <span class="bg-yellow-100 text-yellow-800 text-[10px] px-2 py-1 rounded font-bold border border-yellow-200">Has HR Remarks</span>
                                    @endif
                                    <button type="button" @click="eligibility.push({title: '', rating: '', date_of_exam: '', place_of_exam: '', license_number: '', license_validity: ''})" class="text-[9px] font-black uppercase tracking-[0.1em] bg-indigo-700 text-white px-3 py-1.5 rounded-lg hover:bg-indigo-800 transition">+ Add Eligibility</button>
                                </div>
                            </div>
                            @if($eligibilityReview && $eligibilityReview->remarks)
                                <div class="mb-6 flex items-start gap-3 border-l-4 border-yellow-300 bg-yellow-50/60 py-3 pl-4 pr-3">
                                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <div>
                                        <span class="block text-[10px] font-black text-yellow-800 uppercase tracking-widest mb-1">HR Remarks ({{ ucfirst($eligibilityReview->status) }})</span>
                                        <p class="text-sm font-medium text-yellow-900 leading-relaxed">{{ $eligibilityReview->remarks }}</p>
                                    </div>
                                </div>
                            @endif
                            <div class="overflow-x-auto border-b border-slate-100">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="text-left text-[9px] font-black uppercase text-gray-400 bg-gray-50">
                                            <th class="px-4 py-3">Eligibility Title <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3">Rating</th>
                                            <th class="px-4 py-3">Date</th>
                                            <th class="px-4 py-3 w-8"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 font-bold">
                                        <template x-for="(eli, index) in eligibility" :key="index">
                                            <tr>
                                                <td class="p-1"><select class="w-full text-[10px] font-bold border-transparent px-3" x-model="eli.title" :name="`eligibility[${index}][title]`"><option value="">Select</option>@foreach($utilityOptionSets['eligibility_titles'] as $option)<option value="{{ $option->value }}">{{ $option->label }}</option>@endforeach</select></td>
                                                <td class="p-1"><x-text-input class="w-full text-[10px] font-bold border-transparent px-3" x-model="eli.rating" ::name="`eligibility[${index}][rating]`" /></td>
                                                <td class="p-1"><x-text-input type="date" class="w-full text-[10px] font-bold border-transparent px-3" x-model="eli.date_of_exam" ::name="`eligibility[${index}][date_of_exam]`" /></td>
                                                <td class="p-1 text-center"><button type="button" @click="eligibility.splice(index, 1)" class="text-red-400 hover:text-red-600">&times;</button></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Section V: Work Experience -->
                    <div x-show="tab === 'work'" x-cloak class="space-y-8 animate-fade-in">
                         <div>
                            <div class="pds-section-heading flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-base font-black text-gray-900 uppercase tracking-tight">Work Experience</h3>
                                </div>
                                <div class="flex items-center gap-2">
                                    @php $workReview = $employee->pdsSectionReviews->where('section_name', 'Work Experience')->first(); @endphp

    @php
        $yearOptions = range(now()->year, 1950);
    @endphp
                                    @if($workReview && $workReview->remarks)
                                        <span class="bg-yellow-100 text-yellow-800 text-[10px] px-2 py-1 rounded font-bold border border-yellow-200">Has HR Remarks</span>
                                    @endif
                                    <button type="button" @click="work.push({date_from: '', date_to: '', position_title: '', company: '', monthly_salary: '', salary_grade: '', appointment_status: '', is_gov_service: 0})" class="text-[9px] font-black uppercase tracking-[0.1em] bg-indigo-700 text-white px-3 py-1.5 rounded-lg hover:bg-indigo-800 transition">+ Add Work</button>
                                </div>
                            </div>
                            @if($workReview && $workReview->remarks)
                                <div class="mb-6 flex items-start gap-3 border-l-4 border-yellow-300 bg-yellow-50/60 py-3 pl-4 pr-3">
                                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <div>
                                        <span class="block text-[10px] font-black text-yellow-800 uppercase tracking-widest mb-1">HR Remarks ({{ ucfirst($workReview->status) }})</span>
                                        <p class="text-sm font-medium text-yellow-900 leading-relaxed">{{ $workReview->remarks }}</p>
                                    </div>
                                </div>
                            @endif
                            <div class="overflow-x-auto border-b border-slate-100">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="text-left text-[9px] font-black uppercase text-gray-400 bg-gray-50">
                                            <th class="px-4 py-3">From <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3">To <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3">Position <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3">Company <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3">Salary <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3">Salary Grade</th>
                                            <th class="px-4 py-3">Appointment Status</th>
                                            <th class="px-4 py-3">Gov Service</th>
                                            <th class="px-4 py-3 w-8"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 font-bold">
                                        <template x-for="(w, index) in work" :key="index">
                                            <tr>
                                                <td class="p-1"><x-text-input type="date" class="w-full text-[10px] font-bold border-transparent px-3" x-model="w.date_from" ::name="`work_experience[${index}][date_from]`" /></td>
                                                <td class="p-1"><x-text-input type="date" class="w-full text-[10px] font-bold border-transparent px-3" x-model="w.date_to" ::name="`work_experience[${index}][date_to]`" /></td>
                                                <td class="p-1"><x-text-input class="w-full text-[10px] font-bold border-transparent px-3" x-model="w.position_title" ::name="`work_experience[${index}][position_title]`" /></td>
                                                <td class="p-1"><x-text-input class="w-full text-[10px] font-bold border-transparent px-3" x-model="w.company" ::name="`work_experience[${index}][company]`" /></td>
                                                <td class="p-1"><x-text-input type="number" step="0.01" class="w-full text-[10px] font-bold border-transparent px-3" x-model="w.monthly_salary" ::name="`work_experience[${index}][monthly_salary]`" /></td>
                                                <td class="p-1"><select class="w-full text-[10px] font-bold border-transparent px-3" x-model="w.salary_grade" :name="`work_experience[${index}][salary_grade]`"><option value="">SG</option>@foreach($utilityOptionSets['salary_grades'] as $option)<option value="{{ $option->value }}">{{ $option->label }}</option>@endforeach</select></td>
                                                <td class="p-1"><select class="w-full text-[10px] font-bold border-transparent px-3" x-model="w.appointment_status" :name="`work_experience[${index}][appointment_status]`"><option value="">Status</option>@foreach($utilityOptionSets['appointment_statuses'] as $option)<option value="{{ $option->value }}">{{ $option->label }}</option>@endforeach</select></td>
                                                <td class="p-1"><select class="w-full text-[10px] font-bold border-transparent px-3" x-model="w.is_gov_service" :name="`work_experience[${index}][is_gov_service]`"><option value="">Select</option><option value="1">Yes</option><option value="0">No</option></select></td>
                                                <td class="p-1 text-center"><button type="button" @click="work.splice(index, 1)" class="text-red-400 hover:text-red-600">&times;</button></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Section VI: Voluntary Work -->
                    <div x-show="tab === 'voluntary'" x-cloak class="space-y-8 animate-fade-in">
                         <div>
                            <div class="pds-section-heading flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-base font-black text-gray-900 uppercase tracking-tight">Voluntary Work or Involvement</h3>
                                </div>
                                <button type="button" @click="voluntary.push({organization_name: '', date_from: '', date_to: '', number_of_hours: '', position: ''})" class="text-[9px] font-black uppercase tracking-[0.1em] bg-indigo-700 text-white px-3 py-1.5 rounded-lg hover:bg-indigo-800 transition">+ Add Voluntary Work</button>
                            </div>
                            <div class="overflow-x-auto border-b border-slate-100">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="text-left text-[9px] font-black uppercase text-gray-400 bg-gray-50">
                                            <th class="px-4 py-3">Organization <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3">From</th>
                                            <th class="px-4 py-3">To</th>
                                            <th class="px-4 py-3">Hours</th>
                                            <th class="px-4 py-3">Position / Nature of Work</th>
                                            <th class="px-4 py-3 w-8"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 font-bold">
                                        <template x-for="(vol, index) in voluntary" :key="index">
                                            <tr>
                                                <td class="p-1"><x-text-input class="w-full text-[10px] font-bold border-transparent px-3" x-model="vol.organization_name" ::name="`voluntary[${index}][organization_name]`" /></td>
                                                <td class="p-1"><x-text-input type="date" class="w-full text-[10px] font-bold border-transparent px-3" x-model="vol.date_from" ::name="`voluntary[${index}][date_from]`" /></td>
                                                <td class="p-1"><x-text-input type="date" class="w-full text-[10px] font-bold border-transparent px-3" x-model="vol.date_to" ::name="`voluntary[${index}][date_to]`" /></td>
                                                <td class="p-1"><x-text-input type="number" class="w-full text-[10px] font-bold border-transparent px-3" x-model="vol.number_of_hours" ::name="`voluntary[${index}][number_of_hours]`" /></td>
                                                <td class="p-1"><x-text-input class="w-full text-[10px] font-bold border-transparent px-3" x-model="vol.position" ::name="`voluntary[${index}][position]`" /></td>
                                                <td class="p-1 text-center"><button type="button" @click="voluntary.splice(index, 1)" class="text-red-400 hover:text-red-600">&times;</button></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Section VII: Learning and Development -->
                    <div x-show="tab === 'training'" x-cloak class="space-y-8 animate-fade-in">
                         <div>
                            <div class="pds-section-heading flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-base font-black text-gray-900 uppercase tracking-tight">L&D / Training Interventions</h3>
                                </div>
                                <div class="flex items-center gap-2">
                                    @php $trainingReview = $employee->pdsSectionReviews->where('section_name', 'Training')->first(); @endphp

    @php
        $yearOptions = range(now()->year, 1950);
    @endphp
                                    @if($trainingReview && $trainingReview->remarks)
                                        <span class="bg-yellow-100 text-yellow-800 text-[10px] px-2 py-1 rounded font-bold border border-yellow-200">Has HR Remarks</span>
                                    @endif
                                    <button type="button" @click="training.push({title: '', date_from: '', date_to: '', number_of_hours: '', attachment_path: '', type: '', conducted_by: ''})" class="text-[9px] font-black uppercase tracking-[0.1em] bg-indigo-700 text-white px-3 py-1.5 rounded-lg hover:bg-indigo-800 transition">+ Add Training</button>
                                </div>
                            </div>
                            @if($trainingReview && $trainingReview->remarks)
                                <div class="mb-6 flex items-start gap-3 border-l-4 border-yellow-300 bg-yellow-50/60 py-3 pl-4 pr-3">
                                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <div>
                                        <span class="block text-[10px] font-black text-yellow-800 uppercase tracking-widest mb-1">HR Remarks ({{ ucfirst($trainingReview->status) }})</span>
                                        <p class="text-sm font-medium text-yellow-900 leading-relaxed">{{ $trainingReview->remarks }}</p>
                                    </div>
                                </div>
                            @endif
                            <div class="overflow-x-auto border-b border-slate-100">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="text-left text-[9px] font-black uppercase text-gray-400 bg-gray-50">
                                            <th class="px-4 py-3">Title of Program <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3">From <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3">To <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            {{-- Change: replaced Hours with Attachment for PDS training records. --}}
                                            <th class="px-4 py-3">Attachment</th>
                                            <th class="px-4 py-3 w-8"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 font-bold">
                                        <template x-for="(t, index) in training" :key="index">
                                            <tr>
                                                <td class="p-1"><x-text-input class="w-full text-[10px] font-bold border-transparent px-3" x-model="t.title" ::name="`training[${index}][title]`" /></td>
                                                <td class="p-1"><x-text-input type="date" class="w-full text-[10px] font-bold border-transparent px-3" x-model="t.date_from" ::name="`training[${index}][date_from]`" /></td>
                                                <td class="p-1"><x-text-input type="date" class="w-full text-[10px] font-bold border-transparent px-3" x-model="t.date_to" ::name="`training[${index}][date_to]`" /></td>
                                                <td class="p-1">
                                                    <input type="hidden" :name="`training[${index}][existing_attachment_path]`" :value="t.attachment_path || ''">
                                                    <input type="file" :name="`training[${index}][attachment]`" accept=".pdf,application/pdf" class="block w-full text-[10px] font-bold text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-[10px] file:font-black file:uppercase file:tracking-widest file:text-indigo-700 hover:file:bg-indigo-100">
                                                    <template x-if="t.attachment_path">
                                                        <a :href="`/storage/${t.attachment_path}`" target="_blank" class="mt-1 inline-flex text-[10px] font-black uppercase tracking-widest text-indigo-600 hover:text-indigo-800">
                                                            View current file
                                                        </a>
                                                    </template>
                                                </td>
                                                <td class="p-1 text-center"><button type="button" @click="training.splice(index, 1)" class="text-red-400 hover:text-red-600">&times;</button></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Section VIII: Other Information -->
                    <div x-show="tab === 'otherinfo'" x-cloak class="space-y-8 animate-fade-in">
                        <div>
                            <div class="pds-section-heading flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-base font-black text-gray-900 uppercase tracking-tight">Other Information</h3>
                                </div>
                                <button type="button" @click="others.push({type: 'Skill', description: ''})" class="text-[9px] font-black uppercase tracking-[0.1em] bg-indigo-700 text-white px-3 py-1.5 rounded-lg hover:bg-indigo-800 transition">+ Add Info</button>
                            </div>
                            <div class="overflow-x-auto border-b border-slate-100">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="text-left text-[9px] font-black uppercase text-gray-400 bg-gray-50">
                                            <th class="px-4 py-3">Type</th>
                                            <th class="px-4 py-3">Details</th>
                                            <th class="px-4 py-3 w-8"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 font-bold">
                                        <template x-for="(other, index) in others" :key="index">
                                            <tr>
                                                <td class="p-1">
                                                    <select class="w-full text-[10px] font-bold border-transparent px-3" x-model="other.type" :name="`others[${index}][type]`">
                                                        <option value="Skill">Special Skill / Hobby</option>
                                                        <option value="Distinction">Non-Academic Distinction</option>
                                                        <option value="Membership">Membership in Association</option>
                                                    </select>
                                                </td>
                                                <td class="p-1"><x-text-input class="w-full text-[10px] font-bold border-transparent px-3" x-model="other.description" ::name="`others[${index}][description]`" /></td>
                                                <td class="p-1 text-center"><button type="button" @click="others.splice(index, 1)" class="text-red-400 hover:text-red-600">&times;</button></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div>
                            <div class="pds-section-heading flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-base font-black text-gray-900 uppercase tracking-tight">References</h3>
                                </div>
                                @php $referenceReview = $employee->pdsSectionReviews->where('section_name', 'References')->first(); @endphp

    @php
        $yearOptions = range(now()->year, 1950);
    @endphp
                                @if($referenceReview && $referenceReview->remarks)
                                    <span class="bg-yellow-100 text-yellow-800 text-[10px] px-2 py-1 rounded font-bold border border-yellow-200">Has HR Remarks</span>
                                @endif
                            </div>
                            @if($referenceReview && $referenceReview->remarks)
                                <div class="mb-6 flex items-start gap-3 border-l-4 border-yellow-300 bg-yellow-50/60 py-3 pl-4 pr-3">
                                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <div>
                                        <span class="block text-[10px] font-black text-yellow-800 uppercase tracking-widest mb-1">HR Remarks ({{ ucfirst($referenceReview->status) }})</span>
                                        <p class="text-sm font-medium text-yellow-900 leading-relaxed">{{ $referenceReview->remarks }}</p>
                                    </div>
                                </div>
                            @endif
                            <div class="grid grid-cols-1 gap-6 border-t border-slate-100 pt-5 md:grid-cols-3">
                                <template x-for="(ref, index) in references" :key="index">
                                    <div class="space-y-3">
                                        <div class="space-y-1">
                                            <x-input-label value="Name" :required="true" class="text-[9px] font-black uppercase text-gray-400 tracking-widest" />
                                            <x-text-input class="w-full text-[10px] font-bold border-gray-50 focus:border-indigo-100" x-model="ref.name" ::name="`references[${index}][name]`" />
                                        </div>
                                        <div class="space-y-1">
                                            <x-input-label value="Address" :required="true" class="text-[9px] font-black uppercase text-gray-400 tracking-widest" />
                                            <x-text-input class="w-full text-[10px] font-bold border-gray-50 focus:border-indigo-100" x-model="ref.address" ::name="`references[${index}][address]`" />
                                        </div>
                                        <div class="space-y-1">
                                            <x-input-label value="Telephone" :required="true" class="text-[9px] font-black uppercase text-gray-400 tracking-widest" />
                                            <x-text-input class="w-full text-[10px] font-bold border-gray-50 focus:border-indigo-100" x-model="ref.telephone_no" ::name="`references[${index}][telephone_no]`" />
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>



                    <!-- Section IX: Questions, Declaration, ID, Photo, and Signature -->
                    <div x-show="tab === 'declaration'" x-cloak class="space-y-8 animate-fade-in">
                        <div>
                            <div class="pds-section-heading flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-base font-black text-gray-900 uppercase tracking-tight">Questions 34-42, Declaration, ID, Photo, and Signature</h3>
                                </div>
                            </div>

                            @php
                                $questionRows = [
                                    ['key' => 'q34_a', 'label' => '34a. Related by consanguinity or affinity within the third degree?', 'details' => 'q34_details'],
                                    ['key' => 'q34_b', 'label' => '34b. Related by consanguinity or affinity within the fourth degree?', 'details' => 'q34_details'],
                                    ['key' => 'q35_a', 'label' => '35a. Found guilty of any administrative offense?', 'details' => 'q35_details'],
                                    ['key' => 'q35_b', 'label' => '35b. Criminally charged before any court?', 'details' => null],
                                    ['key' => 'q36', 'label' => '36. Convicted of any crime or violation of any law?', 'details' => 'q36_details'],
                                    ['key' => 'q37', 'label' => '37. Separated from service in any mode?', 'details' => 'q37_details'],
                                    ['key' => 'q38_a', 'label' => '38a. Candidate in a national or local election held within the last year?', 'details' => 'q38_a_details'],
                                    ['key' => 'q38_b', 'label' => '38b. Resigned from government service before the last election?', 'details' => 'q38_b_details'],
                                    ['key' => 'q39', 'label' => '39. Acquired immigrant or permanent resident status in another country?', 'details' => 'q39_details'],
                                    ['key' => 'q40_a', 'label' => '40a. Member of an indigenous group?', 'details' => 'q40_a_details'],
                                    ['key' => 'q40_b', 'label' => '40b. Person with disability?', 'details' => 'q40_b_details'],
                                    ['key' => 'q40_c', 'label' => '40c. Solo parent?', 'details' => 'q40_c_details'],
                                ];
                            @endphp

    @php
        $yearOptions = range(now()->year, 1950);
    @endphp

                            <div class="overflow-x-auto border-b border-slate-100">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="bg-gray-50 text-[9px] font-black uppercase tracking-widest text-gray-400">
                                            <th class="px-4 py-3 text-left">Question</th>
                                            <th class="w-24 px-4 py-3 text-center">Yes</th>
                                            <th class="w-24 px-4 py-3 text-center">No</th>
                                            <th class="px-4 py-3 text-left">Details</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 font-bold">
                                        @foreach($questionRows as $row)
                                            @php $current = old('questionnaire.'.$row['key'], $employee->pdsQuestionnaire?->{$row['key']}); @endphp

    @php
        $yearOptions = range(now()->year, 1950);
    @endphp
                                            <tr>
                                                <td class="px-4 py-3 text-gray-700">{{ $row['label'] }}</td>
                                                <td class="px-4 py-3 text-center"><input type="radio" name="questionnaire[{{ $row['key'] }}]" value="1" {{ (string) $current === '1' ? 'checked' : '' }}></td>
                                                <td class="px-4 py-3 text-center"><input type="radio" name="questionnaire[{{ $row['key'] }}]" value="0" {{ (string) $current === '0' || $current === 0 || $current === false ? 'checked' : '' }}></td>
                                                <td class="px-4 py-3">
                                                    @if($row['key'] === 'q35_b')
                                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                            <x-text-input type="date" class="w-full text-xs font-bold border-gray-100" name="questionnaire[q35_date_filed]" :value="old('questionnaire.q35_date_filed', $employee->pdsQuestionnaire?->q35_date_filed)" />
                                                            <x-text-input class="w-full text-xs font-bold border-gray-100" name="questionnaire[q35_status]" :value="old('questionnaire.q35_status', $employee->pdsQuestionnaire?->q35_status)" placeholder="Status of case" />
                                                        </div>
                                                    @elseif($row['details'])
                                                        <x-text-input class="w-full text-xs font-bold border-gray-100" name="questionnaire[{{ $row['details'] }}]" :value="old('questionnaire.'.$row['details'], $employee->pdsQuestionnaire?->{$row['details']})" placeholder="N/A if not applicable" />
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
                                <div class="space-y-4 border-t border-slate-100 pt-5">
                                    <h4 class="text-xs font-black uppercase tracking-widest text-gray-500">Government Issued ID <span class="text-red-500 font-bold ml-0.5">*</span></h4>
                                    <select name="gov_id[id_type]" class="w-full rounded-xl border-gray-100 text-xs font-bold">
                                        <option value="">ID Type</option>
                                        @foreach($utilityOptionSets['government_id_types'] as $option)
                                            <option value="{{ $option->value }}" {{ old('gov_id.id_type', $employee->pdsGovId?->id_type) == $option->value ? 'selected' : '' }}>{{ $option->label }}</option>
                                        @endforeach
                                    </select>
                                    <x-text-input class="w-full text-xs font-bold border-gray-100" name="gov_id[id_no]" :value="old('gov_id.id_no', $employee->pdsGovId?->id_no)" placeholder="ID / License / Passport No." />
                                    <x-text-input class="w-full text-xs font-bold border-gray-100" name="gov_id[date_place_issuance]" :value="old('gov_id.date_place_issuance', $employee->pdsGovId?->date_place_issuance)" placeholder="Date / Place of Issuance" />
                                </div>
                                <div class="space-y-4 border-t border-slate-100 pt-5">
                                    <h4 class="text-xs font-black uppercase tracking-widest text-gray-500">Official Photo <span class="text-red-500 font-bold ml-0.5">*</span></h4>
                                    <div class="mx-auto flex h-36 w-28 items-center justify-center overflow-hidden border border-black bg-white text-[10px] font-black uppercase text-gray-400">
                                        <template x-if="photoPreview"><img :src="photoPreview" class="h-full w-full object-cover" alt="PDS photo preview"></template>
                                        <span x-show="!photoPreview">Photo</span>
                                    </div>
                                    <input type="file" name="pds_photo" accept="image/jpeg,image/png,image/jpg" @change="photoPreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : photoPreview" class="block w-full text-[10px] font-bold text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-[10px] file:font-black file:uppercase file:tracking-widest file:text-indigo-700">
                                </div>
                                <div class="space-y-4 border-t border-slate-100 pt-5">
                                    <h4 class="text-xs font-black uppercase tracking-widest text-gray-500">Signature <span class="text-red-500 font-bold ml-0.5">*</span></h4>
                                    <div class="flex h-20 items-center justify-center overflow-hidden border border-black bg-white text-[10px] font-black uppercase text-gray-400">
                                        <template x-if="signaturePreview"><img :src="signaturePreview" class="h-full w-full object-contain" alt="PDS signature preview"></template>
                                        <span x-show="!signaturePreview">Signature</span>
                                    </div>
                                    <input type="file" name="pds_signature" accept="image/png,.png" @change="signaturePreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : signaturePreview" class="block w-full text-[10px] font-bold text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-[10px] file:font-black file:uppercase file:tracking-widest file:text-indigo-700">
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(!($isReadOnly ?? false))
                        <div class="mt-12 flex justify-end gap-3 border-t-2 border-gray-50 pt-8 print:hidden">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-6 py-2 bg-white border-2 border-gray-200 rounded-xl font-black text-[10px] uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition duration-150">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center px-8 py-2.5 bg-indigo-700 border-2 border-indigo-800 rounded-xl font-black text-[10px] uppercase tracking-widest text-white hover:bg-indigo-800 shadow-lg shadow-indigo-100 transition duration-150">
                                Save PDS Data
                            </button>
                        </div>
                    @endif
                @if(!($isReadOnly ?? false))
                    </form>
                @else
                    </div>
                @endif
            </div>

            @if($isReadOnly ?? false)
                <div class="mt-6 bg-white px-3 py-5 shadow-sm print:hidden sm:px-4 lg:px-5">
                    <div class="flex flex-col gap-3 border-b border-gray-200 pb-4 sm:flex-row sm:items-center sm:justify-between">
                        <h4 class="flex items-center gap-2 text-base font-black text-gray-900">
                            <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            HR Review & Decision
                        </h4>
                        <form action="{{ route('pds-reviews.approve-all', $employee) }}" method="POST" onsubmit="return confirm('Approve all PDS tabs for this employee? This will clear all changed-field markers.');">
                            @csrf
                            <button type="submit" style="background-color: #0038a8; border-color: #002f8f; color: #ffffff;" class="inline-flex items-center justify-center gap-2 rounded-lg border px-5 py-2.5 text-xs font-black uppercase tracking-wider shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-blue-200 active:scale-95">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Approve All
                            </button>
                        </form>
                    </div>

                    @php
                        $tabSectionMap = [
                            'personal' => 'Personal Information',
                            'family' => 'Family Background',
                            'education' => 'Educational Background',
                            'eligibility' => 'Civil Service Eligibility',
                            'work' => 'Work Experience',
                            'voluntary' => 'Voluntary Work',
                            'training' => 'Training',
                            'otherinfo' => 'Other Information',
                            'declaration' => 'References',
                        ];
                    @endphp

                    @foreach($tabSectionMap as $tKey => $sName)
                        @php
                            $secReview = $employee->pdsSectionReviews->where('section_name', $sName)->first();
                            $updatedItems = $pdsReviewUpdates[$tKey] ?? [];
                        @endphp
                        <div x-show="tab === '{{ $tKey }}'" x-cloak class="mt-4">
                            <form action="{{ route('pds-reviews.store', $employee) }}" method="POST" class="space-y-4" onsubmit="const remarks = this.elements['remarks']; if (event.submitter?.value === 'rejected' && !remarks.value.trim()) { remarks.setCustomValidity('Remarks are required when rejecting this section.'); remarks.reportValidity(); event.preventDefault(); return false; } remarks.setCustomValidity('');">
                                @csrf
                                <input type="hidden" name="section_name" value="{{ $sName }}">

                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="text-sm font-bold text-gray-700">Active Section: <strong class="text-indigo-900">{{ $sName }}</strong></span>
                                    <span class="rounded-full px-3 py-1 text-[10px] font-black uppercase {{ $secReview?->status === 'approved' ? 'border border-emerald-200 bg-emerald-100 text-emerald-800' : ($secReview?->status === 'rejected' ? 'border border-red-200 bg-red-100 text-red-800' : 'border border-amber-200 bg-amber-100 text-amber-800') }}">
                                        Status: {{ ucfirst($secReview?->status ?? 'pending') }}
                                    </span>
                                </div>

                                <div class="border-l-4 border-rose-300 bg-rose-50/70 px-4 py-3">
                                    <h5 class="text-xs font-black uppercase tracking-wider text-rose-800">Changed fields</h5>
                                    @if(count($updatedItems))
                                        <ul class="mt-2 flex flex-wrap gap-2">
                                            @foreach($updatedItems as $item)
                                                <li class="rounded-full border border-rose-100 bg-white px-3 py-1 text-xs font-bold text-rose-700">{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="mt-1 text-xs font-semibold text-slate-500">No specific changed fields are pending for this section.</p>
                                    @endif
                                </div>

                                <div>
                                    <div class="mb-1 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700">Review Remarks </label>
                                       
                                    </div>
                                    <textarea name="remarks" rows="2" class="w-full rounded-lg border border-gray-300 bg-indigo-50/70 p-3 text-sm font-medium text-gray-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="State reasons for rejection or optional approval notes...">{{ $secReview?->remarks ?? '' }}</textarea>
                                </div>

                                <div class="flex flex-col items-stretch justify-end gap-3 pt-2 sm:flex-row sm:items-center">
                                    <button type="submit" name="status" value="rejected" style="background-color: #ffffff; border-color: #dc2626; color: #b91c1c;" class="inline-flex items-center justify-center gap-2 rounded-lg border px-6 py-3 text-xs font-black uppercase tracking-wider shadow-sm transition hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-200 active:scale-95">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Reject
                                    </button>
                                    <button type="submit" name="status" value="approved" style="background-color: #0038a8; border-color: #002f8f; color: #ffffff;" class="inline-flex items-center justify-center gap-2 rounded-lg border px-6 py-3 text-xs font-black uppercase tracking-wider shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-blue-200 active:scale-95">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Approve
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <style>
        .animate-fade-in { animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .pds-line-page .pds-form-body {
            color: #334155;
            font-family: Arial, Helvetica, sans-serif;
        }
        .pds-field-table {
            display: grid;
            column-gap: 1rem;
            row-gap: 1.35rem;
            align-items: end;
        }
        .pds-field-table-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .pds-field-table-4 {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
        @media (max-width: 1023px) {
            .pds-field-table-4 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 767px) {
            .pds-field-table-3,
            .pds-field-table-4 {
                grid-template-columns: 1fr;
            }
        }
        .pds-line-page .pds-form-body label span,
        .pds-line-page .pds-form-body .text-gray-500,
        .pds-line-page .pds-form-body .text-gray-400 {
            color: #94a3b8 !important;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0;
        }
        .pds-line-page .pds-form-body label span.text-red-500,
        .pds-line-page .pds-form-body .text-red-500 {
            color: #ef4444 !important;
            font-weight: 700 !important;
        }
        .pds-line-page .pds-form-body input:not([type="radio"]):not([type="checkbox"]):not([type="file"]),
        .pds-line-page .pds-form-body select,
        .pds-line-page .pds-form-body textarea {
    border: 0 !important;
    border-bottom: 1px dashed #94a3b8 !important;
    border-radius: 6px !important;
    background: #eef2ff !important;
    box-shadow: none !important;
    padding: 0.45rem 0.65rem !important;
    font-size: 1rem !important;
    font-weight: 500 !important;
            color: #475569 !important;
        }
        .pds-line-page .pds-form-body .address-combobox {
            width: 100% !important;
        }
        .pds-line-page .pds-form-body select {
            appearance: auto !important;
            -webkit-appearance: menulist !important;
            padding-right: 2rem !important;
            cursor: pointer;
        }
        .pds-line-page .pds-form-body input:focus,
        .pds-line-page .pds-form-body select:focus,
        .pds-line-page .pds-form-body textarea:focus {
            border-bottom-color: #2b428f !important;
            outline: 0 !important;
            box-shadow: none !important;
            --tw-ring-shadow: 0 0 #0000 !important;
        }
        @if($isReadOnly)
        @foreach($pdsReviewChangedInputNames as $inputName)
        .pds-line-page .pds-form-body [name="{{ $inputName }}"] {
            border-bottom-color: #dc2626 !important;
            border-bottom-width: 2px !important;
            background-color: #fff1f2 !important;
            color: #991b1b !important;
        }
        @endforeach
        @endif
        .pds-line-page .pds-form-body table {
            border-collapse: collapse;
        }
        .pds-line-page .pds-form-body th,
        .pds-line-page .pds-form-body td {
            border-color: #e2e8f0 !important;
        }
        .pds-line-page .pds-section-heading {
            border: 0 !important;
            border-bottom: 2px solid #2b428f !important;
            background: transparent !important;
            padding: 0 0 0.5rem 0 !important;
            margin-bottom: 1.5rem !important;
        }
        .pds-line-page .pds-section-heading h3 {
            color: #0f172a !important;
            letter-spacing: 0.03em;
        }
        @media print {
            body { background: #fff !important; }
            .pds-line-page { padding: 0 !important; background: #fff !important; }
            .pds-paper { box-shadow: none !important; border: 0 !important; }
            .pds-form-body { padding: 0.25in !important; }
        }
    </style>
</x-app-layout>
