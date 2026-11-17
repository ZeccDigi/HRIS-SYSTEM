@php
    $paginationLabel = is_string($label ?? null) ? $label : 'items';
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $pageStart = max(1, $currentPage - 2);
    $pageEnd = min($lastPage, $currentPage + 2);
    $perPageParam = $perPageParam ?? 'per_page';
    $perPageValue = (int) ($perPage ?? request($perPageParam, $paginator->perPage()));
    $paginationQuery = collect(request()->query())
        ->except([$paginator->getPageName(), $perPageParam]);
    $paginationAction = $formAction ?? url()->current();
@endphp

@if($paginator->hasPages() || $paginator->total() > 0)
    <div class="border-t border-slate-100 bg-white px-4 py-3">
        <div class="grid items-center gap-3 text-sm text-slate-600 sm:grid-cols-[1fr_auto_1fr]">
            <div class="font-semibold">
                {{ $paginator->total() > 0 ? $paginator->firstItem() : 0 }}-{{ $paginator->lastItem() ?? 0 }}
                of
                {{ $paginator->total() }}
                {{ $paginationLabel }}
            </div>

            @if($paginator->hasPages())
                <nav class="flex items-center justify-start gap-3 sm:justify-center" aria-label="Pagination">
                    @if($paginator->onFirstPage())
                        <span class="inline-flex h-8 w-8 items-center justify-center text-slate-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m11 17-5-5 5-5m7 10-5-5 5-5" />
                            </svg>
                        </span>
                    @else
                        <a href="{{ $paginator->url(1) }}" class="inline-flex h-8 w-8 items-center justify-center text-sky-500 transition hover:text-blue-900" aria-label="First page">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m11 17-5-5 5-5m7 10-5-5 5-5" />
                            </svg>
                        </a>
                    @endif

                    @if($paginator->onFirstPage())
                        <span class="inline-flex h-8 w-8 items-center justify-center text-slate-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m15 18-6-6 6-6" />
                            </svg>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex h-8 w-8 items-center justify-center text-sky-500 transition hover:text-blue-900" aria-label="Previous page">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m15 18-6-6 6-6" />
                            </svg>
                        </a>
                    @endif

                    @if($pageStart > 1)
                        <a href="{{ $paginator->url(1) }}" class="inline-flex h-8 min-w-8 items-center justify-center px-2 text-sm font-semibold text-slate-600 transition hover:text-blue-900">1</a>
                        @if($pageStart > 2)
                            <span class="inline-flex h-8 min-w-8 items-center justify-center px-1 text-sm font-semibold text-slate-400">...</span>
                        @endif
                    @endif

                    @for($page = $pageStart; $page <= $pageEnd; $page++)
                        @if($page === $currentPage)
                            <span class="inline-flex h-8 min-w-8 items-center justify-center px-2 text-sm font-black text-sky-600">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $paginator->url($page) }}" class="inline-flex h-8 min-w-8 items-center justify-center px-2 text-sm font-semibold text-slate-600 transition hover:text-blue-900">
                                {{ $page }}
                            </a>
                        @endif
                    @endfor

                    @if($pageEnd < $lastPage)
                        @if($pageEnd < $lastPage - 1)
                            <span class="inline-flex h-8 min-w-8 items-center justify-center px-1 text-sm font-semibold text-slate-400">...</span>
                        @endif
                        <a href="{{ $paginator->url($lastPage) }}" class="inline-flex h-8 min-w-8 items-center justify-center px-2 text-sm font-semibold text-slate-600 transition hover:text-blue-900">{{ $lastPage }}</a>
                    @endif

                    @if($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex h-8 w-8 items-center justify-center text-sky-500 transition hover:text-blue-900" aria-label="Next page">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m9 18 6-6-6-6" />
                            </svg>
                        </a>
                    @else
                        <span class="inline-flex h-8 w-8 items-center justify-center text-slate-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m9 18 6-6-6-6" />
                            </svg>
                        </span>
                    @endif

                    @if($paginator->hasMorePages())
                        <a href="{{ $paginator->url($paginator->lastPage()) }}" class="inline-flex h-8 w-8 items-center justify-center text-sky-500 transition hover:text-blue-900" aria-label="Last page">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m6 17 5-5-5-5m7 10 5-5-5-5" />
                            </svg>
                        </a>
                    @else
                        <span class="inline-flex h-8 w-8 items-center justify-center text-slate-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m6 17 5-5-5-5m7 10 5-5-5-5" />
                            </svg>
                        </span>
                    @endif
                </nav>
            @else
                <div></div>
            @endif

            @if(! empty($perPageOptions))
                <form method="GET" action="{{ $paginationAction }}" class="flex items-center justify-start gap-2 sm:justify-end">
                    @foreach($paginationQuery as $queryKey => $queryValue)
                        @if(is_scalar($queryValue))
                            <input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">
                        @endif
                    @endforeach
                    <label for="{{ $perPageSelectId ?? 'table_per_page' }}" class="whitespace-nowrap text-xs font-semibold text-slate-600">
                        {{ __('Show') }}
                    </label>
                    <select
                        id="{{ $perPageSelectId ?? 'table_per_page' }}"
                        name="{{ $perPageParam }}"
                        onchange="this.form.submit()"
                        class="block h-8 w-20 rounded-lg border-gray-300 bg-white py-1 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        @foreach($perPageOptions as $pageSize)
                            <option value="{{ $pageSize }}" @selected($perPageValue === (int) $pageSize)>{{ $pageSize }}</option>
                        @endforeach
                    </select>
                </form>
            @else
                <div class="hidden sm:block"></div>
            @endif
        </div>
    </div>
@endif
