<div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100 p-6 flex flex-col md:flex-row md:items-center justify-between hover:shadow-md transition-shadow duration-300 gap-4">
    <div class="flex-1">
        <div class="flex items-center gap-3 mb-1">
            <x-profile-avatar :employee="$order->employee" size="sm" variant="indigo" rounded="2xl" />
            <div>
                <h3 class="text-base font-bold text-gray-900 leading-tight">
                    {{ $order->employee->firstname }} {{ $order->employee->lastname }}
                </h3>
                @if($order->employee->position)
                    <span class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">{{ $order->employee->position }}</span>
                @endif
            </div>
        </div>

        <div class="mt-2 flex flex-wrap items-center gap-2">
            @php
                $typeColors = [
                    'local' => 'text-blue-600 bg-blue-50 border-blue-100',
                    'foreign' => 'text-purple-600 bg-purple-50 border-purple-100',
                    'official_business' => 'text-emerald-600 bg-emerald-50 border-emerald-100',
                ];
                $typeColor = $typeColors[$order->travel_type] ?? 'text-gray-600 bg-gray-50 border-gray-100';
            @endphp
            <span class="text-[10px] font-black uppercase tracking-widest {{ $typeColor }} px-2 py-0.5 rounded border">
                {{ $order->travel_type_label }}
            </span>
            <span class="text-sm text-gray-600 font-medium">{{ $order->places_of_travel }}</span>
        </div>

        <div class="mt-2 text-sm text-gray-600">
            <span class="font-medium">{{ $order->travel_date_start->format('M d, Y') }}</span>
            <span class="mx-1 text-gray-400">to</span>
            <span class="font-medium">{{ $order->travel_date_end->format('M d, Y') }}</span>
            <span class="ml-2 text-[10px] text-gray-400 uppercase tracking-widest">
                ({{ $order->travel_date_start->diffInDays($order->travel_date_end) + 1 }} {{ Str::plural('Day', $order->travel_date_start->diffInDays($order->travel_date_end) + 1) }})
            </span>
        </div>

        <div class="mt-2 text-xs text-gray-500 italic line-clamp-1">
            {{ $order->purpose }}
        </div>

        @if($order->attachment_path)
            <div class="mt-2">
                <a href="{{ route('supporting-documents.open', ['path' => $order->attachment_path]) }}" target="_blank" class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-indigo-500 hover:text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                    {{ __('Attachment') }}
                </a>
            </div>
        @endif

        @if($order->companions->count())
            <div class="mt-2 flex items-center gap-2 text-[10px] text-gray-400 font-medium">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span>{{ $order->companions->count() }} {{ Str::plural('Companion', $order->companions->count()) }}:
                    {{ $order->companions->map(fn($c) => $c->firstname . ' ' . $c->lastname)->join(', ') }}
                </span>
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
                $statusColor = $statusColors[$order->status] ?? 'text-gray-500 bg-gray-50 border-gray-100';
            @endphp
            <span class="text-[10px] font-black uppercase tracking-widest {{ $statusColor }} px-3 py-1 rounded-full border">
                {{ ucfirst($order->status) }}
            </span>
        </div>

        <a href="{{ route('travel-orders.show', $order) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all shadow-md hover:-translate-y-0.5">
            VIEW
        </a>
    </div>
</div>
