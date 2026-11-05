<x-app-layout>
        @if(request()->boolean('modal'))
        <style>
            body > .flex.h-screen.overflow-hidden > aside,
            body > .flex.h-screen.overflow-hidden header {
                display: none !important;
            }
            body > .flex.h-screen.overflow-hidden,
            body > .flex.h-screen.overflow-hidden > .flex,
            body > .flex.h-screen.overflow-hidden main {
                height: auto !important;
                min-height: 100vh !important;
                overflow: visible !important;
            }
            body > .flex.h-screen.overflow-hidden main {
                padding: 0 !important;
                background: #fff !important;
            }
            body > .flex.h-screen.overflow-hidden main > div {
                max-width: none !important;
                padding: 0 !important;
            }
            body > .flex.h-screen.overflow-hidden main > div > div {
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                overflow: visible !important;
            }
            .modal-form-shell {
                padding: 0 !important;
            }
            .modal-form-shell > div {
                max-width: none !important;
                padding: 0 !important;
            }
            .modal-form-shell .modal-page-back {
                display: none !important;
            }
            .modal-form-shell .modal-form-card {
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                padding: 1.5rem !important;
            }
            .modal-form-shell .modal-form-title {
                display: none !important;
            }
        </style>
    @endif
    <x-slot name="title">{{ __('Create Travel Authority') }}</x-slot>

    <div class="py-12 modal-form-shell">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-5 modal-page-back">
                <a href="{{ route('travel-orders.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 shadow-sm transition hover:border-indigo-200 hover:text-indigo-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    {{ __('Back') }}
                </a>
            </div>

            <div class="bg-white/80 backdrop-blur-xl rounded-2xl shadow-lg p-8 border border-gray-100 modal-form-card">
                <h2 class="text-2xl font-black text-gray-900 mb-8 modal-form-title">{{ __('Create Travel Authority') }}</h2>

                <form
                    action="{{ request()->boolean('modal') ? route('travel-orders.store', ['modal' => 1]) : route('travel-orders.store') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="space-y-6"
                    x-data="travelOrderForm(@js($employees->map(fn ($emp) => [
                        'id' => $emp->id,
                        'name' => trim($emp->lastname . ', ' . $emp->firstname . ' ' . $emp->middlename),
                        'position' => $emp->position,
                        'division' => $emp->division,
                    ])->values()), @js(collect(old('companions', []))->map(fn ($id) => (int) $id)->values()), '{{ old('travel_date_start') }}', '{{ old('travel_date_end') }}', '{{ now()->toDateString() }}')"
                    x-init="init()"
                >
                    @csrf

                    {{-- Travel Type --}}
                    <div>
                        <label for="travel_type" class="block text-sm font-bold text-gray-700 mb-1">{{ __('Travel Type') }} <span class="text-red-500">*</span></label>
                        <select id="travel_type" name="travel_type" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                            <option value="">-- Select Travel Type --</option>
                            <option value="local" {{ old('travel_type') === 'local' ? 'selected' : '' }}>Local</option>
                            <option value="foreign" {{ old('travel_type') === 'foreign' ? 'selected' : '' }}>Foreign</option>
                        </select>
                        @error('travel_type') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Travel Dates --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-weekday-date-picker field="travel_date_start" :label="__('Travel Date From')" model="travelDateStart" />
                        <x-weekday-date-picker field="travel_date_end" :label="__('Travel Date To')" model="travelDateEnd" />
                    </div>

                    {{-- Employee(s) (Optional) --}}
                    <div>
                        <label for="employee_search" class="block text-sm font-bold text-gray-700 mb-1">
                            {{ __('Employee(s)') }}
                            <span class="text-gray-400 font-normal text-xs">- Optional</span>
                        </label>
                        <div class="relative" @click.outside="employeePickerOpen = false">
                            <template x-for="id in selectedCompanions" :key="id">
                                <input type="hidden" name="companions[]" :value="id">
                            </template>

                            <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-3">
                                <div class="mb-3 flex flex-wrap gap-2" x-show="selectedCompanions.length">
                                    <template x-for="employee in selectedEmployees" :key="employee.id">
                                        <span class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700">
                                            <span x-text="employee.name"></span>
                                            <button type="button" @click="removeCompanion(employee.id)" class="text-indigo-400 transition hover:text-red-600" :aria-label="`Remove ${employee.name}`">
                                                x
                                            </button>
                                        </span>
                                    </template>
                                </div>

                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M21 21l-4.35-4.35M10.75 18.5a7.75 7.75 0 100-15.5 7.75 7.75 0 000 15.5z" />
                                        </svg>
                                    </span>
                                    <input
                                        id="employee_search"
                                        type="search"
                                        x-model="employeeSearch"
                                        @focus="employeePickerOpen = true"
                                        placeholder="{{ __('Search and select employees...') }}"
                                        class="block h-10 w-full rounded-lg border-gray-300 bg-white pl-10 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                </div>

                                <div x-show="employeePickerOpen" x-transition class="mt-2 max-h-56 overflow-y-auto rounded-xl border border-gray-100 bg-white shadow-lg" x-cloak>
                                    <template x-for="employee in filteredEmployees" :key="employee.id">
                                        <button type="button" @click="addCompanion(employee.id)" class="flex w-full items-center justify-between gap-3 border-b border-gray-50 px-4 py-3 text-left transition hover:bg-indigo-50">
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-bold text-gray-800" x-text="employee.name"></span>
                                                <span class="block truncate text-[10px] font-bold uppercase tracking-widest text-gray-400" x-text="[employee.position, employee.division].filter(Boolean).join(' | ') || 'Employee'"></span>
                                            </span>
                                            <span class="text-xs font-black text-indigo-600">ADD</span>
                                        </button>
                                    </template>
                                    <p x-show="filteredEmployees.length === 0" class="px-4 py-4 text-sm font-medium italic text-gray-400">
                                        {{ __('No employees found.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        @error('companions') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        @error('companions.*') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Place(s) of Travel --}}
                    <div>
                        <label for="places_of_travel" class="block text-sm font-bold text-gray-700 mb-1">{{ __('Place(s) of Travel') }} <span class="text-red-500">*</span></label>
                        <input type="text" id="places_of_travel" name="places_of_travel" value="{{ old('places_of_travel') }}" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="e.g. Manila, Cebu City" required>
                        @error('places_of_travel') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Purpose of Travel --}}
                    <div>
                        <label for="purpose" class="block text-sm font-bold text-gray-700 mb-1">{{ __('Purpose of Travel') }} <span class="text-red-500">*</span></label>
                        <textarea id="purpose" name="purpose" rows="4" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="Describe the purpose of travel..." required>{{ old('purpose') }}</textarea>
                        @error('purpose') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Printed Travel Authority Details --}}
                    <div class="rounded-2xl border border-gray-100 bg-gray-50/50 p-5 space-y-4">
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-widest text-gray-700">{{ __('Printed Travel Authority Details') }}</h3>
                            
                        </div>

                        <div>
                            <label for="notes_remarks" class="block text-sm font-bold text-gray-700 mb-1">
                                {{ __('Notes / Remarks') }}
                                <span class="text-gray-400 font-normal text-xs">- Optional</span>
                            </label>
                            <textarea id="notes_remarks" name="notes_remarks" rows="3" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">{{ old('notes_remarks') }}</textarea>
                            @error('notes_remarks') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="driver_name" class="block text-sm font-bold text-gray-700 mb-1">
                                    {{ __('Name of Driver') }}
                                    <span class="text-gray-400 font-normal text-xs">- Optional</span>
                                </label>
                                <input type="text" id="driver_name" name="driver_name" value="{{ old('driver_name') }}" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                @error('driver_name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="vehicle_plate_no" class="block text-sm font-bold text-gray-700 mb-1">
                                    {{ __('Vehicle / Plate No.') }}
                                    <span class="text-gray-400 font-normal text-xs">- Optional</span>
                                </label>
                                <input type="text" id="vehicle_plate_no" name="vehicle_plate_no" value="{{ old('vehicle_plate_no') }}" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                @error('vehicle_plate_no') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Attach File --}}
                    <div x-data="{ fileName: '' }">
                        <label for="attachment" class="block text-sm font-bold text-gray-700 mb-1">
                            {{ __('Attach File') }}
                            <span class="text-gray-400 font-normal text-xs">- Optional</span>
                        </label>
                        <div class="relative group">
                            <input type="file"
                                name="attachment"
                                id="attachment"
                                x-ref="attachment"
                                class="hidden"
                                @change="fileName = $event.target.files[0]?.name || ''"
                                accept=".pdf,application/pdf">

                            <label for="attachment"
                                class="flex items-center justify-between w-full border-2 border-dashed border-gray-200 group-hover:border-indigo-400 rounded-xl p-4 bg-gray-50/50 cursor-pointer transition-all duration-300">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-indigo-100 rounded-lg text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <span x-show="!fileName" class="text-sm font-bold text-gray-500">{{ __('Attach PDF File') }}</span>
                                        <span x-show="fileName" x-text="fileName" class="text-sm font-bold text-indigo-700 truncate max-w-xs"></span>
                                        <p class="text-xs text-gray-400">{{ __('PDF file only (Max 5MB)') }}</p>
                                    </div>
                                </div>

                                <span x-show="!fileName" class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                    BROWSE
                                </span>
                                <button type="button" x-show="fileName" @click.prevent="fileName = ''; $refs.attachment.value = ''" class="text-red-500 hover:text-red-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </label>
                        </div>
                        @error('attachment') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Submit --}}
                    <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-100">
                        @if(request()->boolean('modal'))
    <button
        type="button"
        onclick="window.parent.postMessage('close-travel-authority-modal', '*')"
        class="text-sm font-bold text-gray-500 transition-colors hover:text-gray-700"
    >
        {{ __('Cancel') }}
    </button>
@else
    <a
        href="{{ route('travel-orders.index') }}"
        class="text-sm font-bold text-gray-500 transition-colors hover:text-gray-700"
    >
        {{ __('Cancel') }}
    </a>
@endif
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-indigo-200 hover:-translate-y-0.5">
                            {{ __('Submit Travel Authority') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function travelOrderForm(employees, selectedCompanions, oldStart, oldEnd, minDate) {
            return {
                employees,
                selectedCompanions,
                employeeSearch: '',
                employeePickerOpen: false,
                travelDateStart: oldStart || '',
                travelDateEnd: oldEnd || '',
                minDate,
                activePicker: null,
                currentMonth: null,
                init() {
                    this.currentMonth = this.toDate(this.travelDateStart || this.minDate);
                },
                get selectedEmployees() {
                    return this.selectedCompanions
                        .map((id) => this.employees.find((employee) => employee.id === id))
                        .filter(Boolean);
                },
                get filteredEmployees() {
                    const search = this.employeeSearch.trim().toLowerCase();

                    return this.employees.filter((employee) => {
                        if (this.selectedCompanions.includes(employee.id)) return false;

                        const text = [employee.name, employee.position, employee.division]
                            .filter(Boolean)
                            .join(' ')
                            .toLowerCase();

                        return !search || text.includes(search);
                    });
                },
                addCompanion(id) {
                    if (!this.selectedCompanions.includes(id)) {
                        this.selectedCompanions.push(id);
                    }

                    this.employeeSearch = '';
                    this.employeePickerOpen = false;
                },
                removeCompanion(id) {
                    this.selectedCompanions = this.selectedCompanions.filter((selectedId) => selectedId !== id);
                },
                openPicker(field) {
                    this.activePicker = field;
                    const selected = field === 'travel_date_start' ? this.travelDateStart : this.travelDateEnd;
                    this.currentMonth = this.toDate(selected || this.travelDateStart || this.minDate);
                },
                selectDate(field, value) {
                    if (this.isDisabled(value, field)) return;

                    if (field === 'travel_date_start') {
                        this.travelDateStart = value;

                        if (!this.travelDateEnd || this.toDate(this.travelDateEnd) < this.toDate(value)) {
                            this.travelDateEnd = value;
                        }
                    } else {
                        this.travelDateEnd = value;
                    }

                    this.activePicker = null;
                },
                calendarDays(field) {
                    const year = this.currentMonth.getFullYear();
                    const month = this.currentMonth.getMonth();
                    const firstDay = new Date(year, month, 1);
                    const lastDay = new Date(year, month + 1, 0);
                    const days = [];

                    for (let i = 0; i < firstDay.getDay(); i++) {
                        days.push({ key: `blank-${field}-${i}`, blank: true });
                    }

                    for (let day = 1; day <= lastDay.getDate(); day++) {
                        const date = new Date(year, month, day);
                        const value = this.toDateString(date);
                        const selected = field === 'travel_date_start'
                            ? value === this.travelDateStart
                            : value === this.travelDateEnd;

                        days.push({
                            key: `${field}-${value}`,
                            blank: false,
                            day,
                            value,
                            selected,
                            disabled: this.isDisabled(value, field),
                        });
                    }

                    return days;
                },
                isDisabled(value, field) {
                    const date = this.toDate(value);
                    const day = date.getDay();

                    if (value < this.minDate || day === 0 || day === 6) return true;

                    return field === 'travel_date_end'
                        && this.travelDateStart
                        && date < this.toDate(this.travelDateStart);
                },
                moveMonth(direction) {
                    this.currentMonth = new Date(this.currentMonth.getFullYear(), this.currentMonth.getMonth() + direction, 1);
                },
                get monthLabel() {
                    return this.currentMonth.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                },
                formatDate(value) {
                    if (!value) return '';

                    return this.toDate(value).toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                    });
                },
                toDate(value) {
                    return new Date(`${value}T00:00:00`);
                },
                toDateString(date) {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');

                    return `${year}-${month}-${day}`;
                },
            };
        }
    </script>
</x-app-layout>
