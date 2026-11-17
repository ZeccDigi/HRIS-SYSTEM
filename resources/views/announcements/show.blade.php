<x-app-layout>
    <x-slot name="title">{{ __('Announcement Details') }}</x-slot>

    <div class="py-2">
        <div class="mx-auto max-w-6xl px-3 sm:px-4 lg:px-5">

            {{-- Back button --}}
            <div class="mb-3">
                <x-page-action href="{{ in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'hrstaff', 'chief', 'regionaldirector'], true) ? route('announcements.index') : route('announcements.view') }}" />
            </div>

            {{-- Announcement card --}}
            <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="p-5 sm:p-6">

                    @php
                        $displayTags = collect(explode(',', $announcement->tags ?? ''))
                            ->map(fn ($tag) => trim($tag))
                            ->filter(fn ($tag) => filled($tag) && strcasecmp($tag, 'important') !== 0);
                        $authorName = $announcement->author?->display_name ?: $announcement->author?->name ?: __('HR Office');
                        $authorContext = collect([
                            $announcement->author?->employee?->division,
                            Str::headline($announcement->author?->employee?->position ?: $announcement->author?->role ?: __('Staff')),
                        ])->filter()->implode(' - ');
                        $publishedDate = $announcement->published_at ?? $announcement->created_at;
                    @endphp

                    {{-- Category and actions --}}
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach($displayTags as $tag)
                                <span class="rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>

                        {{-- Admin actions --}}
                        @php
                            $viewerRole = strtolower(auth()->user()->role ?? '');
                            $canManageAnnouncement = in_array($viewerRole, ['admin', 'hrstaff', 'regionaldirector'], true)
                                || ($viewerRole === 'chief' && $announcement->author_id === auth()->id());
                        @endphp
                        @if($canManageAnnouncement)
                            <div class="flex shrink-0 gap-0.5">
                                <a
                                    href="{{ route('announcements.edit', $announcement) }}"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-blue-700 transition-colors duration-200 hover:bg-blue-50 hover:text-blue-900"
                                    title="{{ __('Edit') }}"
                                    aria-label="{{ __('Edit') }}"
                                >
                                    <i class="fa-solid fa-pen text-sm" aria-hidden="true"></i>
                                </a>

                                <x-announcement-archive-action :announcement="$announcement" />
                            </div>
                        @endif
                    </div>

                    {{-- Title and publication details --}}
                    <div class="border-b border-gray-200 pb-5">
                        <h1 class="text-2xl font-black leading-tight text-gray-900 sm:text-3xl">
                            {{ $announcement->title }}
                        </h1>

                        <div class="mt-5 flex flex-col gap-3 text-sm sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="font-semibold text-gray-800">{{ $authorName }}</p>
                                @if($authorContext)
                                    <p class="mt-0.5 text-gray-500">{{ $authorContext }}</p>
                                @endif
                            </div>

                            <p class="inline-flex items-center gap-2 text-sm text-gray-500">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ __('Posted') }} {{ $publishedDate?->format('g:i A') }}
                                <span class="text-gray-300" aria-hidden="true">|</span>
                                {{ $publishedDate?->format('M d, Y') }}
                            </p>
                        </div>
                    </div>

                    {{-- Announcement content --}}
                    <section class="pt-5">
                        <div class="prose prose-indigo max-w-none whitespace-pre-wrap text-base leading-6 text-gray-700">
                            {!! nl2br(e($announcement->content)) !!}
                        </div>
                    </section>

                    {{-- Attachment --}}
                    <div class="mt-4 border-t border-gray-200 pt-3">
                        <span class="mb-2 block text-[10px] font-black uppercase tracking-widest text-gray-400">
                            {{ __('Attachment') }}
                        </span>
                        @if($announcement->attachment_path)
                            <a
                                href="{{ route('announcements.attachment', $announcement) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex max-w-full items-center gap-3 text-sm font-bold text-indigo-700 transition-colors hover:text-indigo-900"
                            >
                                <svg
                                    class="h-5 w-5 shrink-0"
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

                                <span class="truncate">
                                    {{ basename($announcement->attachment_path) }}
                                </span>
                            </a>
                        @else
                            <p class="text-sm text-gray-500">{{ __('No attachment provided.') }}</p>
                        @endif
                    </div>

                </div>
            </article>
        </div>
    </div>
</x-app-layout>
