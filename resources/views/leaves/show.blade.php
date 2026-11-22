<x-app-layout>
    <x-slot name="title">{{ __('Leave Request Details') }}</x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            {{-- Back and Print buttons --}}
            <div class="mb-5 flex items-center justify-between gap-3">
                <x-page-action href="{{ route('leaves.index') }}" />

                @if($leaf->canBeEditedBy(auth()->user()->employee))
                    <x-page-action href="{{ route('leaves.edit', $leaf) }}" kind="edit" class="ml-auto" />
                @endif
                {{-- Prints only the form inside the iframe --}}
                <button
                    type="button"
                    onclick="printLeaveForm()"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700"
                >
                    <svg
                        class="mr-2 h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"
                        ></path>
                    </svg>

                    {{ __('Print Leave Form') }}
                </button>
            </div>

            @include('leaves._monetization-summary', ['leaveRequest' => $leaf])
            {{-- Approval tracker --}}
            @php
                $leaveName = Str::of($leaf->leaveType?->name ?? 'Leave')
                    ->replaceMatches('/\s+Leave\b/i', '')
                    ->trim();

                $trackedLeavePayload = [
                    'id' => (string) $leaf->id,
                    'title' => (string) $leaveName,
                    'type' => (string) $leaveName,

                    'stages' => [
                        [
                            'label' => 'HR',
                            'status' => $leaf->hrstaff_status ?: 'pending',
                        ],
                        [
                            'label' => 'Chief',
                            'status' => $leaf->chief_status ?: 'pending',
                        ],
                        [
                            'label' => 'Regional Director',
                            'status' => $leaf->rd_status ?: 'pending',
                        ],
                    ],
                ];
            @endphp

            <x-approval-tracker
                :payload="$trackedLeavePayload"
                event="leave-selected"
                empty="No leave approval process to track yet."
                compact
            />

            <x-approver-remarks :reviews="[
                ['label' => __('HR Staff'), 'comment' => $leaf->hrstaff_remarks],
                ['label' => __('Chief'), 'comment' => $leaf->chief_remarks],
                ['label' => __('Regional Director'), 'comment' => $leaf->rd_remarks],
            ]" />

            {{-- Full A4 leave form preview --}}
            <section
                class="mt-8 rounded-[2rem] border border-gray-100 bg-white p-3 shadow-sm sm:p-5"
            >
                <div
                    class="mx-auto w-full max-w-[1100px] overflow-hidden rounded-2xl border border-gray-200 bg-slate-200"
                >
                    <iframe
                        id="leave-form-preview"
                        src="{{ route('leaves.print', [
                            'leaf' => $leaf->id,
                            'preview' => 1,
                        ]) }}"
                        class="block w-full border-0 bg-white"
                        style="aspect-ratio: 210 / 297;"
                        title="{{ __('Leave form print preview') }}"
                        scrolling="no"
                    ></iframe>
                </div>
            </section>

            @include('leaves._attachment-preview', ['leaveRequest' => $leaf])
        </div>
    </div>

    <script>
        function printLeaveForm() {
            const previewIframe = document.getElementById(
                'leave-form-preview'
            );

            if (!previewIframe) {
                alert('Leave form preview was not found.');
                return;
            }

            try {
                const previewWindow = previewIframe.contentWindow;

                if (!previewWindow) {
                    alert('Leave form preview is not ready yet.');
                    return;
                }

                previewWindow.focus();
                previewWindow.print();
            } catch (error) {
                console.error(
                    'Unable to print the leave form:',
                    error
                );

                alert(
                    'Unable to open the print dialog. Please refresh the page and try again.'
                );
            }
        }
    </script>
</x-app-layout>
