<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TravelOrder extends Model
{
    protected $fillable = [
        'employee_id',
        'ta_number',
        'travel_type',
        'travel_date_start',
        'travel_date_end',
        'places_of_travel',
        'purpose',
        'requesting_office',
        'notes_remarks',
        'driver_name',
        'vehicle_plate_no',
        'attachment_path',
        'status',
        'approved_by_recordofficer',
        'recordofficer_status',
        'recordofficer_remarks',
        'approved_by_chief',
        'chief_status',
        'chief_remarks',
        'approved_by_hrstaff',
        'hrstaff_status',
        'hrstaff_remarks',
        'approved_by_regionaldirector',
        'rd_status',
        'rd_remarks',
        'tar_deadline',
        'tar_status',
        'tar_review_status',
        'tar_hr_status',
        'tar_reviewed_by_hr',
        'tar_hr_remarks',
        'tar_chief_status',
        'tar_reviewed_by_chief',
        'tar_chief_remarks',
        'tar_rd_status',
        'tar_reviewed_by_rd',
        'tar_rd_remarks',
        'tar_approved_at',
        'tar_attachment_path',
        'tar_submitted_at',
        'tar_remarks',
        'tar_activity',
        'tar_activity_date',
        'tar_venue',
        'tar_participants_present',
        'tar_dmw_participants',
        'tar_objectives',
        'tar_highlights',
        'tar_results',
        'tar_recommendations',
        'tar_commitments',
        'tar_attachments',
        'tar_other_attachment',
    ];

    protected function casts(): array
    {
        return [
            'travel_date_start' => 'date',
            'travel_date_end' => 'date',
            'tar_deadline' => 'date',
            'tar_submitted_at' => 'datetime',
            'tar_approved_at' => 'datetime',
            'tar_activity_date' => 'date',
            'tar_attachments' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function recordsOfficer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by_recordofficer');
    }

    public function chief(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by_chief');
    }

    public function regionalDirector(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by_regionaldirector');
    }

    public function hrstaff(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by_hrstaff');
    }

    public function companions(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'travel_order_companions')
            ->withTimestamps();
    }

    public function tarHrReviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'tar_reviewed_by_hr');
    }

    public function tarChiefReviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'tar_reviewed_by_chief');
    }

    public function tarRegionalDirectorReviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'tar_reviewed_by_rd');
    }

    public function getTravelTypeLabelAttribute(): string
    {
        return match ($this->travel_type) {
            'local' => 'Local',
            'foreign' => 'Foreign',
            'official_business' => 'Official Business',
            default => ucfirst((string) $this->travel_type),
        };
    }
}
