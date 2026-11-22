<x-app-layout>
<x-slot name="title">Kiosk Connections</x-slot>
<div class="mx-auto max-w-5xl space-y-5 p-5">
 <a href="{{ route('utilities.index') }}" class="text-blue-700">Back to Utilities</a>
 <h1 class="text-2xl font-bold">Kiosk Connections</h1>
 <p>Connect an attendance computer to this HRIS. Create one key per computer and paste it into the kiosk's Settings.</p>
 @if(session('kiosk_key'))
 <div class="rounded-lg border border-amber-300 bg-amber-50 p-4"><p class="font-bold">Copy this key now. It is shown once.</p><input readonly aria-label="New kiosk key" class="mt-2 w-full font-mono text-sm" value="{{ session('kiosk_key') }}" onclick="this.select()"><p class="mt-2 text-sm">Keep the key private. It permits employee sync, attendance upload and locator scanning.</p></div>
 @endif
 @if(session('success'))<p class="text-green-700">{{ session('success') }}</p>@endif
 <form method="POST" action="{{ route('kiosk-devices.store') }}" class="flex flex-wrap gap-3">@csrf<input name="name" required maxlength="100" placeholder="Computer name, e.g. Entrance kiosk" class="rounded-lg border-slate-300"><button class="rounded-lg bg-blue-700 px-4 py-2 text-white">Create kiosk key</button></form>
 @error('name')<p class="text-red-700">{{ $message }}</p>@enderror
 <table class="w-full text-sm"><thead><tr class="text-left"><th class="p-3">Computer</th><th>Last connected</th><th>Status</th><th></th></tr></thead><tbody>
 @foreach($devices as $device)<tr class="border-t"><td class="p-3">{{ $device->name }}</td><td>{{ $device->last_seen_at ?? 'Not connected yet' }}</td><td>{{ $device->revoked_at ? 'Revoked' : 'Active' }}</td><td>@unless($device->revoked_at)<form method="POST" action="{{ route('kiosk-devices.revoke',$device->id) }}">@csrf<button class="text-red-700">Revoke access</button></form>@endunless</td></tr>@endforeach
 </tbody></table>{{ $devices->links() }}
</div>
</x-app-layout>
