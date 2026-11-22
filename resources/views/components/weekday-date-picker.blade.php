@props(['field', 'label', 'model', 'plainLabel' => false])

<div class="relative" @click.outside="if (activePicker === '{{ $field }}') activePicker = null">
    @if($plainLabel)
        <label for="{{ $field }}" class="mb-1 block text-sm font-bold text-gray-700">{{ $label }}</label>
    @else
        <x-input-label :for="$field" :value="$label" class="text-[10px] font-black uppercase text-indigo-600 tracking-[0.2em] mb-2" />
    @endif
    <input type="hidden" id="{{ $field }}" name="{{ $field }}" x-bind:value="{{ $model }}" required>

    <button
        type="button"
        @click.stop="openPicker('{{ $field }}')"
        class="mt-1 flex {{ $plainLabel ? 'h-11' : '' }} w-full items-center justify-between rounded-xl border border-gray-100 bg-gray-50/50 px-4 py-3 text-left text-sm font-bold text-gray-900 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-indigo-500"
        :class="activePicker === '{{ $field }}' ? 'border-indigo-500 ring-2 ring-indigo-500' : 'hover:border-indigo-200'"
    >
        <span x-text="formatDate({{ $model }}) || 'Select date'" :class="{{ $model }} ? 'text-gray-900' : 'text-gray-400'"></span>
        <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
    </button>

    <div x-show="activePicker === '{{ $field }}'" x-transition x-cloak @click.stop class="absolute left-0 z-50 mt-2 rounded-2xl border border-gray-100 bg-white p-4 shadow-2xl" style="width: 19rem; max-width: calc(100vw - 3rem);">
        <div class="mb-3 flex items-center justify-between">
            <button type="button" @click.stop="moveMonth(-1)" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-indigo-50 hover:text-indigo-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <div class="whitespace-nowrap text-xs font-black uppercase tracking-wide text-gray-700" x-text="monthLabel"></div>
            <button type="button" @click.stop="moveMonth(1)" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-indigo-50 hover:text-indigo-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <div class="mb-2 grid grid-cols-7 gap-1 text-center text-[10px] font-bold uppercase tracking-normal text-gray-400">
            <span>Sun</span>
            <span>Mon</span>
            <span>Tue</span>
            <span>Wed</span>
            <span>Thu</span>
            <span>Fri</span>
            <span>Sat</span>
        </div>

        <div class="grid grid-cols-7 gap-1">
            <template x-for="day in calendarDays('{{ $field }}')" :key="day.key">
                <div>
                    <template x-if="day.blank">
                        <span class="block h-9"></span>
                    </template>
                    <template x-if="!day.blank">
                        <button
                            type="button"
                            x-text="day.day"
                            :disabled="day.disabled"
                            @click.stop="selectDate('{{ $field }}', day.value)"
                            class="flex h-9 w-full items-center justify-center rounded-lg text-xs font-black transition"
                            :class="day.disabled
                                ? 'cursor-not-allowed bg-gray-50 text-gray-300'
                                : (day.selected ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-700 hover:bg-indigo-50 hover:text-indigo-700')"
                        ></button>
                    </template>
                </div>
            </template>
        </div>

    </div>

    <x-input-error class="mt-2" :messages="$errors->get($field)" />
</div>
