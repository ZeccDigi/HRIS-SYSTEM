<div class="space-y-4">
    @forelse($announcements as $announcement)
        @php
            $tags = collect(explode(',', $announcement->tags ?? 'General'))
                ->map(fn ($tag) => trim($tag))
                ->filter();

            $primaryTag = $tags->first() ?: 'General';

            $displayDate = $announcement->published_at
                ?? $announcement->created_at;

            $authorName =
                $announcement->author?->display_name
                ?: $announcement->author?->name
                ?: __('HR Office');

            $authorPosition =
                $announcement->author?->employee?->position
                ?: $announcement->author?->role
                ?: __('Staff');

            $authorDivision =
                $announcement->author?->employee?->division;

            $authorDivisionAbbreviation =
                $announcement->author?->employee?->division_abbreviation;

            $authorContext = collect([$authorDivisionAbbreviation, Str::headline($authorPosition)])
                ->filter()
                ->implode(' - ');

            $viewerRole = strtolower(auth()->user()->role ?? '');
            $canManageThisAnnouncement = in_array($viewerRole, ['admin', 'hrstaff', 'regionaldirector'], true)
                || ($viewerRole === 'chief' && $announcement->author_id === auth()->id());

            $categoryStyle = match(strtolower($primaryTag)) {
                'meeting' =>
                    'bg-blue-50 text-blue-700 ring-blue-200',

                'training', 'workshop' =>
                    'bg-orange-50 text-orange-700 ring-orange-200',

                'memo', 'memorandum' =>
                    'bg-amber-50 text-amber-700 ring-amber-200',

                'office order', 'office orders' =>
                    'bg-slate-100 text-slate-700 ring-slate-200',

                'advisory' =>
                    'bg-emerald-50 text-emerald-700 ring-emerald-200',

                default =>
                    'bg-indigo-50 text-indigo-700 ring-indigo-200',
            };
        @endphp

        <article
            class="group rounded-xl border border-gray-300 bg-white px-5 py-5 shadow-sm transition duration-200 hover:border-indigo-300 hover:shadow-md sm:px-6"
        >
            <div class="flex items-start justify-between gap-4">
                {{-- Announcement information --}}
                <div class="min-w-0 flex-1">
                    {{-- Type and date --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex rounded-md px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider ring-1 ring-inset {{ $categoryStyle }}"
                        >
                            {{ $primaryTag }}
                        </span>

                        <span class="text-xs font-semibold text-gray-500">
                            {{ $displayDate->format('M d, Y') }}
                        </span>

                        <span
                            class="h-1 w-1 rounded-full bg-gray-300"
                            aria-hidden="true"
                        ></span>

                        <span class="text-xs font-medium text-gray-400">
                            {{ $displayDate->diffForHumans() }}
                        </span>
                    </div>

                    {{-- Title --}}
                    <a
                        href="{{ route('announcements.show', $announcement) }}"
                        class="mt-3 block w-fit max-w-full"
                    >
                        <h2
                            class="text-lg font-bold leading-snug text-gray-900 transition group-hover:text-indigo-600 sm:text-xl"
                        >
                            {{ $announcement->title }}
                        </h2>
                    </a>

                    {{-- Author information --}}
                    <div class="mt-4 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                        <span class="font-semibold text-gray-700">
                            {{ $authorName }}
                        </span>

                        <span
                            class="h-1 w-1 rounded-full bg-gray-300"
                            aria-hidden="true"
                        ></span>

                        <span class="font-medium text-gray-500">{{ $authorContext }}</span>
                    </div>
                </div>

                {{-- Upper-right actions --}}
                <div class="flex shrink-0 items-center gap-1">
                    @if($announcement->attachment_path)
                        <a
                            href="{{ route('announcements.attachment', $announcement) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent text-gray-400 transition hover:border-indigo-100 hover:bg-indigo-50 hover:text-indigo-600"
                            title="{{ __('Open attachment') }}"
                            aria-label="{{ __('Open attachment') }}"
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
                                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                                />
                            </svg>
                        </a>
                    @endif

                    <a
                        href="{{ route('announcements.show', $announcement) }}"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent text-gray-400 transition hover:border-indigo-100 hover:bg-indigo-50 hover:text-indigo-600"
                        title="{{ __('View') }}"
                        aria-label="{{ __('View') }}"
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
                                d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z"
                            />

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                    </a>

                    @if($canManageThisAnnouncement)
                    <a
                        href="{{ route('announcements.edit', $announcement) }}"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent text-gray-400 transition hover:border-blue-100 hover:bg-blue-50 hover:text-blue-600"
                        title="{{ __('Edit') }}"
                        aria-label="{{ __('Edit') }}"
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
                                d="M16.862 4.487l1.651-1.651a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"
                            />

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19.5 7.125L16.875 4.5"
                            />
                        </svg>
                    </a>

                    <form
                        action="{{ route('announcements.destroy', $announcement) }}"
                        method="POST"
                        onsubmit="return confirm('{{ __('Delete this announcement?') }}');"
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent text-gray-400 transition hover:border-red-100 hover:bg-red-50 hover:text-red-600"
                            title="{{ __('Delete') }}"
                            aria-label="{{ __('Delete') }}"
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
                                    d="M6 7h12m-9 0V5.25A1.25 1.25 0 0110.25 4h3.5A1.25 1.25 0 0115 5.25V7m-7 0l.75 12A2 2 0 0010.75 21h2.5a2 2 0 002-1.875L16 7"
                                />
                            </svg>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </article>
    @empty
        <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
            <svg
                class="mx-auto h-10 w-10 text-gray-300"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592L5.5 14m5.5-8.118A14.7 14.7 0 0018.326 3H19a1 1 0 011 1v11a1 1 0 01-1 1h-.674A14.7 14.7 0 0011 13.118M5.5 14A3.5 3.5 0 115.5 7m0 7V7"
                />
            </svg>

            <h3 class="mt-4 text-base font-bold text-gray-900">
                {{ __('No announcements found') }}
            </h3>

            <p class="mt-1 text-sm text-gray-500">
                {{ __('No announcements match the current search or filter.') }}
            </p>
        </div>
    @endforelse
</div>

@if($announcements->hasPages())
    <div class="mt-6">
        {{ $announcements->withQueryString()->links() }}
    </div>
@endif
