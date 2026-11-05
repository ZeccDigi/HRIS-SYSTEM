<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationsDropdown extends Component
{
    #[On('notifications-read')]
    public function refresh(): void
    {
        // Re-rendered by polling and when another bell instance marks items read.
    }

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();
        if ($notification) {
            if ($notification->unread()) {
                $notification->markAsRead();
            }

            $url = $this->notificationUrl($notification->data);

            return redirect($url);
        }
    }

    public function markAllAsRead(): void
    {
        Auth::user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        $this->dispatch('notifications-read');
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.notifications-dropdown', [
            'notifications' => $user->notifications()->latest()->limit(10)->get(),
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
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
            return route('leave-applications.show', $data['leave_request_id']);
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
