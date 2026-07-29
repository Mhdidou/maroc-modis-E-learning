<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    /**
     * Le front-end envoie `name` alors que la colonne est `nom_complet` : la
     * traduction doit avoir lieu côté contrôleur, sinon le nom n'est jamais
     * enregistré (attribut non fillable, silencieusement abandonné par fill()).
     */
    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Amina Benali',
                'email' => 'amina.benali@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Amina Benali', $user->nom_complet);
        $this->assertSame('Amina Benali', $user->name);
        $this->assertSame('amina.benali@example.com', $user->email);
    }

    /**
     * Un employé ne peut pas effacer son propre historique de formation : la
     * route de suppression de compte héritée de Breeze n'existe plus.
     */
    public function test_users_cannot_delete_their_own_account(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(Route::has('profile.destroy'));

        $this
            ->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertStatus(405);

        $this->assertTrue(User::withTrashed()->findOrFail($user->id)->estActif());
    }
}
