@php
    $employee = $travelOrder->employee;
    $employeeName = trim(collect([$employee?->firstname, $employee?->middlename, $employee?->lastname, $employee?->suffix])->filter()->implode(' ')) ?: 'N/A';
    $divisionHeadName = trim(collect([$divisionHead?->firstname, $divisionHead?->middlename, $divisionHead?->lastname, $divisionHead?->suffix])->filter()->implode(' ')) ?: 'DIVISION HEAD';
    $selectedAttachments = collect($travelOrder->tar_attachments ?: []);
    $attachmentOptions = ['photo_documentation' => 'photo documentation', 'certificate' => 'copy of certificate of participation/attendance', 'lecture_materials' => 'lecture material/s', 'program_flow' => 'copy of program flow', 'activity_materials' => 'activity material/s', 'others' => 'others (specify)' . ($travelOrder->tar_other_attachment ? ': ' . $travelOrder->tar_other_attachment : '')];
@endphp
<article class="ar-document">
    <header class="ar-header">
        <img src="{{ asset('images/dmw.png') }}" alt="DMW Logo" class="ar-logo">
        <div class="ar-agency"><strong>DEPARTMENT OF MIGRANT WORKERS</strong><strong>REGIONAL OFFICE XI</strong><span>3rd Floor, NCCC Mall, Buhangin, Davao City</span></div>
    </header>
    <h1 class="ar-title">TRAVEL AUTHORITY REPORT (TAR)</h1>
    <table class="ar-info">
        <tr><th>Name</th><td>{{ $employeeName }}</td></tr>
        <tr><th>Activity</th><td>{{ $travelOrder->tar_activity ?: $travelOrder->purpose ?: 'N/A' }}</td></tr>
        <tr><th>Date</th><td>{{ $travelOrder->tar_activity_date?->format('F d, Y') ?: $travelOrder->travel_date_end?->format('F d, Y') ?: 'N/A' }}</td></tr>
        <tr><th>Venue</th><td>{{ $travelOrder->tar_venue ?: $travelOrder->places_of_travel ?: 'N/A' }}</td></tr>
        <tr><th>Participants<br>Present</th><td><div class="tall">{{ $travelOrder->tar_participants_present ?: 'N/A' }}</div></td></tr>
        <tr><th>Participants<br>of DMW</th><td><div class="tall">{{ $travelOrder->tar_dmw_participants ?: $employeeName }}</div></td></tr>
    </table>
    @foreach([['I. Objective/s of the Activity', $travelOrder->tar_objectives], ['II. Facts/Highlights of the Activity', $travelOrder->tar_highlights], ['III. Result/s of the Activity', $travelOrder->tar_results], ['IV. Recommendations/Resolutions', $travelOrder->tar_recommendations], ['V. Commitments/Requested Action from DMW', $travelOrder->tar_commitments]] as [$heading, $content])
        <section class="ar-section"><h2 class="ar-section-title">{{ $heading }}</h2><div class="ar-section-body {{ $loop->index < 4 ? 'large' : '' }}">{{ $content ?: 'N/A' }}</div></section>
    @endforeach
    <section class="ar-section">
        <h2 class="ar-section-title">VI. Attachments (check applicable)</h2>
        <div class="ar-attachments">@foreach($attachmentOptions as $value => $label)<div class="ar-check"><span class="ar-box">{{ $selectedAttachments->contains($value) ? '✓' : '' }}</span><span>{{ $label }}</span></div>@endforeach</div>
    </section>
    <div class="ar-signatures">
        <div class="ar-signature-block"><div class="ar-signature-label">Prepared by:</div>@if($employee?->effective_signature_url)<img src="{{ $employee->effective_signature_url }}" alt="Prepared by signature" class="ar-signature-image">@endif<div class="ar-signature-line">{{ $employeeName }}</div></div>
        <div class="ar-signature-block"><div class="ar-signature-label">Approved by: (Division Head)</div>@if($travelOrder->tar_chief_status === 'approved' && $divisionHead?->effective_signature_url)<img src="{{ $divisionHead->effective_signature_url }}" alt="Division head signature" class="ar-signature-image">@endif<div class="ar-signature-line">{{ $divisionHeadName }}<div class="ar-signature-role">Division Head</div></div></div>
    </div>
</article>
