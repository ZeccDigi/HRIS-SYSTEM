<x-app-layout>
    <x-slot name="title">{{ __('Review Locator Slip') }}</x-slot>

    @php
        $employee = $locatorSlip->employee;
        $role = strtolower(auth()->user()->role ?? '');
        $displayStatus = strtolower((string) $locatorSlip->status) === 'approved by chief'
            ? 'approved'
            : strtolower((string) $locatorSlip->status);
        $canReview = $role === 'chief' && $displayStatus === 'pending';

        $trackedLocatorPayload = [
            'id' => (string) $locatorSlip->id,
            'title' => trim(($employee?->firstname ?? '') . ' ' . ($employee?->lastname ?? '')),
            'employee' => trim(($employee?->firstname ?? '') . ' ' . ($employee?->lastname ?? '')),
            'type' => ($locatorSlip->type ?? '') === 'Personal'
                ? 'Pass Slip'
                : (string) ($locatorSlip->type ?: 'Locator Slip'),
            'stages' => [
                [
                    'label' => 'Chief',
                    'status' => $displayStatus ?: 'pending',
                ],
            ],
        ];
    @endphp

    <div class="py-10">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-5 flex items-center justify-between gap-3">
                <a
                    href="{{ route('hr.locator-slips.index', [
                        'tab' => $displayStatus === 'pending' ? 'pending' : 'all',
                    ]) }}"
                    class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-600 shadow-sm transition hover:bg-gray-50 hover:text-indigo-600"
                >
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                    </svg>

                    {{ __('Back') }}
                </a>

                <button
                    type="button"
                    onclick="printLocatorForm()"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700"
                >
                    <i class="fa-solid fa-print mr-2"></i>
                    {{ __('Print Locator Slip') }}
                </button>
            </div>

            <x-approval-tracker
                :payload="$trackedLocatorPayload"
                event="locator-selected"
                empty="No locator slip approval process to track yet."
            />

            <div class="mt-8 space-y-6">
                <section class="rounded-[2rem] border border-gray-100 bg-white px-3 pb-3 pt-6 shadow-sm sm:px-5 sm:pb-5 sm:pt-8">
                    <div class="mx-auto w-full max-w-[900px] overflow-hidden rounded-2xl border border-gray-200 bg-slate-200">
                        <iframe
                            id="locator-form-preview"
                            src="{{ route('locator-slips.print', [
                                'locatorSlip' => $locatorSlip->id,
                                'preview' => 1,
                            ]) }}"
                            class="block w-full border-0 bg-white"
                            style="aspect-ratio: 210 / 297;"
                            title="{{ __('Locator slip print preview') }}"
                            scrolling="no"
                        ></iframe>
                    </div>
                </section>

                @if($canReview)
    <section class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm sm:p-7">
        <div class="mb-6 border-b border-gray-100 pb-5">
            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-indigo-500">
                {{ __('Current Review Stage') }}
            </p>

            <h3 class="mt-1 text-xl font-black text-gray-900">
                {{ __('Review Locator Slip') }}
            </h3>
        </div>

        <div>
            <label
                for="locator_review_remarks"
                class="mb-2 block text-xs font-black uppercase tracking-widest text-gray-600"
            >
                {{ __('Remarks / Notes') }}
            </label>

            <textarea
                id="locator_review_remarks"
                name="remarks"
                rows="4"
                form="locator-reject-form"
                class="block w-full resize-y rounded-2xl border-gray-300 bg-gray-50 text-sm text-gray-800 shadow-sm transition focus:border-indigo-500 focus:bg-white focus:ring-indigo-500"
                placeholder="{{ __('Enter remarks or notes here...') }}"
            >{{ old('remarks') }}</textarea>

            <p class="mt-2 text-xs text-gray-400">
                {{ __('Remarks are optional when approving and required when rejecting.') }}
            </p>

            @error('remarks')
                <p class="mt-2 text-xs font-semibold text-red-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <form
                id="locator-reject-form"
                action="{{ route('locator-slips.reject', $locatorSlip) }}"
                method="POST"
            >
                @csrf
                @method('PATCH')

                <button
                    type="button"
                    onclick="openLocatorDecisionModal('rejected')"
                    class="inline-flex min-w-44 items-center justify-center rounded-xl border border-red-300 bg-white px-6 py-3 text-xs font-black uppercase tracking-widest text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2"
                >
                    <i class="fa-solid fa-xmark mr-2"></i>
                    {{ __('Reject Request') }}
                </button>
            </form>

            <form
                id="locator-approve-form"
                action="{{ route('locator-slips.approve', $locatorSlip) }}"
                method="POST"
            >
                @csrf
                @method('PATCH')
                <input type="hidden" id="locator_approve_remarks" name="remarks" value="">

                <button
                    type="button"
                    onclick="openLocatorDecisionModal('approved')"
                    class="inline-flex min-w-44 items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-xs font-black uppercase tracking-widest text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    <i class="fa-solid fa-check mr-2"></i>
                    {{ __('Approve Request') }}
                </button>
            </form>
        </div>

        <div
            id="locatorDecisionModal"
            class="fixed inset-0 z-[100000] hidden items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="locatorDecisionModalTitle"
        >
            <div
                id="locatorDecisionModalBackdrop"
                class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
            ></div>

            <div class="relative z-10 w-full max-w-xs overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h2
                        id="locatorDecisionModalTitle"
                        class="text-base font-black text-slate-900"
                    ></h2>

                    <p class="mt-0.5 text-xs text-slate-500">
                        {{ __('ID: :id', ['id' => $locatorSlip->id]) }}
                    </p>
                </div>

                <div class="px-4 py-3 text-center">
                    <p
                        id="locatorDecisionModalDescription"
                        class="whitespace-pre-line text-xs leading-5 text-slate-600"
                    ></p>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50 px-4 py-3 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        onclick="closeLocatorDecisionModal()"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-[11px] font-black uppercase tracking-widest text-slate-600 transition hover:bg-slate-100"
                    >
                        {{ __('Cancel') }}
                    </button>

                    <button
                        type="button"
                        id="locatorDecisionConfirmButton"
                        class="inline-flex items-center justify-center rounded-lg bg-blue-900 px-3 py-2 text-[11px] font-black uppercase tracking-widest text-white transition hover:bg-blue-800"
                    ></button>
                </div>
            </div>
        </div>
    </section>
@endif
            </div>
        </div>
    </div>

    <script>
        let locatorDecisionAction = null;

        function printLocatorForm() {
            const previewIframe = document.getElementById('locator-form-preview');

            if (!previewIframe || !previewIframe.contentWindow) {
                alert('Locator slip preview is not ready yet.');
                return;
            }

            previewIframe.contentWindow.focus();
            previewIframe.contentWindow.print();
        }

        function openLocatorDecisionModal(action) {
            locatorDecisionAction = action;

            const modal = document.getElementById('locatorDecisionModal');
            const title = document.getElementById('locatorDecisionModalTitle');
            const description = document.getElementById('locatorDecisionModalDescription');
            const confirmButton = document.getElementById('locatorDecisionConfirmButton');

            if (title) {
                title.textContent = action === 'approved'
                    ? 'Approve this locator slip?'
                    : 'Reject this locator slip?';
            }

            if (description) {
                description.textContent = action === 'approved'
                    ? 'Approving will mark this request as approved.'
                    : 'Rejecting will mark this request as rejected.';
            }

            if (confirmButton) {
                confirmButton.textContent = action === 'approved'
                    ? 'Approve'
                    : 'Reject';
            }

            modal?.classList.remove('hidden');
            modal?.classList.add('flex');
            document.documentElement.classList.add('overflow-hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeLocatorDecisionModal() {
            const modal = document.getElementById('locatorDecisionModal');

            modal?.classList.add('hidden');
            modal?.classList.remove('flex');
            locatorDecisionAction = null;
            document.documentElement.classList.remove('overflow-hidden');
            document.body.classList.remove('overflow-hidden');
        }

        document.getElementById('locatorDecisionConfirmButton')?.addEventListener('click', function () {
            const remarks = document.getElementById('locator_review_remarks')?.value || '';
            const approveRemarks = document.getElementById('locator_approve_remarks');

            if (approveRemarks) {
                approveRemarks.value = remarks;
            }

            const form = locatorDecisionAction === 'approved'
                ? document.getElementById('locator-approve-form')
                : document.getElementById('locator-reject-form');

            form?.submit();
        });

        document.getElementById('locatorDecisionModalBackdrop')?.addEventListener(
            'click',
            closeLocatorDecisionModal
        );

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeLocatorDecisionModal();
            }
        });
    </script>
</x-app-layout>
