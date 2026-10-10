@forelse($requests as $request)
    @php
        $statusColors = [
            'pending' => 'border-orange-100 bg-orange-50 text-orange-700',
            'approved' => 'border-green-100 bg-green-50 text-green-600',
            'rejected' => 'border-red-100 bg-red-50 text-red-600',
        ];

        $requestStatus = strtolower(
            (string) ($request->status ?: 'pending')
        );

        $statusColor = $statusColors[$requestStatus]
            ?? 'border-gray-100 bg-gray-50 text-gray-600';

        $typeColors = [
            'earn' => 'border-emerald-100 bg-emerald-50 text-emerald-600',
            'use' => 'border-blue-100 bg-blue-50 text-blue-600',
        ];

        $requestType = strtolower(
            (string) ($request->type ?? '')
        );

        $typeColor = $typeColors[$requestType]
            ?? 'border-gray-100 bg-gray-50 text-gray-600';

        $employeeName = trim(
            ($request->employee?->firstname ?? '') . ' ' .
            ($request->employee?->lastname ?? '')
        );

        $searchText = Str::lower(
            implode(' ', array_filter([
                $employeeName,
                $request->employee?->division,
                $request->type_label,
                $request->purpose,
                $requestStatus,
            ]))
        );

        $hrStatus = strtolower(
            (string) ($request->hrstaff_status ?: 'pending')
        );

        $chiefStatus = strtolower(
            (string) ($request->chief_status ?: 'pending')
        );

        $rdStatus = strtolower(
            (string) ($request->rd_status ?: 'pending')
        );

        $approvalDotClass = fn (string $status) => match ($status) {
            'approved' => 'bg-green-500',
            'rejected' => 'bg-red-500',
            default => 'bg-gray-300',
        };

        /*
         * review = Pending CTO table
         * view   = All CTO table
         */
        $rowSection = ($actionMode ?? 'view') === 'review'
            ? 'pending'
            : 'all';

        $role = strtolower(
            (string) (auth()->user()->role ?? '')
        );

        $isHR = in_array(
            $role,
            ['hrstaff', 'hr staff', 'admin'],
            true
        );

        $previewPayload = [
            'title' => (string) $request->type_label,
            'date' => $request->created_at?->format('M d, Y') ?? 'N/A',
            'status' => $requestStatus,
            'remarks' => trim(
                collect([
                    $request->rd_remarks,
                    $request->chief_remarks,
                    $request->hrstaff_remarks,
                ])
                    ->filter()
                    ->implode(' ')
            ) ?: 'No remarks available.',
            'printUrl' => route('my-cto.print', [
                'ctoRequest' => $request->id,
                'preview' => 1,
            ]),
            'directPrintUrl' => route(
                'my-cto.print',
                $request->id
            ),
        ];
    @endphp

    <tr
        class="transition-colors hover:bg-gray-50/70"
        data-approval-row="{{ $request->id }}"
        data-manage-cto-row="{{ $rowSection }}"
        data-search="{{ $searchText }}"
        data-type="{{ $requestType }}"
        data-status="{{ $requestStatus }}"
        data-employee="{{ Str::lower($employeeName) }}"
        data-filed="{{ $request->created_at?->timestamp ?? 0 }}"
    >
        {{-- Date Filed --}}
        <td class="px-2 py-3 align-middle">
            <div class="min-w-0 whitespace-normal break-words text-sm font-medium leading-5 text-gray-700">
                {{ $request->created_at?->format('M d, Y') ?? __('N/A') }}
            </div>
        </td>

        {{-- Employee: name only --}}
        <td class="px-2 py-3 align-middle">
            <div
                class="min-w-0 whitespace-normal break-words [overflow-wrap:anywhere] text-sm font-bold leading-5 text-gray-900"
                title="{{ $employeeName }}"
            >
                {{ $employeeName ?: __('N/A') }}
            </div>
        </td>

        {{-- Type --}}
        <td class="px-2 py-3 align-middle">
            <span
                class="inline-flex max-w-full whitespace-normal break-words rounded-full border px-2 py-1 text-center text-[10px] font-black uppercase leading-4 tracking-wider {{ $typeColor }}"
            >
                {{ $request->type_label }}
            </span>
        </td>

        {{-- Status --}}
        <td class="px-2 py-3 text-center align-middle">
            <span
                class="inline-flex max-w-full whitespace-normal break-words rounded-full border px-2 py-1 text-center text-[10px] font-bold uppercase leading-4 {{ $statusColor }}"
            >
                {{ ucfirst($requestStatus) }}
            </span>
        </td>

        {{-- Human Resource status: circle only --}}
        <td class="px-2 py-3 text-center align-middle">
            <div class="flex items-center justify-center">
                <span
                    class="h-2.5 w-2.5 shrink-0 rounded-full {{ $approvalDotClass($hrStatus) }}"
                    title="{{ ucfirst($hrStatus) }}"
                    aria-label="{{ __('Human Resource status: :status', [
                        'status' => ucfirst($hrStatus),
                    ]) }}"
                ></span>
            </div>
        </td>

        {{-- Chief status: circle only --}}
        <td class="px-2 py-3 text-center align-middle">
            <div class="flex items-center justify-center">
                <span
                    class="h-2.5 w-2.5 shrink-0 rounded-full {{ $approvalDotClass($chiefStatus) }}"
                    title="{{ ucfirst($chiefStatus) }}"
                    aria-label="{{ __('Chief status: :status', [
                        'status' => ucfirst($chiefStatus),
                    ]) }}"
                ></span>
            </div>
        </td>

        {{-- Regional Director status: circle only --}}
        <td class="px-2 py-3 text-center align-middle">
            <div class="flex items-center justify-center">
                <span
                    class="h-2.5 w-2.5 shrink-0 rounded-full {{ $approvalDotClass($rdStatus) }}"
                    title="{{ ucfirst($rdStatus) }}"
                    aria-label="{{ __('Regional Director status: :status', [
                        'status' => ucfirst($rdStatus),
                    ]) }}"
                ></span>
            </div>
        </td>

        {{-- Actions: 2 × 2 grid --}}
        <td class="px-1 py-3 text-center align-middle">
            <div class="mx-auto grid w-fit grid-cols-2 place-items-center gap-1">

                {{-- View --}}
                <a
                    href="{{ route('my-cto.show', $request) }}"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-indigo-700 transition-colors hover:bg-indigo-50 hover:text-indigo-900"
                    title="{{ __('View details') }}"
                    aria-label="{{ __('View details') }}"
                >
                    <i class="fa-solid fa-eye text-sm"></i>
                </a>

                {{-- Print --}}
                <a
                    href="{{ route('my-cto.print', $request) }}"
                    target="_blank"
                    rel="noopener"
                    data-no-transition
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900"
                    title="{{ __('Print CTO') }}"
                    aria-label="{{ __('Print CTO') }}"
                >
                    <i class="fa-solid fa-print text-sm"></i>
                </a>

                @if(($actionMode ?? 'view') === 'review')
                    {{-- Verify or approve --}}
                    <form
                        action="{{ route('cto.update-status', $request->id) }}"
                        method="POST"
                        id="ctoApproveRowForm_{{ $request->id }}"
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
                            onclick="openCtoApproveModal(
                                {{ $request->id }},
                                @js('CTO-'.$request->id),
                                {{ $isHR ? 'true' : 'false' }}
                            )"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-green-600 transition-colors hover:bg-green-50 hover:text-green-800"
                            title="{{ $isHR ? __('Verify') : __('Approve') }}"
                            aria-label="{{ $isHR ? __('Verify') : __('Approve') }}"
                        >
                            <i class="fa-solid fa-check text-sm"></i>
                        </button>
                    </form>

                    {{-- Decline --}}
                    <form
                        action="{{ route('cto.update-status', $request->id) }}"
                        method="POST"
                        id="ctoRejectRowForm_{{ $request->id }}"
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
                            id="ctoRejectRowRemarks_{{ $request->id }}"
                            value=""
                        >

                        <button
                            type="button"
                            onclick="openCtoRejectModal(
                                {{ $request->id }},
                                @js('CTO-'.$request->id),
                                {{ $isHR ? 'true' : 'false' }}
                            )"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-600 transition-colors hover:bg-red-50 hover:text-red-800"
                            title="{{ __('Decline') }}"
                            aria-label="{{ __('Decline') }}"
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
            colspan="8"
            class="px-4 py-10 text-center text-sm font-medium italic text-gray-500"
        >
            {{ $emptyMessage }}
        </td>
    </tr>
@endforelse

@if(($actionMode ?? 'view') === 'review')
    @once
        <script>
            window.getCtoManagerData = function () {
                const root = document.querySelector(
                    '[x-data^="manageCtoRequests"]'
                );

                if (!root) {
                    return null;
                }

                if (window.Alpine && typeof window.Alpine.$data === 'function') {
                    return window.Alpine.$data(root);
                }

                return root.__x?.$data || root._x_dataStack?.[0] || null;
            };

            window.openCtoApproveModal = function (id, title, isHR) {
                const manager = window.getCtoManagerData();

                if (!manager) {
                    return;
                }

                manager.openApproveModal(id, title, isHR);
            };

            window.openCtoRejectModal = function (id, title, isHR) {
                const manager = window.getCtoManagerData();

                if (!manager) {
                    return;
                }

                manager.openRejectModal(id, title, isHR);
            };
        </script>
    @endonce
@endif
