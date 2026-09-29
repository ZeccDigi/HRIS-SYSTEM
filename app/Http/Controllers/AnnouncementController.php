<?php

namespace App\Http\Controllers;

use App\Listeners\AuditLogListener;
use Illuminate\Http\Request;

use App\Models\Announcement;
use App\Models\User;
use App\Notifications\NewAnnouncementNotification;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status', 'all');
        $category = $request->query('category', 'all');
        $year = (int) $request->query('year', now()->year);
        $month = $request->query('month', 'all');
        $sort = $request->query('sort', 'latest');
        $month = preg_match('/^\d{2}$/', (string) $month) ? $month : 'all';
        $mine = $request->boolean('mine');
        $categories = ['General', 'Meeting', 'Memo', 'Training', 'Workshop', 'Office Orders', 'Advisory'];
        $years = Announcement::query()
            ->latest()
            ->get(['created_at'])
            ->map(fn ($announcement) => (int) $announcement->created_at->format('Y'))
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();
        if (! $years->contains($year)) {
            $year = (int) ($years->first() ?: now()->year);
        }
        $months = collect(range(1, 12))->map(fn ($number) => [
            'value' => str_pad((string) $number, 2, '0', STR_PAD_LEFT),
            'label' => Carbon::create($year, $number, 1)->format('F'),
        ]);

        $announcements = $this->announcementIndexQuery($search, $status, $category, $year, $month, $mine, $sort)
            ->paginate(10)
            ->appends($request->only(['search', 'status', 'category', 'year', 'month', 'mine', 'sort']));

        return view('announcements.index', compact('announcements', 'search', 'status', 'category', 'year', 'years', 'month', 'categories', 'months', 'mine', 'sort'));
    }

    public function filter(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status', 'all');
        $category = $request->query('category', 'all');
        $year = (int) $request->query('year', now()->year);
        $month = $request->query('month', 'all');
        $sort = $request->query('sort', 'latest');
        $month = preg_match('/^\d{2}$/', (string) $month) ? $month : 'all';
        $mine = $request->boolean('mine');

        $announcements = $this->announcementIndexQuery($search, $status, $category, $year, $month, $mine, $sort)
            ->paginate(10)
            ->appends($request->only(['search', 'status', 'category', 'year', 'month', 'mine', 'sort']));

        return response()->json([
            'html' => view('announcements.partials.results', compact('announcements'))->render(),
            'count' => $announcements->total(),
        ]);
    }

    private function announcementIndexQuery(?string $search, string $status, string $category, int $year, string $month, bool $mine, string $sort)
    {
        $query = Announcement::with('author.employee')
            ->when($search, function ($query, $search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%")
                        ->orWhere('tags', 'like', "%{$search}%");
                });
            })
            ->when($status === 'published', fn ($query) => $query->where('is_published', true))
            ->when($status === 'draft', fn ($query) => $query->where('is_published', false))
            ->when($category !== 'all', fn ($query) => $query->where('tags', 'like', "%{$category}%"))
            ->when($month !== 'all', function ($query) use ($year, $month) {
                $query->whereBetween('created_at', [
                    Carbon::create($year, (int) $month, 1)->startOfMonth(),
                    Carbon::create($year, (int) $month, 1)->endOfMonth(),
                ]);
            }, fn ($query) => $query->whereYear('created_at', $year))
            ->when($mine, fn ($query) => $query->where('author_id', auth()->id()))
        ;

        return match ($sort) {
            'oldest' => $query->oldest(),
            'title_asc' => $query->orderBy('title'),
            default => $query->latest(),
        };
    }

    /**
     * Display a public listing of the published announcements.
     */
    public function userIndex(Request $request)
    {
        $search = $request->query('search');
        $month = $request->query('month', 'all');
        $sort = $request->query('sort', 'latest');
        $month = preg_match('/^\d{4}-\d{2}$/', $month) ? $month : 'all';
        $months = Announcement::published()
            ->latest()
            ->get(['created_at'])
            ->map(fn ($announcement) => [
                'value' => $announcement->created_at->format('Y-m'),
                'label' => $announcement->created_at->format('F Y'),
            ])
            ->unique('value')
            ->values();

        $announcements = Announcement::with('author.employee')
            ->published()
            ->when($search, function ($query, $search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%")
                        ->orWhere('tags', 'like', "%{$search}%");
                });
            })
            ->when($month !== 'all', function ($query) use ($month) {
                $query->whereBetween('created_at', [
                    Carbon::createFromFormat('Y-m', $month)->startOfMonth(),
                    Carbon::createFromFormat('Y-m', $month)->endOfMonth(),
                ]);
            })
            ->when($sort === 'oldest', fn ($query) => $query->oldest(), fn ($query) => $query->latest())
            ->paginate(12)
            ->appends($request->only(['search', 'month', 'sort']));

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('announcements.partials.public-results', compact('announcements'))->render(),
                'count' => $announcements->total(),
            ]);
        }

        return view('announcements.view', compact('announcements', 'search', 'month', 'months', 'sort'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('announcements.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_published' => 'boolean',
            'tags' => 'nullable|string|max:255',
            'attachment' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store('announcement-attachments', 'public');
        }
        unset($validated['attachment']);

        $announcement = new Announcement($validated);
        $announcement->author_id = auth()->id();
        $announcement->is_published = true;
        $announcement->published_at = Carbon::now();

        $announcement->save();

        Notification::send(User::all(), new NewAnnouncementNotification($announcement));

        AuditLogListener::logActivity(
            'Post Announcement',
            'System',
            "Posted announcement: {$announcement->title}",
            auth()->user()
        );

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Announcement $announcement): View
    {
        if (!$announcement->is_published && !in_array(auth()->user()->role, ['admin', 'hrstaff'])) {
            abort(403, 'Unauthorized access to draft announcements.');
        }

        return view('announcements.show', compact('announcement'));
    }

    public function attachment(Announcement $announcement): BinaryFileResponse
    {
        if (!$announcement->attachment_path || !Storage::disk('public')->exists($announcement->attachment_path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($announcement->attachment_path), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Announcement $announcement): View
    {
        return view('announcements.edit', compact('announcement'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_published' => 'boolean',
            'tags' => 'nullable|string|max:255',
            'attachment' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $was_published = $announcement->is_published;

        if ($request->hasFile('attachment')) {
            if ($announcement->attachment_path) {
                Storage::disk('public')->delete($announcement->attachment_path);
            }

            $validated['attachment_path'] = $request->file('attachment')->store('announcement-attachments', 'public');
        }
        unset($validated['attachment']);

        $announcement->fill($validated);
        $announcement->is_published = true;

        if ($announcement->is_published && !$was_published) {
            $announcement->published_at = Carbon::now();
        } elseif (!$announcement->published_at) {
            $announcement->published_at = Carbon::now();
        }

        $announcement->save();

        if ($announcement->is_published && !$was_published) {
            Notification::send(User::all(), new NewAnnouncementNotification($announcement));
        }

        AuditLogListener::logActivity(
            'Update Announcement',
            'System',
            "Updated announcement: {$announcement->title}",
            auth()->user()
        );

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Announcement $announcement): RedirectResponse
    {
        if ($announcement->attachment_path) {
            Storage::disk('public')->delete($announcement->attachment_path);
        }

        $announcement->delete();

        AuditLogListener::logActivity(
            'Delete Announcement',
            'System',
            "Deleted announcement: {$announcement->title}",
            auth()->user()
        );

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement deleted successfully.');
    }
}
