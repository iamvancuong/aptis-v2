<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MockTest extends Model
{
    protected $fillable = [
        'user_id',
        'skill',
        'sections',
        'duration_minutes',
        'started_at',
        'finished_at',
        'duration_seconds',
        'score',
        'section_scores',
        'status',
    ];

    protected $casts = [
        'sections' => 'array',
        'section_scores' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'score' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    /**
     * Get the sets for each section, eager loaded with questions.
     */
    public function getSectionsWithSets()
    {
        $sections = $this->sections;
        $setIds = collect($sections)->pluck('set_id')->unique()->values();

        $sets = Set::with(['questions' => function ($q) {
            $q->orderBy('order');
        }, 'quiz'])->whereIn('id', $setIds)->get()->keyBy('id');

        return collect($sections)->map(function ($section, $index) use ($sets) {
            return [
                'index' => $index,
                'part' => $section['part'],
                'set_id' => $section['set_id'],
                'set' => $sets->get($section['set_id']),
            ];
        });
    }
}
