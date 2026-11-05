<x-app-layout>
    <x-slot name="title">{{ __('Manage Announcements') }}</x-slot>

    @php
        $role = strtolower(auth()->user()->role ?? '');

        $canPost = in_array(
            $role,
            ['admin', 'hrstaff', 'chief', 'regionaldirector'],
            true
        );
        $isChief = $role === 'chief';

        $shouldOpenCreateModal =
            request()->boolean('openCreate') ||
            $errors->any();

        /*
         * Current year plus three previous years.
         *
         * Example:
         * 2026
         * 2025
         * 2024
         * 2023
         */
        $availableYears = range(
            now()->year,
            now()->year - 3
        );
    @endphp

    <div
        class="py-10 sm:py-12"
        x-data="{
            openAnnouncement: {{ $shouldOpenCreateModal ? 'true' : 'false' }}
        }"
        @keydown.escape.window="openAnnouncement = false"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Success message --}}
            @if(session('success'))
                <div
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    class="mb-6 flex items-center justify-between gap-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700 shadow-sm"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <svg
                            class="h-5 w-5 shrink-0"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                            aria-hidden="true"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"
                            />
                        </svg>

                        <p class="truncate text-sm font-semibold">
                            {{ session('success') }}
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="show = false"
                        class="rounded-lg p-1 text-emerald-500 transition hover:bg-emerald-100 hover:text-emerald-700"
                        aria-label="{{ __('Dismiss message') }}"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            @endif

            {{-- Validation error message --}}
            @if($errors->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                    {{ __('Please check the highlighted announcement fields and try again.') }}
                </div>
            @endif

            {{-- Year and New Announcement button --}}
            <div class="flex items-center justify-end gap-3">
                <div class="w-32">
                    <label
                        for="announcementYear"
                        class="sr-only"
                    >
                        {{ __('Year') }}
                    </label>

                    <select
                        form="announcementFilterForm"
                        id="announcementYear"
                        name="year"
                        class="block h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        @foreach($availableYears as $yearOption)
                            <option
                                value="{{ $yearOption }}"
                                {{ (int) $year === (int) $yearOption ? 'selected' : '' }}
                            >
                                {{ $yearOption }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($canPost)
                    <button
                        type="button"
                        @click="openAnnouncement = true"
                        class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 text-xs font-black uppercase tracking-widest text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 5v14m7-7H5"
                            />
                        </svg>

                        {{ __('New Announcement') }}
                    </button>
                @endif
            </div>

            {{-- Announcement tabs --}}
            <div class="mt-10 border-b border-gray-200">
                <div class="flex gap-8 overflow-x-auto">
                    <a
                        href="{{ route('announcements.index', array_filter([
                            'search' => $search,
                            'category' => $category !== 'all' ? $category : null,
                            'year' => $year,
                            'month' => $month !== 'all' ? $month : null,
                            'status' => 'all',
                        ])) }}"
                        data-announcement-tab
                        data-status="all"
                        data-mine="0"
                        class="whitespace-nowrap border-b-2 px-1 pb-4 text-sm font-bold uppercase tracking-[0.14em] transition
                            {{ $status === 'all' && !$mine
                                ? 'border-indigo-600 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                            }}"
                    >
                        {{ __('All Announcements') }}
                    </a>

                    <a
                        href="{{ route('announcements.index', array_filter([
                            'search' => $search,
                            'category' => $category !== 'all' ? $category : null,
                            'year' => $year,
                            'month' => $month !== 'all' ? $month : null,
                            'status' => $status,
                            'mine' => 1,
                        ])) }}"
                        data-announcement-tab
                        data-status="{{ $status }}"
                        data-mine="1"
                        class="whitespace-nowrap border-b-2 px-1 pb-4 text-sm font-bold uppercase tracking-[0.14em] transition
                            {{ $mine
                                ? 'border-indigo-600 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                            }}"
                    >
                        {{ __('My Announcements') }}
                    </a>
                </div>
            </div>

            {{-- Search and filter form --}}
            <form
                id="announcementFilterForm"
                method="GET"
                action="{{ route('announcements.index') }}"
                data-filter-url="{{ route('announcements.filter') }}"
                class="mt-8 flex flex-col gap-2 sm:flex-row sm:items-center"
            >
                <input
                    type="hidden"
                    id="announcementMine"
                    name="mine"
                    value="{{ $mine ? 1 : 0 }}"
                >

                <input
                    type="hidden"
                    id="announcementStatus"
                    name="status"
                    value="{{ $status }}"
                >

                {{-- Search --}}
                <div class="relative min-w-0 sm:flex-1">
                    <label
                        for="announcementSearch"
                        class="sr-only"
                    >
                        {{ __('Search announcements') }}
                    </label>

                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.4"
                                d="M21 21l-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </span>

                    <input
                        id="announcementSearch"
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="{{ __('Search title, content, or type...') }}"
                        class="block h-10 w-full rounded-lg border-gray-300 bg-white pl-10 pr-3 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500"
                        data-announcement-autofilter
                    >
                </div>

                {{-- Category --}}
                <div class="sm:w-48 sm:shrink-0">
                    <label
                        for="announcementCategory"
                        class="sr-only"
                    >
                        {{ __('Category') }}
                    </label>

                    <select
                        id="announcementCategory"
                        name="category"
                        class="block h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option
                            value="all"
                            {{ $category === 'all' ? 'selected' : '' }}
                        >
                            {{ __('All Categories') }}
                        </option>

                        @foreach($categories as $option)
                            <option
                                value="{{ $option }}"
                                {{ $category === $option ? 'selected' : '' }}
                            >
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Month --}}
                <div class="sm:w-48 sm:shrink-0">
                    <label
                        for="announcementMonth"
                        class="sr-only"
                    >
                        {{ __('Month') }}
                    </label>

                    <select
                        id="announcementMonth"
                        name="month"
                        class="block h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option
                            value="all"
                            {{ $month === 'all' ? 'selected' : '' }}
                        >
                            {{ __('All Months') }}
                        </option>

                        @foreach($months as $option)
                            <option
                                value="{{ $option['value'] }}"
                                {{ $month === $option['value'] ? 'selected' : '' }}
                            >
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Reset --}}
                <div class="flex shrink-0">
                    <button
                        type="button"
                        id="announcementFilterReset"
                        class="inline-flex h-10 w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-xs font-black uppercase tracking-widest text-gray-600 transition hover:bg-gray-50 hover:text-gray-800 sm:w-auto"
                    >
                        {{ __('Reset') }}
                    </button>
                </div>
            </form>

            {{-- Announcement results --}}
            <div
                id="announcementResults"
                class="mt-8 transition-opacity duration-200"
            >
                @include('announcements.partials.results', [
                    'announcements' => $announcements,
                    'canPost' => $canPost,
                ])
            </div>
        </div>

        {{-- Create Announcement modal --}}
        @if($canPost)
            <div
                x-show="openAnnouncement"
                x-cloak
                x-transition.opacity
               class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4 backdrop-blur-sm sm:items-center"
                role="dialog"
                aria-modal="true"
                aria-labelledby="createAnnouncementTitle"
            >
                {{-- Modal backdrop --}}
                <div
                    class="absolute inset-0"
                    @click="openAnnouncement = false"
                    aria-hidden="true"
                ></div>

                {{-- Modal panel --}}
                <div
                    x-show="openAnnouncement"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="scale-95 opacity-0"
                    x-transition:enter-end="scale-100 opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="scale-100 opacity-100"
                    x-transition:leave-end="scale-95 opacity-0"
                   class="relative my-4 flex max-h-[calc(100vh-2rem)] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl sm:my-8 sm:max-h-[90vh]"
                >
                    {{-- Modal header --}}
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 sm:px-6">
                        <div>
                            <h2
                                id="createAnnouncementTitle"
                                class="text-xl font-black text-gray-900 sm:text-2xl"
                            >
                                {{ __('Create Announcement') }}
                            </h2>

                            <p class="mt-1 text-sm font-medium text-gray-500">
                                {{ __('Post an announcement for employees.') }}
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="openAnnouncement = false"
                            class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                            aria-label="{{ __('Close modal') }}"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    {{-- Create form --}}
                    <form
                        action="{{ route('announcements.store') }}"
                        method="POST"
                        enctype="multipart/form-data"
                        class="flex min-h-0 flex-1 flex-col"
                    >
                        @csrf

<div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
                            {{-- Title --}}
                            <div>
                                <label
                                    for="modalTitle"
                                    class="mb-1.5 block text-sm font-bold text-gray-700"
                                >
                                    {{ __('Title') }}
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="modalTitle"
                                    type="text"
                                    name="title"
                                    value="{{ old('title') }}"
                                    placeholder="{{ __('Enter announcement title') }}"
                                    class="block w-full rounded-lg border-gray-300 px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 @error('title') border-red-400 @enderror"
                                    required
                                >

                                @error('title')
                                    <p class="mt-1 text-sm text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            {{-- Announcement type --}}
                            <div>
                                <label
                                    for="modalTags"
                                    class="mb-1.5 block text-sm font-bold text-gray-700"
                                >
                                    {{ __('Announcement Type') }}
                                </label>

                                @if($isChief)
                                    <input type="hidden" name="tags" value="Office Orders">
                                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-700">
                                        {{ __('Office Orders') }}
                                        <span class="ml-2 text-xs font-medium text-gray-400">{{ __('Your division only') }}</span>
                                    </div>
                                @else
                                    <select
                                        id="modalTags"
                                        name="tags"
                                        class="block w-full rounded-lg border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('tags') border-red-400 @enderror"
                                    >
                                        @foreach($categories as $option)
                                            <option
                                                value="{{ $option }}"
                                                {{ old('tags', 'General') === $option ? 'selected' : '' }}
                                            >
                                                {{ $option }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif

                                @error('tags')
                                    <p class="mt-1 text-sm text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            {{-- Content --}}
                            <div>
                                <label
                                    for="modalContent"
                                    class="mb-1.5 block text-sm font-bold text-gray-700"
                                >
                                    {{ __('Content') }}
                                    <span class="text-red-500">*</span>
                                </label>

                                <textarea
                                    id="modalContent"
                                    name="content"
                                    rows="6"
                                    placeholder="{{ __('Provide detailed information...') }}"
                                    class="block w-full resize-y rounded-lg border-gray-300 px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 @error('content') border-red-400 @enderror"
                                    required
                                >{{ old('content') }}</textarea>

                                @error('content')
                                    <p class="mt-1 text-sm text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            {{-- PDF attachment --}}
                            <div x-data="{ fileName: '' }">
                                <label
                                    for="modalAttachment"
                                    class="mb-1.5 block text-sm font-bold text-gray-700"
                                >
                                    {{ __('PDF Attachment') }}

                                    <span class="text-xs font-medium text-gray-400">
                                        {{ __('Optional') }}
                                    </span>
                                </label>

                                <input
                                    id="modalAttachment"
                                    x-ref="modalAttachment"
                                    type="file"
                                    name="attachment"
                                    accept=".pdf,application/pdf"
                                    class="hidden"
                                    @change="fileName = $event.target.files[0]?.name || ''"
                                >

                                <label
                                    for="modalAttachment"
                                    class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50/50 p-4 transition hover:border-indigo-400 hover:bg-indigo-50/30 @error('attachment') border-red-300 @enderror"
                                >
                                    <div class="flex min-w-0 items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">
                                            <svg
                                                class="h-5 w-5"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                                                />
                                            </svg>
                                        </div>

                                        <div class="min-w-0">
                                            <span
                                                x-show="!fileName"
                                                class="text-sm font-bold text-gray-600"
                                            >
                                                {{ __('Attach PDF File') }}
                                            </span>

                                            <span
                                                x-show="fileName"
                                                x-text="fileName"
                                                class="block max-w-sm truncate text-sm font-bold text-indigo-700"
                                            ></span>

                                            <p class="mt-0.5 text-xs text-gray-400">
                                                {{ __('PDF file only, maximum 5 MB') }}
                                            </p>
                                        </div>
                                    </div>

                                    <span
                                        x-show="!fileName"
                                        class="shrink-0 rounded-full bg-indigo-50 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-indigo-600"
                                    >
                                        {{ __('Browse') }}
                                    </span>

                                    <button
                                        type="button"
                                        x-show="fileName"
                                        @click.prevent.stop="
                                            fileName = '';
                                            $refs.modalAttachment.value = '';
                                        "
                                        class="shrink-0 rounded-lg p-1.5 text-red-500 transition hover:bg-red-50 hover:text-red-700"
                                        aria-label="{{ __('Remove attachment') }}"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                            aria-hidden="true"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </button>
                                </label>

                                @error('attachment')
                                    <p class="mt-1 text-sm text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>

                        {{-- Modal footer --}}
                        <div class="flex shrink-0 justify-end gap-3 border-t border-gray-100 bg-white px-5 py-4 sm:px-6">
                            <button
                                type="button"
                                @click="openAnnouncement = false"
                                class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-bold text-gray-600 transition hover:bg-gray-50 hover:text-gray-800"
                            >
                                {{ __('Cancel') }}
                            </button>

                            <button
                                type="submit"
                                class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                {{ __('Post Announcement') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const announcementFilterForm =
                document.getElementById('announcementFilterForm');

            const announcementSearchInput =
                document.querySelector('[data-announcement-autofilter]');

            const announcementCategorySelect =
                document.getElementById('announcementCategory');

            const announcementYearSelect =
                document.getElementById('announcementYear');

            const announcementMonthSelect =
                document.getElementById('announcementMonth');

            const announcementResults =
                document.getElementById('announcementResults');

            const announcementFilterReset =
                document.getElementById('announcementFilterReset');

            const announcementMineInput =
                document.getElementById('announcementMine');

            const announcementStatusInput =
                document.getElementById('announcementStatus');

            const announcementTabs =
                document.querySelectorAll('[data-announcement-tab]');

            let announcementSearchTimer;

            function updateAnnouncementTabs(activeMine) {
                announcementTabs.forEach((tab) => {
                    const isActive =
                        tab.dataset.mine === activeMine;

                    tab.classList.toggle(
                        'border-indigo-600',
                        isActive
                    );

                    tab.classList.toggle(
                        'text-indigo-600',
                        isActive
                    );

                    tab.classList.toggle(
                        'border-transparent',
                        !isActive
                    );

                    tab.classList.toggle(
                        'text-gray-500',
                        !isActive
                    );
                });
            }

            async function filterAnnouncements(
                requestUrl = null,
                browserUrl = null
            ) {
                if (
                    !announcementFilterForm ||
                    !announcementResults
                ) {
                    return;
                }

                const filterUrl =
                    announcementFilterForm.dataset.filterUrl;

                const params =
                    new URLSearchParams(
                        new FormData(announcementFilterForm)
                    );

                const nextRequestUrl =
                    requestUrl ||
                    `${filterUrl}?${params.toString()}`;

                const nextBrowserUrl =
                    browserUrl ||
                    `${announcementFilterForm.action}?${params.toString()}`;

                announcementResults.style.opacity = '0.55';
                announcementResults.style.pointerEvents = 'none';

                try {
                    const response = await fetch(
                        nextRequestUrl,
                        {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        }
                    );

                    if (!response.ok) {
                        throw new Error(
                            'Unable to filter announcements.'
                        );
                    }

                    const data = await response.json();

                    announcementResults.innerHTML =
                        data.html;

                    window.history.replaceState(
                        {},
                        '',
                        nextBrowserUrl
                    );
                } catch (error) {
                    announcementFilterForm.submit();
                } finally {
                    announcementResults.style.opacity = '1';
                    announcementResults.style.pointerEvents = 'auto';
                }
            }

            if (announcementFilterForm) {
                announcementFilterForm.addEventListener(
                    'submit',
                    (event) => {
                        event.preventDefault();
                        filterAnnouncements();
                    }
                );
            }

            if (announcementSearchInput) {
                announcementSearchInput.addEventListener(
                    'input',
                    () => {
                        clearTimeout(
                            announcementSearchTimer
                        );

                        announcementSearchTimer =
                            setTimeout(() => {
                                filterAnnouncements();
                            }, 600);
                    }
                );
            }

            [
                announcementCategorySelect,
                announcementYearSelect,
                announcementMonthSelect,
            ].forEach((element) => {
                if (element) {
                    element.addEventListener(
                        'change',
                        () => filterAnnouncements()
                    );
                }
            });

            if (announcementFilterReset) {
                announcementFilterReset.addEventListener(
                    'click',
                    () => {
                        if (announcementSearchInput) {
                            announcementSearchInput.value = '';
                        }

                        if (announcementCategorySelect) {
                            announcementCategorySelect.value = 'all';
                        }

                        if (announcementYearSelect) {
                            announcementYearSelect.value =
                                '{{ now()->year }}';
                        }

                        if (announcementMonthSelect) {
                            announcementMonthSelect.value = 'all';
                        }

                        filterAnnouncements();
                    }
                );
            }

            announcementTabs.forEach((tab) => {
                tab.addEventListener(
                    'click',
                    (event) => {
                        event.preventDefault();

                        if (announcementMineInput) {
                            announcementMineInput.value =
                                tab.dataset.mine || '0';
                        }

                        if (announcementStatusInput) {
                            announcementStatusInput.value =
                                tab.dataset.status || 'all';
                        }

                        updateAnnouncementTabs(
                            tab.dataset.mine || '0'
                        );

                        filterAnnouncements();
                    }
                );
            });

            if (
                announcementResults &&
                announcementFilterForm
            ) {
                announcementResults.addEventListener(
                    'click',
                    (event) => {
                        const link =
                            event.target.closest('a');

                        if (
                            !link ||
                            !link.href ||
                            !link.closest('nav')
                        ) {
                            return;
                        }

                        event.preventDefault();

                        const url = new URL(link.href);

                        const filterUrl =
                            announcementFilterForm.dataset.filterUrl;

                        filterAnnouncements(
                            `${filterUrl}${url.search}`,
                            `${announcementFilterForm.action}${url.search}`
                        );
                    }
                );
            }
        });
    </script>
</x-app-layout>
