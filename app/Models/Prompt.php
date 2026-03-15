<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Prompt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'category_id',
        'tags',
        'pinned_version_id',
        'created_by',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Prompt $prompt) {
            if (empty($prompt->slug)) {
                $base = Str::slug($prompt->name);
                $slug = $base;
                $counter = 1;
                while (static::withTrashed()->where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $counter++;
                }
                $prompt->slug = $slug;
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function pinnedVersion()
    {
        return $this->belongsTo(PromptVersion::class, 'pinned_version_id');
    }

    public function activeVersion()
    {
        if ($this->pinned_version_id) {
            return $this->pinnedVersion();
        }

        return $this->hasOne(PromptVersion::class)->orderByDesc('version_number');
    }

    public function latestVersion()
    {
        return $this->hasOne(PromptVersion::class)->orderByDesc('version_number');
    }

    public function versions()
    {
        return $this->hasMany(PromptVersion::class)->orderByDesc('version_number');
    }

    public function results()
    {
        return $this->hasMany(Result::class)->orderByDesc('created_at');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isFragment(): bool
    {
        return $this->type === 'fragment';
    }

    public function getActiveVersionAttribute()
    {
        if ($this->pinned_version_id && $this->relationLoaded('pinnedVersion')) {
            return $this->pinnedVersion;
        }

        if ($this->relationLoaded('latestVersion')) {
            return $this->latestVersion;
        }

        return $this->pinnedVersion ?? $this->latestVersion;
    }
}
