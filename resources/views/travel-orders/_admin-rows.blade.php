@forelse($orders as $order)
    @php
        $employeeName = trim(
            ($order->employee?->firstname ?? '') . ' ' .
            ($order->employee?->lastname ?? '')
        );

        $recordStatus = strtolower(
            (string) ($order->recordofficer_status ?: 'pending')
        );

        $rdStatus = strtolower(
            (string) ($order->rd_status ?: 'pending')
        );

        $mainStatus = strtolower(
            (string) ($order->status ?: 'pending')
        );

        $tarDeadline = $order->tar_deadline
            ?: optional($order->travel_date_end)->copy()?->addDays(5);

        $computedTarStatus = strtolower(
            (string) ($order->tar_status ?: 'pending')
        );

        if (
            $computedTarStatus !== 'submitted' &&
            $mainStatus === 'approved' &&
            $tarDeadline &&
            now()->gt($tarDeadline)
        ) {
            $computedTarStatus = 'overdue';
        }

        if (
            $mainStatus !== 'approved' &&
            $computedTarStatus === 'pending'
        ) {
            $computedTarStatus = 'not_applicable';
        }

        $acknowledgementReportUrl = $order->tar_attachment_path
            ? route('travel-orders.ar.file', $order)
            : route('travel-orders.ar.print', $order);

        $recordsOfficerName = $order->recordsOfficer
            ? trim(
                ($order->recordsOfficer->firstname ?? '') . ' ' .
                ($order->recordsOfficer->lastname ?? '')
            )
            : '';

        $regionalDirectorName = $order->regionalDirector
            ? trim(
                ($order->regionalDirector->firstname ?? '') . ' ' .
                ($order->regionalDirector->lastname ?? '')
            )
            : '';

        $taNumber = $order->ta_number
            ?: 'TA-' .
                optional($order->created_at)->format('Y-m-d') .
                '-' .
                str_pad(
                    (string) $order->id,
                    3,
                    '0',
                    STR_PAD_LEFT
                );

        $searchText = strtolower(
            implode(
                ' ',
                array_filter([
                    $taNumber,
                    $employeeName,
                    $order->places_of_travel,
                    $recordsOfficerName,
                    $regionalDirectorName,
                    $mainStatus,
                    $computedTarStatus,
                ])
            )
        );

        $role = strtolower((string) auth()->user()->role);

        $canRecordsReview =
            $role === 'recordofficer' &&
            $mainStatus === 'pending' &&
            $recordStatus === 'pending';

        $canDirectorReview =
            $role === 'regionaldirector' &&
            $recordStatus === 'approved' &&
            $rdStatus === 'pending';

        /*
         * This determines which Alpine table collection owns the row.
         *
         * review = Pending tab
         * view   = All tab
         */
        $rowSection = ($actionMode ?? 'view') === 'review'
            ? 'pending'
            : 'all';

        $previewPayload = [
            'title' => $taNumber,
            'date' => $order->created_at?->format('M d, Y') ?? 'N/A',
            'status' => $mainStatus,
            'remarks' => trim(
                collect([
                    $order->rd_remarks,
                    $order->recordofficer_remarks,
                    $order->notes_remarks,
                ])
                    ->filter()
                    ->implode(' ')
            ) ?: 'No remarks available.',
            'printUrl' =>
                route('travel-orders.print', $order) .
                '#toolbar=0&navpanes=0&scrollbar=0',
            'directPrintUrl' =>
                route('travel-orders.print', $order),
        ];

        $statusClasses = [
            'pending' =>
                'border-orange-100 bg-orange-50 text-orange-700',
            'approved' =>
                'border-green-100 bg-green-50 text-green-700',
            'rejected' =>
                'border-red-100 bg-red-50 text-red-700',
        ];
    @endphp

    <tr
        class="transition hover:bg-blue-50/40"
        data-manage-travel-row="{{ $rowSection }}"
        data-search="{{ $searchText }}"
        data-type="{{ $order->travel_type }}"
        data-employee="{{ strtolower($employeeName) }}"
        data-filed="{{ $order->created_at?->timestamp ?? 0 }}"
    >
        {{-- Date filed --}}
        <td class="px-3 py-3 align-top text-xs text-gray-700">
            {{ $order->created_at?->format('M d, Y') ?? __('N/A') }}
        </td>

        {{-- TA stamp --}}
        <td class="break-words px-3 py-3 align-top text-xs font-bold text-gray-900">
            {{ $taNumber }}
        </td>

        {{-- Employee --}}
        <td class="break-words px-3 py-3 align-top text-xs font-semibold text-gray-900">
            {{ $employeeName ?: __('N/A') }}
        </td>

        {{-- Date of travel --}}
        <td class="px-3 py-3 align-top text-xs text-gray-700">
            <div>
                {{ $order->travel_date_start?->format('M d, Y') ?? __('N/A') }}
            </div>

            @if(
                $order->travel_date_end &&
                $order->travel_date_start &&
                $order->travel_date_end->ne($order->travel_date_start)
            )
                <div>
                    {{ $order->travel_date_end->format('M d, Y') }}
                </div>
            @endif
        </td>

        {{-- Destination --}}
        <td class="break-words px-3 py-3 align-top text-xs font-semibold text-gray-900">
            {{ $order->places_of_travel ?: __('N/A') }}
        </td>

        {{-- Record officer --}}
        {{-- Record officer status --}}
<td class="px-3 py-3 align-top">
    <div class="flex justify-center">
        <span
            class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full
                {{ $recordStatus === 'approved'
                    ? 'bg-green-500'
                    : ($recordStatus === 'rejected'
                        ? 'bg-red-500'
                        : 'bg-gray-300') }}"
            title="{{ ucfirst($recordStatus) }}"
        ></span>
    </div>
</td>

        {{-- Regional director status --}}
<td class="px-3 py-3 align-top">
    <div class="flex justify-center">
        <span
            class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full
                {{ $rdStatus === 'approved'
                    ? 'bg-green-500'
                    : ($rdStatus === 'rejected'
                        ? 'bg-red-500'
                        : 'bg-gray-300') }}"
            title="{{ ucfirst($rdStatus) }}"
        ></span>
    </div>
</td>
        {{-- Main status --}}
        <td class="px-3 py-3 align-top">
            <span
                class="inline-flex rounded-full border px-2 py-1 text-[11px] font-bold uppercase
                    {{ $statusClasses[$mainStatus]
                        ?? 'border-gray-100 bg-gray-50 text-gray-700' }}"
            >
                {{ ucfirst($mainStatus) }}
            </span>
        </td>

        {{-- TAR deadline --}}
        <td class="px-3 py-3 align-top text-xs text-gray-700">
            {{ $tarDeadline?->format('M d, Y') ?? __('N/A') }}
        </td>

        {{-- AR status --}}
        <td class="px-3 py-3 align-top text-xs font-semibold">
            @if($computedTarStatus === 'submitted')
                <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-100 bg-emerald-50 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-700">
                    <i class="fa-solid fa-file-lines"></i>
                    <span>{{ __('Submitted') }}</span>
                </span>
            @elseif($computedTarStatus === 'overdue')
                <span class="inline-flex items-center gap-1.5 rounded-full border border-red-100 bg-red-50 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-red-700">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>{{ __('Overdue') }}</span>
                </span>
            @elseif($computedTarStatus === 'not_applicable')
                <span class="text-gray-400">—</span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-100 bg-slate-50 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-slate-600">
                    <i class="fa-regular fa-clock"></i>
                    <span>{{ __('Pending') }}</span>
                </span>
            @endif
        </td>

        {{-- Actions --}}
        <td class="px-3 py-3 text-center align-top">
            <div class="mx-auto grid w-fit grid-cols-2 place-items-center gap-x-2 gap-y-1">
                <a
                    href="{{ route('travel-orders.show', $order) }}"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-indigo-700 transition hover:bg-indigo-50 hover:text-indigo-900"
                    title="{{ __('View') }}"
                    aria-label="{{ __('View travel authority') }}"
                >
                    <i class="fa-solid fa-eye text-sm"></i>
                </a>

            

                <a
                    href="{{ route('travel-orders.print', $order) }}"
                    target="_blank"
                    rel="noopener"
                    data-no-transition
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-700 transition hover:bg-slate-50 hover:text-slate-900"
                    title="{{ __('Print') }}"
                    aria-label="{{ __('Print travel authority') }}"
                >
                    <i class="fa-solid fa-print text-sm"></i>
                </a>

                @if($computedTarStatus === 'submitted')
                    <a
                        href="{{ $acknowledgementReportUrl }}"
                        target="_blank"
                        rel="noopener"
                        data-no-transition
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-blue-700 transition hover:bg-blue-50 hover:text-blue-900"
                        title="{{ __('View Acknowledgement Report') }}"
                        aria-label="{{ __('View Acknowledgement Report') }}"
                    >
                        <i class="fa-solid fa-file-circle-check text-sm"></i>
                    </a>
                @endif

                @if(
                    ($actionMode ?? 'view') === 'review' &&
                    ($canRecordsReview || $canDirectorReview)
                )
                    <form
                        action="{{ route('travel-orders.update-status', $order->id) }}"
                        method="POST"
                        id="travelApproveForm_{{ $order->id }}"
                        class="m-0 flex h-8 w-8 items-center justify-center"
                    >
                        @csrf
                        @method('PUT')

                        <input
                            type="hidden"
                            name="status"
                            value="approved"
                        >

                        <button
                            type="button"
                            onclick="openTravelApproveModal({{ $order->id }}, @js($taNumber))"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-green-600 transition hover:bg-green-50 hover:text-green-800"
                            title="{{ __('Approve') }}"
                            aria-label="{{ __('Approve travel authority') }}"
                        >
                            <i class="fa-solid fa-check text-sm"></i>
                        </button>
                    </form>

                    <form
                        action="{{ route('travel-orders.update-status', $order->id) }}"
                        method="POST"
                        id="travelRejectForm_{{ $order->id }}"
                        class="m-0 flex h-8 w-8 items-center justify-center"
                    >
                        @csrf
                        @method('PUT')

                        <input
                            type="hidden"
                            name="status"
                            value="rejected"
                        >

                        <input
                            type="hidden"
                            name="remarks"
                            id="travelRejectRemarks_{{ $order->id }}"
                            value=""
                        >

                        <button
                            type="button"
                            onclick="submitTravelReject(this)"
                            data-travel-id="{{ $order->id }}"
                            data-travel-title="{{ $taNumber }}"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-600 transition hover:bg-red-50 hover:text-red-800"
                            title="{{ __('Reject') }}"
                            aria-label="{{ __('Reject travel authority') }}"
                        >
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </form>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td
            colspan="11"
            class="px-6 py-8 text-center text-sm text-gray-500"
        >
            {{ $emptyMessage }}
        </td>
    </tr>
@endforelse
