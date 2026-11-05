<x-app-layout>
    <x-slot name="title">{{ __('Announcement Details') }}</x-slot>

    <div class="py-2">
        <div class="mx-auto max-w-6xl px-3 sm:px-4 lg:px-5">

            {{-- Back button --}}
            <div class="mb-3">
                <a
                    href="{{ in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'hrstaff', 'chief', 'regionaldirector'], true)
                        ? route('announcements.index')
                        : route('announcements.view') }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-600 shadow-sm transition hover:border-indigo-300 hover:bg-gray-50 hover:text-indigo-600"
                >
                    <svg
                        class="mr-2 h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M11 17l-5-5m0 0l5-5m-5 5h12"
                        />
                    </svg>

                    {{ __('Back') }}
                </a>
            </div>

            {{-- Announcement card --}}
            <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="p-4 sm:p-5">

                    {{-- Date, categories, and actions --}}
                    <div class="mb-4 flex flex-col gap-3 border-b border-gray-200 pb-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="flex items-center text-xs font-medium text-gray-500">
                                <svg
                                    class="mr-1.5 h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>

                                {{ $announcement->created_at->format('M d, Y h:i A') }}
                            </span>

                            @if($announcement->tags)
                                @foreach(explode(',', $announcement->tags) as $tag)
                                    <span class="rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                                        {{ trim($tag) }}
                                    </span>
                                @endforeach
                            @endif
                        </div>

                        {{-- Admin actions --}}
                        @php
                            $viewerRole = strtolower(auth()->user()->role ?? '');
                            $canManageAnnouncement = in_array($viewerRole, ['admin', 'hrstaff', 'regionaldirector'], true)
                                || ($viewerRole === 'chief' && $announcement->author_id === auth()->id());
                        @endphp
                        @if($canManageAnnouncement)
                            <div class="flex shrink-0 gap-2">
                                <a
                                    href="{{ route('announcements.edit', $announcement) }}"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 text-indigo-700 transition-colors hover:border-indigo-300 hover:bg-indigo-100"
                                    title="{{ __('Edit') }}"
                                    aria-label="{{ __('Edit') }}"
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
                                    onsubmit="return confirm('{{ __('Are you sure you want to delete this announcement?') }}');"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-red-200 bg-red-50 text-red-700 transition-colors hover:border-red-300 hover:bg-red-100"
                                        title="{{ __('Delete') }}"
                                        aria-label="{{ __('Delete') }}"
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
                                                d="M6 7h12m-9 0V5.25A1.25 1.25 0 0110.25 4h3.5A1.25 1.25 0 0115 5.25V7m-7 0l.75 12A2 2 0 0010.75 21h2.5a2 2 0 002-1.875L16 7"
                                            />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>

                    {{-- Title --}}
                    <div class="mb-4 border-b border-gray-200 pb-4">
                        <p class="mb-2 text-[10px] font-black uppercase tracking-widest text-gray-400">
                            {{ __('Title') }}
                        </p>

                        <h1 class="text-3xl font-black leading-tight text-gray-900">
                            {{ $announcement->title }}
                        </h1>
                    </div>

                    {{-- Announcement content --}}
                    <section>
                        <p class="mb-2 text-[10px] font-black uppercase tracking-widest text-gray-400">
                            {{ __('Description') }}
                        </p>
                        <div class="prose prose-indigo max-w-none whitespace-pre-wrap text-base leading-6 text-gray-700">
                            {!! nl2br(e($announcement->content)) !!}
                        </div>
                    </section>

                    {{-- Attachment --}}
                    @if($announcement->attachment_path)
                        <div class="mt-4 border-t border-gray-200 pt-3">
                            <span class="mb-2 block text-[10px] font-black uppercase tracking-widest text-gray-400">
                                {{ __('Attachment') }}
                            </span>

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
                        </div>
                    @endif

                    {{-- Author shown once only --}}
                    <div class="mt-4 border-t border-gray-200 pt-3 text-sm text-gray-500">
                        <p class="mb-1.5 text-[10px] font-black uppercase tracking-widest text-gray-400">
                            {{ __('Posted By') }}
                        </p>
                        <p class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-gray-700">
                                {{ $announcement->author?->display_name
                                    ?: $announcement->author?->name
                                    ?: __('HR Office') }}
                            </span>

                            <span
                                class="h-4 w-px bg-gray-300"
                                aria-hidden="true"
                            ></span>

                            <span>
                                {{ collect([
                                    $announcement->author?->employee?->division_abbreviation,
                                    Str::headline($announcement->author?->employee?->position ?: $announcement->author?->role ?: __('Staff')),
                                ])->filter()->implode(' - ') }}
                            </span>
                        </p>
                    </div>
                </div>
            </article>
        </div>
    </div>
</x-app-layout>
