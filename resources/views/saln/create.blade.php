<x-app-layout>
    @php
        $selectedYear = $selectedYear ?? now()->year;
        $selectedSaln = $selectedSaln ?? null;
        $salns = $salns ?? collect();
        $isSalnIndex = $isSalnIndex ?? false;
        $declarantDefaults = $selectedSaln?->declarant_info ?? [];
        $spouseDefaults = $selectedSaln?->spouse_info ?? [];

        $oldFilingType = old('type_of_filing', $selectedSaln?->type_of_filing ?? 'annual_filing');
        $oldAsOfDate = old('as_of_date', $selectedSaln?->as_of_date?->format('Y-m-d'));
        $oldAnnualYear = $oldFilingType === 'annual_filing' && $oldAsOfDate
            ? \Carbon\Carbon::parse($oldAsOfDate)->format('Y')
            : ($oldFilingType === 'annual_filing' ? $selectedYear : now()->year);
        $oldAssumptionDate = $oldFilingType === 'assumption_of_office' ? ($oldAsOfDate ?? '') : '';
        $oldExitDate = $oldFilingType === 'exit' ? ($oldAsOfDate ?? '') : '';
        $oldFilingStatus = old('filing_status', $selectedSaln?->filing_status ?? 'not_applicable');

        $tabs = [
            'compliance' => 'Compliance',
            'declarant' => 'Declarant Details',
            'children' => 'Children',
            'assets' => 'Assets',
            'liabilities' => 'Liabilities',
            'business' => 'Business Interests',
            'relatives' => 'Relatives in Government',
            'certification' => 'Certification',
        ];
    @endphp

    <style>
        .saln-entry-page {
            color: #334155;
        }

        .saln-entry-page label.block > span:first-child,
        .saln-entry-page th {
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .saln-entry-page .text-red-500 {
            color: #ef4444 !important;
            font-weight: 700 !important;
        }

        /* Match the PDS field appearance without changing typography */
        .saln-entry-page input[type="text"],
        .saln-entry-page input[type="number"],
        .saln-entry-page input[type="date"],
        .saln-entry-page textarea,
        .saln-entry-page select {
            border: 0 !important;
            border-bottom: 1px dashed #94a3b8 !important;
            border-radius: 6px !important;
            background: #eef2ff !important;
            box-shadow: none !important;
            padding: .5rem .7rem !important;
            color: #475569;
            font-size: .875rem;
            font-weight: 700;
        }

        .saln-entry-page input:focus,
        .saln-entry-page textarea:focus,
        .saln-entry-page select:focus {
            border-bottom-color: #2b428f !important;
            outline: 0 !important;
            box-shadow: none !important;
            --tw-ring-shadow: 0 0 #0000 !important;
        }

        .saln-entry-page input:disabled,
        .saln-entry-page textarea:disabled,
        .saln-entry-page select:disabled {
            background: #f1f5f9 !important;
            color: #94a3b8 !important;
            cursor: not-allowed;
        }

        .saln-entry-page label.block input,
        .saln-entry-page label.block textarea {
            width: 100%;
            line-height: 1.4;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .saln-entry-page label.block textarea {
            min-height: 4rem;
            resize: vertical;
        }

        /* PDS-like field spacing */
        .saln-entry-page .saln-field-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            column-gap: 1.5rem;
            row-gap: 1.75rem;
            align-items: end;
        }

        .saln-entry-page .saln-field-grid label {
            min-width: 0;
        }

        .saln-entry-page .saln-field-grid .wide-field {
            grid-column: span 2 / span 2;
        }

        @media (max-width: 1023px) {
            .saln-entry-page .saln-field-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767px) {
            .saln-entry-page .saln-field-grid {
                grid-template-columns: 1fr;
            }

            .saln-entry-page .saln-field-grid .wide-field {
                grid-column: auto;
            }
        }

        /* Consistent soft rounded panels */
        .saln-entry-page .pds-paper {
            border: 1px solid #e2e8f0;
            border-radius: .5rem;
            background: #fff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .06);
        }

        .saln-entry-page .saln-soft-panel {
            border: 1px solid #e2e8f0;
            border-radius: .75rem;
            background: #f8fafc;
            padding: 1.5rem;
        }

        .saln-entry-page .saln-subsection-title {
            color: #111827;
            font-size: .78rem;
            font-weight: 900;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        /* Table containers */
        .saln-entry-page .saln-table-box {
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: .75rem;
            background: #fff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        }

        .saln-entry-page .saln-table-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 1rem 1.25rem;
        }

        .saln-entry-page table {
            border-collapse: collapse;
        }

        .saln-entry-page table th {
            padding: .85rem 1rem;
        }

        .saln-entry-page table td {
            padding: .85rem 1rem;
        }

        .saln-entry-page table tbody tr:hover {
            background: #f8fafc;
        }

        .saln-entry-page table input,
        .saln-entry-page table textarea,
        .saln-entry-page table select {
            width: 100%;
            min-width: 115px;
            padding: .5rem .65rem !important;
        }

        /* More room for the Asset tables */
        .saln-entry-page .saln-real-properties-table {
            min-width: 1380px !important;
        }

        .saln-entry-page .saln-personal-properties-table,
        .saln-entry-page .saln-liabilities-table {
            min-width: 860px !important;
        }

        .saln-entry-page .saln-summary-box {
            border-radius: .75rem;
            background: #f8fafc;
            padding: 1rem 1.25rem;
        }

        .saln-entry-page .saln-summary-box-primary {
            border-radius: .75rem;
            background: #eef2ff;
            padding: 1rem 1.25rem;
        }

        .saln-entry-page .pds-form-body {
            padding: 2rem !important;
        }

        @media (max-width: 640px) {
            .saln-entry-page .pds-form-body {
                padding: 1rem !important;
            }
        }
    </style>

    <div
        class="saln-entry-page pds-line-page min-h-screen bg-white py-8 font-sans"
        x-data="salnForm(@js([
            'children' => old('children', $selectedSaln?->children ?? []),
            'real_properties' => old('real_properties', $selectedSaln?->real_properties ?? []),
            'personal_properties' => old('personal_properties', $selectedSaln?->personal_properties ?? []),
            'liabilities' => old('liabilities', $selectedSaln?->liabilities ?? []),
            'business_interests' => old('business_interests', $selectedSaln?->business_interests ?? []),
            'relatives_in_gov' => old('relatives_in_gov', $selectedSaln?->relatives_in_gov ?? []),
            'no_business_interests' => old('has_business_interests') !== null ? ! filter_var(old('has_business_interests'), FILTER_VALIDATE_BOOLEAN) : ! (bool) ($selectedSaln?->has_business_interests ?? false),
            'no_relatives_in_gov' => old('has_relatives_in_gov') !== null ? ! filter_var(old('has_relatives_in_gov'), FILTER_VALIDATE_BOOLEAN) : ! (bool) ($selectedSaln?->has_relatives_in_gov ?? false),
            'multiple_marriages_na' => (bool) old('declarant_info.multiple_marriages_not_applicable', $declarantDefaults['multiple_marriages_not_applicable'] ?? true),
            'filing_type' => $oldFilingType,
            'assumption_date' => $oldAssumptionDate,
            'annual_year' => (string) $oldAnnualYear,
            'exit_date' => $oldExitDate,
        ]))"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @unless($isSalnIndex)
                <div class="mb-5 flex items-center justify-between gap-3">
                    <a href="{{ route('salns.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back
                    </a>
                </div>
            @endunless

            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 shadow-sm">
                    <p class="font-bold text-red-800">Please review and fix the following errors:</p>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Statement of Assets, Liabilities, and Net Worth</h2>
                    <p class="mt-1 text-sm font-medium text-slate-600">Please put N/A for fields if not applicable.</p>
                </div>
                <form method="GET" action="{{ route('salns.index') }}" class="flex w-fit items-center gap-2">
                    <label for="saln_year" class="text-[10px] font-black uppercase tracking-widest text-slate-400">Year</label>
                    <select id="saln_year" name="year" onchange="this.form.submit()" class="w-32 rounded-lg border-gray-100 bg-gray-50/30 px-3 py-2 text-sm font-bold text-slate-700 shadow-sm outline-none focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach(range(now()->year + 1, now()->year - 5) as $year)
                            <option value="{{ $year }}" @selected((int) $selectedYear === (int) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="pds-paper overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-xl font-black tracking-tight text-slate-900">{{ $selectedYear }} SALN Form</h1>
                        <p class="mt-1 text-xs font-medium text-slate-500">Per CSC Resolution No. 2500632 | As required by R.A. No. 6713</p>
                    </div>

                    <div class="flex flex-col items-stretch gap-3 sm:items-end">
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            @if($selectedSaln)
                                <a href="{{ route('salns.show', $selectedSaln) }}" class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-xs font-black uppercase tracking-widest text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-50">View {{ $selectedYear }} Copy</a>
                            @endif
                            <button
                                type="submit"
                                form="salnForm"
                                class="inline-flex items-center rounded-lg bg-indigo-700 px-4 py-2 text-xs font-black uppercase tracking-widest text-white hover:bg-indigo-800"
                            >
                                Save SALN
                            </button>
                        </div>
                    </div>
                </div>

                <div class="border-b border-gray-200 bg-white">
                <div class="flex items-center gap-5 overflow-x-auto px-6 pt-5">
                    <button type="button" @click="$refs.salnTabs.scrollBy({ left: -240, behavior: 'smooth' })" class="shrink-0 p-2 text-slate-400 hover:text-[#2b428f]">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>

                    <div x-ref="salnTabs" class="flex min-w-0 flex-1 items-center gap-5 overflow-x-auto">
                        @foreach($tabs as $key => $label)
                            <button
                                type="button"
                                @click="activeTab = '{{ $key }}'"
                                class="shrink-0 whitespace-nowrap border-b-2 px-1 pb-3 text-xs font-black uppercase tracking-[0.16em] transition-all duration-200"
                                :class="activeTab === '{{ $key }}'
                                    ? 'border-indigo-700 text-indigo-700'
                                    : 'border-transparent text-gray-500 hover:text-gray-800'"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    <button type="button" @click="$refs.salnTabs.scrollBy({ left: 240, behavior: 'smooth' })" class="shrink-0 p-2 text-slate-400 hover:text-[#2b428f]">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
                </div>

                <form id="salnForm" method="POST" action="{{ route('salns.store') }}" class="pds-form-body px-8 pb-8 pt-8">
                    @csrf
                    @if($selectedSaln)
                        <input type="hidden" name="saln_id" value="{{ $selectedSaln->id }}">
                    @endif
                    <input type="hidden" name="as_of_date" :value="asOfDate">

                    <section x-show="activeTab === 'compliance'" x-cloak class="space-y-6">
                    <div class="saln-soft-panel">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-indigo-700">Filing Details</p>
                                <p class="mt-1 text-xs font-medium text-slate-500">Select the SALN filing basis for the selected year.</p>
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <label class="rounded-xl border bg-white p-4 shadow-sm transition" :class="filingType === 'assumption_of_office' ? 'border-indigo-700 ring-2 ring-indigo-100' : 'border-gray-100'">
                                <span class="flex items-center gap-3 text-sm font-semibold text-slate-800">
                                    <input type="radio" name="type_of_filing" value="assumption_of_office" class="h-4 w-4 text-indigo-700" x-model="filingType">
                                    Assumption of Office
                                </span>
                                <span class="mt-3 block text-[10px] font-black uppercase tracking-wider text-slate-400">As of date</span>
                                <input type="date" x-model="assumptionDate" :disabled="filingType !== 'assumption_of_office'" class="mt-1 w-full rounded-lg border-gray-100 bg-gray-50/30 text-sm font-bold disabled:bg-slate-100">
                            </label>

                            <label class="rounded-xl border bg-white p-4 shadow-sm transition" :class="filingType === 'annual_filing' ? 'border-indigo-700 ring-2 ring-indigo-100' : 'border-gray-100'">
                                <span class="flex items-center gap-3 text-sm font-semibold text-slate-800">
                                    <input type="radio" name="type_of_filing" value="annual_filing" class="h-4 w-4 text-indigo-700" x-model="filingType">
                                    Annual Filing
                                </span>
                                <span class="mt-3 block text-[10px] font-black uppercase tracking-wider text-slate-400">As of December 31</span>
                                <input type="text" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" x-model="annualYear" :disabled="filingType !== 'annual_filing'" class="mt-1 w-full rounded-lg border-gray-100 bg-gray-50/30 text-sm font-bold disabled:bg-slate-100">
                            </label>

                            <label class="rounded-xl border bg-white p-4 shadow-sm transition" :class="filingType === 'exit' ? 'border-indigo-700 ring-2 ring-indigo-100' : 'border-gray-100'">
                                <span class="flex items-center gap-3 text-sm font-semibold text-slate-800">
                                    <input type="radio" name="type_of_filing" value="exit" class="h-4 w-4 text-indigo-700" x-model="filingType">
                                    Exit from Service
                                </span>
                                <span class="mt-3 block text-[10px] font-black uppercase tracking-wider text-slate-400">As of date</span>
                                <input type="date" x-model="exitDate" :disabled="filingType !== 'exit'" class="mt-1 w-full rounded-lg border-gray-100 bg-gray-50/30 text-sm font-bold disabled:bg-slate-100">
                            </label>
                        </div>
                    </div>
                    </section>

                    <section x-show="activeTab === 'declarant'" x-cloak class="space-y-6">
                        <div class="saln-field-grid">
                            <label class="block"><span class="text-xs text-slate-400">Declarant Family Name <span class="text-red-500 font-bold ml-0.5">*</span></span><input name="declarant_info[family_name]" value="{{ old('declarant_info.family_name', $declarantDefaults['family_name'] ?? $employee->lastname) }}" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0"></label>
                            <label class="block"><span class="text-xs text-slate-400">Declarant First Name <span class="text-red-500 font-bold ml-0.5">*</span></span><input name="declarant_info[first_name]" value="{{ old('declarant_info.first_name', $declarantDefaults['first_name'] ?? $employee->firstname) }}" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0"></label>
                            <label class="block"><span class="text-xs text-slate-400">Declarant Middle Initial <span class="text-red-500 font-bold ml-0.5">*</span></span><input name="declarant_info[middle_initial]" value="{{ old('declarant_info.middle_initial', $declarantDefaults['middle_initial'] ?? strtoupper(substr($employee->middlename ?? '', 0, 1))) }}" maxlength="3" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0"></label>
                            <label class="block"><span class="text-xs text-slate-400">Position <span class="text-red-500 font-bold ml-0.5">*</span></span><input name="declarant_info[position]" value="{{ old('declarant_info.position', $declarantDefaults['position'] ?? $employee->position) }}" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0"></label>
                            <label class="block wide-field"><span class="text-xs text-slate-400">Agency / Office <span class="text-red-500 font-bold ml-0.5">*</span></span><textarea name="declarant_info[agency]" rows="2" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0">{{ old('declarant_info.agency', $declarantDefaults['agency'] ?? 'Department of Migrant Workers Region XI') }}</textarea></label>
                            <label class="block wide-field"><span class="text-xs text-slate-400">Office Address <span class="text-red-500 font-bold ml-0.5">*</span></span><textarea name="declarant_info[office_address]" rows="2" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0">{{ old('declarant_info.office_address', $declarantDefaults['office_address'] ?? '') }}</textarea></label>
                            <label class="block"><span class="text-xs text-slate-400">Spouse Family Name</span><input name="spouse_info[family_name]" value="{{ old('spouse_info.family_name', $spouseDefaults['family_name'] ?? '') }}" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0"></label>
                            <label class="block"><span class="text-xs text-slate-400">Spouse First Name</span><input name="spouse_info[first_name]" value="{{ old('spouse_info.first_name', $spouseDefaults['first_name'] ?? '') }}" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0"></label>
                            <label class="block"><span class="text-xs text-slate-400">Spouse Middle Initial</span><input name="spouse_info[middle_initial]" value="{{ old('spouse_info.middle_initial', $spouseDefaults['middle_initial'] ?? '') }}" maxlength="3" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0"></label>
                            <label class="block"><span class="text-xs text-slate-400">Spouse Position</span><input name="spouse_info[position]" value="{{ old('spouse_info.position', $spouseDefaults['position'] ?? '') }}" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0"></label>
                            <label class="block wide-field"><span class="text-xs text-slate-400">Spouse Agency / Office</span><textarea name="spouse_info[agency]" rows="2" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0">{{ old('spouse_info.agency', $spouseDefaults['agency'] ?? '') }}</textarea></label>
                            <label class="block wide-field"><span class="text-xs text-slate-400">Spouse Office Address</span><textarea name="spouse_info[office_address]" rows="2" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0">{{ old('spouse_info.office_address', $spouseDefaults['office_address'] ?? '') }}</textarea></label>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Filing Status</p>
                            <p class="mt-1 text-xs font-medium text-slate-500">Spouses who are both public officials or employees may file jointly or separately.</p>
                            <div class="mt-4 flex flex-wrap gap-4 rounded border border-slate-200 bg-white p-4">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="filing_status" value="joint" class="h-4 w-4 text-indigo-700" @checked($oldFilingStatus === 'joint')>
                                    Joint Filing
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="filing_status" value="separate" class="h-4 w-4 text-indigo-700" @checked($oldFilingStatus === 'separate')>
                                    Separate Filing
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="filing_status" value="not_applicable" class="h-4 w-4 text-indigo-700" @checked($oldFilingStatus === 'not_applicable')>
                                    Not Applicable
                                </label>
                            </div>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-600">Multiple Marriages</p>
                                    <p class="mt-1 text-xs text-slate-400">If with multiple marriages, indicate name(s) of spouse(s).</p>
                                </div>
                                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500">
                                    <input type="checkbox" name="declarant_info[multiple_marriages_not_applicable]" value="1" class="rounded text-[#2b428f]" x-model="multipleMarriagesNA" @change="clearSpouseNames()">
                                    Not Applicable
                                </label>
                            </div>
                            <div class="mt-4 grid gap-4 md:grid-cols-2">
                                <input name="declarant_info[multiple_spouses][]" x-model="spouseName1" :disabled="multipleMarriagesNA" placeholder="Name of spouse" class="rounded border-slate-200 text-sm disabled:bg-slate-100">
                                <input name="declarant_info[multiple_spouses][]" x-model="spouseName2" :disabled="multipleMarriagesNA" placeholder="Name of spouse" class="rounded border-slate-200 text-sm disabled:bg-slate-100">
                            </div>
                        </div>
                    </section>

                    <section x-show="activeTab === 'children'" x-cloak>
                        <div class="saln-table-box">
                            <div class="saln-table-heading">
                                <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-500">Unmarried Children Below 18</h3>
                                <button type="button" @click="addChild()" class="rounded-lg bg-indigo-700 px-3 py-1.5 text-xs font-black uppercase tracking-widest text-white hover:bg-indigo-800">+ Add Row</button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[560px] text-left text-sm">
                                    <thead class="bg-white text-[10px] uppercase tracking-widest text-slate-500">
                                        <tr>
                                            <th class="px-4 py-3 font-semibold">Name of Child <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3 font-semibold">Age <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="w-16 px-4 py-3"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="(row, index) in children" :key="row._id">
                                            <tr>
                                                <td class="px-4 py-4"><input :name="'children['+index+'][name]'" x-model="row.name" class="w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm font-bold text-slate-600 outline-none focus:border-indigo-700 focus:ring-0"></td>
                                                <td class="px-4 py-4"><input type="number" min="0" max="17" :name="'children['+index+'][age]'" x-model="row.age" class="w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm font-bold text-slate-600 outline-none focus:border-indigo-700 focus:ring-0"></td>
                                                <td class="px-4 py-4 text-right"><button type="button" @click="removeChild(index)" class="text-xs font-black uppercase tracking-wider text-red-500">Remove</button></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section x-show="activeTab === 'assets'" x-cloak class="space-y-8">
                        <div class="saln-table-box">
                            <div class="saln-table-heading">
                                <h3 class="saln-subsection-title">Real Properties</h3>
                                <button type="button" @click="addRealProperty()" class="rounded-lg bg-indigo-700 px-3 py-1.5 text-xs font-black uppercase tracking-widest text-white hover:bg-indigo-800">+ Add Row</button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="saln-real-properties-table w-full min-w-[1380px] text-left text-sm">
                                    <thead class="bg-white text-xs uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th class="px-4 py-3 font-semibold">Description <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3 font-semibold">Kind <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3 font-semibold">Exact Location <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3 font-semibold">Assessed Value <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3 font-semibold">Fair Market Value <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3 font-semibold">Year <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3 font-semibold">Mode <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3 font-semibold">Acquisition Cost <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="w-16 px-4 py-3"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="(row, index) in realProperties" :key="row._id">
                                            <tr>
                                                <td class="px-4 py-3"><input :name="'real_properties['+index+'][description]'" x-model="row.description" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"></td>
                                                <td class="px-4 py-3"><select :name="'real_properties['+index+'][kind]'" x-model="row.kind" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"><option value=""></option>@foreach($salnOptionSets['property_kinds'] as $option)<option value="{{ $option->value }}">{{ $option->label }}</option>@endforeach</select></td>
                                                <td class="px-4 py-3"><input :name="'real_properties['+index+'][location]'" x-model="row.location" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"></td>
                                                <td class="px-4 py-3"><input type="number" step="0.01" min="0" :name="'real_properties['+index+'][assessed_value]'" x-model="row.assessed_value" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"></td>
                                                <td class="px-4 py-3"><input type="number" step="0.01" min="0" :name="'real_properties['+index+'][fair_market_value]'" x-model="row.fair_market_value" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"></td>
                                                <td class="px-4 py-3"><input maxlength="4" :name="'real_properties['+index+'][acquisition_year]'" x-model="row.acquisition_year" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"></td>
                                                <td class="px-4 py-3"><select :name="'real_properties['+index+'][acquisition_mode]'" x-model="row.acquisition_mode" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"><option value=""></option>@foreach($salnOptionSets['acquisition_modes'] as $option)<option value="{{ $option->value }}">{{ $option->label }}</option>@endforeach</select></td>
                                                <td class="px-4 py-3"><input type="number" step="0.01" min="0" :name="'real_properties['+index+'][acquisition_cost]'" x-model="row.acquisition_cost" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"></td>
                                                <td class="px-4 py-3 text-right"><button type="button" @click="removeRealProperty(index)" class="text-sm font-bold text-red-500">Remove</button></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <p class="mt-3 text-right text-sm font-bold text-slate-700">Real Property Total: <span x-text="currency(totalRealAssets)"></span></p>

                        <div class="saln-table-box">
                            <div class="saln-table-heading">
                                <h3 class="saln-subsection-title">Personal Properties</h3>
                                <button type="button" @click="addPersonalProperty()" class="rounded-lg bg-indigo-700 px-3 py-1.5 text-xs font-black uppercase tracking-widest text-white hover:bg-indigo-800">+ Add Row</button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="saln-personal-properties-table w-full min-w-[860px] text-left text-sm">
                                    <thead class="bg-white text-xs uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th class="px-4 py-3 font-semibold">Description <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3 font-semibold">Acquisition Year <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3 font-semibold">Acquisition Cost / Amount <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="w-16 px-4 py-3"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="(row, index) in personalProperties" :key="row._id">
                                            <tr>
                                                <td class="px-4 py-3"><input :name="'personal_properties['+index+'][description]'" x-model="row.description" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"></td>
                                                <td class="px-4 py-3"><input maxlength="4" :name="'personal_properties['+index+'][acquisition_year]'" x-model="row.acquisition_year" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"></td>
                                                <td class="px-4 py-3"><input type="number" step="0.01" min="0" :name="'personal_properties['+index+'][acquisition_cost]'" x-model="row.acquisition_cost" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"></td>
                                                <td class="px-4 py-3 text-right"><button type="button" @click="removePersonalProperty(index)" class="text-sm font-bold text-red-500">Remove</button></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="grid gap-3 text-sm font-bold text-slate-700 sm:grid-cols-3">
                            <p class="saln-summary-box">Real Property Total: <span class="text-indigo-700" x-text="currency(totalRealAssets)"></span></p>
                            <p class="saln-summary-box">Personal Property Total: <span class="text-indigo-700" x-text="currency(totalPersonalAssets)"></span></p>
                            <p class="saln-summary-box-primary">Total Assets: <span class="text-indigo-700" x-text="currency(totalAssets)"></span></p>
                        </div>
                    </section>

                    <section x-show="activeTab === 'liabilities'" x-cloak>
                        <div class="saln-table-box">
                            <div class="saln-table-heading">
                                <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-500">Liabilities</h3>
                                <button type="button" @click="addLiability()" class="rounded-lg bg-indigo-700 px-3 py-1.5 text-xs font-black uppercase tracking-widest text-white hover:bg-indigo-800">+ Add Row</button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="saln-liabilities-table w-full min-w-[860px] text-left text-sm">
                                    <thead class="bg-white text-xs uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th class="px-4 py-3 font-semibold">Nature <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3 font-semibold">Name of Creditors <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="px-4 py-3 font-semibold">Outstanding Balance <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                            <th class="w-16 px-4 py-3"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="(row, index) in liabilities" :key="row._id">
                                            <tr>
                                                <td class="px-4 py-3"><input :name="'liabilities['+index+'][nature]'" x-model="row.nature" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"></td>
                                                <td class="px-4 py-3"><input :name="'liabilities['+index+'][creditor]'" x-model="row.creditor" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"></td>
                                                <td class="px-4 py-3"><input type="number" step="0.01" min="0" :name="'liabilities['+index+'][outstanding_balance]'" x-model="row.outstanding_balance" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"></td>
                                                <td class="px-4 py-3 text-right"><button type="button" @click="removeLiability(index)" class="text-sm font-bold text-red-500">Remove</button></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <p class="mt-3 text-right text-sm font-bold text-slate-700">Total Liabilities: <span x-text="currency(totalLiabilities)"></span></p>
                    </section>

                    <section x-show="activeTab === 'business'" x-cloak class="space-y-4">
                        <div class="flex flex-wrap items-center justify-end gap-4">
                            <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-700">
                                <input type="checkbox" class="rounded text-indigo-700" x-model="noBusinessInterests" @change="toggleNoBusiness()">
                                No business interest
                            </label>
                            <button type="button" @click="addBusinessInterest()" x-show="!noBusinessInterests" class="rounded-lg bg-indigo-700 px-3 py-1.5 text-xs font-black uppercase tracking-widest text-white hover:bg-indigo-800">+ Add Row</button>
                        </div>
                        <input type="hidden" name="has_business_interests" :value="noBusinessInterests ? 0 : 1">
                        <div x-show="!noBusinessInterests">
                            <div class="saln-table-box">
                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-[980px] text-left text-sm">
                                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                            <tr>
                                                <th class="px-4 py-3 font-semibold">Name of Entity / Business Enterprise <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                                <th class="px-4 py-3 font-semibold">Business Address <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                                <th class="px-4 py-3 font-semibold">Nature of Interest / Connection <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                                <th class="px-4 py-3 font-semibold">Date of Acquisition <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                                <th class="w-16 px-4 py-3"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <template x-for="(row, index) in businessInterests" :key="row._id">
                                                <tr>
                                                    <td class="px-4 py-3"><input :name="'business_interests['+index+'][name]'" x-model="row.name" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"></td>
                                                    <td class="px-4 py-3"><input :name="'business_interests['+index+'][address]'" x-model="row.address" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"></td>
                                                    <td class="px-4 py-3"><input :name="'business_interests['+index+'][nature]'" x-model="row.nature" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"></td>
                                                    <td class="px-4 py-3"><input type="date" :name="'business_interests['+index+'][acquisition_date]'" x-model="row.acquisition_date" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"></td>
                                                    <td class="px-4 py-3 text-right"><button type="button" @click="removeBusinessInterest(index)" class="text-sm font-bold text-red-500">Remove</button></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div x-show="noBusinessInterests" class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Marked as not applicable.</div>
                    </section>

                    <section x-show="activeTab === 'relatives'" x-cloak class="space-y-4">
                        <div class="flex flex-wrap items-center justify-end gap-4">
                            <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-700">
                                <input type="checkbox" class="rounded text-indigo-700" x-model="noRelativesInGov" @change="toggleNoRelatives()">
                                No relatives in government
                            </label>
                            <button type="button" @click="addRelative()" x-show="!noRelativesInGov" class="rounded-lg bg-indigo-700 px-3 py-1.5 text-xs font-black uppercase tracking-widest text-white hover:bg-indigo-800">+ Add Row</button>
                        </div>
                        <input type="hidden" name="has_relatives_in_gov" :value="noRelativesInGov ? 0 : 1">
                        <div x-show="!noRelativesInGov">
                            <div class="saln-table-box">
                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-[980px] text-left text-sm">
                                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                            <tr>
                                                <th class="px-4 py-3 font-semibold">Name of Relative <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                                <th class="px-4 py-3 font-semibold">Relationship <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                                <th class="px-4 py-3 font-semibold">Position <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                                <th class="px-4 py-3 font-semibold">Agency / Office and Address <span class="text-red-500 font-bold ml-0.5">*</span></th>
                                                <th class="w-16 px-4 py-3"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <template x-for="(row, index) in relatives" :key="row._id">
                                                <tr>
                                                    <td class="px-4 py-3"><input :name="'relatives_in_gov['+index+'][name]'" x-model="row.name" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"></td>
                                                    <td class="px-4 py-3"><select :name="'relatives_in_gov['+index+'][relationship]'" x-model="row.relationship" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"><option value=""></option>@foreach($salnOptionSets['relationships'] as $option)<option value="{{ $option->value }}">{{ $option->label }}</option>@endforeach</select></td>
                                                    <td class="px-4 py-3"><input :name="'relatives_in_gov['+index+'][position]'" x-model="row.position" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"></td>
                                                    <td class="px-4 py-3"><input :name="'relatives_in_gov['+index+'][agency]'" x-model="row.agency" class="w-full border-0 border-b border-dashed border-slate-300 bg-transparent px-0 py-1 outline-none focus:border-[#2b428f] focus:ring-0"></td>
                                                    <td class="px-4 py-3 text-right"><button type="button" @click="removeRelative(index)" class="text-sm font-bold text-red-500">Remove</button></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div x-show="noRelativesInGov" class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Marked as not applicable.</div>
                    </section>

                    <section x-show="activeTab === 'certification'" x-cloak class="space-y-6">
                        <div class="rounded-lg border border-amber-200 bg-amber-50/60 p-5 text-sm leading-6 text-slate-600">
                            <p>I hereby certify that these are true and correct statements of my assets, liabilities, net worth, business interests and financial connections, including those of my spouse and unmarried children below eighteen (18) years of age living in my household.</p>
                        </div>
                        <div class="grid gap-8 lg:grid-cols-2">
                            <div class="space-y-5">
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Declarant ID / Signature Details</p>
                                <div class="grid gap-x-6 gap-y-7 sm:grid-cols-2">
                                    <label class="block"><span class="text-xs text-slate-400">Date Accomplished <span class="text-red-500 font-bold ml-0.5">*</span></span><input type="date" name="declarant_info[date_accomplished]" value="{{ old('declarant_info.date_accomplished', $declarantDefaults['date_accomplished'] ?? now()->toDateString()) }}" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0"></label>
                                    <label class="block"><span class="text-xs text-slate-400">Government Issued ID <span class="text-red-500 font-bold ml-0.5">*</span></span><select name="declarant_info[government_issued_id]" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0"><option value=""></option>@foreach($salnOptionSets['government_id_types'] as $option)<option value="{{ $option->value }}" {{ old('declarant_info.government_issued_id', $declarantDefaults['government_issued_id'] ?? '') == $option->value ? 'selected' : '' }}>{{ $option->label }}</option>@endforeach</select></label>
                                    <label class="block"><span class="text-xs text-slate-400">ID Number <span class="text-red-500 font-bold ml-0.5">*</span></span><input name="declarant_info[id_no]" value="{{ old('declarant_info.id_no', $declarantDefaults['id_no'] ?? '') }}" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0"></label>
                                    <label class="block"><span class="text-xs text-slate-400">Date / Place Issued <span class="text-red-500 font-bold ml-0.5">*</span></span><input name="declarant_info[date_issued]" value="{{ old('declarant_info.date_issued', $declarantDefaults['date_issued'] ?? '') }}" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0"></label>
                                </div>
                            </div>

                            <div class="space-y-5">
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Spouse ID / Signature Details</p>
                                <div class="grid gap-x-6 gap-y-7 sm:grid-cols-2">
                                    <label class="block"><span class="text-xs text-slate-400">Date Accomplished</span><input type="date" name="spouse_info[date_accomplished]" value="{{ old('spouse_info.date_accomplished', $spouseDefaults['date_accomplished'] ?? '') }}" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0"></label>
                                    <label class="block"><span class="text-xs text-slate-400">Government Issued ID</span><select name="spouse_info[government_issued_id]" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0"><option value=""></option>@foreach($salnOptionSets['government_id_types'] as $option)<option value="{{ $option->value }}" {{ old('spouse_info.government_issued_id', $spouseDefaults['government_issued_id'] ?? '') == $option->value ? 'selected' : '' }}>{{ $option->label }}</option>@endforeach</select></label>
                                    <label class="block"><span class="text-xs text-slate-400">ID Number</span><input name="spouse_info[id_no]" value="{{ old('spouse_info.id_no', $spouseDefaults['id_no'] ?? '') }}" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0"></label>
                                    <label class="block"><span class="text-xs text-slate-400">Date / Place Issued</span><input name="spouse_info[date_issued]" value="{{ old('spouse_info.date_issued', $spouseDefaults['date_issued'] ?? '') }}" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0"></label>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-x-6 gap-y-7 md:grid-cols-2">
                            <label class="block"><span class="text-xs text-slate-400">Person Administering Oath</span><input name="declarant_info[person_administering_oath]" value="{{ old('declarant_info.person_administering_oath', $declarantDefaults['person_administering_oath'] ?? '') }}" class="mt-1 w-full border-0 border-b border-dashed border-slate-400 bg-transparent px-0 py-1 text-sm text-slate-600 outline-none focus:border-[#2b428f] focus:ring-0"></label>
                        </div>
                    </section>

                    <div class="mt-8 border-t border-slate-100 pt-6">
                        <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            <span class="font-bold text-slate-800">Computed Net Worth:</span>
                            <span class="font-bold text-[#2b428f]" x-text="currency(netWorth)"></span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function salnForm(initial = {}) {
            const uid = () => Date.now().toString(36) + Math.random().toString(36).slice(2);
            const withIds = (rows, defaults = {}) => (Array.isArray(rows) ? rows : []).map(row => ({ ...defaults, ...row, _id: row._id || uid() }));
            const multipleSpouses = @json(old('declarant_info.multiple_spouses', []));

            return {
                activeTab: 'compliance',
                children: withIds(initial.children, { name: '', age: '' }),
                realProperties: withIds(initial.real_properties, { description: '', kind: '', location: '', assessed_value: '', fair_market_value: '', acquisition_year: '', acquisition_mode: '', acquisition_cost: 0 }),
                personalProperties: withIds(initial.personal_properties, { description: '', acquisition_year: '', acquisition_cost: 0 }),
                liabilities: withIds(initial.liabilities, { nature: '', creditor: '', outstanding_balance: 0 }),
                businessInterests: withIds(initial.business_interests, { name: '', address: '', nature: '', acquisition_date: '' }),
                relatives: withIds(initial.relatives_in_gov, { name: '', relationship: '', position: '', agency: '' }),
                noBusinessInterests: initial.no_business_interests ?? true,
                noRelativesInGov: initial.no_relatives_in_gov ?? true,
                multipleMarriagesNA: initial.multiple_marriages_na ?? true,
                spouseName1: multipleSpouses[0] ?? '',
                spouseName2: multipleSpouses[1] ?? '',
                filingType: initial.filing_type ?? 'annual_filing',
                assumptionDate: initial.assumption_date ?? '',
                annualYear: initial.annual_year ?? new Date().getFullYear().toString(),
                exitDate: initial.exit_date ?? '',
                get asOfDate() {
                    if (this.filingType === 'assumption_of_office') return this.assumptionDate;
                    if (this.filingType === 'annual_filing') return this.annualYear ? `${this.annualYear}-12-31` : '';
                    if (this.filingType === 'exit') return this.exitDate;
                    return '';
                },
                get totalRealAssets() { return this.realProperties.reduce((sum, row) => sum + (Number(row.acquisition_cost) || 0), 0); },
                get totalPersonalAssets() { return this.personalProperties.reduce((sum, row) => sum + (Number(row.acquisition_cost) || 0), 0); },
                get totalAssets() { return this.totalRealAssets + this.totalPersonalAssets; },
                get totalLiabilities() { return this.liabilities.reduce((sum, row) => sum + (Number(row.outstanding_balance) || 0), 0); },
                get netWorth() { return this.totalAssets - this.totalLiabilities; },
                currency(value) { return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value || 0); },
                clearSpouseNames() { if (this.multipleMarriagesNA) { this.spouseName1 = ''; this.spouseName2 = ''; } },
                addChild() { this.children.push({ _id: uid(), name: '', age: '' }); },
                removeChild(index) { this.children.splice(index, 1); },
                addRealProperty() { this.realProperties.push({ _id: uid(), description: '', kind: '', location: '', assessed_value: '', fair_market_value: '', acquisition_year: '', acquisition_mode: '', acquisition_cost: 0 }); },
                removeRealProperty(index) { this.realProperties.splice(index, 1); },
                addPersonalProperty() { this.personalProperties.push({ _id: uid(), description: '', acquisition_year: '', acquisition_cost: 0 }); },
                removePersonalProperty(index) { this.personalProperties.splice(index, 1); },
                addLiability() { this.liabilities.push({ _id: uid(), nature: '', creditor: '', outstanding_balance: 0 }); },
                removeLiability(index) { this.liabilities.splice(index, 1); },
                addBusinessInterest() { this.businessInterests.push({ _id: uid(), name: '', address: '', nature: '', acquisition_date: '' }); },
                removeBusinessInterest(index) { this.businessInterests.splice(index, 1); },
                addRelative() { this.relatives.push({ _id: uid(), name: '', relationship: '', position: '', agency: '' }); },
                removeRelative(index) { this.relatives.splice(index, 1); },
                toggleNoBusiness() { if (this.noBusinessInterests) this.businessInterests = []; else if (!this.businessInterests.length) this.addBusinessInterest(); },
                toggleNoRelatives() { if (this.noRelativesInGov) this.relatives = []; else if (!this.relatives.length) this.addRelative(); },
            };
        }
    </script>
</x-app-layout>
