<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Vacation extends Model
{
    public $timestamps = true;
    use HasFactory;
    protected $table = 'vacations';

    protected $fillable = [
        'date',
        'type_id',
        'year_carried_from',
    ];

    public function type()
    {
        return $this->belongsTo(VacationType::class, 'type_id');
    }

    // Scopes for filtering by vacation type
    public function scopeRegular($query)
    {
        return $query->where('type_id', 1);
    }

    public function scopeCarried($query)
    {
        return $query->where('type_id', 2);
    }

    public function scopeVolunteer($query)
    {
        return $query->where('type_id', 3);
    }

    public function scopeBonus($query)
    {
        return $query->where('type_id', 4);
    }

    public function scopeCountsTowardLimit($query)
    {
        return $query->whereHas('type', function($q) {
            $q->where('counts_toward_limit', true);
        });
    }
}
