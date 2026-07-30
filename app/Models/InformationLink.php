<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InformationLink extends Model
{
    protected $table = 'information_links';

    protected $fillable = [
        'type',
        'title',
        'url',
        'description',
    ];

    public function informationLinks()
    {
        return $this->hasMany(InformationLink::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
