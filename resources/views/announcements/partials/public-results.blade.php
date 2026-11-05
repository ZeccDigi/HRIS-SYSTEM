<div class="space-y-4">
    @forelse($announcements as $announcement)
        @php
            $tags = collect(
                explode(',', $announcement->tags ?? 'General')
            )
                ->map(fn ($tag) => trim($tag))
                ->filter();

            $primaryTag = $tags->first() ?: 'General';

            $displayDate =
                $announcement->published_at
                ?? $announcement->created_at;

            $authorName =
                $announcement->author?->display_name
                ?: $announcement->author?->name
                ?: __('HR Office');

            $authorPosition =
                $announcement->author?->employee?->position
                ?: $announcement->author?->role
                ?: __('Staff');

            $authorContext = collect([
                $announcement->author?->employee?->division_abbreviation,
                Str::headline($authorPosition),
            ])->filter()->implode(' - ');

            $categoryStyle = match(strtolower($primaryTag)) {
                'meeting' =>
                    'border-blue-200 bg-blue-50 text-blue-700',

                'training', 'workshop' =>
                    'border-orange-200 bg-orange-50 text-orange-700',

                'memo', 'memorandum' =>
                    'border-amber-200 bg-amber-50 text-amber-700',

                'office order', 'office orders' =>
                    'border-slate-200 bg-slate-100 text-slate-700',

                'advisory' =>
                    'border-emerald-200 bg-emerald-50 text-emerald-700',

                default =>
                    'border-indigo-200 bg-indigo-50 text-indigo-700',
            };
        @endphp

        <article
            class="group rounded-2xl border border-gray-300 bg-white px-5 py-5 shadow-sm transition duration-200 hover:border-indigo-300 hover:shadow-md sm:px-6"
        >
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                {{-- Announcement information --}}
                <div class="min-w-0 flex-1">
                    {{-- Type and date --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex rounded-lg border px-3 py-1 text-[11px] font-bold uppercase tracking-wider {{ $categoryStyle }}"
                        >
                            {{ $primaryTag }}
                        </span>

                        @if($displayDate)
                            <span class="text-sm font-semibold text-gray-500">
                                {{ $displayDate->format('M d, Y') }}
                            </span>

                            <span
                                class="h-1 w-1 rounded-full bg-gray-300"
                                aria-hidden="true"
                            ></span>

                            <span class="text-sm font-medium text-gray-400">
                                {{ $displayDate->diffForHumans() }}
                            </span>
                        @endif
                    </div>

                    {{-- Title --}}
                    <a
                        href="{{ route('announcements.show', $announcement) }}"
                        class="mt-4 block w-fit max-w-full"
                    >
                        <h2
                            class="break-words text-xl font-bold leading-snug text-gray-900 transition group-hover:text-indigo-600 sm:text-2xl"
                        >
                            {{ $announcement->title }}
                        </h2>
                    </a>

                    {{-- Author name and position --}}
                    <div class="mt-5 flex flex-wrap items-center gap-2 text-sm">
                        <span class="font-semibold text-gray-700">
                            {{ $authorName }}
                        </span>

                        <span
                            class="h-4 w-px bg-gray-300"
                            aria-hidden="true"
                        ></span>

                        <span class="font-medium text-gray-500">{{ $authorContext }}</span>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex shrink-0 items-center gap-2 sm:pt-1">
                    @if($announcement->attachment_path)
                        <a
                            href="{{ route('announcements.attachment', $announcement) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-400 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600"
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
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        {{ __('Read More') }}

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
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </a>
                </div>
            </div>
        </article>
    @empty
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
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
                {{ __('No announcements match the current search or filters.') }}
            </p>
        </div>
    @endforelse
</div>

@if($announcements->hasPages())
    <div class="mt-6">
        {{ $announcements->withQueryString()->links() }}
    </div>
@endif
