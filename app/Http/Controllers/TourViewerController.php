<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\View\View;

class TourViewerController extends Controller
{
    /**
     * Display a public 360 tour based on username and workspace slug.
     * Route format: /{username}/{workspace_slug}
     */
    public function show(string $username, string $workspace_slug): View
    {
        $user = User::where('username', strtolower($username))->firstOrFail();

        $workspace = Workspace::where('user_id', $user->id)
            ->where('slug', $workspace_slug)
            ->where('is_public', true)
            ->with([
                'views.hotspots.targetView',
                'views.hotspots.carouselImages',
                'startingView',
            ])
            ->firstOrFail();

        // Determine starting view (FR-12, V-FR-02)
        $startingView = $workspace->startingView ?? $workspace->views()->first();

        // Prepare JSON payload for the Three.js viewer
        $viewsPayload = $workspace->views->map(function ($view) {
            return [
                'id' => $view->id,
                'name' => $view->name,
                'custom_id' => $view->custom_id,
                'has_nadir_censor' => (bool) $view->has_nadir_censor,
                'nadir_censor_type' => $view->nadir_censor_type,
                'default_yaw' => $view->default_yaw,
                'default_pitch' => $view->default_pitch,
                'view_type' => $view->view_type ?? 'cubemap',
                'cubemap' => $view->getCubemapUrls(),
                'panels' => $view->getPanelsUrls(),
                'hotspots' => $view->hotspots->map(function ($hotspot) {
                    return [
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
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return view('tour.player', [
            'user' => $user,
            'workspace' => $workspace,
            'startingViewId' => $startingView?->id,
            'viewsPayload' => $viewsPayload,
        ]);
    }
}
