<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class NotificationRedirectController extends Controller
{
    public function __invoke(string $notificationId): RedirectResponse
    {
        $notification = Auth::user()
            ->notifications()
            ->where('id', $notificationId)
            ->firstOrFail();

        if ($notification->unread()) {
            $notification->markAsRead();
        }

        return redirect($this->notificationUrl($notification->data));
    }

    private function notificationUrl(array $data): string
    {
        if (! empty($data['action_url'])) {
            return $data['action_url'];
        }

        if (($data['type'] ?? null) === 'announcement') {
            return route('announcements.view');
        }

        if (! empty($data['locator_slip_id'])) {
            $role = strtolower((string) Auth::user()->role);

            return in_array($role, ['admin', 'hrstaff', 'chief', 'regionaldirector'], true)
                ? route('hr.locator-slips.show', $data['locator_slip_id'])
                : route('locator-slips.show', $data['locator_slip_id']);
        }

        if (! empty($data['leave_request_id'])) {
            $role = strtolower((string) Auth::user()->role);

            return in_array($role, ['admin', 'hrstaff', 'chief', 'regionaldirector'], true)
                ? route('leave-applications.show', $data['leave_request_id'])
                : route('leaves.show', $data['leave_request_id']);
        }

        if (! empty($data['cto_request_id'])) {
            return route('my-cto.show', $data['cto_request_id']);
        }

        if (! empty($data['travel_order_id'])) {
            return route('travel-orders.show', $data['travel_order_id']);
        }

        return route('dashboard');
    }
}
