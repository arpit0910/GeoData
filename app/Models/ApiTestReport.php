<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiTestReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'generated_by_user_id',
        'target_user_id',
        'mode',
        'status',
        'report_name',
        'total_endpoints',
        'passed_endpoints',
        'failed_endpoints',
        'skipped_endpoints',
        'average_duration_ms',
        'selected_endpoints',
        'summary',
        'results',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'selected_endpoints' => 'array',
        'summary' => 'array',
        'results' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'average_duration_ms' => 'decimal:2',
    ];

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
