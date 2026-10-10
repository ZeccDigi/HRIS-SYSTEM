<x-app-layout>
    <x-slot name="title">{{ __('Acknowledgement Report') }}</x-slot>

    @php
        $role = strtolower(auth()->user()->role ?? '');
        $isApprover = in_array($role, ['admin', 'hrstaff', 'recordofficer', 'regionaldirector'], true);
        $employeeName = trim(($travelOrder->employee?->firstname ?? '') . ' ' . ($travelOrder->employee?->lastname ?? '')) ?: __('N/A');
        $taNumber = $travelOrder->ta_number ?: 'TA-' . optional($travelOrder->created_at)->format('Y-m-d') . '-' . str_pad((string) $travelOrder->id, 3, '0', STR_PAD_LEFT);
        $tarDeadline = $travelOrder->tar_deadline ?: optional($travelOrder->travel_date_end)->copy()?->addDays(5);
        $arStatus = strtolower((string) ($travelOrder->tar_status ?: 'pending'));

        if ($arStatus !== 'submitted' && strtolower((string) $travelOrder->status) === 'approved' && $tarDeadline && now()->gt($tarDeadline)) {
            $arStatus = 'overdue';
        }

        $statusClass = match ($arStatus) {
            'submitted' => 'border-emerald-100 bg-emerald-50 text-emerald-700',
            'overdue' => 'border-red-100 bg-red-50 text-red-700',
            default => 'border-slate-100 bg-slate-50 text-slate-600',
        };
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ $isApprover ? route('hr.travel-orders.index') : route('travel-orders.index') }}" class="inline-flex w-fit items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-blue-900 shadow-sm transition hover:border-blue-200 hover:text-blue-800">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    {{ __('Back') }}
                </a>

                <div class="flex flex-wrap gap-2">
                    @if($travelOrder->tar_attachment_path)
                        <a href="{{ route('travel-orders.ar.file', $travelOrder) }}" target="_blank" rel="noopener" data-no-transition class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-black uppercase tracking-widest text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <i class="fa-solid fa-file-pdf"></i>
                            {{ __('View Uploaded PDF') }}
                        </a>
                    @endif

                    <a href="{{ route('travel-orders.ar.print', $travelOrder) }}" target="_blank" rel="noopener" data-no-transition class="inline-flex items-center gap-2 rounded-xl bg-blue-900 px-4 py-2 text-xs font-black uppercase tracking-widest text-white shadow-sm transition hover:bg-blue-800">
                        <i class="fa-solid fa-print"></i>
                        {{ __('Print AR Copy') }}
                    </a>
                </div>
            </div>

            <section class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-blue-700">{{ __('Travel Authority') }} {{ $taNumber }}</p>
                            <h1 class="mt-2 text-2xl font-black text-slate-900">{{ __('Acknowledgement Report') }}</h1>
                            <p class="mt-1 text-sm text-slate-500">{{ __('Filed after completion of official travel. HR, Record Officer, and Regional Director may view this copy.') }}</p>
                        </div>

                        <span class="inline-flex w-fit rounded-full border px-3 py-1 text-xs font-black uppercase tracking-widest {{ $statusClass }}">
                            {{ $arStatus === 'overdue' ? __('Overdue') : ucfirst($arStatus) }}
                        </span>
                    </div>
                </div>

                <div class="grid gap-0 border-b border-slate-100 md:grid-cols-2">
                    <div class="border-b border-slate-100 px-6 py-5 md:border-b-0 md:border-r">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ __('Employee') }}</p>
                        <p class="mt-1 text-base font-bold text-slate-900">{{ $employeeName }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $travelOrder->employee?->position ?: __('N/A') }}</p>
                    </div>

                    <div class="px-6 py-5">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ __('Destination') }}</p>
                        <p class="mt-1 text-base font-bold text-slate-900">{{ $travelOrder->places_of_travel ?: __('N/A') }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $travelOrder->travel_type_label }}</p>
                    </div>
                </div>

                <div class="grid gap-0 border-b border-slate-100 md:grid-cols-3">
                    <div class="border-b border-slate-100 px-6 py-5 md:border-b-0 md:border-r">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ __('Travel Period') }}</p>
                        <p class="mt-1 text-sm font-bold text-slate-800">
                            {{ $travelOrder->travel_date_start?->format('M d, Y') ?? __('N/A') }}
                            @if($travelOrder->travel_date_end && $travelOrder->travel_date_start && $travelOrder->travel_date_end->ne($travelOrder->travel_date_start))
                                - {{ $travelOrder->travel_date_end->format('M d, Y') }}
                            @endif
                        </p>
                    </div>

                    <div class="border-b border-slate-100 px-6 py-5 md:border-b-0 md:border-r">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ __('AR Deadline') }}</p>
                        <p class="mt-1 text-sm font-bold text-slate-800">{{ $tarDeadline?->format('M d, Y') ?? __('N/A') }}</p>
                    </div>

                    <div class="px-6 py-5">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ __('Submitted') }}</p>
                        <p class="mt-1 text-sm font-bold text-slate-800">{{ $travelOrder->tar_submitted_at?->format('M d, Y h:i A') ?? __('Not submitted') }}</p>
                    </div>
                </div>

                <div class="px-6 py-5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ __('Purpose of Travel') }}</p>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $travelOrder->purpose ?: __('N/A') }}</p>
                </div>

                <div class="border-t border-slate-100 px-6 py-5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ __('Employee Remarks') }}</p>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $travelOrder->tar_remarks ?: __('No remarks provided.') }}</p>
                </div>

                <div class="border-t border-slate-100 bg-slate-50 px-6 py-5">
                    @if($travelOrder->tar_attachment_path)
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-bold text-slate-900">{{ __('Uploaded AR Document') }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ __('The employee uploaded a PDF copy of the acknowledgement report.') }}</p>
                            </div>
                            <a href="{{ route('travel-orders.ar.file', $travelOrder) }}" target="_blank" rel="noopener" data-no-transition class="inline-flex w-fit items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-xs font-black uppercase tracking-widest text-white transition hover:bg-slate-800">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                {{ __('Open PDF') }}
                            </a>
                        </div>
                    @else
                        <p class="text-sm font-semibold text-slate-500">{{ __('No acknowledgement report has been uploaded yet.') }}</p>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
