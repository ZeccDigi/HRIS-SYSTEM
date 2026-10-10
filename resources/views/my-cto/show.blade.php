<x-app-layout>
    <x-slot name="title">{{ __('CTO Request Details') }}</x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @php
                $role = strtolower(auth()->user()->role ?? '');
                $isApprover = in_array($role, ['admin', 'hrstaff', 'hr staff', 'chief', 'regionaldirector'], true);
                $isHR = in_array($role, ['admin', 'hrstaff', 'hr staff'], true);
                $isChief = $role === 'chief';
                $isRegionalDirector = $role === 'regionaldirector';

                $trackedEmployee = trim(($ctoRequest->employee?->firstname ?? '') . ' ' . ($ctoRequest->employee?->lastname ?? ''));
                $trackedCtoPayload = [
                    'id' => (string) $ctoRequest->id,
                    'title' => $isApprover ? $trackedEmployee : (string) $ctoRequest->type_label,
                    'employee' => $trackedEmployee,
                    'type' => (string) $ctoRequest->type_label,
                    'stages' => [
                        ['label' => 'Human Resource', 'status' => $ctoRequest->hrstaff_status ?: 'pending'],
                        ['label' => 'Chief', 'status' => $ctoRequest->chief_status ?: 'pending'],
                        ['label' => 'Regional Director', 'status' => $ctoRequest->rd_status ?: 'pending'],
                    ],
                ];

                $isMyTurn = false;
                $waitingMessage = '';

                if ($ctoRequest->status === 'pending') {
                    if ($isHR && $ctoRequest->hrstaff_status === 'pending') {
                        $isMyTurn = true;
                    } elseif ($isChief && $ctoRequest->chief_status === 'pending') {
                        if ($ctoRequest->hrstaff_status === 'approved') {
                            $isMyTurn = true;
                        } else {
                            $waitingMessage = 'Waiting for HR verification.';
                        }
                    } elseif ($isRegionalDirector && $ctoRequest->rd_status === 'pending') {
                        if ($ctoRequest->chief_status === 'approved') {
                            $isMyTurn = true;
                        } else {
                            $waitingMessage = 'Waiting for Chief approval.';
                        }
                    }
                }
            @endphp

            <div class="mb-5 flex items-center justify-between gap-3">
                <a href="{{ $isApprover ? route('hr.cto.index') : route('my-cto.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition-colors hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ __('Back') }}
                </a>
                <button type="button" onclick="printCtoForm()" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700">
                    {{ __('Print CTO') }}
                </button>
            </div>

            <x-approval-tracker :payload="$trackedCtoPayload" event="cto-selected" empty="No CTO request to track yet." />

            {{-- Attachment --}}
            @if($ctoRequest->attachment_path)
                <section class="mt-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <span class="block text-[10px] font-black uppercase tracking-widest text-gray-400">
                                {{ __('Attached File') }}
                            </span>

                            <p class="mt-1 text-sm font-semibold text-gray-700">
                                {{ __('Supporting document submitted with this CTO request.') }}
                            </p>
                        </div>

                        <a
                            href="{{ asset('storage/' . $ctoRequest->attachment_path) }}"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex shrink-0 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-black uppercase tracking-widest text-white transition hover:bg-indigo-700"
                        >
                            <i class="fa-solid fa-paperclip mr-2"></i>
                            {{ __('View Attachment') }}
                        </a>
                    </div>
                </section>
            @endif

            {{-- Full CTO Form --}}
            <section class="mt-6 overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm">
                <div class="bg-slate-100 px-3 pb-3 pt-6 sm:px-5 sm:pb-5 sm:pt-8">
                    <div class="mx-auto w-full max-w-[1100px] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <iframe
                            id="ctoPreviewFrame"
                            src="{{ route('my-cto.print', [
                                'ctoRequest' => $ctoRequest->id,
                                'preview' => 1
                            ]) }}"
                            class="block w-full border-0 bg-white"
                            style="height: 1300px;"
                            scrolling="no"
                            title="{{ __('CTO form print preview') }}"
                        ></iframe>
                    </div>
                </div>
            </section>

            {{-- Review Action Below Form --}}
            <section class="mt-6 rounded-3xl border border-gray-100 bg-white p-5 shadow-sm sm:p-7">
                @if($isMyTurn)
                    <div class="mb-6 flex flex-col gap-3 border-b border-gray-100 pb-5 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-indigo-500">
                                {{ __('Current Review Stage') }}
                            </p>

                            <h3 class="mt-1 text-xl font-black text-gray-900">
                                {{ $isHR
                                    ? __('Verify CTO Request')
                                    : __('Review CTO Request') }}
                            </h3>
                        </div>

                        <div class="inline-flex shrink-0 items-center gap-2 rounded-full bg-orange-50 px-3 py-2">
                            <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-orange-400"></span>

                            <span class="text-[10px] font-black uppercase tracking-widest text-orange-600">
                                {{ __('Your Review Needed') }}
                            </span>
                        </div>
                    </div>

                    <form
                        id="ctoStatusForm"
                        action="{{ route('cto.update-status', $ctoRequest->id) }}"
                        method="POST"
                    >
                        @csrf
                        @method('PUT')

                        {{-- Remarks --}}
                        <div>
                            <label
                                for="cto_remarks"
                                class="mb-2 block text-xs font-black uppercase tracking-widest text-gray-600"
                            >
                                {{ __('Remarks / Notes') }}
                            </label>

                            <textarea
                                id="cto_remarks"
                                name="remarks"
                                rows="4"
                                placeholder="{{ __('Enter remarks or notes here...') }}"
                                class="block w-full resize-y rounded-2xl border-gray-300 bg-gray-50 text-sm text-gray-800 shadow-sm transition focus:border-indigo-500 focus:bg-white focus:ring-indigo-500"
                            ></textarea>

                            <div class="mt-2 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-xs text-gray-400">
                                    {{ __('Remarks are required when rejecting the request.') }}
                                </p>

                                <span
                                    id="ctoRemarksError"
                                    class="hidden text-xs font-semibold text-red-600"
                                ></span>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button
                                type="submit"
                                id="ctoRejectButton"
                                name="status"
                                value="rejected"
                                class="inline-flex min-w-44 items-center justify-center rounded-xl border border-red-300 bg-white px-6 py-3 text-xs font-black uppercase tracking-widest text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2"
                            >
                                <i class="fa-solid fa-xmark mr-2"></i>
                                {{ __('Reject Request') }}
                            </button>

                            <button
                                type="submit"
                                id="ctoApproveButton"
                                name="status"
                                value="approved"
                                class="inline-flex min-w-44 items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-xs font-black uppercase tracking-widest text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                <i class="fa-solid fa-check mr-2"></i>

                                {{ $isHR
                                    ? __('Verify Request')
                                    : __('Approve Request') }}
                            </button>
                        </div>
                    </form>

                    <div
                        id="ctoDecisionModal"
                        class="fixed inset-0 z-[100000] hidden items-center justify-center p-4"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="ctoDecisionModalTitle"
                    >
                        <div
                            id="ctoDecisionModalBackdrop"
                            class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                        ></div>

                        <div class="relative z-10 w-full max-w-xs overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
                            <div class="border-b border-slate-100 px-4 py-3">
                                <h2
                                    id="ctoDecisionModalTitle"
                                    class="text-base font-black text-slate-900"
                                ></h2>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ __('ID: :id', ['id' => $ctoRequest->id]) }}
                                </p>
                            </div>

                            <div class="px-4 py-3 text-center">
                                <p
                                    id="ctoDecisionModalDescription"
                                    class="whitespace-pre-line text-xs leading-5 text-slate-600"
                                ></p>
                            </div>

                            <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50 px-4 py-3 sm:flex-row sm:justify-end">
                                <button
                                    type="button"
                                    id="ctoDecisionCancelButton"
                                    class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-[11px] font-black uppercase tracking-widest text-slate-600 transition hover:bg-slate-100"
                                >
                                    {{ __('Cancel') }}
                                </button>

                                <button
                                    type="button"
                                    id="ctoDecisionConfirmButton"
                                    class="inline-flex items-center justify-center rounded-lg bg-blue-900 px-3 py-2 text-[11px] font-black uppercase tracking-widest text-white transition hover:bg-blue-800"
                                ></button>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-start gap-4">
                            <div class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                <i class="fa-solid fa-lock"></i>
                            </div>

                            <div>
                                <h3 class="text-sm font-black uppercase tracking-widest text-gray-700">
                                    {{ __('Review Action Unavailable') }}
                                </h3>

                                <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500">
                                    @if($waitingMessage)
                                        {{ $waitingMessage }}
                                    @else
                                        {{ __('This CTO request has already been processed or is currently assigned to another approval stage.') }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="inline-flex shrink-0 items-center gap-2 rounded-full px-3 py-2
                            {{ $waitingMessage ? 'bg-yellow-50' : 'bg-green-50' }}">
                            @if($waitingMessage)
                                <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-yellow-400"></span>

                                <span class="text-[10px] font-black uppercase tracking-widest text-yellow-600">
                                    {{ __('In Progress') }}
                                </span>
                            @else
                                <span class="h-2.5 w-2.5 rounded-full bg-green-500"></span>

                                <span class="text-[10px] font-black uppercase tracking-widest text-green-600">
                                    {{ __('CTO Request Processed') }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            </section>

            @if($ctoRequest->hrstaff_remarks || $ctoRequest->chief_remarks || $ctoRequest->rd_remarks)
                <section class="mt-6 overflow-hidden rounded-3xl border border-gray-100 bg-white p-6 shadow-sm md:p-8">
                    <div class="text-xs font-black text-indigo-500 uppercase tracking-widest mb-6 inline-block border-b-2 border-indigo-100 pb-1">Approver Remarks</div>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        @if($ctoRequest->hrstaff_remarks)
                            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-5">
                                <span class="block text-[10px] font-black uppercase tracking-widest text-gray-400">HR Staff</span>
                                <p class="mt-2 whitespace-pre-line break-words text-sm font-semibold italic leading-relaxed text-gray-700">"{{ $ctoRequest->hrstaff_remarks }}"</p>
                            </div>
                        @endif
                        @if($ctoRequest->chief_remarks)
                            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-5">
                                <span class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Chief</span>
                                <p class="mt-2 whitespace-pre-line break-words text-sm font-semibold italic leading-relaxed text-gray-700">"{{ $ctoRequest->chief_remarks }}"</p>
                            </div>
                        @endif
                        @if($ctoRequest->rd_remarks)
                            <div class="rounded-2xl border border-gray-100 bg-gray-50 p-5">
                                <span class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Regional Director</span>
                                <p class="mt-2 whitespace-pre-line break-words text-sm font-semibold italic leading-relaxed text-gray-700">"{{ $ctoRequest->rd_remarks }}"</p>
                            </div>
                        @endif
                    </div>
                </section>
            @endif
        </div>
    </div>

    <script>
        function printCtoForm() {
            const previewIframe = document.getElementById('ctoPreviewFrame');

            if (!previewIframe) {
                alert('CTO form preview was not found.');
                return;
            }

            try {
                const previewWindow = previewIframe.contentWindow;

                if (!previewWindow) {
                    alert('CTO form preview is not ready yet.');
                    return;
                }

                previewWindow.focus();
                previewWindow.print();
            } catch (error) {
                console.error('Unable to print the CTO form:', error);
                alert('Unable to open the print dialog. Please refresh the page and try again.');
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const iframe = document.getElementById('ctoPreviewFrame');
            const form = document.getElementById('ctoStatusForm');
            const remarksField = document.getElementById('cto_remarks');
            const errorSpan = document.getElementById('ctoRemarksError');
            const approveButton = document.getElementById('ctoApproveButton');
            const rejectButton = document.getElementById('ctoRejectButton');
            const decisionModal = document.getElementById('ctoDecisionModal');
            const decisionBackdrop = document.getElementById('ctoDecisionModalBackdrop');
            const decisionTitle = document.getElementById('ctoDecisionModalTitle');
            const decisionDescription = document.getElementById('ctoDecisionModalDescription');
            const decisionConfirmButton = document.getElementById('ctoDecisionConfirmButton');
            const decisionCancelButton = document.getElementById('ctoDecisionCancelButton');
            let confirmedAction = null;
            let pendingButton = null;

            function resizeCtoPreview() {
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

                    const html = iframeDocument.documentElement;
                    const body = iframeDocument.body;

                    if (html) {
                        html.style.overflow = 'hidden';
                    }

                    if (body) {
                        body.style.overflow = 'hidden';
                        body.style.margin = '0';
                    }

                    const fullHeight = Math.max(
                        body?.scrollHeight || 0,
                        body?.offsetHeight || 0,
                        html?.clientHeight || 0,
                        html?.scrollHeight || 0,
                        html?.offsetHeight || 0,
                        1300
                    );

                    iframe.style.height = `${fullHeight + 10}px`;
                } catch (error) {
                    console.warn(
                        'Unable to resize the CTO preview.',
                        error
                    );
                }
            }

            iframe?.addEventListener('load', function () {
                resizeCtoPreview();

                setTimeout(resizeCtoPreview, 300);
                setTimeout(resizeCtoPreview, 800);
                setTimeout(resizeCtoPreview, 1500);
            });

            window.addEventListener(
                'resize',
                resizeCtoPreview
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
                const action = event.submitter?.value;
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
                            'Remarks are required when rejecting this request.';
                        errorSpan.classList.remove('hidden');
                    }

                    remarksField?.focus();
                    return;
                }

                if ((action === 'approved' || action === 'rejected') && confirmedAction !== action) {
                    event.preventDefault();
                    pendingButton = action === 'approved' ? approveButton : rejectButton;
                    openDecisionModal(action);
                }
            });

            function openDecisionModal(action) {
                if (!decisionModal) {
                    confirmedAction = action;
                    form?.requestSubmit(pendingButton);
                    return;
                }

                if (decisionTitle) {
                    decisionTitle.textContent = action === 'approved'
                        ? 'Approve this CTO request?'
                        : 'Reject this CTO request?';
                }

                if (decisionDescription) {
                    decisionDescription.textContent = action === 'approved'
                        ? 'Approving will move this request to the next step\nor mark it as fully approved.'
                        : 'Rejecting will mark this request as rejected.';
                }

                if (decisionConfirmButton) {
                    decisionConfirmButton.textContent = action === 'approved'
                        ? 'Approve'
                        : 'Reject';
                }

                decisionModal.classList.remove('hidden');
                decisionModal.classList.add('flex');
                document.documentElement.classList.add('overflow-hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeDecisionModal() {
                decisionModal?.classList.add('hidden');
                decisionModal?.classList.remove('flex');
                pendingButton = null;
                document.documentElement.classList.remove('overflow-hidden');
                document.body.classList.remove('overflow-hidden');
            }

            decisionConfirmButton?.addEventListener('click', function () {
                const action = pendingButton?.value;

                if (!action) {
                    closeDecisionModal();
                    return;
                }

                confirmedAction = action;
                closeDecisionModal();
                form?.requestSubmit(action === 'approved' ? approveButton : rejectButton);
            });

            decisionCancelButton?.addEventListener('click', closeDecisionModal);
            decisionBackdrop?.addEventListener('click', closeDecisionModal);

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeDecisionModal();
                }
            });
        });
    </script>
</x-app-layout>
