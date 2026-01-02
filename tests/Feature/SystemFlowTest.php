<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SystemFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_page_is_displayed_correctly(): void
    {
        $response = $this->get('/');

        // Check for new SaaS text (Inertia Component)
        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Welcome')
        );
    }

    public function test_new_users_can_register_and_see_dashboard(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $response = $this->get('/dashboard');
        $response->assertOk();
        $response->assertSee('Dashboard');
    }

    public function test_authenticated_user_can_access_upload_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/uploads');

        $response->assertStatus(200);
        // Use Inertia assertion
        $response->assertInertia(
            fn($page) => $page
                ->component('Upload/Index')
        );
    }

    public function test_user_can_upload_a_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('extrato.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->post('/uploads', [
            'file' => $file,
            'provider_cnpj' => '12.345.678/0001-90',
            'provider_razao_social' => 'Empresa Teste Ltda',
            'xml_type' => 'servico',
            'provider_uf' => 'BA',
            'provider_municipio' => 'Salvador'
        ]);

        $response->assertRedirect('/uploads');

        // Assert the file was stored... 
        // Note: The controller stores in 'uploads/{tenant_id}' usually.
        // We can inspect the database to be sure.
        $this->assertDatabaseHas('uploads', [
            'original_name' => 'extrato.pdf',
            'user_id' => $user->id,
        ]);
    }

    public function test_mapping_service_logic_keywords(): void
    {
        // This is a unit-style test inside feature to verify our recent changes
        // "Total" and "Locatarios"

        $service = new \App\Services\MappingService();

        // Test "Total" mapping
        $mapping = $service->getStandardMapping(['Data Doc', 'Historico', 'Total', 'CNPJ Prestador']);
        $this->assertEquals('Total', $mapping['Valor']);

        // Test "Locatários" mapping
        $mapping2 = $service->getStandardMapping(['Data', 'Valor', 'Locatários', 'CPF']);
        $this->assertEquals('Locatários', $mapping2['Razao Social']);
    }
}
