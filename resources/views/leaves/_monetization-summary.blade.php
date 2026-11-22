@if($leaveRequest->is_monetization)
<section class="mt-5 rounded-xl border border-blue-100 bg-blue-50/50 p-4 text-sm text-slate-700">
    <h3 class="font-bold text-blue-900">{{ $leaveRequest->monetization_posted_at ? 'Approved gross monetization' : 'Estimated gross monetization' }}</h3>
    <div class="mt-3 grid gap-3 sm:grid-cols-3">
        <div><p class="text-xs text-slate-500">Credits to monetize</p><p class="font-semibold">{{ number_format((float) $leaveRequest->monetization_days, 3) }} {{ $leaveRequest->leaveType?->name }} days</p></div>
        <div><p class="text-xs text-slate-500">Highest monthly salary received</p><p class="font-semibold">₱{{ number_format((float) $leaveRequest->monetization_salary, 2) }}</p>@if($leaveRequest->monetization_salary_grade)<p class="mt-1 text-xs text-slate-500">{{ $leaveRequest->monetization_salary_grade }} · {{ $leaveRequest->monetization_salary_step }} · {{ $leaveRequest->monetization_salary_year }} schedule</p>@endif</div>
        <div><p class="text-xs text-slate-500">Gross amount</p><p class="font-bold text-blue-900">₱{{ number_format((float) $leaveRequest->monetization_amount, 2) }}</p></div>
    </div>
    <p class="mt-3 text-xs">Salary × days × {{ $leaveRequest->monetization_factor ?? \App\Services\LeaveMonetizationService::FACTOR }}. Credits: {{ number_format((float) $leaveRequest->monetization_credit_before, 3) }} → {{ number_format((float) $leaveRequest->monetization_credit_after, 3) }} days.</p>
</section>
@endif
