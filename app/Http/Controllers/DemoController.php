<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DemoController extends Controller
{
    /**
     * Display the interactive 360 demo for guest users.
     */
    public function index(): View
    {
        // Sample views for guest demo
        $demoViews = [
            [
                'id' => 1,
                'name' => 'Ruang Tamu Demo',
                'custom_id' => 'living-room',
                'has_nadir_censor' => true,
                'nadir_censor_type' => 'blur',
                'default_yaw' => 0,
                'default_pitch' => 0,
                'cubemap' => [
                    'px' => 'https://images.unsplash.com/photo-1513694203232-719a280e022f?w=1024&q=80',
                    'nx' => 'https://images.unsplash.com/photo-1513694203232-719a280e022f?w=1024&q=80',
                    'py' => 'https://images.unsplash.com/photo-1513694203232-719a280e022f?w=1024&q=80',
                    'ny' => 'https://images.unsplash.com/photo-1513694203232-719a280e022f?w=1024&q=80',
                    'pz' => 'https://images.unsplash.com/photo-1513694203232-719a280e022f?w=1024&q=80',
                    'nz' => 'https://images.unsplash.com/photo-1513694203232-719a280e022f?w=1024&q=80',
                ],
                'hotspots' => [
                    [
                        'id' => 101,
                        'type' => 'navigation',
                        'pos_x' => 120.0,
                        'pos_y' => -10.0,
                        'pos_z' => -350.0,
                        'target_view_id' => 2,
                        'title' => 'Menuju Balkon',
                    ],
                    [
                        'id' => 102,
                        'type' => 'info',
                        'pos_x' => -250.0,
                        'pos_y' => 30.0,
                        'pos_z' => -200.0,
                        'info_title' => 'Dekorasi & Lampu Gantung',
                        'info_caption' => 'Lampu gantung tembaga minimalis memberikan nuansa hangat untuk seluruh ruangan.',
                        'carousel' => [
                            'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?w=800&q=80',
                            'https://images.unsplash.com/photo-1513506003901-1e6a229e2d15?w=800&q=80',
                        ],
                    ],
                ],
            ],
            [
                'id' => 2,
                'name' => 'Balkon Terbuka',
                'custom_id' => 'balcony',
                'has_nadir_censor' => false,
                'nadir_censor_type' => 'blur',
                'default_yaw' => 0,
                'default_pitch' => 0,
                'cubemap' => [
                    'px' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1024&q=80',
                    'nx' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1024&q=80',
                    'py' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1024&q=80',
                    'ny' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1024&q=80',
                    'pz' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1024&q=80',
                    'nz' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1024&q=80',
                ],
                'hotspots' => [
                    [
                        'id' => 103,
                        'type' => 'navigation',
                        'pos_x' => -100.0,
                        'pos_y' => -10.0,
                        'pos_z' => 320.0,
                        'target_view_id' => 1,
                        'title' => 'Kembali ke Ruang Tamu',
                    ],
                ],
            ],
        ];

        return view('demo.index', [
            'views' => $demoViews,
        ]);
    }
}
