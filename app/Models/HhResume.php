<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * hh.uz qidiruvidan kelgan rezyume (kontaktlarsiz qisqa ma'lumot)
 */
class HhResume extends Model
{
    protected $table = 'hh_resumes';

    protected $fillable = [
        'resume_id',
        'title',
        'age',
        'gender',
        'area_id',
        'area_name',
        'salary_amount',
        'salary_currency',
        'salary_uzs',
        'experience_months',
        'education_level',
        'hh_updated_at',
        'alternate_url',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'hh_updated_at' => 'datetime',
        'salary_amount' => 'float',
        'salary_uzs' => 'float',
    ];
}
