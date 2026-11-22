<?php

namespace App\Services;

class PdsCompletionService
{
    public const SECTIONS = [
        ['Personal Information'], ['Family Background'], ['Educational Background'],
        ['Civil Service Eligibility'], ['Work Experience'],
        ['Voluntary Work', 'Voluntary Work or Involvement in Civic / Non-Government / People / Voluntary Organization/s'],
        ['Training', 'Learning and Development', 'Learning and Development (L&D) Interventions/Training Programs Attended'],
        ['Other Information'], ['References', 'Questionnaire'],
    ];

    public function summary($reviews): array
    {
        $reviews = collect($reviews);
        $approved = collect(self::SECTIONS)->filter(function ($names) use ($reviews) {
            $matching = $reviews->whereIn('section_name', $names);
            return $matching->isNotEmpty() && $matching->every(fn ($review) => data_get($review, 'status') === 'approved');
        })->count();
        $total = count(self::SECTIONS);
        return ['approved' => $approved, 'total' => $total, 'percentage' => (int) round($approved / $total * 100),
            'status' => $approved === $total ? 'approved' : ($reviews->contains('status', 'rejected') ? 'rejected' : 'pending')];
    }
}
