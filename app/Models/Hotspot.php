<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotspot extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tour_view_id',
        'type', // 'navigation' or 'info'
        'pos_x',
        'pos_y',
        'pos_z',
        'target_view_id',
        'info_title',
        'info_caption',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pos_x' => 'float',
            'pos_y' => 'float',
            'pos_z' => 'float',
        ];
    }

    /**
     * Get the view this hotspot is located on.
     *
     * @return BelongsTo<TourView, $this>
     */
    public function tourView(): BelongsTo
    {
        return $this->belongsTo(TourView::class);
    }

    /**
     * Get the destination view (if navigation hotspot).
     *
     * @return BelongsTo<TourView, $this>
     */
    public function targetView(): BelongsTo
    {
        return $this->belongsTo(TourView::class, 'target_view_id');
    }

    /**
     * Get all carousel images for this hotspot (if info hotspot).
     *
     * @return HasMany<HotspotCarouselImage, $this>
     */
    public function carouselImages(): HasMany
    {
        return $this->hasMany(HotspotCarouselImage::class)->orderBy('sort_order', 'asc');
    }
}
