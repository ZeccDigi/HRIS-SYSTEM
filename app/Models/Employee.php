<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use App\Services\LeaveCreditService;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lastname',
        'firstname',
        'middlename',
        'suffix',
        'division',
        'contact_number',
        'notification_email',
        'position',
        'account_role',
        'employment_status',
        'service_start_date',
        'remarks',
        'user_id',
        'rfid_number',
        'profile_picture',
        'e_signature_path',
    ];

    protected function casts(): array
    {
        return [
            'service_start_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // PDS Relationships
    public function pdsPersonal()
    {
        return $this->hasOne(PdsPersonalInformation::class);
    }

    public function pdsFamily()
    {
        return $this->hasOne(PdsFamilyBackground::class);
    }

    public function pdsChildren()
    {
        return $this->hasMany(PdsChild::class);
    }

    public function pdsEducation()
    {
        return $this->hasMany(PdsEducation::class);
    }

    public function pdsEligibilities()
    {
        return $this->hasMany(PdsEligibility::class);
    }

    public function pdsWorkExperiences()
    {
        return $this->hasMany(PdsWorkExperience::class);
    }

    public function pdsVoluntaryWorks()
    {
        return $this->hasMany(PdsVoluntaryWork::class);
    }

    public function pdsTrainings()
    {
        return $this->hasMany(PdsTraining::class);
    }

    public function pdsOthers()
    {
        return $this->hasMany(PdsOtherInfo::class);
    }

    public function pdsQuestionnaire()
    {
        return $this->hasOne(PdsQuestionnaire::class);
    }

    public function pdsReferences()
    {
        return $this->hasMany(PdsReference::class);
    }

    public function pdsSectionReviews()
    {
        return $this->hasMany(PdsSectionReview::class);
    }

    public function pdsGovId()
    {
        return $this->hasOne(PdsGovernmentId::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveCredits()
    {
        return $this->hasMany(LeaveCredit::class);
    }

    public function leaveCreditTransactions()
    {
        return $this->hasMany(LeaveCreditTransaction::class);
    }

    public function salns(): HasMany
    {
        return $this->hasMany(Saln::class);
    }

    public function travelOrders(): HasMany
    {
        return $this->hasMany(TravelOrder::class);
    }

    public function ctoRequests(): HasMany
    {
        return $this->hasMany(CtoRequest::class);
    }

    public function getProfilePictureUrlAttribute(): ?string
    {
        if (! $this->profile_picture) {
            return null;
        }

        if (Storage::disk('public_uploads')->exists($this->profile_picture)) {
            return asset('uploads/'.$this->profile_picture);
        }

        if (Storage::disk('public')->exists($this->profile_picture)) {
            return asset('storage/'.$this->profile_picture);
        }

        return null;
    }

    /**
     * Absolute filesystem path for embedding images in PDF exports.
     */
    public function getProfilePictureAbsolutePathAttribute(): ?string
    {
        if (! $this->profile_picture) {
            return null;
        }

        $uploadsPath = public_path('uploads/'.$this->profile_picture);
        if (file_exists($uploadsPath)) {
            return $uploadsPath;
        }

        $storagePath = storage_path('app/public/'.$this->profile_picture);
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        return null;
    }

    public function getESignatureUrlAttribute(): ?string
    {
        if (! $this->e_signature_path) {
            return null;
        }

        if (Storage::disk('public')->exists($this->e_signature_path)) {
            return asset('storage/'.$this->e_signature_path);
        }

        return null;
    }

    public function getEffectiveSignatureUrlAttribute(): ?string
    {
        if ($this->e_signature_url) {
            return $this->e_signature_url;
        }

        $pdsSignature = $this->pdsGovId?->signature_path;
        if ($pdsSignature && Storage::disk('public')->exists($pdsSignature)) {
            return asset('storage/'.$pdsSignature);
        }

        return null;
    }

    public function getESignatureAbsolutePathAttribute(): ?string
    {
        if ($this->e_signature_path) {
            $storagePath = storage_path('app/public/'.$this->e_signature_path);
            if (file_exists($storagePath)) {
                return $storagePath;
            }
        }

        $pdsSignature = $this->pdsGovId?->signature_path;
        if ($pdsSignature) {
            $storagePath = storage_path('app/public/'.$pdsSignature);
            if (file_exists($storagePath)) {
                return $storagePath;
            }
        }

        return null;
    }

    public function getInitialsAttribute(): string
    {
        $initials = strtoupper(substr($this->firstname ?? '', 0, 1).substr($this->lastname ?? '', 0, 1));

        return $initials !== '' ? $initials : '?';
    }

    /**
     * Ensure the employee has leave credits for the given year.
     */
    public function ensureLeaveCredits(int $year): void
    {
        app(LeaveCreditService::class)->ensureCredits($this, $year);
    }
}
