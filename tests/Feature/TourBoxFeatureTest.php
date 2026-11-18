<?php

namespace Tests\Feature;

use App\Models\TourView;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TourBoxFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test FR-18 & FR-19: User registration and login.
     */
    public function test_user_can_register_and_login(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('profile'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'username' => 'johndoe',
            'email' => 'john@example.com',
        ]);
    }

    /**
     * Test FR-20: Guest can try demo.
     */
    public function test_guest_can_try_demo(): void
    {
        $response = $this->get(route('demo'));
        $response->assertOk();
        $response->assertSee('Demo Mode');
        $response->assertSee('Ruang Tamu Demo');
    }

    /**
     * Test FR-11 & FR-16: User can create workspace and see it on profile.
     */
    public function test_user_can_create_workspace_and_view_profile(): void
    {
        $user = User::factory()->create(['username' => 'alice']);

        $response = $this->actingAs($user)->post(route('workspaces.store'), [
            'title' => 'Villa Canggu Indah',
            'description' => 'Tur 360 derajat di sekitar villa.',
        ]);

        $workspace = Workspace::first();
        $this->assertNotNull($workspace);
        $this->assertEquals('Villa Canggu Indah', $workspace->title);
        $this->assertEquals('villa-canggu-indah', $workspace->slug);

        $response->assertRedirect(route('workspaces.editor', $workspace));

        $profileResponse = $this->actingAs($user)->get(route('profile'));
        $profileResponse->assertOk();
        $profileResponse->assertSee('Villa Canggu Indah');
        $profileResponse->assertSee('@ alice');
    }

    /**
     * Test FR-02, FR-03, FR-12, FR-14: Adding 360 view, custom id, starting point, and nadir censor.
     */
    public function test_user_can_create_360_view_and_set_starting_point(): void
    {
        $user = User::factory()->create(['username' => 'creator']);
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('api.views.store', $workspace), [
            'name' => 'Ruang Santai',
            'custom_id' => 'lounge-room',
            'has_nadir_censor' => true,
            'nadir_censor_type' => 'blur',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tour_views', [
            'workspace_id' => $workspace->id,
            'name' => 'Ruang Santai',
            'custom_id' => 'lounge-room',
            'has_nadir_censor' => 1,
        ]);

        $view = TourView::first();

        // Update nadir censor toggle
        $updateResponse = $this->actingAs($user)->putJson(route('api.views.update', $view), [
            'has_nadir_censor' => false,
        ]);
        $updateResponse->assertOk();
        $this->assertEquals(0, $view->fresh()->has_nadir_censor);

        // Set starting point
        $startingResponse = $this->actingAs($user)->postJson(route('api.views.starting-point', $view));
        $startingResponse->assertOk();
        $this->assertEquals($view->id, $workspace->fresh()->starting_view_id);
    }

    /**
     * Test FR-05 to FR-10: Hotspots Navigation & Information with Carousel.
     */
    public function test_user_can_add_navigation_and_info_hotspots(): void
    {
        $user = User::factory()->create(['username' => 'creator']);
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);
        $view1 = TourView::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Kamar Utama']);
        $view2 = TourView::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Balkon']);

        // Add navigation arrow (FR-05, FR-06)
        $navResponse = $this->actingAs($user)->postJson(route('api.hotspots.store', $view1), [
            'type' => 'navigation',
            'pos_x' => 100.5,
            'pos_y' => -10.0,
            'pos_z' => -300.2,
            'target_view_id' => $view2->id,
        ]);
        $navResponse->assertStatus(201);
        $this->assertDatabaseHas('hotspots', [
            'tour_view_id' => $view1->id,
            'type' => 'navigation',
            'target_view_id' => $view2->id,
        ]);

        // Add info hotspot with title, caption, and carousel (FR-07 - FR-10)
        $infoResponse = $this->actingAs($user)->postJson(route('api.hotspots.store', $view1), [
            'type' => 'info',
            'pos_x' => -150.0,
            'pos_y' => 20.0,
            'pos_z' => -200.0,
            'info_title' => 'Koleksi Seni',
            'info_caption' => 'Lukisan cat minyak asli buatan seniman lokal.',
            'carousel_images' => [
                'https://example.com/art1.jpg',
                'https://example.com/art2.jpg',
            ],
        ]);
        $infoResponse->assertStatus(201);
        $this->assertDatabaseHas('hotspots', [
            'tour_view_id' => $view1->id,
            'type' => 'info',
            'info_title' => 'Koleksi Seni',
        ]);
        $this->assertDatabaseCount('hotspot_carousel_images', 2);
    }

    /**
     * Test FR-13 & Viewer FR-01 - FR-04: Public share link format /{username}/{workspace_slug}.
     */
    public function test_viewer_can_access_shared_tour_link(): void
    {
        $user = User::factory()->create(['username' => 'bali-host', 'name' => 'Wayan Host']);
        $workspace = Workspace::factory()->create([
            'user_id' => $user->id,
            'title' => 'Sunset Villa',
            'slug' => 'sunset-villa',
            'is_public' => true,
        ]);
        $view = TourView::factory()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Kolam Renang Infinity',
        ]);
        $workspace->update(['starting_view_id' => $view->id]);

        // Access via friendly route /{username}/{workspace_slug} (FR-13, V-FR-01)
        $response = $this->get('/bali-host/sunset-villa');
        $response->assertOk();
        $response->assertSee('Sunset Villa');
        $response->assertSee('Wayan Host');
        $response->assertSee('Kolam Renang Infinity');
    }

    /**
     * Test creating and loading 3D Sphere Panels ("Naro Tembok" AR mode).
     */
    public function test_user_can_create_and_load_sphere_panels_view(): void
    {
        $user = User::factory()->create(['username' => 'photographer']);
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);

        $mockBase64 = 'data:image/jpeg;base64,'.base64_encode('fake-image-bytes');

        $response = $this->actingAs($user)->postJson(route('api.views.store', $workspace), [
            'name' => 'Kamar Tidur Bola 3D',
            'custom_id' => 'bedroom-sphere',
            'view_type' => 'sphere_panels',
            'panels_data' => [
                [
                    'id' => 'eq_0',
                    'ring' => 'equator',
                    'yaw' => 0,
                    'pitch' => 0,
                    'image' => $mockBase64,
                ],
                [
                    'id' => 'eq_1',
                    'ring' => 'equator',
                    'yaw' => 30,
                    'pitch' => 0,
                    'image' => $mockBase64,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('view.view_type', 'sphere_panels');
        $this->assertCount(2, $response->json('view.panels'));

        $this->assertDatabaseHas('tour_views', [
            'workspace_id' => $workspace->id,
            'name' => 'Kamar Tidur Bola 3D',
            'view_type' => 'sphere_panels',
        ]);
    }
}
