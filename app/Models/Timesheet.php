<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Eloquent model representing a timesheet entry.
// Stores user_id, project, hours_worked, date, notes and approval metadata.
class Timesheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project',
        'hours_worked',
        'date',
        'notes',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $attributes = [
        'status' => 'Pending',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}