<x-app-layout>
    @if(request()->boolean('modal'))
        <style>
            html,
            body {
                margin: 0 !important;
                min-height: 100% !important;
                background: #ffffff !important;
            }

            body > .flex.h-screen.overflow-hidden > aside,
            body > .flex.h-screen.overflow-hidden header {
                display: none !important;
            }

            body > .flex.h-screen.overflow-hidden,
            body > .flex.h-screen.overflow-hidden > .flex,
            body > .flex.h-screen.overflow-hidden main {
                width: 100% !important;
                height: auto !important;
                min-height: 100vh !important;
                overflow: visible !important;
                background: #ffffff !important;
            }

            body > .flex.h-screen.overflow-hidden main {
                padding: 0 !important;
            }

            body > .flex.h-screen.overflow-hidden main > div,
            body > .flex.h-screen.overflow-hidden main > div > div {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }

            .modal-form-shell {
                width: 100% !important;
                padding: 0 !important;
                background: #ffffff !important;
            }

            .modal-form-shell > div {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .modal-form-shell .modal-page-back,
            .modal-form-shell .modal-form-title {
                display: none !important;
            }

            .modal-form-shell .modal-form-card {
                width: 100% !important;
                overflow: visible !important;
                border: 0 !important;
                border-radius: 0 !important;
                background: transparent !important;
                box-shadow: none !important;
            }

            .modal-form-shell .modal-form-card > div {
                padding: 1rem 1.1rem 1.25rem !important;
            }

            @media (min-width: 640px) {
                .modal-form-shell .modal-form-card > div {
                    padding: 1.25rem 1.5rem 1.5rem !important;
                }
            }


            .modal-form-shell form {
                max-width: 100% !important;
            }

            .modal-form-shell input,
            .modal-form-shell textarea,
            .modal-form-shell button,
            .modal-form-shell label {
                font-size: 0.875rem;
            }

            @media (min-width: 768px) {
                .modal-form-shell .modal-form-card > div {
                    padding: 1.25rem 1.75rem 1.5rem !important;
                }
            }

            /*
             * Fixed modal layout:
             * - the iframe/page itself does not scroll
             * - only .leave-form-body scrolls
             * - .leave-form-footer stays fixed at the bottom
             */
            html,
            body {
                height: 100% !important;
                overflow: hidden !important;
            }

            body > .flex.h-screen.overflow-hidden,
            body > .flex.h-screen.overflow-hidden > .flex,
            body > .flex.h-screen.overflow-hidden main,
            body > .flex.h-screen.overflow-hidden main > div,
            body > .flex.h-screen.overflow-hidden main > div > div,
            .modal-form-shell,
            .modal-form-shell > div,
            .modal-form-shell .modal-form-card,
            .modal-form-shell .modal-form-card > div {
                height: 100% !important;
                min-height: 0 !important;
            }

            body > .flex.h-screen.overflow-hidden,
            body > .flex.h-screen.overflow-hidden > .flex,
            body > .flex.h-screen.overflow-hidden main {
                overflow: hidden !important;
            }

            .modal-form-shell {
                height: 100vh !important;
                height: 100dvh !important;
                overflow: hidden !important;
            }

            .modal-form-shell .modal-form-card {
                display: flex !important;
                flex-direction: column !important;
                overflow: hidden !important;
            }

            .modal-form-shell .modal-form-card > div {
                display: flex !important;
                flex: 1 1 auto !important;
                flex-direction: column !important;
                overflow: hidden !important;
                padding: 0 !important;
            }

            .leave-modal-form {
                display: flex;
                flex: 1 1 auto;
                flex-direction: column;
                width: 100%;
                height: 100%;
                min-height: 0;
                max-width: 100% !important;
                overflow: hidden;
            }

            .leave-form-body {
                flex: 1 1 auto;
                min-height: 0;
                overflow-y: auto;
                overscroll-behavior: contain;
                padding: 1.25rem 1.75rem;
                -webkit-overflow-scrolling: touch;
            }

            .leave-form-footer {
                position: relative;
                z-index: 20;
                flex: 0 0 auto;
            }

            @media (max-width: 639px) {
                .leave-form-body {
                    padding: 1rem 1.1rem;
                }

                .leave-form-footer {
                    padding-left: 1rem;
                    padding-right: 1rem;
                }
            }

            body::-webkit-scrollbar {
                width: 8px;
            }

            body::-webkit-scrollbar-track {
                background: #f1f5f9;
            }

            body::-webkit-scrollbar-thumb {
                border: 2px solid #f1f5f9;
                border-radius: 999px;
                background: #cbd5e1;
            }
        </style>
    @endif
    <x-slot name="title">{{ __('File New Leave Request') }}</x-slot>

    <div class="modal-form-shell">
        <div class="mx-auto w-full max-w-5xl">
            <div class="bg-white overflow-hidden shadow-2xl sm:rounded-2xl border border-gray-100 ring-1 ring-black/5 modal-form-card">
                <div>
                    <form method="POST" action="{{ route('leaves.store', request()->boolean('modal') ? ['modal' => 1] : []) }}" class="leave-modal-form" enctype="multipart/form-data" x-data="leaveDateForm('{{ old('start_date') }}', '{{ old('end_date') }}', '{{ now()->toDateString() }}', {{ Js::from($holidayDates) }})" @submit="submitting = true">
                        @csrf
                        @if(request()->boolean('modal'))
                            <input type="hidden" name="modal" value="1">
                        @endif

                        {{-- Only this section scrolls inside the modal --}}
                        <div class="leave-form-body space-y-5">

                        <!-- Leave Type -->
                        @php
                            $leaveGroups = [
                                'Regular Leave' => ['Vacation Leave', 'Mandatory/Force Leave', 'Sick Leave'],
                                'Parental / Family Leave' => ['Maternity Leave', 'Paternity Leave', 'Solo Parent Leave', 'Adoption Leave'],
                                'Special Privilege / Study Leave' => ['Special Privilege Leave', 'Study Leave'],
                                'Women\'s / Gender-Related Leave' => ['VAWC Leave', 'Special Leave Benefits for Women'],
                                'Emergency / Rehabilitation Leave' => ['Rehabilitation Leave', 'Special Emergency (Calamity) Leave'],
                            ];
                            $groupedLeaveNames = collect($leaveGroups)->flatten()->all();
                            $customLeaveTypes = $leaveTypes->reject(fn($type) => in_array($type->name, $groupedLeaveNames, true));
                        @endphp
                        <div x-data="{
                            open: false,
                            query: '',
                            selectedId: '{{ old('leave_type_id', '') }}',
                            selectedName: '',
                            types: {{ Js::from($leaveTypePicker) }},
                            get info() { return this.types[this.selectedId] || null; },
                            matchesType(id) {
                                const info = this.types[id];
                                if (!info) return false;
                                const term = this.query.trim().toLowerCase();
                                if (!term) return true;
                                return [info.name, info.description, info.legal_basis].filter(Boolean).join(' ').toLowerCase().includes(term);
                            },
                            groupHasMatches(ids) { return ids.some((id) => this.matchesType(String(id))); },
                            applySelectedPolicy() {
                                if (this.info && typeof setDatePolicy === 'function') {
                                    setDatePolicy(this.info.date_policy, this.info.date_help);
                                }
                            },
                            pick(id) {
                                this.selectedId = String(id);
                                this.selectedName = this.types[this.selectedId]?.name || '';
                                this.query = this.selectedName;
                                this.open = false;
                                this.applySelectedPolicy();
                            },
                            clearSelectionIfSearching() {
                                if (this.query !== this.selectedName) {
                                    this.selectedId = '';
                                    this.selectedName = '';
                                }
                            },
                            init() {
                                if (this.selectedId && this.types[this.selectedId]) {
                                    this.selectedName = this.types[this.selectedId].name;
                                    this.query = this.selectedName;
                                }
                                this.$nextTick(() => this.applySelectedPolicy());
                            }
                        }" @click.outside="open = false" class="relative">
                            <x-input-label for="leave_type_id" :value="__('Type of Leave')" class="mb-1.5 text-[11px] font-bold uppercase tracking-[0.08em] text-slate-600" />
                            <input type="hidden" name="leave_type_id" :value="selectedId" required>

                            {{-- Searchable Leave Type field --}}
<div class="relative mt-1">
    <input
        type="search"
        x-model="query"
        @focus="open = true; $event.target.select()"
        @input="open = true; clearSelectionIfSearching()"
        @keydown.escape.prevent="open = false"
        placeholder="{{ __('Select or search leave type') }}"
        class="block h-11 w-full appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-3.5 pr-12 text-sm font-semibold text-slate-800 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
        :class="open ? 'border-indigo-500 ring-2 ring-indigo-100' : ''"
    >

    <span
        class="pointer-events-none absolute inset-y-0 right-0 flex w-11 items-center justify-center"
    >
        <svg
            class="h-4 w-4 text-slate-400 transition-transform duration-200"
            :class="open ? 'rotate-180 text-indigo-600' : ''"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2.25"
                d="m7 10 5 5 5-5"
            />
        </svg>
    </span>
</div>
                            <x-input-error class="mt-2" :messages="$errors->get('leave_type_id')" />

                            <!-- Dropdown List -->
                            <div x-show="open" x-transition.opacity.duration.150ms x-cloak class="absolute z-50 mt-1 w-full bg-white rounded-xl shadow-lg border border-gray-200 max-h-64 overflow-y-auto">
                                @foreach($leaveGroups as $group => $names)
                                    @php $groupTypes = $leaveTypes->filter(fn($t) => in_array($t->name, $names)); @endphp
                                    @if($groupTypes->isNotEmpty())
                                        {{-- Section Header --}}
                                        <div x-show="groupHasMatches({{ Js::from($groupTypes->pluck('id')->map(fn($id) => (string) $id)->values()) }})" class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ $group }}</span>
                                        </div>
                                        {{-- Items --}}
                                        @foreach($groupTypes as $type)
                                            @php $balance = $credits[$type->id]->balance ?? 0; @endphp
                                            <button type="button"
                                                x-show="matchesType('{{ $type->id }}')"
                                                @click="pick('{{ $type->id }}')"
                                                class="w-full text-left px-4 py-3 border-b border-gray-100 hover:bg-indigo-50 transition-colors"
                                                :class="selectedId == '{{ $type->id }}' && 'bg-indigo-50'">
                                                <div class="flex items-center justify-between">
                                                    <div class="min-w-0">
                                                        <div class="text-sm font-bold text-gray-800" :class="selectedId == '{{ $type->id }}' && 'text-indigo-700'">
                                                            {{ $type->name }}
                                                        </div>
                                                        @if($type->legal_basis)
                                                            <div class="text-[11px] text-gray-400 italic mt-0.5">{{ $type->legal_basis }}</div>
                                                        @endif
                                                    </div>
                                                    <span class="flex-shrink-0 ml-3 text-[10px] font-bold px-2 py-0.5 rounded-full {{ $balance > 0 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400' }}">
                                                        {{ number_format($balance, 1) }} days
                                                    </span>
                                                </div>
                                            </button>
                                        @endforeach
                                    @endif
                                @endforeach
                                @if($customLeaveTypes->isNotEmpty())
                                    <div x-show="groupHasMatches({{ Js::from($customLeaveTypes->pluck('id')->map(fn($id) => (string) $id)->values()) }})" class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ __('Other Leave Types') }}</span>
                                    </div>
                                    @foreach($customLeaveTypes as $type)
                                        @php $balance = $credits[$type->id]->balance ?? 0; @endphp
                                        <button type="button"
                                            x-show="matchesType('{{ $type->id }}')"
                                            @click="pick('{{ $type->id }}')"
                                            class="w-full text-left px-4 py-3 border-b border-gray-100 hover:bg-indigo-50 transition-colors"
                                            :class="selectedId == '{{ $type->id }}' && 'bg-indigo-50'">
                                            <div class="flex items-center justify-between">
                                                <div class="min-w-0">
                                                    <div class="text-sm font-bold text-gray-800" :class="selectedId == '{{ $type->id }}' && 'text-indigo-700'">
                                                        {{ $type->name }}
                                                    </div>
                                                    @if($type->legal_basis)
                                                        <div class="text-[11px] text-gray-400 italic mt-0.5">{{ $type->legal_basis }}</div>
                                                    @endif
                                                </div>
                                                <span class="flex-shrink-0 ml-3 text-[10px] font-bold px-2 py-0.5 rounded-full {{ $balance > 0 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400' }}">
                                                    {{ number_format($balance, 1) }} days
                                                </span>
                                            </div>
                                        </button>
                                    @endforeach
                                @endif
                                <p x-show="!Object.keys(types).some((id) => matchesType(id))" class="px-4 py-5 text-center text-xs font-bold text-gray-400">
                                    {{ __('No leave type found.') }}
                                </p>
                            </div>

                        </div>

                        <!-- Date Range -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-weekday-date-picker field="start_date" :label="__('Start Date')" model="startDate" />
                            <x-weekday-date-picker field="end_date" :label="__('End Date')" model="endDate" />
                        </div>
                        <div class="rounded-xl border border-blue-100 bg-blue-50/70 px-4 py-3">
                            <p class="text-[11px] font-bold uppercase tracking-[0.08em] text-blue-700">{{ __('Leave Days') }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">
                                <span x-text="leaveDays"></span>
                                <span x-text="leaveDays === 1 ? 'weekday counted' : 'weekdays counted'"></span>
                            </p>
                        </div>

                        <!-- Pay Option -->
                        <div>
                            <span class="block text-[11px] font-bold uppercase tracking-[0.08em] text-slate-600">{{ __('Pay Option') }}</span>
                            <p class="mt-1 text-xs font-medium text-slate-500">{{ __('If available credits are insufficient, the system will automatically save this request as Without Pay.') }}</p>
                            @php($payOption = old('is_paid', '1'))
                            <div class="mt-2 grid grid-cols-2 gap-3">
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50/40">
                                    <input type="radio" name="is_paid" value="1" class="border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ (string) $payOption === '1' ? 'checked' : '' }}>
                                    {{ __('With Pay') }}
                                </label>
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50/40">
                                    <input type="radio" name="is_paid" value="0" class="border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ (string) $payOption === '0' ? 'checked' : '' }}>
                                    {{ __('Without Pay') }}
                                </label>
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('is_paid')" />
                        </div>

                        <!-- Reason -->
                        <div>
                            <x-input-label for="reason" :value="__('Reason for Leave')" class="mb-1.5 text-[11px] font-bold uppercase tracking-[0.08em] text-slate-600" />
                            <textarea id="reason" name="reason" class="mt-1 block w-full rounded-xl border-slate-200 bg-white px-3.5 py-3 text-sm font-medium text-slate-800 shadow-sm focus:border-blue-500 focus:ring-blue-100" rows="4" required placeholder="{{ __('Please provide a detailed reason for your leave request...') }}">{{ old('reason') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('reason')" />
                        </div>
                        <div x-data="{ fileName: '' }">
                            <x-input-label for="attachment" :value="__('Supporting Documents (Optional)')" class="mb-1.5 text-[11px] font-bold uppercase tracking-[0.08em] text-slate-600" />
                            
                            <div class="relative group">
                                <input type="file" 
                                    name="attachment" 
                                    id="attachment" 
                                    x-ref="attachment"
                                    class="hidden" 
                                    @change="fileName = $event.target.files[0]?.name || ''"
                                    accept=".pdf,application/pdf">
                                
                                <label for="attachment" 
                                    class="flex w-full cursor-pointer items-center justify-between rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/70 p-3.5 transition-all duration-200 group-hover:border-blue-400 group-hover:bg-blue-50/40">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-indigo-100 rounded-lg text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <span x-show="!fileName" class="text-sm font-bold text-gray-500 uppercase tracking-tight">{{ __('Attach PDF File') }}</span>
                                            <span x-show="fileName" x-text="fileName" class="text-sm font-bold text-indigo-700 truncate max-w-xs"></span>
                                            <p class="text-[10px] text-gray-400">{{ __('PDF file only (Max 5MB)') }}</p>
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
                            <x-input-error class="mt-2" :messages="$errors->get('attachment')" />
                        </div>

                        </div>

                        {{-- Fixed footer: remains visible while the form body scrolls --}}
                        <div class="leave-form-footer flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 bg-white px-5 py-3 shadow-[0_-8px_24px_rgba(15,23,42,0.06)]">
                        @if(request()->boolean('modal'))
    <button
        type="button"
        onclick="window.parent.postMessage('close-create-request-modal', '*')"
        class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
    >
        {{ __('Cancel') }}
    </button>
@else
    <a
        href="{{ route('leaves.index') }}"
        class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
    >
        {{ __('Cancel') }}
    </a>
@endif
                            <x-primary-button
                                class="h-10 rounded-xl border-none bg-blue-800 px-6 text-sm font-semibold shadow-lg shadow-blue-900/15 transition hover:bg-blue-900 active:bg-blue-950 disabled:cursor-not-allowed disabled:opacity-70"
                                x-bind:disabled="submitting"
                            >
                                <span x-show="!submitting">{{ __('Submit Request') }}</span>
                                <span x-show="submitting" x-cloak>{{ __('Submitting...') }}</span>
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        function leaveDateForm(initialStart, initialEnd, minDate, holidayDates = []) {
            return {
                startDate: initialStart || '',
                endDate: initialEnd || '',
                minDate,
                holidayDates: new Set(holidayDates),
                datePolicy: 'today_or_future',
                dateHelp: '',
                submitting: false,
                activePicker: null,
                currentMonth: new Date((initialStart || minDate) + 'T00:00:00'),
                setDatePolicy(policy, help) {
                    this.datePolicy = policy || 'today_or_future';
                    this.dateHelp = help || '';
                    if (this.startDate && this.isDisabled(this.startDate, 'start_date')) this.startDate = '';
                    if (this.endDate && this.isDisabled(this.endDate, 'end_date')) this.endDate = '';
                },
                openPicker(field) {
                    this.activePicker = field;
                    const seed = field === 'end_date'
                        ? (this.endDate || this.startDate || this.minDate)
                        : (this.startDate || this.minDate);
                    this.currentMonth = new Date(seed + 'T00:00:00');
                    this.currentMonth.setDate(1);
                },
                get monthLabel() {
                    return this.currentMonth.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                },
                moveMonth(amount) {
                    this.currentMonth = new Date(this.currentMonth.getFullYear(), this.currentMonth.getMonth() + amount, 1);
                },
                toIso(date) {
                    return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
                },
                formatDate(value) {
                    if (!value) return '';
                    const date = new Date(value + 'T00:00:00');
                    return date.toLocaleDateString('en-GB');
                },
                isDisabled(value, field) {
                    const date = new Date(value + 'T00:00:00');
                    const today = new Date(this.minDate + 'T00:00:00');
                    const day = date.getDay();
                    if (this.holidayDates.has(value)) return true;
                    if (day === 0 || day === 6) return true;
                    if (this.datePolicy === 'past_or_today' && date > today) return true;
                    if (this.datePolicy === 'today_or_future' && date < today) return true;
                    if (field === 'end_date' && this.startDate) {
                        return date < new Date(this.startDate + 'T00:00:00');
                    }
                    if (field === 'start_date' && this.endDate) {
                        return date > new Date(this.endDate + 'T00:00:00');
                    }
                    return false;
                },
                calendarDays(field) {
                    const year = this.currentMonth.getFullYear();
                    const month = this.currentMonth.getMonth();
                    const first = new Date(year, month, 1);
                    const last = new Date(year, month + 1, 0);
                    const days = [];

                    for (let i = 0; i < first.getDay(); i++) {
                        days.push({ key: 'blank-' + i, blank: true });
                    }

                    for (let day = 1; day <= last.getDate(); day++) {
                        const date = new Date(year, month, day);
                        const value = this.toIso(date);
                        days.push({
                            key: value,
                            blank: false,
                            day,
                            value,
                            disabled: this.isDisabled(value, field),
                            selected: field === 'start_date' ? value === this.startDate : value === this.endDate,
                        });
                    }

                    return days;
                },
                selectDate(field, value) {
                    if (this.isDisabled(value, field)) return;
                    if (field === 'start_date') {
                        this.startDate = value;
                        if (this.endDate && new Date(this.endDate + 'T00:00:00') < new Date(value + 'T00:00:00')) {
                            this.endDate = '';
                        }
                    } else {
                        this.endDate = value;
                    }
                    this.activePicker = null;
                },
                get leaveDays() {
                    if (!this.startDate || !this.endDate) return 0;
                    const start = new Date(this.startDate + 'T00:00:00');
                    const end = new Date(this.endDate + 'T00:00:00');
                    if (start > end) return 0;

                    let days = 0;
                    for (let date = new Date(start); date <= end; date.setDate(date.getDate() + 1)) {
                        const value = this.toIso(date);
                        if (![0, 6].includes(date.getDay()) && !this.holidayDates.has(value)) days++;
                    }
                    return days;
                },
            };
        }
    </script>
</x-app-layout>`
