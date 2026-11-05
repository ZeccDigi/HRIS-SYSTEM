@forelse($orders as $order)
    @php
        $employeeName = trim(($order->employee?->firstname ?? '') . ' ' . ($order->employee?->lastname ?? '')) ?: 'N/A';
        $reviewStage = $order->tar_review_status ?: 'pending_hr';
        $stageLabels = [
            'pending_hr' => 'Pending HR',
            'pending_chief' => 'Pending Division Chief',
            'pending_rd' => 'Pending Regional Director',
            'approved' => 'Approved',
            'returned' => 'Returned',
        ];
        $searchText = strtolower(implode(' ', array_filter([
            $order->ta_number,
            $employeeName,
            $order->employee?->division,
            $order->places_of_travel,
            $stageLabels[$reviewStage] ?? $reviewStage,
        ])));
    @endphp
    <tr
        class="transition hover:bg-blue-50/40"
        data-manage-travel-row="reports"
        data-search="{{ $searchText }}"
        data-type="{{ $order->travel_type }}"
        data-filed="{{ $order->tar_submitted_at?->timestamp ?? $order->created_at?->timestamp ?? 0 }}"
    >
        <td class="px-3 py-3 align-middle text-xs text-slate-600">{{ $order->tar_submitted_at?->format('M d, Y') ?? 'N/A' }}</td>
        <td class="px-3 py-3 align-middle text-xs font-bold text-slate-900">{{ $order->ta_number }}</td>
        <td class="px-3 py-3 align-middle text-xs font-semibold text-slate-900">{{ $employeeName }}</td>
        <td class="px-3 py-3 align-middle text-xs text-slate-600">{{ $order->employee?->division_abbreviation ?: $order->employee?->division ?: 'N/A' }}</td>
        <td class="px-3 py-3 align-middle text-xs text-slate-600">{{ $order->tar_deadline?->format('M d, Y') ?? 'N/A' }}</td>
        <td class="px-3 py-3 align-middle">
            <span class="inline-flex rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-wider {{ $reviewStage === 'approved' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ($reviewStage === 'returned' ? 'border-red-200 bg-red-50 text-red-700' : 'border-amber-200 bg-amber-50 text-amber-700') }}">
                {{ $stageLabels[$reviewStage] ?? ucfirst(str_replace('_', ' ', $reviewStage)) }}
            </span>
        </td>
        <td class="px-3 py-3 text-center align-middle">
            <a href="{{ route('travel-orders.ar.show', $order) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-indigo-700 transition hover:bg-indigo-50" title="{{ __('Open and review report') }}" aria-label="{{ __('Open and review report') }}">
                <i class="fa-solid fa-eye"></i>
            </a>
        </td>
    </tr>
@empty
    <tr><td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">{{ __('No TAR records have been submitted.') }}</td></tr>
@endforelse
