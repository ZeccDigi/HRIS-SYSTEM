@php
    $trackedLeave = ($preferPending ?? false)
        ? ($leaves->firstWhere('status', 'pending') ?? $leaves->first())
        : $leaves->first();
    $trackedStages = $trackedLeave ? [
        ['label' => 'HR', 'status' => $trackedLeave->hrstaff_status],
        ['label' => 'Chief', 'status' => $trackedLeave->chief_status],
        ['label' => 'Regional Director', 'status' => $trackedLeave->rd_status],
    ] : [];
    $trackedType = $trackedLeave ? Str::of($trackedLeave->leaveType?->name ?? 'Leave')->replaceMatches('/\s+Leave\b/i', '')->trim() : null;
    $trackerDisplay = $trackerDisplay ?? 'employee';
    $trackedEmployee = $trackedLeave ? trim(($trackedLeave->employee?->firstname ?? '') . ' ' . ($trackedLeave->employee?->lastname ?? '')) : '';
    $trackedPayload = $trackedLeave ? [
        'id' => (string) $trackedLeave->id,
        'title' => $trackerDisplay === 'type' ? (string) $trackedType : $trackedEmployee,
        'employee' => $trackedEmployee,
        'type' => (string) $trackedType,
        'stages' => collect($trackedStages)->map(fn ($stage) => [
            'label' => $stage['label'],
            'status' => $stage['status'] ?: 'pending',
        ])->values(),
    ] : null;
@endphp

<x-approval-tracker :payload="$trackedPayload" event="leave-selected" empty="No leave to track yet." compact />
