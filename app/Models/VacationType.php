<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VacationType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vacation_types';

    protected $fillable = [
        'name',
        'description',
        'counts_toward_limit',
    ];

    protected $casts = [
        'counts_toward_limit' => 'boolean',
    ];

    public function vacations()
    {
        return $this->hasMany(Vacation::class, 'type_id');
    }
}
