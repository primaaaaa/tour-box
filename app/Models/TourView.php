<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class TourView extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'workspace_id',
        'name',
        'custom_id',
        'view_type',
        'path_px',
        'path_nx',
        'path_py',
        'path_ny',
        'path_pz',
        'path_nz',
        'panels_data',
        'has_nadir_censor',
        'nadir_censor_type',
        'default_yaw',
        'default_pitch',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'has_nadir_censor' => 'boolean',
            'default_yaw' => 'float',
            'default_pitch' => 'float',
            'panels_data' => 'array',
        ];
    }

    /**
     * Get the workspace this view belongs to.
     *
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get all hotspots registered on this view.
     *
     * @return HasMany<Hotspot, $this>
     */
    public function hotspots(): HasMany
    {
        return $this->hasMany(Hotspot::class);
    }

    /**
     * Get full public URLs for all 6 faces of the cubemap.
     *
     * @return array<string, string|null>
     */
    public function getCubemapUrls(): array
    {
        $resolve = function (?string $path): ?string {
            if (! $path) {
                return null;
            }

            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
                return $path;
            }

            return Storage::url($path);
        };

        return [
            'px' => $resolve($this->path_px),
            'nx' => $resolve($this->path_nx),
            'py' => $resolve($this->path_py),
            'ny' => $resolve($this->path_ny),
            'pz' => $resolve($this->path_pz),
            'nz' => $resolve($this->path_nz),
        ];
    }

    /**
     * Get public URLs for sphere panels if view_type is 'sphere_panels'.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPanelsUrls(): array
    {
        if (! is_array($this->panels_data)) {
            return [];
        }

        return array_map(function ($panel) {
            $path = $panel['image'] ?? null;
            if ($path && ! str_starts_with($path, 'http') && ! str_starts_with($path, 'data:')) {
                $panel['image'] = Storage::url($path);
            }

            return $panel;
        }, $this->panels_data);
    }
}
