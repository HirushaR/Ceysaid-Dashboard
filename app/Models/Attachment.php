<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $fillable = [
        'lead_id',
        'file_path',
        'original_name',
        'type',
    ];

    protected $appends = ['file_url'];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function getFileUrlAttribute(): string
    {
        return Storage::disk('lead-attachments')->url($this->file_path);
    }

    public function getFileSizeAttribute(): ?int
    {
        if (Storage::disk('lead-attachments')->exists($this->file_path)) {
            return Storage::disk('lead-attachments')->size($this->file_path);
        }
        return null;
    }

    public function getFileExistsAttribute(): bool
    {
        return Storage::disk('lead-attachments')->exists($this->file_path);
    }
} 