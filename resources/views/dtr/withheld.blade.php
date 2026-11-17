<x-app-layout>
    <x-slot name="title">{{ __('Daily Time Records (DTR)') }}</x-slot>

    @php
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold text-amber-800">{{ session('error') }}</div>
            @endif

            <form method="GET" action="{{ route('my-dtr.index') }}" class="grid gap-4 sm:grid-cols-3">
                <select name="month" class="rounded-lg border-slate-300 py-3">
                    @foreach($months as $number => $name)
                        <option value="{{ $number }}" @selected($selectedMonth === $number)>{{ $name }}</option>
                    @endforeach
                </select>
                <select name="year" class="rounded-lg border-slate-300 py-3">
                    @for($year = now()->year - 3; $year <= now()->year + 1; $year++)
                        <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}</option>
                    @endfor
                </select>
                <button type="submit" class="rounded-lg bg-[#0c3d91] px-4 py-3 font-bold text-white">View month</button>
            </form>

            <section class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                <h1 class="text-xl font-bold text-slate-900">{{ $releasePeriod->format('F Y') }} DTR is not released yet</h1>
                <p class="mx-auto mt-3 max-w-lg text-sm leading-6 text-slate-600">Your DTR will appear here once HR completes its review and releases the month.</p>
            </section>
        </div>
    </div>
</x-app-layout>
