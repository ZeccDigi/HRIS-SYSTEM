<x-app-layout>
    <x-slot name="title">{{ __('Official Announcements') }}</x-slot>

    @php
        $search = isset($search)
            ? (string) $search
            : (string) request('search', '');

        $year = isset($year)
            ? (int) $year
            : (int) request('year', now()->year);

        $category = isset($category)
            ? (string) $category
            : (string) request('category', 'all');

        $month = isset($month)
            ? (string) $month
            : (string) request('month', 'all');

        $categories = isset($categories)
            ? $categories
            : [
                'General',
                'Meeting',
                'Training',
                'Workshop',
                'Memo',
                'Office Orders',
                'Advisory',
            ];

        $months = isset($months)
            ? $months
            : collect(range(1, 12))
                ->map(function ($monthNumber) {
                    return [
                        'value' => str_pad(
                            (string) $monthNumber,
                            2,
                            '0',
                            STR_PAD_LEFT
                        ),
                        'label' => now()
                            ->copy()
                            ->month($monthNumber)
                            ->format('F'),
                    ];
                })
                ->all();

        $availableYears = range(
            now()->year,
            now()->year - 3
        );
    @endphp

    <div class="py-10 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Year selector --}}
            <div class="flex items-center justify-end">
                <div class="w-32">
                    <label
                        for="publicAnnouncementYear"
                        class="sr-only"
                    >
                        {{ __('Year') }}
                    </label>

                    <select
                        form="publicAnnouncementFilterForm"
                        id="publicAnnouncementYear"
                        name="year"
                        class="block h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        @foreach($availableYears as $yearOption)
                            <option
                                value="{{ $yearOption }}"
                                {{ $year === (int) $yearOption ? 'selected' : '' }}
                            >
                                {{ $yearOption }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Search and filters --}}
            <form
                id="publicAnnouncementFilterForm"
                method="GET"
                action="{{ route('announcements.view') }}"
                class="mt-8 flex flex-col gap-2 sm:flex-row sm:items-center"
            >
                {{-- Search --}}
                <div class="relative min-w-0 sm:flex-1">
                    <label
                        for="publicAnnouncementSearch"
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
                        id="publicAnnouncementSearch"
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="{{ __('Search title, content, or type...') }}"
                        class="block h-10 w-full rounded-lg border-gray-300 bg-white pl-10 pr-3 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500"
                        data-public-announcement-autofilter
                    >
                </div>

                {{-- Category --}}
                <div class="sm:w-48 sm:shrink-0">
                    <label
                        for="publicAnnouncementCategory"
                        class="sr-only"
                    >
                        {{ __('Category') }}
                    </label>

                    <select
                        id="publicAnnouncementCategory"
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
                        for="publicAnnouncementMonth"
                        class="sr-only"
                    >
                        {{ __('Month') }}
                    </label>

                    <select
                        id="publicAnnouncementMonth"
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
                                {{ $month === (string) $option['value'] ? 'selected' : '' }}
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
                        id="publicAnnouncementFilterReset"
                        class="inline-flex h-10 w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-xs font-black uppercase tracking-widest text-gray-600 transition hover:bg-gray-50 hover:text-gray-800 sm:w-auto"
                    >
                        {{ __('Reset') }}
                    </button>
                </div>
            </form>

            {{-- Announcement results --}}
            <div
                id="publicAnnouncementResults"
                class="mt-8 transition-opacity duration-200"
            >
                @include('announcements.partials.public-results', [
                    'announcements' => $announcements,
                ])
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filterForm =
                document.getElementById('publicAnnouncementFilterForm');

            const searchInput =
                document.querySelector(
                    '[data-public-announcement-autofilter]'
                );

            const yearSelect =
                document.getElementById('publicAnnouncementYear');

            const categorySelect =
                document.getElementById('publicAnnouncementCategory');

            const monthSelect =
                document.getElementById('publicAnnouncementMonth');

            const resultsContainer =
                document.getElementById('publicAnnouncementResults');

            const resetButton =
                document.getElementById('publicAnnouncementFilterReset');

            let searchTimer;

            async function filterPublicAnnouncements(
                requestUrl = null,
                browserUrl = null
            ) {
                if (!filterForm || !resultsContainer) {
                    return;
                }

                const params = new URLSearchParams(
                    new FormData(filterForm)
                );

                /*
                 * The year selector is outside the form,
                 * so add its current value explicitly.
                 */
                if (yearSelect) {
                    params.set('year', yearSelect.value);
                }

                const nextRequestUrl =
                    requestUrl ||
                    `${filterForm.action}?${params.toString()}`;

                const nextBrowserUrl =
                    browserUrl ||
                    `${filterForm.action}?${params.toString()}`;

                resultsContainer.style.opacity = '0.55';
                resultsContainer.style.pointerEvents = 'none';

                try {
                    const response = await fetch(nextRequestUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error(
                            'Unable to filter announcements.'
                        );
                    }

                    const data = await response.json();

                    if (typeof data.html !== 'string') {
                        throw new Error(
                            'Announcement results were not returned.'
                        );
                    }

                    resultsContainer.innerHTML = data.html;

                    window.history.replaceState(
                        {},
                        '',
                        nextBrowserUrl
                    );
                } catch (error) {
                    filterForm.submit();
                } finally {
                    resultsContainer.style.opacity = '1';
                    resultsContainer.style.pointerEvents = 'auto';
                }
            }

            if (filterForm) {
                filterForm.addEventListener('submit', (event) => {
                    event.preventDefault();
                    filterPublicAnnouncements();
                });
            }

            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    clearTimeout(searchTimer);

                    searchTimer = setTimeout(() => {
                        filterPublicAnnouncements();
                    }, 600);
                });
            }

            [
                yearSelect,
                categorySelect,
                monthSelect,
            ].forEach((element) => {
                if (element) {
                    element.addEventListener('change', () => {
                        filterPublicAnnouncements();
                    });
                }
            });

            if (resetButton) {
                resetButton.addEventListener('click', () => {
                    if (searchInput) {
                        searchInput.value = '';
                    }

                    if (yearSelect) {
                        yearSelect.value = '{{ now()->year }}';
                    }

                    if (categorySelect) {
                        categorySelect.value = 'all';
                    }

                    if (monthSelect) {
                        monthSelect.value = 'all';
                    }

                    filterPublicAnnouncements();
                });
            }

            if (resultsContainer && filterForm) {
                resultsContainer.addEventListener(
                    'click',
                    (event) => {
                        const link = event.target.closest('a');

                        if (
                            !link ||
                            !link.href ||
                            !link.closest('nav')
                        ) {
                            return;
                        }

                        event.preventDefault();

                        const url = new URL(link.href);

                        filterPublicAnnouncements(
                            `${filterForm.action}${url.search}`,
                            `${filterForm.action}${url.search}`
                        );
                    }
                );
            }
        });
    </script>
</x-app-layout>
