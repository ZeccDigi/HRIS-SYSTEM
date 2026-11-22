<?php

namespace App\Services;

use App\Models\CtoRequest;
use Illuminate\Support\Facades\DB;

class CocCertificateService
{
    public function data(CtoRequest $request): array
    {
        $grants = $request->coc_certificate_id
            ? CtoRequest::withTrashed()->where('coc_certificate_id', $request->coc_certificate_id)->where('status', 'approved')->orderBy('id')->get()
            : collect([$request]);
        $hours = (float) $grants->sum('hours');
        $remaining = $hours;
        $usages = DB::table('coc_usages')->join('cto_requests', 'cto_requests.id', '=', 'coc_usages.use_request_id')
            ->whereIn('coc_usages.grant_id', $grants->pluck('id'))->orderBy('coc_usages.id')
            ->get(['cto_requests.id', 'cto_requests.date_start', 'coc_usages.hours'])
            ->groupBy('id')->map(function ($items) use (&$remaining) {
                $used = (float) $items->sum('hours');
                $remaining = round($remaining - $used, 2);
                return (object) ['date_start' => $items->first()->date_start, 'hours' => $used, 'remaining_after' => $remaining];
            })->values();
        return ['hours' => $hours, 'usages' => $usages, 'remaining' => $remaining];
    }
}
