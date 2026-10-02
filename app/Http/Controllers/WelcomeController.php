<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    /**
     * Public marketing landing page.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'headline' => 'Your Court. Your Game. Your Community.',
            'tagline' => 'Play. Book. Compete. Connect.',
            'stats' => [
                ['label' => 'Courts', 'value' => '1'],
                ['label' => 'Facilities', 'value' => '1'],
                ['label' => 'Phases planned', 'value' => '19'],
            ],
        ]);
    }
}
