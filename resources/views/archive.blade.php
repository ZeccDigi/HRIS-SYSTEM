<x-app-layout>
    <x-slot name="title">{{ __('Archive') }}</x-slot>

    <div class="space-y-4 p-4 sm:p-6">
        <nav aria-label="Archive categories" class="flex flex-wrap gap-1 border-b border-slate-200">
            @foreach($tabs as $key => $label)
                <a href="{{ route('archive.index', ['tab' => $key]) }}"
                   @if($tab === $key) aria-current="page" @endif
                   class="border-b-2 px-4 py-2 text-sm font-semibold {{ $tab === $key ? 'border-indigo-700 text-indigo-700' : 'border-transparent text-slate-600 hover:text-indigo-700' }}">
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
                                    <div class="text-xs text-slate-600">Cancelled · {{ $record->start_date?->format('M j, Y') }} – {{ $record->end_date?->format('M j, Y') }}</div>
                                @else
                                    <span class="font-semibold">{{ $record->description }}</span>
                                    <div class="text-xs text-slate-600">{{ $record->module }} · Time spent {{ $record->page_duration }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @switch($tab)
                                    @case('announcements') {{ $record->author?->display_name ?? '—' }} @break
                                    @case('leave') {{ $record->employee ? trim($record->employee->firstname.' '.$record->employee->lastname) : '—' }} @break
                                    @default {{ $record->user_name }}
                                @endswitch
                            </td>
                            <td class="px-4 py-3">{{ $record->created_at?->format('M j, Y · g:i A') ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $record->deleted_at?->format('M j, Y · g:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">No archived records in this category.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $records->links() }}
    </div>
</x-app-layout>
