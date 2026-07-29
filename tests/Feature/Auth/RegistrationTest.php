<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * L'inscription publique est volontairement désactivée : sur un LMS interne
 * d'usine, seuls l'admin du site et les superviseurs créent des comptes (voir
 * /utilisateurs). Ces tests verrouillent cette décision — la réapparition des
 * routes Breeze `register` doit faire échouer la suite.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_disabled(): void
    {
        $this->assertFalse(Route::has('register'));

        $this->get('/register')->assertNotFound();
    }

    public function test_nobody_can_create_an_account_without_a_manager(): void
    {
        $this->post('/register', [
            'name' => 'Intrus',
            'email' => 'intrus@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertGuest();
        $this->assertDatabaseCount('utilisateurs', 0);
    }

    /**
     * Le seul chemin de création : un gestionnaire authentifié.
     */
    public function test_managers_create_accounts_instead(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('utilisateurs.store'), [
                'nom_complet' => 'Amina Benali',
                'email' => 'amina@usine.ma',
                'role' => User::ROLE_APPRENANT,
                'domaine' => 'Couture',
                'password' => 'MotDePasse!2026',
                'password_confirmation' => 'MotDePasse!2026',
            ])
            ->assertRedirect(route('utilisateurs.index'));

        $this->assertDatabaseHas('utilisateurs', [
            'email' => 'amina@usine.ma',
            'role' => User::ROLE_APPRENANT,
        ]);
    }
}
