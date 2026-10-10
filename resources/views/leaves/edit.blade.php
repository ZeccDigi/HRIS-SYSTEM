<x-app-layout>
    <x-slot name="title">{{ __('Edit Leave Request') }}</x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-2xl sm:rounded-2xl border border-gray-100 ring-1 ring-black/5">
                <div class="p-8">
                    <form method="POST" action="{{ route('leaves.update', $leaf) }}" class="space-y-8" x-data="leaveDateForm('{{ old('start_date', $leaf->start_date?->format('Y-m-d')) }}', '{{ old('end_date', $leaf->end_date?->format('Y-m-d')) }}', '{{ now()->toDateString() }}', {{ Js::from($holidayDates) }})">
                        @csrf
                        @method('PUT')

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
                            selectedId: '{{ old('leave_type_id', $leaf->leave_type_id) }}',
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
                            <x-input-label for="leave_type_id" :value="__('Type of Leave')" class="text-[10px] font-black uppercase text-indigo-600 tracking-[0.2em] mb-2" />
                            <input type="hidden" name="leave_type_id" :value="selectedId" required>

                            <!-- Searchable Field -->
                            <div class="relative">
                                <input type="search"
                                    x-model="query"
                                    @focus="open = true; $event.target.select()"
                                    @input="open = true; clearSelectionIfSearching()"
                                    @keydown.escape.prevent="open = false"
                                    placeholder="{{ __('Select or search leave type') }}"
                                    class="mt-1 w-full rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-3 pr-11 text-sm font-bold text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    :class="open && 'border-indigo-500 ring-2 ring-indigo-500'">
                                <svg class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('leave_type_id')" />

                            <!-- Dropdown List -->
                            <div x-show="open" x-transition.opacity.duration.150ms x-cloak class="absolute z-50 mt-1 w-full bg-white rounded-xl shadow-lg border border-gray-200 max-h-[400px] overflow-y-auto">
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <x-weekday-date-picker field="start_date" :label="__('Start Date')" model="startDate" />
                            <x-weekday-date-picker field="end_date" :label="__('End Date')" model="endDate" />
                        </div>
                        <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 px-4 py-3">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-600">{{ __('Leave Days') }}</p>
                            <p class="mt-1 text-sm font-bold text-gray-700">
                                <span x-text="leaveDays"></span>
                                <span x-text="leaveDays === 1 ? 'weekday counted' : 'weekdays counted'"></span>
                            </p>
                        </div>

                        <!-- Pay Option -->
                        <div>
                            <span class="block text-sm font-medium text-gray-700">{{ __('Pay Option') }}</span>
                            <p class="mt-1 text-xs font-medium text-gray-500">{{ __('If available credits are insufficient, the system will automatically save this request as Without Pay.') }}</p>
                            @php($payOption = old('is_paid', $leaf->is_paid === false ? '0' : '1'))
                            <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:border-indigo-300">
                                    <input type="radio" name="is_paid" value="1" class="border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ (string) $payOption === '1' ? 'checked' : '' }}>
                                    {{ __('With Pay') }}
                                </label>
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:border-indigo-300">
                                    <input type="radio" name="is_paid" value="0" class="border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ (string) $payOption === '0' ? 'checked' : '' }}>
                                    {{ __('Without Pay') }}
                                </label>
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('is_paid')" />
                        </div>

                        <!-- Reason -->
                        <div>
                            <x-input-label for="reason" :value="__('Reason for Leave')" class="text-[10px] font-black uppercase text-indigo-600 tracking-[0.2em] mb-2" />
                            <textarea id="reason" name="reason" class="mt-1 block w-full border-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm text-sm font-bold py-3 px-4 bg-gray-50/50" rows="5" required>{{ old('reason', $leaf->reason) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('reason')" />
                        </div>

                        <div class="flex items-center justify-end gap-6 pt-6 border-t border-gray-50">
                            <a href="{{ route('leaves.index') }}" class="text-sm font-black text-gray-400 hover:text-indigo-600 transition-colors duration-300 uppercase tracking-widest">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button class="bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-900 border-none shadow-xl shadow-indigo-200/50 px-8 py-3 rounded-xl transition-all duration-300 transform hover:-translate-y-1">
                                {{ __('Update Request') }}
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
</x-app-layout>
