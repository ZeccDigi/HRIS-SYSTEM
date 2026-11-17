@php
    $tableKey = is_string($tableKey ?? null) ? $tableKey : 'all';
    // Translation files may supply an array for a missing or grouped key.
    // The footer always needs plain text so a malformed label cannot break
    // application pages that use the shared client-side pagination control.
    $paginationLabel = is_string($label ?? null) ? $label : 'items';
@endphp

<div
    x-show="counts['{{ $tableKey }}'] > 0"
    x-cloak
    class="border-t border-slate-100 bg-white px-4 py-3"
>
    <div class="grid items-center gap-3 text-sm text-slate-600 sm:grid-cols-[1fr_auto_1fr]">
        <div class="font-semibold">
            <span x-text="rangeStart('{{ $tableKey }}')"></span>-<span x-text="rangeEnd('{{ $tableKey }}')"></span>
            of
            <span x-text="counts['{{ $tableKey }}']"></span>
            {{ $paginationLabel }}
        </div>

        <nav class="flex items-center justify-start gap-3 sm:justify-center" aria-label="Pagination">
            <button type="button" @click="setPage('{{ $tableKey }}', 1)" :disabled="pages['{{ $tableKey }}'] <= 1" class="inline-flex h-8 w-8 items-center justify-center text-sky-500 transition hover:text-blue-900 disabled:text-slate-300" aria-label="First page">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m11 17-5-5 5-5m7 10-5-5 5-5" /></svg>
            </button>
            <button type="button" @click="setPage('{{ $tableKey }}', pages['{{ $tableKey }}'] - 1)" :disabled="pages['{{ $tableKey }}'] <= 1" class="inline-flex h-8 w-8 items-center justify-center text-sky-500 transition hover:text-blue-900 disabled:text-slate-300" aria-label="Previous page">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m15 18-6-6 6-6" /></svg>
            </button>

            <template x-for="pageNumber in pageNumbers('{{ $tableKey }}')" :key="pageNumber">
                <button type="button" @click="setPage('{{ $tableKey }}', pageNumber)" class="inline-flex h-8 min-w-8 items-center justify-center px-2 text-sm font-semibold transition hover:text-blue-900" :class="pages['{{ $tableKey }}'] === pageNumber ? 'font-black text-sky-600' : 'text-slate-600'" x-text="pageNumber"></button>
            </template>

            <button type="button" @click="setPage('{{ $tableKey }}', pages['{{ $tableKey }}'] + 1)" :disabled="pages['{{ $tableKey }}'] >= totalPages('{{ $tableKey }}')" class="inline-flex h-8 w-8 items-center justify-center text-sky-500 transition hover:text-blue-900 disabled:text-slate-300" aria-label="Next page">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m9 18 6-6-6-6" /></svg>
            </button>
            <button type="button" @click="setPage('{{ $tableKey }}', totalPages('{{ $tableKey }}'))" :disabled="pages['{{ $tableKey }}'] >= totalPages('{{ $tableKey }}')" class="inline-flex h-8 w-8 items-center justify-center text-sky-500 transition hover:text-blue-900 disabled:text-slate-300" aria-label="Last page">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="m6 17 5-5-5-5m7 10 5-5-5-5" /></svg>
            </button>
        </nav>

        <div class="flex items-center justify-start gap-2 sm:justify-end">
            <label for="{{ $tableKey }}_client_per_page" class="whitespace-nowrap text-xs font-semibold text-slate-600">Show</label>
            <select id="{{ $tableKey }}_client_per_page" x-model.number="perPage" @change="resetPages(); applyFilters()" class="block h-8 w-20 rounded-lg border-gray-300 bg-white py-1 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach([5, 10, 20, 50] as $pageSize)
                    <option value="{{ $pageSize }}">{{ $pageSize }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
