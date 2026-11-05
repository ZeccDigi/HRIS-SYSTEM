@if($canReviewSaln && $reviewStatus !== 'approved')
    <div class="saln-review-stage-card mx-auto rounded-xl border border-slate-200 bg-white p-4 font-sans shadow-sm print:hidden">
        <div class="border-b border-slate-100 pb-3">
            <p class="text-[10px] font-black uppercase tracking-widest text-blue-800">Current Review Stage</p>
            <h2 class="mt-1 text-lg font-black tracking-tight text-slate-900">Verify Statement of Assets, Liabilities, and Net Worth</h2>
        </div>

        <div class="pt-3">
            <label for="saln_review_remarks_{{ $cardId ?? 'main' }}" class="block text-[11px] font-black uppercase tracking-widest text-slate-600">Remarks / Notes</label>
            <textarea
                id="saln_review_remarks_{{ $cardId ?? 'main' }}"
                rows="3"
                placeholder="Enter remarks or notes here..."
                class="saln-review-remarks-input mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                oninput="syncSalnRemarks(this)"
            >{{ old('review_remarks', $saln->review_remarks) }}</textarea>
            <p class="mt-1.5 text-xs font-medium text-slate-400">Remarks are required when rejecting the SALN submission.</p>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    onclick="submitSalnReview('returned', this)"
                    class="inline-flex min-w-44 items-center justify-center rounded-xl border border-red-300 bg-white px-6 py-3 text-xs font-black uppercase tracking-widest text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2"
                >
                    <i class="fa-solid fa-xmark mr-2"></i>
                    Reject Request
                </button>
                <button
                    type="button"
                    onclick="submitSalnReview('approved', this)"
                    class="inline-flex min-w-44 items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-xs font-black uppercase tracking-widest text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    <i class="fa-solid fa-check mr-2"></i>
                    Verify Request
                </button>
            </div>
        </div>
    </div>
@endif
