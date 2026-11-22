@props(['employee', 'name', 'year', 'years', 'tab', 'otherName', 'otherYear', 'status' => 'all'])
<form method="GET" action="{{ route('employees.show', $employee) }}" class="flex flex-wrap items-center gap-2 pt-1">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <input type="hidden" name="{{ $otherName }}" value="{{ $otherYear }}">
    @if($tab === 'pds' && request()->filled('saln_status'))
        <input type="hidden" name="saln_status" value="{{ request('saln_status') }}">
    @elseif($tab === 'saln' && request()->filled('pds_status'))
        <input type="hidden" name="pds_status" value="{{ request('pds_status') }}">
    @endif
    <div class="relative w-28">
    <label for="employee-{{ $tab }}-year" class="sr-only">{{ __('Year') }}</label>
    <select id="employee-{{ $tab }}-year" name="{{ $name }}" onchange="this.form.submit()" class="h-9 w-full appearance-none rounded-lg border-slate-300 bg-white pl-3 pr-8 py-1 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="background-image:none">
        @foreach($years as $option)
            <option value="{{ $option }}" @selected((int) $year === (int) $option)>{{ $option }}</option>
        @endforeach
    </select>
    <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
    </div>
    <div class="relative w-32">
        <label for="employee-{{ $tab }}-status" class="sr-only">{{ __('Status') }}</label>
        <select id="employee-{{ $tab }}-status" name="{{ $tab }}_status" onchange="this.form.submit()" class="h-9 w-full appearance-none rounded-lg border-slate-300 bg-white pl-3 pr-8 py-1 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500" style="background-image:none">
            @foreach(['all' => 'All Statuses', 'pending' => 'Pending', 'approved' => 'Approved', ($tab === 'pds' ? 'rejected' : 'returned') => ($tab === 'pds' ? 'Rejected' : 'Returned')] as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
    </div>
</form>
