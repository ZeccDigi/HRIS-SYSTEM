@php
    $employee = auth()->user()?->employee;

    $employeeName = trim(collect([
        $employee?->firstname,
        $employee?->middlename,
        $employee?->lastname,
        $employee?->suffix,
    ])->filter()->implode(' '));

    $employeePosition = $employee?->position ?: 'N/A';
    $latestLocatorRemark = $latestRejectedRemark ?? null;
@endphp

<div
    x-data="{
        open: {{ $errors->any() ? 'true' : 'false' }},
        type: @js(old('type', 'Pass Slip')),
        dateCovered: @js(old('date_covered', now()->toDateString())),
        minDate: @js(now()->toDateString()),
        activePicker: null,
        currentMonth: new Date((@js(old('date_covered', now()->toDateString()))) + 'T00:00:00'),

        close() {
            this.open = false;

            document.documentElement.classList.remove('overflow-hidden');
            document.body.classList.remove('overflow-hidden');
        },

        show() {
            this.open = true;

            document.documentElement.classList.add('overflow-hidden');
            document.body.classList.add('overflow-hidden');

            this.$nextTick(() => {
                document.getElementById('destination')?.focus();
            });
        },

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
        }
    }"
    x-init="
        if (open) {
            document.documentElement.classList.add('overflow-hidden');
            document.body.classList.add('overflow-hidden');
        }
    "
    x-on:open-create-locator-slip-modal.window="show()"
    x-on:keydown.escape.window="if (open) close()"
>
    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            class="locator-modal-overlay"
            style="display: none;"
            role="dialog"
            aria-modal="true"
            aria-labelledby="locator-slip-title"
        >
            {{-- Full-screen backdrop --}}
            <div
                class="locator-modal-backdrop"
                @click="close()"
            ></div>

            {{-- Main modal box --}}
            <div
                x-show="open"
                x-transition:enter="transition duration-200 ease-out"
                x-transition:enter-start="scale-95 opacity-0"
                x-transition:enter-end="scale-100 opacity-100"
                x-transition:leave="transition duration-150 ease-in"
                x-transition:leave-start="scale-100 opacity-100"
                x-transition:leave-end="scale-95 opacity-0"
                @click.stop
                class="locator-modal-panel"
            >
                {{-- Fixed header --}}
                <div class="flex shrink-0 items-center justify-between bg-blue-900 px-6 py-4 text-white">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-blue-100">
                            Request Form
                        </p>

                        <h2
                            id="locator-slip-title"
                            class="mt-1 text-2xl font-black tracking-tight"
                        >
                            Locator Slip
                        </h2>
                    </div>

                    <button
                        type="button"
                        @click="close()"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full text-white transition hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-white/70"
                        aria-label="Close locator slip form"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <form
                    id="create-locator-slip-form"
                    method="POST"
                    action="{{ route('locator-slips.store') }}"
                    class="flex min-h-0 flex-1 flex-col overflow-hidden bg-white"
                >
                    @csrf

                    <input
                        type="hidden"
                        name="type"
                        :value="type"
                    >

                    {{-- Scrollable form body --}}
                    <div class="locator-slip-scroll min-h-0 flex-1 overflow-y-auto overscroll-contain px-6 py-5 sm:px-8">
                        @if ($errors->any())
                            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                Please check the form and correct the errors below.
                            </div>
                        @endif

                        {{-- Request type --}}
                        <div class="mb-6 grid grid-cols-1 gap-2 rounded-xl border border-slate-200 bg-slate-50 p-1.5 sm:grid-cols-2">
                            <button
                                type="button"
                                @click="type = 'Pass Slip'"
                                class="flex items-center justify-center gap-2 rounded-lg border px-4 py-3 text-xs font-bold uppercase tracking-wide transition"
                                :class="type === 'Pass Slip'
                                    ? 'border-blue-700 bg-white text-blue-900 shadow-sm'
                                    : 'border-transparent text-slate-500 hover:bg-white'"
                            >
                                <span
                                    class="inline-flex h-4 w-4 items-center justify-center rounded-full border"
                                    :class="type === 'Pass Slip'
                                        ? 'border-blue-700'
                                        : 'border-slate-400'"
                                >
                                    <span
                                        x-show="type === 'Pass Slip'"
                                        class="h-2 w-2 rounded-full bg-blue-900"
                                    ></span>
                                </span>

                                Pass Slip
                            </button>

                            <button
                                type="button"
                                @click="type = 'Official Business'"
                                class="flex items-center justify-center gap-2 rounded-lg border px-4 py-3 text-xs font-bold uppercase tracking-wide transition"
                                :class="type === 'Official Business'
                                    ? 'border-blue-700 bg-white text-blue-900 shadow-sm'
                                    : 'border-transparent text-slate-500 hover:bg-white'"
                            >
                                <span
                                    class="inline-flex h-4 w-4 items-center justify-center rounded-full border"
                                    :class="type === 'Official Business'
                                        ? 'border-blue-700'
                                        : 'border-slate-400'"
                                >
                                    <span
                                        x-show="type === 'Official Business'"
                                        class="h-2 w-2 rounded-full bg-blue-900"
                                    ></span>
                                </span>

                                Official Business
                            </button>
                        </div>

                        @error('type')
                            <p class="-mt-3 mb-4 text-xs text-red-500">
                                {{ $message }}
                            </p>
                        @enderror

                        <div class="space-y-5">
                            {{-- Name --}}
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-[160px_minmax(0,1fr)] md:items-center md:gap-5">
                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Name:
                                </label>

                                <div class="flex min-h-[44px] items-center rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700">
                                    {{ $employeeName ?: 'N/A' }}
                                </div>
                            </div>

                            {{-- Position --}}
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-[160px_minmax(0,1fr)] md:items-center md:gap-5">
                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Position:
                                </label>

                                <div class="flex min-h-[44px] items-center rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700">
                                    {{ $employeePosition }}
                                </div>
                            </div>

                            {{-- Destination --}}
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-[160px_minmax(0,1fr)] md:items-start md:gap-5">
                                <label
                                    for="destination"
                                    class="text-xs font-semibold uppercase tracking-wide text-slate-500 md:pt-3"
                                >
                                    Destination:
                                </label>

                                <div>
                                    <input
                                        type="text"
                                        id="destination"
                                        name="destination"
                                        value="{{ old('destination') }}"
                                        class="block min-h-[44px] w-full rounded-lg border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-700 focus:ring-blue-700"
                                        autocomplete="off"
                                        required
                                    >

                                    @error('destination')
                                        <p class="mt-1.5 text-xs text-red-500">
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Purpose --}}
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-[160px_minmax(0,1fr)] md:items-start md:gap-5">
                                <label
                                    for="purpose"
                                    class="text-xs font-semibold uppercase tracking-wide text-slate-500 md:pt-3"
                                >
                                    Purpose(s):
                                </label>

                                <div>
                                    <textarea
                                        id="purpose"
                                        name="purpose"
                                        rows="4"
                                        class="block w-full resize-y rounded-lg border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-blue-700 focus:ring-blue-700"
                                        required
                                    >{{ old('purpose') }}</textarea>

                                    @error('purpose')
                                        <p class="mt-1.5 text-xs text-red-500">
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Inclusive date --}}
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-[160px_minmax(0,1fr)] md:items-start md:gap-5">
                                <div class="w-full md:col-start-2">
                                    <x-weekday-date-picker field="date_covered" :label="__('Inclusive Date')" model="dateCovered" />
                                </div>
                            </div>

                            {{-- Time out and time in --}}
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-[160px_minmax(0,1fr)] md:items-start md:gap-5">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 md:pt-3">
                                    QR Records:
                                </span>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                        <span class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Time Out
                                        </span>

                                        <p class="mt-1 text-xs text-slate-400">
                                            Recorded by QR scan
                                        </p>
                                    </div>

                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                        <span class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Time In
                                        </span>

                                        <p class="mt-1 text-xs text-slate-400">
                                            Recorded by QR scan
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Fixed footer --}}
                    <div class="flex shrink-0 justify-end border-t border-slate-200 bg-slate-50 px-6 py-4 sm:px-8">
                        <div class="flex shrink-0 gap-3">
                            <button
                                type="button"
                                @click="close()"
                                class="inline-flex flex-1 items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-slate-600 transition hover:bg-slate-100 sm:flex-none"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="inline-flex flex-1 items-center justify-center rounded-lg bg-blue-900 px-6 py-2.5 text-xs font-bold uppercase tracking-wider text-white transition hover:bg-blue-800 sm:flex-none"
                            >
                                Submit
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<style>
    [x-cloak] {
        display: none !important;
    }

    /*
     * Full-screen modal container.
     * The maximum z-index ensures it appears above the sidebar,
     * dashboard, topbar and notification dropdowns.
     */
    .locator-modal-overlay {
        position: fixed !important;
        inset: 0 !important;
        z-index: 2147483647 !important;

        display: flex;
        align-items: center;
        justify-content: center;

        width: 100vw !important;
        height: 100vh !important;
        height: 100dvh !important;

        padding: 24px;
        overflow-y: auto;
        isolation: isolate;
    }

    /*
     * Covers the entire page, including the topbar.
     * Increase the final value to 1 if you want a completely solid
     * background instead of seeing the dashboard faintly.
     */
    .locator-modal-backdrop {
        position: fixed !important;
    inset: 0 !important;
    z-index: -1 !important;

    width: 100vw !important;
    height: 100vh !important;
    height: 100dvh !important;

    background: rgba(15, 23, 42, 0.60);

    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    }

    /*
     * Wide rectangular modal.
     * Maximum width is 1200px while still fitting smaller screens.
     */
    .locator-modal-panel {
        position: relative !important;
        z-index: 1 !important;

        display: flex;
        flex-direction: column;

        width: min(90vw, 720px) !important;
        max-width: 720px !important;
        min-width: 0;

        height: min(84vh, 720px) !important;
        height: min(84dvh, 720px) !important;
        max-height: calc(100dvh - 48px) !important;

        overflow: hidden;

        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;

        box-shadow:
            0 30px 70px -20px rgba(0, 0, 0, 0.55),
            0 0 0 1px rgba(15, 23, 42, 0.05);
    }

    .locator-slip-scroll {
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: #93c5fd #f1f5f9;
    }

    .locator-slip-scroll::-webkit-scrollbar {
        width: 7px;
    }

    .locator-slip-scroll::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    .locator-slip-scroll::-webkit-scrollbar-thumb {
        background: #93c5fd;
        border: 1px solid #f1f5f9;
        border-radius: 999px;
    }

    .locator-slip-scroll::-webkit-scrollbar-thumb:hover {
        background: #2563eb;
    }

    @media (max-width: 768px) {
        .locator-modal-overlay {
            padding: 12px;
        }

        .locator-modal-panel {
            width: calc(100vw - 24px) !important;
            max-width: none !important;

            height: calc(100dvh - 24px) !important;
            max-height: calc(100dvh - 24px) !important;

            border-radius: 14px;
        }
    }
</style>
