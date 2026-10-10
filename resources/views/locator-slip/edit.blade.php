<x-app-layout>
    <x-slot name="title">{{ __('Edit Locator Slip') }}</x-slot>

    <div class="p-4 sm:p-6 lg:p-8">
        <div class="rounded-2xl border border-gray-100 bg-white/80 p-6 shadow-lg backdrop-blur-xl">
            <h2 class="mb-6 text-2xl font-bold text-gray-800">Edit Locator Slip</h2>

            <form action="{{ route('locator-slips.update', $locatorSlip) }}" method="POST" x-data="locatorSlipDateForm('{{ old('date_covered', $locatorSlip->date_covered) }}', '{{ now()->toDateString() }}')">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <x-weekday-date-picker field="date_covered" :label="__('Date Covered')" model="dateCovered" />
                    </div>

                    <div>
                        <span class="block text-sm font-medium text-gray-700">Type</span>
                        <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:border-indigo-300">
                                <input type="radio" name="type" value="Official Business" class="border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('type', $locatorSlip->type) === 'Official Business' ? 'checked' : '' }} required>
                                {{ __('Official Business') }}
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:border-indigo-300">
                                <input type="radio" name="type" value="Pass Slip" class="border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('type', $locatorSlip->type === 'Personal' ? 'Pass Slip' : $locatorSlip->type) === 'Pass Slip' ? 'checked' : '' }} required>
                                {{ __('Pass Slip') }}
                            </label>
                        </div>
                        @error('type') <span class="mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="destination" class="block text-sm font-medium text-gray-700">Destination</label>
                        <input type="text" name="destination" id="destination" value="{{ old('destination', $locatorSlip->destination) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        @error('destination') <span class="mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-4">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-gray-400">Time Logging</p>
                        <p class="mt-2 text-sm text-gray-600">OUT and IN times are recorded automatically through the QR scan after approval.</p>
                    </div>

                    <div class="md:col-span-2">
                        <label for="purpose" class="block text-sm font-medium text-gray-700">Purpose</label>
                        <textarea name="purpose" id="purpose" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ old('purpose', $locatorSlip->purpose) }}</textarea>
                        @error('purpose') <span class="mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ route('locator-slips.show', $locatorSlip) }}" class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 transition hover:bg-gray-50">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="inline-flex items-center rounded-xl border border-transparent bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25">
                        Update Locator Slip
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function locatorSlipDateForm(initialDate, minDate) {
            return {
                dateCovered: initialDate || '',
                minDate,
                activePicker: null,
                currentMonth: new Date((initialDate || minDate) + 'T00:00:00'),
                openPicker() {
                    const seed = this.dateCovered || this.minDate;
                    this.currentMonth = new Date(seed + 'T00:00:00');
                    this.currentMonth.setDate(1);
                    this.activePicker = 'date_covered';
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
                    return new Date(value + 'T00:00:00').toLocaleDateString('en-GB');
                },
                isDisabled(value) {
                    const date = new Date(value + 'T00:00:00');
                    const today = new Date(this.minDate + 'T00:00:00');
                    return date < today || date.getDay() === 0 || date.getDay() === 6;
                },
                calendarDays() {
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
                            disabled: this.isDisabled(value),
                            selected: value === this.dateCovered,
                        });
                    }

                    return days;
                },
                selectDate(field, value) {
                    if (this.isDisabled(value)) return;
                    this.dateCovered = value;
                    this.activePicker = null;
                },
            };
        }
    </script>
</x-app-layout>
