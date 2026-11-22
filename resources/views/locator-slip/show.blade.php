<x-app-layout>
    <x-slot name="title">{{ __('Locator Slip Details') }}</x-slot>

    @php
        $displayStatus = strtolower((string) $locatorSlip->status) === 'approved by chief'
            ? 'approved'
            : strtolower((string) $locatorSlip->status);

        $trackedLocatorPayload = [
            'id' => (string) $locatorSlip->id,
            'title' => ($locatorSlip->type ?? '') === 'Personal'
                ? 'Pass Slip'
                : (string) ($locatorSlip->type ?: 'Locator Slip'),
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
                <x-page-action href="{{ route('locator-slips.index') }}" />

                <div class="flex items-center gap-2">
                    @if($locatorSlip->canBeEditedBy(auth()->user()->employee))
                        <x-page-action href="{{ route('locator-slips.edit', $locatorSlip) }}" kind="edit" />
                    @endif

                    <a
                        href="{{ route('locator-slips.print', $locatorSlip) }}"
                        target="_blank"
                        rel="noopener"
                        data-no-transition
                        class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700"
                    >
                        <i class="fa-solid fa-print mr-2"></i>
                        {{ __('Print Locator Slip') }}
                    </a>
                </div>
            </div>

            <x-approval-tracker
                :payload="$trackedLocatorPayload"
                event="locator-selected"
                empty="No locator slip approval process to track yet."
                compact
            />

            {{-- Locator slip form preview --}}
            <section class="mt-8 rounded-[2rem] border border-gray-100 bg-white p-3 shadow-sm sm:p-5">
                <div class="mx-auto w-full max-w-[900px] overflow-hidden rounded-2xl border border-gray-200 bg-slate-200">
                    <iframe
                        id="locator-form-preview"
                        src="{{ route('locator-slips.print', [
                            'locatorSlip' => $locatorSlip->id,
                            'preview' => 1,
                        ]) }}"
                        class="block w-full border-0 bg-white"
                        style="height: 1px;"
                        title="{{ __('Locator slip print preview') }}"
                        scrolling="no"
                        onload="resizeLocatorPreview(this)"
                    ></iframe>
                </div>
            </section>

            @php($chiefComment = \App\Support\ApproverRemark::visible($locatorSlip->chief_remarks))
            @if($chiefComment)
                <section class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm sm:p-6">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                            <i class="fa-solid fa-comment-dots"></i>
                        </div>

                        <div class="min-w-0">
                            <span class="text-[10px] font-black uppercase tracking-widest text-red-500">
                                {{ __('Chief Remarks') }}
                            </span>

                            <p class="mt-2 whitespace-pre-line break-words text-sm font-medium leading-relaxed text-red-800">
                                {{ $chiefComment }}
                            </p>
                        </div>
                    </div>
                </section>
            @endif
        </div>
    </div>

    <script>
        function resizeLocatorPreview(frame) {
            const previewSheet = frame.contentDocument?.querySelector('.preview-sheet');

            if (previewSheet) {
                frame.style.height = `${Math.ceil(previewSheet.getBoundingClientRect().height) + 2}px`;
            }
        }
    </script>
</x-app-layout>
