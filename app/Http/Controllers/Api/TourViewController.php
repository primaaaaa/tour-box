<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TourView;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TourViewController extends Controller
{
    /**
     * Store a new 360 view for a workspace.
     */
    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($workspace->user_id !== $user->id && ! $user->isSuperAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'custom_id' => ['nullable', 'string', 'max:50'],
            'px' => ['nullable'],
            'nx' => ['nullable'],
            'py' => ['nullable'],
            'ny' => ['nullable'],
            'pz' => ['nullable'],
            'nz' => ['nullable'],
        ]);

        $faces = ['px', 'nx', 'py', 'ny', 'pz', 'nz'];
        $paths = [];

        foreach ($faces as $face) {
            if ($request->hasFile($face)) {
                $file = $request->file($face);
                $paths["path_{$face}"] = $file->store("workspaces/{$workspace->id}/views", 'public');
            } elseif ($request->filled($face)) {
                $data = $request->input($face);
                // Check if base64 data URL
                if (str_starts_with($data, 'data:image')) {
                    $imageParts = explode(';base64,', $data);
                    if (count($imageParts) === 2) {
                        $imageTypeAux = explode('image/', $imageParts[0]);
                        $imageType = $imageTypeAux[1] ?? 'jpeg';
                        $imageBase64 = base64_decode($imageParts[1]);
                        $fileName = "workspaces/{$workspace->id}/views/".Str::random(24)."_{$face}.{$imageType}";
                        Storage::disk('public')->put($fileName, $imageBase64);
                        $paths["path_{$face}"] = $fileName;
                    }
                } else {
                    $paths["path_{$face}"] = $data;
                }
            }
        }

        $customId = $request->input('custom_id');
        if (empty($customId)) {
            $customId = 'view-'.Str::slug($request->input('name')).'-'.Str::random(4);
        }

        // Process sphere panels if provided
        $viewType = $request->input('view_type', 'cubemap');
        $panelsData = $request->input('panels_data');

        if (is_array($panelsData)) {
            $viewType = 'sphere_panels';
            foreach ($panelsData as $idx => &$panel) {
                $data = $panel['image'] ?? null;
                if ($data && str_starts_with($data, 'data:image')) {
                    $imageParts = explode(';base64,', $data);
                    if (count($imageParts) === 2) {
                        $imageTypeAux = explode('image/', $imageParts[0]);
                        $imageType = $imageTypeAux[1] ?? 'jpeg';
                        $imageBase64 = base64_decode($imageParts[1]);
                        $fileName = "workspaces/{$workspace->id}/views/panel_{$idx}_".Str::random(16).".{$imageType}";
                        Storage::disk('public')->put($fileName, $imageBase64);
                        $panel['image'] = $fileName;
                    }
                }
            }
            unset($panel);
        }

        $view = $workspace->views()->create(array_merge([
            'name' => $request->input('name'),
            'custom_id' => $customId,
            'view_type' => $viewType,
            'panels_data' => $panelsData,
            'has_nadir_censor' => $request->boolean('has_nadir_censor', false),
            'nadir_censor_type' => $request->input('nadir_censor_type', 'blur'),
            'default_yaw' => (float) $request->input('default_yaw', 0),
            'default_pitch' => (float) $request->input('default_pitch', 0),
        ], $paths));

        // If workspace has no starting view yet, set this as starting view
        if (! $workspace->starting_view_id) {
            $workspace->update(['starting_view_id' => $view->id]);
        }

        return response()->json([
            'message' => '360 View berhasil dibuat!',
            'view' => [
                'id' => $view->id,
                'name' => $view->name,
                'custom_id' => $view->custom_id,
                'view_type' => $view->view_type,
                'has_nadir_censor' => (bool) $view->has_nadir_censor,
                'nadir_censor_type' => $view->nadir_censor_type,
                'default_yaw' => $view->default_yaw,
                'default_pitch' => $view->default_pitch,
                'cubemap' => $view->getCubemapUrls(),
                'panels' => $view->getPanelsUrls(),
                'hotspots' => [],
            ],
            'is_starting_view' => $workspace->starting_view_id === $view->id,
        ], 201);
    }

    /**
     * Update an existing 360 view.
     */
    public function update(Request $request, TourView $view): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($view->workspace->user_id !== $user->id && ! $user->isSuperAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'custom_id' => ['nullable', 'string', 'max:50'],
            'has_nadir_censor' => ['sometimes', 'boolean'],
            'nadir_censor_type' => ['sometimes', 'string', 'in:blur,patch,logo'],
            'default_yaw' => ['sometimes', 'numeric'],
            'default_pitch' => ['sometimes', 'numeric'],
        ]);

        $view->update($validated);

        return response()->json([
            'message' => 'View berhasil diperbarui',
            'view' => $view,
        ]);
    }

    /**
     * Set a view as the starting point of its workspace.
     */
    public function setStartingPoint(TourView $view): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $workspace = $view->workspace;
        if ($workspace->user_id !== $user->id && ! $user->isSuperAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $workspace->update(['starting_view_id' => $view->id]);

        return response()->json([
            'message' => 'Starting point berhasil diubah!',
            'starting_view_id' => $view->id,
        ]);
    }

    /**
     * Delete a 360 view.
     */
    public function destroy(TourView $view): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $workspace = $view->workspace;
        if ($workspace->user_id !== $user->id && ! $user->isSuperAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Clean up images if local
        $faces = ['path_px', 'path_nx', 'path_py', 'path_ny', 'path_pz', 'path_nz'];
        foreach ($faces as $faceField) {
            $path = $view->{$faceField};
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        if ($workspace->starting_view_id === $view->id) {
            $nextView = $workspace->views()->where('id', '!=', $view->id)->first();
            $workspace->update(['starting_view_id' => $nextView?->id]);
        }

        $view->delete();

        return response()->json([
            'message' => 'View berhasil dihapus',
            'new_starting_view_id' => $workspace->fresh()->starting_view_id,
        ]);
    }
}
