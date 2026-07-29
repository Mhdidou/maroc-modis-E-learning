<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SuiviApprenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Page « Mes formations » de l'apprenant : formations attribuées avec leur
 * progression et l'objectif du jour.
 */
class MesFormationsController extends Controller
{
    use SuiviApprenant;

    public function index(Request $request): Response
    {
        $user = $request->user();
        $suivi = $this->suiviFormations($user);

        return Inertia::render('Espace/Formations', [
            'formations' => $suivi['formations'],
            'objectifDuJour' => $suivi['objectifDuJour'],
            'activiteSemaine' => $this->activiteSemaine($user),
        ]);
    }
}
