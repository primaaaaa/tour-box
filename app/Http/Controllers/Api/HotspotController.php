<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotspot;
use App\Models\TourView;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HotspotController extends Controller
{
    /**
     * Store a new hotspot for a 360 view.
     */
    public function store(Request $request, TourView $view): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($view->workspace->user_id !== $user->id && ! $user->isSuperAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'type' => ['required', 'in:navigation,info'],
            'pos_x' => ['required', 'numeric'],
            'pos_y' => ['required', 'numeric'],
            'pos_z' => ['required', 'numeric'],
            'target_view_id' => ['nullable', 'exists:tour_views,id'],
            'info_title' => ['nullable', 'string', 'max:255'],
            'info_caption' => ['nullable', 'string'],
            'carousel_images' => ['nullable', 'array'],
            'carousel_images.*' => ['nullable'],
        ]);

        $hotspot = $view->hotspots()->create([
            'type' => $validated['type'],
            'pos_x' => (float) $validated['pos_x'],
            'pos_y' => (float) $validated['pos_y'],
            'pos_z' => (float) $validated['pos_z'],
            'target_view_id' => $validated['target_view_id'] ?? null,
            'info_title' => $validated['info_title'] ?? null,
            'info_caption' => $validated['info_caption'] ?? null,
        ]);

        // Process carousel images if provided
        if ($request->hasFile('carousel_files')) {
            $files = $request->file('carousel_files');
            foreach ($files as $index => $file) {
                $path = $file->store("workspaces/{$view->workspace_id}/hotspots", 'public');
                $hotspot->carouselImages()->create([
                    'image_path' => $path,
                    'sort_order' => $index,
                ]);
            }
        } elseif (! empty($validated['carousel_images'])) {
            foreach ($validated['carousel_images'] as $index => $item) {
                if (empty($item)) {
                    continue;
                }

                if (str_starts_with($item, 'data:image')) {
                    $parts = explode(';base64,', $item);
                    if (count($parts) === 2) {
                        $typeAux = explode('image/', $parts[0]);
                        $imageType = $typeAux[1] ?? 'jpeg';
                        $base64 = base64_decode($parts[1]);
                        $fileName = "workspaces/{$view->workspace_id}/hotspots/".Str::random(24).".{$imageType}";
                        Storage::disk('public')->put($fileName, $base64);

                        $hotspot->carouselImages()->create([
                            'image_path' => $fileName,
                            'sort_order' => $index,
                        ]);
                    }
                } else {
                    $hotspot->carouselImages()->create([
                        'image_path' => $item,
                        'sort_order' => $index,
                    ]);
                }
            }
        }

        $hotspot->load(['targetView', 'carouselImages']);

        return response()->json([
            'message' => 'Hotspot berhasil ditambahkan!',
            'hotspot' => [
                'id' => $hotspot->id,
                'type' => $hotspot->type,
                'pos_x' => $hotspot->pos_x,
                'pos_y' => $hotspot->pos_y,
                'pos_z' => $hotspot->pos_z,
                'target_view_id' => $hotspot->target_view_id,
                'target_view_name' => $hotspot->targetView?->name,
                'info_title' => $hotspot->info_title,
                'info_caption' => $hotspot->info_caption,
                'carousel' => $hotspot->carouselImages->map(fn ($img) => $img->getUrl())->values()->all(),
            ],
        ], 201);
    }

    /**
     * Delete a hotspot.
     */
    public function destroy(Hotspot $hotspot): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $workspace = $hotspot->tourView->workspace;
        if ($workspace->user_id !== $user->id && ! $user->isSuperAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Delete images
        foreach ($hotspot->carouselImages as $img) {
            if ($img->image_path && Storage::disk('public')->exists($img->image_path)) {
                Storage::disk('public')->delete($img->image_path);
            }
        }

        $hotspot->delete();

        return response()->json(['message' => 'Hotspot berhasil dihapus!']);
    }
}
