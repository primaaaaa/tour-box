<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class HotspotCarouselImage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'hotspot_id',
        'image_path',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the hotspot this image belongs to.
     *
     * @return BelongsTo<Hotspot, $this>
     */
    public function hotspot(): BelongsTo
    {
        return $this->belongsTo(Hotspot::class);
    }

    /**
     * Get full public URL for this carousel image.
     */
    public function getUrl(): string
    {
        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://') || str_starts_with($this->image_path, 'data:')) {
            return $this->image_path;
        }

        return Storage::url($this->image_path);
    }
}
