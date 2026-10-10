<?php

namespace App\Support;

use App\Models\Certification;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Skill;

/** Everything the resume shows: visible rows only, in manual order. */
class ResumeData
{
    public function toArray(): array
    {
        $skills = Skill::query()->where('is_visible', true)->ordered()->get();

        return [
            'profile' => Profile::current(),
            'experiences' => Experience::query()->where('is_visible', true)->ordered()->get(),
            'educations' => Education::query()->where('is_visible', true)->ordered()->get(),
            'certifications' => Certification::query()->where('is_visible', true)->ordered()->get(),
            'skillGroups' => $skills->where('group', '!=', 'language')->groupBy('group')
                ->sortBy(fn ($items, $group) => array_search($group, array_keys(Skill::GROUPS), true)),
            'languages' => $skills->where('group', 'language')->values(),
        ];
    }
}
