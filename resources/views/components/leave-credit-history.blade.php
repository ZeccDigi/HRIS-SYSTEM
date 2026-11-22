@props(['historyUrl' => null, 'eventName' => 'open-credit-history', 'historyYear' => null, 'unit' => 'Days', 'precision' => 3, 'historyId' => 'credit-history', 'alwaysOpen' => false, 'yearOptions' => []])
<div x-data="{
    historyYear: @js($historyYear),
    open: false, loading: false, error: '', name: '', typeId: null,
    rows: [], page: 1, lastPage: 1, total: 0, sequence: 0,
    async load(page = 1) {
        const token = ++this.sequence; this.loading = true; this.error = '';
        const url = new URL(@js($historyUrl ?? route('leaves.credit-history')));
        url.searchParams.set('leave_type_id', this.typeId); url.searchParams.set('year', this.historyYear ?? this.year); url.searchParams.set('page', page);
        try {
            const response = await fetch(url, {headers: {'Accept': 'application/json'}});
            if (!response.ok) throw new Error();
            const data = await response.json();
            if (token !== this.sequence) return;
            this.rows = data.rows; this.page = data.page; this.lastPage = data.last_page; this.total = data.total;
        } catch (e) { if (token === this.sequence) this.error = 'Unable to load credit history. Please try again.'; }
        finally { if (token === this.sequence) this.loading = false; }
    },
    show(detail) { if (this.open && this.typeId === detail.id && (!detail.year || this.historyYear === detail.year)) return this.close(); if (detail.year) this.historyYear = detail.year; this.name = detail.name; this.typeId = detail.id; this.rows = []; this.open = true; this.load(); },
    close() { if (@js($alwaysOpen)) return; this.open = false; this.sequence++; this.loading = false; },
    days(value) { return value === null ? '--' : Number(value).toFixed({{ $precision }}); }
}" x-init="@if($alwaysOpen) name = 'COC'; typeId = 0; open = true; load(); @endif" x-on:{{ $eventName }}.window="show($event.detail)">
        <div x-show="open" x-cloak style="display:none" class="mt-4" @keydown.escape.window="close()" @click.outside="if (!$event.target.closest('[data-credit-history-trigger]')) close()">
            <section class="w-full overflow-hidden rounded-xl border border-slate-200 bg-white" aria-labelledby="{{ $historyId }}-title">
                <header class="flex items-center justify-between gap-3 border-b border-slate-200 px-3 py-2">
                    <h2 id="{{ $historyId }}-title" class="text-sm font-bold text-slate-900"><span x-text="name"></span> — {{ __('Credit History') }} @unless($alwaysOpen)<span class="font-normal text-slate-500" x-text="historyYear ?? year"></span>@endunless</h2>
                    @if($alwaysOpen)
                        <select x-model.number="historyYear" @change="rows = []; load()" aria-label="COC history year" class="h-9 w-28 rounded-lg border-slate-300 bg-white py-1 text-xs">
                            @foreach($yearOptions as $optionYear)<option value="{{ $optionYear }}">{{ $optionYear }}</option>@endforeach
                        </select>
                    @else
                    <button type="button" @click="close()" aria-label="Close credit history" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100"><i class="fa-solid fa-xmark"></i></button>
                    @endif
                </header>
                <div class="flex items-center justify-between gap-3 px-3 py-2">
                    <p class="text-[11px] text-slate-500">{{ $unit === 'Hours' ? __('Posted COC additions and CTO deductions · Hours') : __('Earnings, deductions, adjustments and carryover · Days') }}</p>
                </div>
                <div class="min-h-0 overflow-auto border-y border-slate-100">
                    <p x-show="loading" class="p-8 text-center text-sm text-slate-500">{{ __('Loading credit history…') }}</p>
                    <p x-show="error" x-text="error" class="p-6 text-center text-sm text-red-600"></p>
                    <table x-show="!loading && !error" class="w-full table-fixed text-left text-[11px] [&_th]:px-2 [&_th]:py-2 [&_td]:px-2 [&_td]:py-2 [&_td]:break-words">
                        <colgroup><col class="w-[19%]"><col class="w-[19%]"><col class="w-[11%]"><col class="w-[9%]"><col class="w-[12%]"><col class="w-[17%]"><col class="w-[13%]"></colgroup>
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wide text-slate-500"><tr>
                            <th class="px-4 py-3">{{ __('Action Date & Time') }}</th><th class="px-4 py-3">{{ $unit === 'Hours' ? __('COC / CTO') : __('Effective Date') }}</th><th class="px-4 py-3">{{ __('Adjustment') }}</th><th class="px-4 py-3 text-right">{{ __($unit) }}</th><th class="px-4 py-3 text-right">{{ __('Balance After') }}</th><th class="px-4 py-3">{{ $unit === 'Hours' ? __('Approved by') : __('Recorded by') }}</th><th class="px-4 py-3">{{ __('Remarks') }}</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="row in rows" :key="row.id"><tr>
                                <td class="break-words px-3 py-2 text-slate-600" x-text="row.date"></td>
                                <td class="break-words px-3 py-2 text-slate-600" x-text="row.schedule || '--'"></td>
                                <td class="px-4 py-3" x-text="row.label || (row.operation === 'carryover' ? 'Carryover' : (row.operation === 'deduct' ? 'Deduction' : 'Addition'))"></td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums" :class="row.operation === 'deduct' ? 'text-red-600' : 'text-emerald-700'" x-text="(row.operation === 'deduct' ? '−' : '+') + days(row.amount)"></td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums" x-text="days(row.balance)"></td>
                                <td class="px-4 py-3"><span class="block font-semibold" x-text="row.staff"></span><span class="text-[10px] text-slate-500" x-text="row.position"></span></td>
                                <td class="max-w-xs whitespace-pre-line break-words px-4 py-3 text-slate-600" x-text="row.remarks || '--'"></td>
                            </tr></template>
                            <tr x-show="rows.length === 0"><td colspan="7" class="px-4 py-10 text-center text-slate-500">{{ __('No credit history recorded for this year.') }}</td></tr>
                        </tbody>
                    </table>
                </div>
                <footer class="flex items-center justify-between gap-3 px-3 py-2 text-[11px] text-slate-600">
                    <span x-text="total + ' record(s)'"></span>
                    <div class="flex items-center gap-2"><button type="button" :disabled="page <= 1 || loading" @click="load(page - 1)" class="rounded-lg border border-slate-200 px-3 py-2 disabled:opacity-40">{{ __('Previous') }}</button><span x-text="page + ' / ' + lastPage"></span><button type="button" :disabled="page >= lastPage || loading" @click="load(page + 1)" class="rounded-lg border border-slate-200 px-3 py-2 disabled:opacity-40">{{ __('Next') }}</button></div>
                </footer>
            </section>
        </div>
</div>
