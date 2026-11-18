<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Workspace extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'starting_view_id',
        'is_public',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Workspace $workspace): void {
            if (empty($workspace->slug)) {
                $baseSlug = Str::slug($workspace->title) ?: 'workspace';
                $slug = $baseSlug;
                $counter = 1;

                while (static::where('user_id', $workspace->user_id)->where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$counter++;
                }

                $workspace->slug = $slug;
            }
        });
    }

    /**
     * Get the user who owns this workspace.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all 360 views contained within this workspace.
     *
     * @return HasMany<TourView, $this>
     */
    public function views(): HasMany
    {
        return $this->hasMany(TourView::class);
    }

    /**
     * Get the starting view for this workspace.
     *
     * @return BelongsTo<TourView, $this>
     */
    public function startingView(): BelongsTo
    {
        return $this->belongsTo(TourView::class, 'starting_view_id');
    }
}
