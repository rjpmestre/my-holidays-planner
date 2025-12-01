<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacationSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'max_vacation_days',
        'max_volunteer_days',
        'manual_carried_days',
        'min_consecutive_vacation_days',
    ];

    protected $casts = [
        'year' => 'integer',
        'max_vacation_days' => 'integer',
        'max_volunteer_days' => 'integer',
        'manual_carried_days' => 'integer',
        'min_consecutive_vacation_days' => 'integer',
    ];
}
