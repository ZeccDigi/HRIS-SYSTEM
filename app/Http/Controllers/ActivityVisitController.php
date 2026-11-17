<?php

namespace App\Http\Controllers;

use App\Models\ActivityAudit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivityVisitController extends Controller
{
    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'module' => ['required', 'string', 'max:80'],
            'page' => ['required', 'string', 'max:120'],
            'route' => ['nullable', 'string', 'max:120'],
            'visit_token' => ['nullable', 'uuid'],
        ]);

        $user = $request->user();

        if (! empty($data['visit_token'])) {
            $existingVisit = ActivityAudit::query()
                ->where('visit_token', $data['visit_token'])
                ->where('user_id', $user->id)
                ->first();

            if ($existingVisit) {
                if ($existingVisit->ended_at) {
                    $existingVisit->update([
                        'active_started_at' => now(),
                        'ended_at' => null,
                    ]);
                }

                return response()->json(['visit_token' => $existingVisit->visit_token]);
            }
        }

        $visit = ActivityAudit::create([
            'user_id' => $user->id,
            'user_name' => $user->employee
                ? trim($user->employee->firstname.' '.$user->employee->lastname)
                : $user->name,
            'user_role' => ucfirst($user->role ?? 'Employee'),
            'action' => 'Page Visit',
            'module' => $data['module'],
            'description' => 'Visited '.$data['page'],
            'ip_address' => $request->ip(),
            'visit_token' => (string) Str::uuid(),
            'active_started_at' => now(),
            'duration_seconds' => 0,
        ]);

        return response()->json(['visit_token' => $visit->visit_token]);
    }

    public function end(Request $request): JsonResponse
    {
        $data = $request->validate(['visit_token' => ['required', 'uuid']]);
        $visit = ActivityAudit::where('visit_token', $data['visit_token'])
            ->where('user_id', $request->user()->id)
            ->whereNull('ended_at')
            ->first();

        if ($visit) {
            $endedAt = now();
            $activeStartedAt = $visit->active_started_at ?? $visit->created_at;
            $activeSeconds = max(0, (int) $activeStartedAt->diffInSeconds($endedAt));
            $visit->update([
                'ended_at' => $endedAt,
                'active_started_at' => null,
                'duration_seconds' => (int) ($visit->duration_seconds ?? 0) + $activeSeconds,
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
