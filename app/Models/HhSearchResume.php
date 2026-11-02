<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Filtr ↔ rezyume: moslik foizi va HR holati
 */
class HhSearchResume extends Model
{
    public const STATUSES = ['new', 'viewed', 'selected', 'rejected'];

    protected $table = 'hh_search_resumes';

    protected $fillable = [
        'hh_search_id',
        'hh_resume_id',
        'score',
        'score_details',
        'status',
    ];

    protected $casts = [
        'score_details' => 'array',
    ];

    public function search(): BelongsTo
    {
        return $this->belongsTo(HhSearch::class, 'hh_search_id');
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(HhResume::class, 'hh_resume_id');
    }
}
