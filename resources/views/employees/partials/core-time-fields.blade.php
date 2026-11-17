@php
    $coreTimeEmployee = $employee ?? null;
    $savedCoreSchedule = old('core_time_schedule', $coreTimeEmployee?->core_time_schedule ?? []);
    $coreWeekdays = [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
    ];
@endphp

<div class="pt-1 md:col-span-2">
    <div class="mb-3 flex items-start justify-between gap-4 border-b border-gray-100 pb-2">
        <div>
            <h3 class="text-base font-bold text-gray-900">{{ __('Approved Core Time') }}</h3>
            <p class="mt-0.5 text-xs text-gray-500">{{ __('Set the effective period and official weekday schedule that will appear in the employee DTR.') }}</p>
        </div>
        <button
            type="button"
            x-show="!coreTimeEditing"
            @click="coreTimeEditing = true"
            class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-blue-700 bg-white px-3 py-2 text-[10px] font-black uppercase tracking-wider text-blue-800 transition hover:bg-blue-50"
        >
            <i class="fa-solid fa-pen"></i>
            {{ __('Edit Core Time') }}
        </button>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <x-input-label for="core_time_start_date" :value="__('Effective From')" />
            <x-text-input id="core_time_start_date" name="core_time_start_date" type="date" class="mt-1 block w-full" :value="old('core_time_start_date', optional($coreTimeEmployee?->core_time_start_date)->format('Y-m-d'))" x-bind:disabled="!coreTimeEditing" x-bind:class="{ 'cursor-not-allowed bg-gray-50 text-gray-500': !coreTimeEditing }" />
            <x-input-error class="mt-2" :messages="$errors->get('core_time_start_date')" />
        </div>
        <div>
            <x-input-label for="core_time_end_date" :value="__('Effective To')" />
            <x-text-input id="core_time_end_date" name="core_time_end_date" type="date" class="mt-1 block w-full" :value="old('core_time_end_date', optional($coreTimeEmployee?->core_time_end_date)->format('Y-m-d'))" x-bind:disabled="!coreTimeEditing" x-bind:class="{ 'cursor-not-allowed bg-gray-50 text-gray-500': !coreTimeEditing }" />
            <x-input-error class="mt-2" :messages="$errors->get('core_time_end_date')" />
        </div>
    </div>

    <div class="mt-4 flex justify-end">
        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700" :class="coreTimeEditing ? 'cursor-pointer' : 'cursor-not-allowed text-gray-400'">
            <input
                id="apply-monday-core-time"
                type="checkbox"
                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                x-bind:disabled="!coreTimeEditing"
            >
            <span>{{ __('Apply Monday schedule to all weekdays') }}</span>
        </label>
    </div>

    <div class="mt-2 overflow-x-auto rounded-lg border border-gray-200">
        <table class="w-full min-w-[720px] border-collapse text-sm">
            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="border-b border-gray-200 px-3 py-3 text-left">Day</th>
                    <th class="border-b border-gray-200 px-3 py-3 text-center">AM In</th>
                    <th class="border-b border-gray-200 px-3 py-3 text-center">AM Out</th>
                    <th class="border-b border-gray-200 px-3 py-3 text-center">PM In</th>
                    <th class="border-b border-gray-200 px-3 py-3 text-center">PM Out</th>
                </tr>
            </thead>
            <tbody>
                @foreach($coreWeekdays as $dayKey => $dayLabel)
                    <tr>
                        <th class="border-b border-gray-100 px-3 py-2 text-left font-medium text-gray-700">{{ $dayLabel }}</th>
                        @foreach(['am_start', 'am_end', 'pm_start', 'pm_end'] as $slot)
                            <td class="border-b border-gray-100 px-2 py-2">
                                <input
                                    type="time"
                                    name="core_time_schedule[{{ $dayKey }}][{{ $slot }}]"
                                    value="{{ data_get($savedCoreSchedule, $dayKey.'.'.$slot) }}"
                                    data-core-day="{{ $dayKey }}"
                                    data-core-slot="{{ $slot }}"
                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    x-bind:disabled="!coreTimeEditing"
                                    x-bind:class="{ 'cursor-not-allowed bg-gray-50 text-gray-500': !coreTimeEditing }"
                                >
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <x-input-error class="mt-2" :messages="$errors->get('core_time_schedule')" />
</div>

@once
    <script>
        (() => {
            const bindCoreTimeCopy = () => {
                const applyToAll = document.getElementById('apply-monday-core-time');

                if (!applyToAll || applyToAll.dataset.bound === 'true') {
                    return;
                }

                applyToAll.dataset.bound = 'true';
                applyToAll.addEventListener('change', () => {
                    if (!applyToAll.checked) {
                        return;
                    }

                    const mondayValues = {};
                    document.querySelectorAll('[data-core-day="monday"]').forEach((input) => {
                        mondayValues[input.dataset.coreSlot] = input.value;
                    });

                    document.querySelectorAll('[data-core-day]:not([data-core-day="monday"])').forEach((input) => {
                        input.value = mondayValues[input.dataset.coreSlot] ?? '';
                    });
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bindCoreTimeCopy, { once: true });
            } else {
                bindCoreTimeCopy();
            }
        })();
    </script>
@endonce
