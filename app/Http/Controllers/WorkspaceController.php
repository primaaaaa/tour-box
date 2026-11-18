<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    /**
     * Display the user's profile and their workspaces.
     */
    public function profile(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $workspaces = $user->workspaces()
            ->withCount('views')
            ->latest()
            ->get();

        return view('profile.index', [
            'user' => $user,
            'workspaces' => $workspaces,
        ]);
    }

    /**
     * Store a newly created workspace.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $workspace = $user->workspaces()->create([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'description' => $validated['description'] ?? null,
            'is_public' => true,
        ]);

        return redirect()->route('workspaces.editor', $workspace)
            ->with('status', 'Workspace "'.$workspace->title.'" berhasil dibuat!');
    }

    /**
     * Show the 360 editor for a workspace.
     */
    public function editor(Workspace $workspace): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Check ownership (or superadmin)
        if ($workspace->user_id !== $user->id && ! $user->isSuperAdmin()) {
            abort(403, 'Anda tidak memiliki hak akses ke workspace ini.');
        }

        $workspace->load([
            'views.hotspots.targetView',
            'views.hotspots.carouselImages',
            'startingView',
        ]);

        return view('workspaces.editor', [
            'workspace' => $workspace,
            'user' => $user,
        ]);
    }

    /**
     * Update the specified workspace.
     */
    public function update(Request $request, Workspace $workspace): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($workspace->user_id !== $user->id && ! $user->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'starting_view_id' => ['nullable', 'exists:tour_views,id'],
            'is_public' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['title']) && $validated['title'] !== $workspace->title) {
            $workspace->slug = Str::slug($validated['title']);
        }

        $workspace->update($validated);

        return back()->with('status', 'Workspace berhasil diperbarui.');
    }

    /**
     * Remove the specified workspace.
     */
    public function destroy(Workspace $workspace): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($workspace->user_id !== $user->id && ! $user->isSuperAdmin()) {
            abort(403);
        }

        $title = $workspace->title;
        $workspace->delete();

        return redirect()->route('profile')->with('status', 'Workspace "'.$title.'" berhasil dihapus.');
    }
}
