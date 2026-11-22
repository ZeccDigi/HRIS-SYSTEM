<x-app-layout>
    <x-slot name="title">{{ __('Archive') }}</x-slot>

    <div class="space-y-4 p-4 sm:p-6">
        @if(session('success'))
            <p role="status" class="text-sm font-semibold text-emerald-700">{{ session('success') }}</p>
        @endif
        <nav aria-label="Archive categories" class="flex w-full min-w-0 flex-nowrap gap-1 overflow-x-auto border-b border-slate-200">
            @foreach($tabs as $key => $label)
                <a href="{{ route('archive.index', ['tab' => $key]) }}"
                   @if($tab === $key) aria-current="page" @endif
                   class="shrink-0 whitespace-nowrap border-b-2 px-4 py-2 text-sm font-semibold {{ $tab === $key ? 'border-indigo-700 text-indigo-700' : 'border-transparent text-slate-600 hover:text-indigo-700' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        @if($tab === 'logs')
            <p class="text-sm text-slate-600">Page visits move here after {{ max(1, (int) config('activity-tracking.retention_days', 365)) }} days. Use these records only for authorized HR, security, or operational review.</p>
        @endif

        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th scope="col" class="px-4 py-3">Record</th>
                        <th scope="col" class="px-4 py-3">{{ $tab === 'announcements' ? 'Posted by' : 'Employee' }}</th>
                        <th scope="col" class="px-4 py-3">Original date</th>
                        <th scope="col" class="px-4 py-3">Archived on</th>
                        <th scope="col" class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($records as $record)
                        <tr class="align-top">
                            <td class="px-4 py-3">
                                @if($tab === 'announcements')
                                    <details>
                                        <summary class="cursor-pointer font-semibold">{{ $record->title }}</summary>
                                        <div class="mt-2 whitespace-pre-line break-words text-slate-600">{{ $record->content }}</div>
                                    </details>
                                @elseif($tab === 'leave')
                                    <details>
                                        <summary class="cursor-pointer font-semibold">{{ $record->leaveType?->name ?? 'Leave' }} #{{ $record->id }}</summary>
                                        <p class="mt-2 whitespace-pre-line text-slate-600">{{ $record->reason }}</p>
                                    </details>
                                    <div class="text-xs text-slate-600">Status: {{ ucfirst($record->status ?? 'pending') }} · {{ $record->start_date?->format('M j, Y') }} – {{ $record->end_date?->format('M j, Y') }}</div>
                                @elseif($tab === 'travel-orders')
                                    <details>
                                        <summary class="cursor-pointer font-semibold">{{ $record->ta_number ?: 'TA #'.$record->id }}</summary>
                                        <p class="mt-2 whitespace-pre-line text-slate-600">{{ $record->purpose }}</p>
                                    </details>
                                    <div class="text-xs text-slate-600">{{ $record->places_of_travel }} · {{ $record->travel_date_start?->format('M j, Y') }} – {{ $record->travel_date_end?->format('M j, Y') }}</div>
                                @elseif($tab === 'locator-slips')
                                    <details>
                                        <summary class="cursor-pointer font-semibold">{{ ucfirst($record->type ?? 'Locator Slip') }} #{{ $record->id }}</summary>
                                        <p class="mt-2 whitespace-pre-line text-slate-600">{{ $record->purpose }}</p>
                                    </details>
                                    <div class="text-xs text-slate-600">{{ $record->destination }} · {{ $record->date_covered?->format('M j, Y') }}</div>
                                @elseif($tab === 'cto')
                                    <details>
                                        <summary class="cursor-pointer font-semibold">{{ ucfirst($record->type ?? 'CTO') }} #{{ $record->id }}</summary>
                                        <p class="mt-2 whitespace-pre-line text-slate-600">{{ $record->purpose }}</p>
                                    </details>
                                    <div class="text-xs text-slate-600">{{ $record->hours }} hrs · {{ $record->date_start?->format('M j, Y') }} – {{ $record->date_end?->format('M j, Y') }}</div>
                                @elseif($tab === 'pds')
                                    <span class="font-semibold">PDS {{ $record->year }}</span>
                                    <div class="text-xs text-slate-600">Date Filed: {{ ($record->submitted_at ?? $record->created_at)?->format('M j, Y') ?? '—' }}</div>
                                @elseif($tab === 'saln')
                                    <span class="font-semibold">SALN {{ $record->as_of_date?->year }} #{{ $record->id }}</span>
                                    <div class="text-xs text-slate-600">{{ ucwords(str_replace('_', ' ', $record->type_of_filing)) }} · {{ ucfirst($record->review_status ?? 'pending') }}</div>
                                @else
                                    <span class="font-semibold">{{ $record->description }}</span>
                                    <div class="text-xs text-slate-600">{{ $record->module }} · Time spent {{ $record->page_duration }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @switch($tab)
                                    @case('announcements') {{ $record->author?->display_name ?? '—' }} @break
                                    @case('leave')
                                    @case('travel-orders')
                                    @case('locator-slips')
                                    @case('saln')
                                    @case('pds')
                                    @case('cto') {{ $record->employee ? trim($record->employee->firstname.' '.$record->employee->lastname) : '—' }} @break
                                    @default {{ $record->user_name }}
                                @endswitch
                            </td>
                            <td class="px-4 py-3">{{ $record->created_at?->format('M j, Y · g:i A') ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $record->deleted_at?->format('M j, Y · g:i A') }}</td>
                            <td class="px-4 py-3 text-center">
                                <form method="POST" action="{{ route('archive.restore', ['category' => $tab, 'record' => $record->id]) }}"
                                    data-archive-question="{{ __('Restore this record to its original page?') }}" data-confirm-label="{{ __('Restore') }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" title="{{ __('Restore') }}" aria-label="{{ __('Restore record') }}"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-indigo-700 transition hover:bg-indigo-50">
                                        <i class="fa-solid fa-rotate-left text-sm" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No archived records in this category.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $records->links() }}
    </div>
</x-app-layout>
