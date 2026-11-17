<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <div>
        <table class="w-full table-fixed divide-y divide-gray-200">
            <colgroup>
                <col style="width: 43%">
                <col style="width: 17%">
                <col style="width: 20%">
                <col style="width: 20%">
            </colgroup>
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left text-[10px] font-black uppercase tracking-wider text-gray-500">{{ __('Announcement') }}</th>
                    <th class="px-3 py-2 text-center text-[10px] font-black uppercase tracking-wider text-gray-500">{{ __('Date') }}</th>
                    <th class="px-3 py-2 text-center text-[10px] font-black uppercase tracking-wider text-gray-500">{{ __('Posted By') }}</th>
                    <th class="px-4 py-2 text-center text-[10px] font-black uppercase tracking-wider text-gray-500">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($announcements as $announcement)
                    @php
                        $primaryTag = collect(explode(',', $announcement->tags ?? 'General'))->map(fn ($tag) => trim($tag))->filter()->first() ?: 'General';
                        $displayDate = $announcement->published_at ?? $announcement->created_at;
                        $authorName = $announcement->author?->display_name ?: $announcement->author?->name ?: __('HR Office');
                    @endphp
                    <tr class="transition hover:bg-gray-50/80">
                        <td class="px-3 py-2 align-middle">
                            <span class="mb-0.5 block truncate text-[10px] font-bold uppercase tracking-wide text-gray-500">{{ $primaryTag }}</span>
                            <a href="{{ route('announcements.show', $announcement) }}" class="block truncate text-sm font-bold text-gray-900 transition hover:text-indigo-600" title="{{ $announcement->title }}">{{ $announcement->title }}</a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-2 text-center align-middle text-sm font-medium text-gray-600">{{ $displayDate?->format('M d, Y') ?? '—' }}</td>
                        <td class="px-3 py-2 text-center align-middle">
                            <p class="text-sm font-semibold text-gray-800">{{ $authorName }}</p>
                        </td>
                        <td class="px-4 py-2 align-middle">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('announcements.show', $announcement) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-indigo-700 transition-colors duration-200 hover:bg-indigo-50 hover:text-indigo-900" title="{{ __('View') }}" aria-label="{{ __('View announcement') }}">
                                    <i class="fa-solid fa-eye text-sm" aria-hidden="true"></i>
                                </a>
                                @if($announcement->attachment_path)
                                    <a href="{{ route('announcements.attachment', $announcement) }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-600 transition-colors duration-200 hover:bg-slate-100 hover:text-slate-900" title="{{ __('Open attachment') }}" aria-label="{{ __('Open attachment') }}">
                                        <i class="fa-solid fa-paperclip text-sm" aria-hidden="true"></i>
                                    </a>
                                @else
                                    <span class="h-9 w-9" aria-hidden="true"></span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center"><p class="font-bold text-gray-800">{{ __('No announcements found') }}</p><p class="mt-1 text-sm text-gray-500">{{ __('No announcements match the current search or filters.') }}</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.table-pagination', [
        'paginator' => $announcements,
        'label' => __('announcements'),
        'perPageOptions' => [5, 10, 20, 50],
        'perPageSelectId' => 'employee_announcements_per_page',
    ])
</div>
