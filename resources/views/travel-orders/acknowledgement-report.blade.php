<x-app-layout>
    <x-slot name="title">{{ __('Travel Authority Report (TAR)') }}</x-slot>
    @include('travel-orders.partials.acknowledgement-report-styles')
    @php
        $role = strtolower(auth()->user()->role ?? '');
        $isApprover = in_array($role, ['admin', 'hrstaff', 'recordofficer', 'chief', 'regionaldirector'], true);
        $reviewStage = $travelOrder->tar_review_status ?: ($travelOrder->tar_submitted_at ? 'pending_hr' : 'not_submitted');
        $stageLabels = [
            'pending_hr' => 'Pending HR Review',
            'pending_chief' => 'Pending Division Chief Review',
            'pending_rd' => 'Pending Regional Director Review',
            'approved' => 'Fully Approved',
            'returned' => 'Returned for Correction',
            'not_submitted' => 'Not Submitted',
        ];
        $canReview = ($reviewStage === 'pending_hr' && in_array($role, ['admin', 'hrstaff'], true))
            || ($reviewStage === 'pending_chief' && $role === 'chief' && strcasecmp((string) auth()->user()->employee?->division, (string) $travelOrder->employee?->division) === 0)
            || ($reviewStage === 'pending_rd' && $role === 'regionaldirector');
        $workflowSteps = [
            ['label' => 'HR', 'status' => $travelOrder->tar_hr_status ?: 'pending', 'stage' => 'pending_hr'],
            ['label' => 'Division Chief', 'status' => $travelOrder->tar_chief_status ?: 'pending', 'stage' => 'pending_chief'],
            ['label' => 'Regional Director', 'status' => $travelOrder->tar_rd_status ?: 'pending', 'stage' => 'pending_rd'],
        ];
    @endphp
    <div class="bg-slate-100 py-6">
        <div class="mx-auto mb-4 flex max-w-[210mm] flex-wrap items-center justify-between gap-3 px-3 sm:px-0">
            <a href="{{ $isApprover ? route('hr.travel-orders.index', ['tab' => 'reports']) : route('travel-orders.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                <i class="fa-solid fa-arrow-left text-xs"></i>{{ __('Back') }}
            </a>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('travel-orders.ar.print', $travelOrder) }}" target="_blank" rel="noopener" data-no-transition class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-xs font-black uppercase tracking-wider text-white shadow-sm transition hover:bg-blue-800"><i class="fa-solid fa-print"></i>{{ __('Print Report') }}</a>
            </div>
        </div>
        <div class="overflow-x-auto px-3 pb-8">
            <div class="mx-auto w-[210mm] space-y-5">
                @if(session('success'))
                    <div class="rounded-xl border-l-4 border-emerald-500 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="rounded-xl border-l-4 border-red-500 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ session('error') }}</div>
                @endif

                @if($travelOrder->tar_submitted_at)
                    <section class="rounded-xl border border-slate-100 bg-white px-5 py-6 shadow-sm">
                        <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-3 sm:gap-x-6">
                            @foreach($workflowSteps as $step)
                                @php
                                    $isApprovedStep = $step['status'] === 'approved';
                                    $isReturnedStep = $step['status'] === 'returned';
                                    $isCurrentStep = $reviewStage === $step['stage'];
                                @endphp
                                <div class="flex items-center gap-3">
                                    <span class="h-3.5 w-3.5 shrink-0 rounded-full {{ $isApprovedStep || $isCurrentStep ? 'bg-emerald-500' : ($isReturnedStep ? 'bg-red-500' : 'bg-slate-300') }}"></span>
                                    <span class="text-sm font-bold text-slate-600">{{ $step['label'] }}</span>
                                </div>
                                @unless($loop->last)
                                    <i class="fa-solid fa-arrow-right text-xs text-slate-300"></i>
                                @endunless
                            @endforeach
                        </div>
                    </section>
                @endif

                <div class="shadow-xl">@include('travel-orders.partials.acknowledgement-report-document')</div>

                @if($travelOrder->tar_attachment_path)
                    <section class="bg-white shadow-sm">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                            <div class="min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-blue-900">{{ __('Supporting Document') }}</p>
                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ __('Attachment submitted with this TAR') }}</p>
                            </div>
                            <a href="{{ route('travel-orders.ar.file', $travelOrder) }}" target="_blank" rel="noopener" data-no-transition class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-blue-900 bg-white px-3 py-2 text-[10px] font-black uppercase tracking-wider text-blue-900 transition hover:bg-blue-50">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>{{ __('Open Full File') }}
                            </a>
                        </div>
                    </section>
                @endif

                @if($travelOrder->tar_submitted_at && $canReview)
                    <section class="border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-4">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-blue-700">{{ __('Current Review Action') }}</p>
                                <h2 class="mt-1 text-xl font-black text-slate-900">{{ __('Verify TAR') }}</h2>
                            </div>
                            <span class="inline-flex rounded-full border px-3 py-2 text-[10px] font-black uppercase tracking-wider {{ $reviewStage === 'approved' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ($reviewStage === 'returned' ? 'border-red-200 bg-red-50 text-red-700' : 'border-amber-200 bg-amber-50 text-amber-700') }}">
                                {{ $stageLabels[$reviewStage] ?? ucfirst(str_replace('_', ' ', $reviewStage)) }}
                            </span>
                        </div>

                            <form method="POST" action="{{ route('travel-orders.tar.review', $travelOrder) }}" class="mt-4">
                                @csrf
                                @method('PATCH')
                                <label for="tar_review_remarks" class="text-xs font-black uppercase tracking-widest text-slate-600">{{ __('Remarks / Notes') }}</label>
                                <textarea id="tar_review_remarks" name="remarks" rows="3" class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600" placeholder="{{ __('Optional when approving; required when returning.') }}">{{ old('remarks') }}</textarea>
                                @error('remarks')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                                <p class="mt-2 text-xs text-slate-400">{{ __('Remarks are required when returning the report to the employee.') }}</p>
                                <div class="mt-4 flex flex-wrap justify-end gap-3">
                                    <button type="submit" name="action" value="return" class="inline-flex min-h-11 min-w-48 items-center justify-center gap-2 rounded-xl border border-red-300 bg-white px-5 text-xs font-black uppercase tracking-wider text-red-600 transition hover:bg-red-50">
                                        <i class="fa-solid fa-xmark"></i>{{ __('Return TAR') }}
                                    </button>
                                    <button type="submit" name="action" value="approve" class="inline-flex min-h-11 min-w-48 items-center justify-center gap-2 rounded-xl bg-blue-800 px-5 text-xs font-black uppercase tracking-wider text-white transition hover:bg-blue-900">
                                        <i class="fa-solid fa-check"></i>{{ in_array($role, ['admin', 'hrstaff'], true) ? __('Verify TAR') : __('Approve TAR') }}
                                    </button>
                                </div>
                            </form>
                    </section>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
