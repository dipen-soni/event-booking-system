<?php

namespace App\Models;

use App\Traits\CommonQueryScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory, CommonQueryScopes;

    protected $fillable = [
        'title',
        'description',
        'date',
        'location',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function organizer()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
