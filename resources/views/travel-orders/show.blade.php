<x-app-layout>
    <x-slot name="title">{{ __('Travel Authority Details') }}</x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @php
                $role = strtolower(auth()->user()->role ?? '');

                $isApprover = in_array(
                    $role,
                    ['admin', 'hrstaff', 'recordofficer', 'regionaldirector'],
                    true
                );

                $isRecordsOfficer = $role === 'recordofficer';
                $isDirector = $role === 'regionaldirector';

                $recordStatus = $travelOrder->recordofficer_status ?: 'pending';
                $rdStatus = $travelOrder->rd_status ?: 'pending';
                $mainStatus = $travelOrder->status ?: 'pending';
                $travelOrderIdLabel = $travelOrder->ta_number
                    ?: 'TA-' .
                        optional($travelOrder->created_at)->format('Y-m-d') .
                        '-' .
                        str_pad((string) $travelOrder->id, 3, '0', STR_PAD_LEFT);
                $approveDescription = $isRecordsOfficer
                    ? "Approving will forward this request\nto Regional Director for final review."
                    : ($isDirector
                        ? "Approving will mark this as\nfully approved."
                        : "Approving will move this request to the next step\nor mark it as fully approved.");

                $trackedEmployee = trim(
                    ($travelOrder->employee?->firstname ?? '') . ' ' .
                    ($travelOrder->employee?->lastname ?? '')
                );

                $trackedOrderPayload = [
                    'id' => (string) $travelOrder->id,
                    'title' => $isApprover
                        ? $trackedEmployee
                        : (string) $travelOrder->places_of_travel,
                    'employee' => $trackedEmployee,
                    'type' => (string) $travelOrder->places_of_travel,
                    'stages' => [
                        [
                            'label' => 'Record Officer',
                            'status' => $recordStatus,
                        ],
                        [
                            'label' => 'Regional Director',
                            'status' => $rdStatus,
                        ],
                    ],
                ];

                $isMyTurn = false;
                $waitingMessage = '';

                if ($mainStatus === 'pending') {
                    if ($isRecordsOfficer && $recordStatus === 'pending') {
                        $isMyTurn = true;
                    } elseif (
                        $isDirector &&
                        $recordStatus === 'approved' &&
                        $rdStatus === 'pending'
                    ) {
                        $isMyTurn = true;
                    } elseif (
                        $isDirector &&
                        $recordStatus !== 'approved'
                    ) {
                        $waitingMessage = 'Waiting for Record Officer review.';
                    } elseif (
                        ! $isRecordsOfficer &&
                        ! $isDirector
                    ) {
                        $waitingMessage = 'Waiting for Travel Authority approval.';
                    }
                }
            @endphp

            {{-- Top actions --}}
            <div class="mb-5 flex items-center justify-between gap-3">
                <a
                    href="{{ $isApprover
                        ? route('hr.travel-orders.index')
                        : route('travel-orders.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-blue-900 shadow-sm transition hover:border-blue-200 hover:text-blue-800"
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>

                    {{ __('Back') }}
                </a>

                <a
                    href="{{ route('travel-orders.print', $travelOrder) }}"
                    target="_blank"
                    rel="noopener"
                    data-no-transition
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-900 px-5 py-2.5 text-xs font-black uppercase tracking-widest text-white shadow-sm transition hover:bg-blue-800"
                >
                    <i class="fa-solid fa-print"></i>

                    {{ __('Print Travel Authority') }}
                </a>
            </div>

            {{-- Approval tracker --}}
            <x-approval-tracker
                :payload="$trackedOrderPayload"
                event="travel-selected"
                empty="No travel authority to track yet."
            />

            {{-- Full Travel Authority Form --}}
            <section class="mt-6 overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm">
                <div class="bg-slate-100 px-3 pb-3 pt-6 sm:px-5 sm:pb-5 sm:pt-8">
                    <div class="mx-auto w-full max-w-[1100px] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <iframe
                            id="travelAuthorityPreview"
                            src="{{ route('travel-orders.preview', $travelOrder) }}"
                            class="block w-full border-0 bg-white"
                            style="height: 1300px;"
                            scrolling="no"
                            title="{{ __('Travel authority print preview') }}"
                        ></iframe>
                    </div>
                </div>
            </section>

            @if($isMyTurn)
                <section class="mt-6 rounded-3xl border border-gray-100 bg-white p-5 shadow-sm sm:p-7">
                    <div class="mb-5 flex justify-end">
                        <div class="inline-flex shrink-0 items-center gap-2 rounded-full bg-orange-50 px-3 py-2">
                            <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-orange-400"></span>

                            <span class="text-[10px] font-black uppercase tracking-widest text-orange-600">
                                {{ __('Your Review Needed') }}
                            </span>
                        </div>
                    </div>

                    <form
                        id="travelOrderStatusForm"
                        action="{{ route('travel-orders.update-status', $travelOrder->id) }}"
                        method="POST"
                    >
                        @csrf
                        @method('PUT')

                        <div>
                            <label
                                for="travel_order_remarks"
                                class="mb-2 block text-xs font-black uppercase tracking-widest text-gray-600"
                            >
                                {{ __('Remarks / Notes') }}
                            </label>

                            <textarea
                                id="travel_order_remarks"
                                name="remarks"
                                rows="4"
                                placeholder="{{ __('Enter remarks or notes here...') }}"
                                class="block w-full resize-y rounded-2xl border-gray-300 bg-gray-50 text-sm text-gray-800 shadow-sm transition focus:border-indigo-500 focus:bg-white focus:ring-indigo-500"
                            ></textarea>

                            <div class="mt-2 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-xs text-gray-400">
                                    {{ __('Remarks are required when rejecting the travel authority.') }}
                                </p>

                                <span
                                    id="travelOrderRemarksError"
                                    class="hidden text-xs font-semibold text-red-600"
                                ></span>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button
                                type="submit"
                                id="travelOrderRejectButton"
                                name="status"
                                value="rejected"
                                class="inline-flex min-w-48 items-center justify-center rounded-xl border border-red-300 bg-white px-6 py-3 text-xs font-black uppercase tracking-widest text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2"
                            >
                                <i class="fa-solid fa-xmark mr-2"></i>
                                {{ __('Reject Travel Authority') }}
                            </button>

                            <button
                                type="submit"
                                id="travelOrderApproveButton"
                                name="status"
                                value="approved"
                                class="inline-flex min-w-48 items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-xs font-black uppercase tracking-widest text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                <i class="fa-solid fa-check mr-2"></i>

                                {{ $isRecordsOfficer
                                    ? __('Recommend Travel Authority')
                                    : __('Approve Travel Authority') }}
                            </button>
                        </div>
                    </form>
                </section>

                <div
                    id="travelApproveConfirmModal"
                    class="fixed inset-0 z-[100000] hidden items-center justify-center p-4"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="travelApproveConfirmTitle"
                >
                    <div
                        id="travelApproveConfirmBackdrop"
                        class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                    ></div>

                    <div class="relative z-10 w-full max-w-xs overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <h2
                                id="travelApproveConfirmTitle"
                                class="text-base font-black text-slate-900"
                            ></h2>

                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ __('ID: :id', ['id' => $travelOrderIdLabel]) }}
                            </p>
                        </div>

                        <div class="px-4 py-3 text-center">
                            <p class="whitespace-pre-line text-xs leading-5 text-slate-600">
                                <span id="travelApproveConfirmDescription"></span>
                            </p>
                        </div>

                        <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50 px-4 py-3 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                id="travelApproveCancelButton"
                                class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-[11px] font-black uppercase tracking-widest text-slate-600 transition hover:bg-slate-100"
                            >
                                {{ __('Cancel') }}
                            </button>

                            <button
                                type="button"
                                id="travelApproveConfirmButton"
                                class="inline-flex items-center justify-center rounded-lg bg-blue-900 px-3 py-2 text-[11px] font-black uppercase tracking-widest text-white transition hover:bg-blue-800"
                            ></button>
                        </div>
                    </div>
                </div>
            @endif

            @if($travelOrder->recordofficer_remarks || $travelOrder->chief_remarks || $travelOrder->hrstaff_remarks || $travelOrder->rd_remarks)
                <section class="mt-6 overflow-hidden rounded-3xl border border-gray-100 bg-white p-6 shadow-sm md:p-8">
                    <div class="text-xs font-black text-indigo-500 uppercase tracking-widest mb-6 inline-block border-b-2 border-indigo-100 pb-1">Approver Remarks</div>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                        @if($travelOrder->recordofficer_remarks)
                            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-5">
                                <span class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Records Officer</span>
                                <p class="mt-2 whitespace-pre-line break-words text-sm font-semibold italic leading-relaxed text-gray-700">"{{ $travelOrder->recordofficer_remarks }}"</p>
                            </div>
                        @endif
                        @if($travelOrder->chief_remarks)
                            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-5">
                                <span class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Chief</span>
                                <p class="mt-2 whitespace-pre-line break-words text-sm font-semibold italic leading-relaxed text-gray-700">"{{ $travelOrder->chief_remarks }}"</p>
                            </div>
                        @endif
                        @if($travelOrder->hrstaff_remarks)
                            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-5">
                                <span class="block text-[10px] font-black uppercase tracking-widest text-gray-400">HR Staff</span>
                                <p class="mt-2 whitespace-pre-line break-words text-sm font-semibold italic leading-relaxed text-gray-700">"{{ $travelOrder->hrstaff_remarks }}"</p>
                            </div>
                        @endif
                        @if($travelOrder->rd_remarks)
                            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-5">
                                <span class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Regional Director</span>
                                <p class="mt-2 whitespace-pre-line break-words text-sm font-semibold italic leading-relaxed text-gray-700">"{{ $travelOrder->rd_remarks }}"</p>
                            </div>
                        @endif
                    </div>
                </section>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const iframe = document.getElementById('travelAuthorityPreview');
            const form = document.getElementById('travelOrderStatusForm');
            const remarksField = document.getElementById('travel_order_remarks');
            const errorSpan = document.getElementById('travelOrderRemarksError');
            const approveButton = document.getElementById('travelOrderApproveButton');
            const rejectButton = document.getElementById('travelOrderRejectButton');
            const approveModal = document.getElementById('travelApproveConfirmModal');
            const approveTitle = document.getElementById('travelApproveConfirmTitle');
            const approveDescription = document.getElementById('travelApproveConfirmDescription');
            const approveConfirmButton = document.getElementById('travelApproveConfirmButton');
            const approveCancelButton = document.getElementById('travelApproveCancelButton');
            const approveBackdrop = document.getElementById('travelApproveConfirmBackdrop');
            let confirmedAction = null;
            let pendingButton = null;

            function resizeTravelAuthorityPreview() {
                if (!iframe) {
                    return;
                }

                try {
                    const iframeDocument =
                        iframe.contentDocument ||
                        iframe.contentWindow?.document;

                    if (!iframeDocument) {
                        return;
                    }

                    const bodyHeight = iframeDocument.body?.scrollHeight || 0;
                    const documentHeight =
                        iframeDocument.documentElement?.scrollHeight || 0;

                    iframe.style.height =
                        Math.max(bodyHeight, documentHeight, 1300) + 'px';
                } catch (error) {
                    console.warn(
                        'Unable to resize the travel authority preview.',
                        error
                    );
                }
            }

            iframe?.addEventListener('load', function () {
                resizeTravelAuthorityPreview();

                setTimeout(resizeTravelAuthorityPreview, 300);
                setTimeout(resizeTravelAuthorityPreview, 800);
            });

            window.addEventListener(
                'resize',
                resizeTravelAuthorityPreview
            );

            remarksField?.addEventListener('input', function () {
                remarksField.classList.remove(
                    'ring-2',
                    'ring-red-500',
                    'border-red-500'
                );

                if (errorSpan) {
                    errorSpan.classList.add('hidden');
                    errorSpan.textContent = '';
                }
            });

            form?.addEventListener('submit', function (event) {
                const submitter = event.submitter;
                const action = submitter?.value;
                const remarks = remarksField?.value.trim() || '';

                if (action === 'rejected' && !remarks) {
                    event.preventDefault();

                    remarksField?.classList.add(
                        'ring-2',
                        'ring-red-500',
                        'border-red-500'
                    );

                    if (errorSpan) {
                        errorSpan.textContent =
                            'Remarks are required when rejecting this travel authority.';
                        errorSpan.classList.remove('hidden');
                    }

                    remarksField?.focus();
                    return;
                }

                if ((action === 'approved' || action === 'rejected') && confirmedAction !== action) {
                    event.preventDefault();
                    pendingButton = action === 'approved' ? approveButton : rejectButton;
                    openTravelApproveConfirmModal(action);
                    return;
                }
            });

            function openTravelApproveConfirmModal(action) {
                if (!approveModal) {
                    confirmedAction = action;
                    form?.requestSubmit(pendingButton);
                    return;
                }

                if (approveTitle) {
                    approveTitle.textContent = action === 'approved'
                        ? 'Approve this travel authority?'
                        : 'Reject this travel authority?';
                }

                if (approveDescription) {
                    approveDescription.textContent = action === 'approved'
                        ? @js($approveDescription)
                        : 'Rejecting will mark this request as rejected.';
                }

                if (approveConfirmButton) {
                    approveConfirmButton.textContent = action === 'approved'
                        ? 'Approve'
                        : 'Reject';
                }

                approveModal.classList.remove('hidden');
                approveModal.classList.add('flex');
                document.documentElement.classList.add('overflow-hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeTravelApproveConfirmModal() {
                approveModal?.classList.add('hidden');
                approveModal?.classList.remove('flex');
                pendingButton = null;
                document.documentElement.classList.remove('overflow-hidden');
                document.body.classList.remove('overflow-hidden');
            }

            approveConfirmButton?.addEventListener('click', function () {
                const action = pendingButton?.value;

                if (!action) {
                    closeTravelApproveConfirmModal();
                    return;
                }

                confirmedAction = action;
                closeTravelApproveConfirmModal();
                form?.requestSubmit(action === 'approved' ? approveButton : rejectButton);
            });

            approveCancelButton?.addEventListener(
                'click',
                closeTravelApproveConfirmModal
            );

            approveBackdrop?.addEventListener(
                'click',
                closeTravelApproveConfirmModal
            );

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeTravelApproveConfirmModal();
                }
            });
        });
    </script>
</x-app-layout>
