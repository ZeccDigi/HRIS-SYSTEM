<x-app-layout>
    <x-slot name="title">{{ __('SALN') }}</x-slot>

    @include('saln.partials.official-styles')

    @php
        $selectedYear = $selectedYear ?? (int) $saln->as_of_date->format('Y');
        $canManageSaln = $canManageSaln ?? ($canExportSaln ?? true);
        $hasSavedSaln = $hasSavedSaln ?? $saln->exists;
        $reviewStatus = strtolower((string) ($saln->review_status ?? ($hasSavedSaln ? 'pending' : 'draft')));
        $reviewBadgeClass = match ($reviewStatus) {
            'approved' => 'border-emerald-100 bg-emerald-50 text-emerald-700',
            'returned' => 'border-rose-100 bg-rose-50 text-rose-700',
            'pending' => 'border-amber-100 bg-amber-50 text-amber-700',
            default => 'border-slate-100 bg-slate-50 text-slate-600',
        };
        $reviewLabel = match ($reviewStatus) {
            'approved' => 'Approved',
            'returned' => 'Returned for Update',
            'pending' => 'Pending HR Review',
            default => 'Not Submitted',
        };
    @endphp

    <div class="bg-slate-50 py-6 print:bg-white print:py-0">
        <div class="mx-auto max-w-[900px] px-4 sm:px-6 lg:px-8 print:max-w-none print:mx-0 print:px-0">
            <div class="mb-6 space-y-4 font-sans print:hidden">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-lg font-extrabold tracking-tight text-slate-900">Statement of Assets, Liabilities, and Net Worth</h2>
                            <span class="inline-flex rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-widest {{ $reviewBadgeClass }}">{{ $reviewLabel }}</span>
                        </div>
                        @if($reviewStatus === 'returned' && $saln->review_remarks)
                            <p class="mt-2 max-w-2xl rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700">{{ $saln->review_remarks }}</p>
                        @endif
                    </div>
                    @if($canManageSaln)
                        <form method="GET" action="{{ route('salns.index') }}" class="flex items-center gap-3">
                            <label for="saln_preview_year" class="text-[10px] font-black uppercase tracking-widest text-slate-400">Year</label>
                            <select id="saln_preview_year" name="year" onchange="this.form.submit()" class="h-12 w-32 rounded-lg border-gray-100 bg-indigo-50/60 px-3 text-sm font-bold text-slate-700 shadow-sm outline-none focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach(range(now()->year + 1, now()->year - 5) as $year)
                                    <option value="{{ $year }}" @selected((int) $selectedYear === (int) $year)>{{ $year }}</option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        @if($backUrl ?? null)
                            <a href="{{ $backUrl }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-5 text-[13px] font-extrabold uppercase leading-none tracking-[.12em] text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-[#0038a8]">Back</a>
                        @elseif($canManageSaln)
                            <a href="{{ route('salns.index', ['year' => $selectedYear, 'edit' => 1]) }}" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-[#002f8f] bg-[#0038a8] px-5 text-[13px] font-extrabold uppercase leading-none tracking-[.12em] text-white shadow-[0_8px_18px_rgba(0,56,168,.18)] transition hover:bg-[#002f8f]">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M16.862 4.487l1.651-1.651a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                </svg>
                                Edit
                            </a>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-3 sm:justify-end">
                        @if($canManageSaln)
                            <button type="button" onclick="window.print()" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-[#0038a8] bg-white px-5 text-[13px] font-extrabold uppercase leading-none tracking-[.12em] text-[#0038a8] shadow-sm transition hover:bg-blue-50">Print PDF</button>
                        @endif
                        @if($canExportSaln ?? true)
                            <a href="{{ route('salns.download', $saln) }}" data-no-transition class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-gray-200 bg-white px-5 text-[13px] font-extrabold uppercase leading-none tracking-[.12em] text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-[#0038a8]">Download PDF</a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="pds-paper w-full bg-white p-4 print:p-0">
                <div class="saln-official saln-preview">
                    @include('saln.partials.content')
                </div>

            </div>
        </div>
    </div>
    <script>
        function updateSalnPageFooters() {
            document.querySelectorAll('.saln-official').forEach((salnCopy) => {
                const pages = Array.from(salnCopy.querySelectorAll('.saln-page'));
                const total = pages.length;

                pages.forEach((page, index) => {
                    const footer = page.querySelector('.saln-page-footer');
                    if (footer) {
                        footer.textContent = `Page ${index + 1} of ${total}`;
                    }
                });
            });
        }

        window.addEventListener('load', updateSalnPageFooters);
        window.addEventListener('beforeprint', updateSalnPageFooters);
    </script>
    @if(request()->boolean('print'))
        <script>
            window.addEventListener('load', () => window.print());
        </script>
    @endif
</x-app-layout>
