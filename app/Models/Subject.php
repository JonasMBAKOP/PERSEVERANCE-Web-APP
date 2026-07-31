<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'subject_category_id',
        'code',
        'name_fr',
        'name_en',
        'type',
    ];

    // ── Relations ──────────────────────────────────────────────────────────
    public function category()
    {
        return $this->belongsTo(SubjectCategory::class, 'subject_category_id');
    }

    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class);
    }

    public function getNameAttribute(): string
    {
        return (string) $this->name_fr;
    }
}
