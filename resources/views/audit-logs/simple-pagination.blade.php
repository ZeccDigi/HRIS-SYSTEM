@if($paginator->hasPages() || $paginator->total() > 0)
    <div class="mt-4 flex flex-col gap-3 rounded-2xl border border-slate-100 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm font-semibold text-slate-600">
            {{ __('Showing') }}
            {{ $paginator->total() > 0 ? $paginator->firstItem() : 0 }}
            {{ __('to') }}
            {{ $paginator->lastItem() ?? 0 }}
            {{ __('of') }}
            {{ $paginator->total() }}
            {{ __('results') }}
        </p>

        @if($paginator->hasPages())
            <div class="inline-flex items-center gap-2">
                @if($paginator->onFirstPage())
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-300" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m15 18-6-6 6-6" />
                        </svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-700" aria-label="{{ __('Previous page') }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m15 18-6-6 6-6" />
                        </svg>
                    </a>
                @endif

                <span class="min-w-[5.5rem] text-center text-xs font-black uppercase tracking-wider text-slate-500">
                    {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
                </span>

                @if($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-700" aria-label="{{ __('Next page') }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m9 18 6-6-6-6" />
                        </svg>
                    </a>
                @else
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-300" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m9 18 6-6-6-6" />
                        </svg>
                    </span>
                @endif
            </div>
        @endif
    </div>
@endif
