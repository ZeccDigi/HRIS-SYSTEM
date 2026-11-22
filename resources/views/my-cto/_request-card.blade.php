<div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 p-6 flex flex-col md:flex-row md:items-center justify-between hover:shadow-md transition-shadow duration-300 gap-4">
    <div class="flex-1">
        <div class="flex items-center gap-3 mb-1">
            <x-profile-avatar :employee="$request->employee" size="sm" variant="indigo" rounded="2xl" />
            <div>
                <h3 class="text-base font-bold text-gray-900 leading-tight">
                    {{ $request->employee->firstname }} {{ $request->employee->lastname }}
                </h3>
                @if($request->employee->position)
                    <span class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">{{ $request->employee->position }}</span>
                @endif
            </div>
        </div>

        <div class="mt-2 flex flex-wrap items-center gap-2">
            <span class="text-sm font-medium text-gray-900">
                {{ $request->type_label }}
            </span>
            <span class="text-sm text-gray-600 font-medium">{{ number_format($request->hours, 1) }} {{ Str::plural('Hour', $request->hours) }}</span>
        </div>

        <div class="mt-2 text-sm text-gray-600">
            <span class="font-medium">{{ $request->date_start->format('M d, Y') }}</span>
            <span class="mx-1 text-gray-400">to</span>
            <span class="font-medium">{{ $request->date_end->format('M d, Y') }}</span>
        </div>

        <div class="mt-2 text-xs text-gray-500 italic line-clamp-1">
            {{ $request->purpose }}
        </div>

        @if($request->attachment_path)
            <div class="mt-2">
                <a href="{{ route('supporting-documents.open', ['path' => $request->attachment_path]) }}" target="_blank" class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-indigo-500 hover:text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                    {{ __('Attachment') }}
                </a>
            </div>
        @endif
    </div>

    <div class="flex flex-row md:flex-col items-center md:items-end justify-between md:justify-center gap-3 border-t md:border-t-0 pt-4 md:pt-0">
        <div class="text-right">
            <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Status</div>
            @php
                $statusColors = [
                    'pending' => 'text-orange-500 bg-orange-50 border-orange-100',
                    'approved' => 'text-green-600 bg-green-50 border-green-100',
                    'rejected' => 'text-red-600 bg-red-50 border-red-100',
                ];
                $statusColor = $statusColors[$request->status] ?? 'text-gray-500 bg-gray-50 border-gray-100';
            @endphp
            <span class="text-[10px] font-black uppercase tracking-widest {{ $statusColor }} px-3 py-1 rounded-full border">
                {{ ucfirst($request->status) }}
            </span>
        </div>

        <a href="{{ route('my-cto.show', $request) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all shadow-md hover:-translate-y-0.5">
            VIEW
        </a>
    </div>
</div>
