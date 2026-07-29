<?php

namespace App\Policies;

use App\Models\Formation;
use App\Models\User;

/**
 * Un formateur ne peut construire/modifier que SES formations. L'admin du site
 * a la main sur toutes. Sert de point d'autorisation unique pour la formation
 * et, par ricochet, pour ses chapitres/modules/questions/checkpoints.
 */
class FormationPolicy
{
    /**
     * Court-circuit : l'admin peut tout.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * Chacun ne modifie que les formations dont il est responsable — qu'il soit
     * formateur ou superviseur (une formation peut être attribuée à l'un ou à
     * l'autre). L'admin passe par `before()`.
     */
    public function update(User $user, Formation $formation): bool
    {
        return ($user->isFormateur() || $user->isSuperviseur())
            && $formation->cree_par === $user->id;
    }

    public function delete(User $user, Formation $formation): bool
    {
        return $this->update($user, $formation);
    }
}
