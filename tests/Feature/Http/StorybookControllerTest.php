<?php

namespace ChrisVasey\SageStorybookBlade\Tests\Feature\Http;

use ChrisVasey\SageStorybookBlade\Tests\TestCase\PackageTestCase;

class StorybookControllerTest extends PackageTestCase
{
    /** @test */
    public function health_endpoint_returns_correct_response()
    {
        $response = $this->getJson('/storybook/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'service' => 'Sage Storybook',
                'version' => '1.0.0',
            ])
            ->assertJsonStructure([
                'status',
                'service', 
                'timestamp',
                'version'
            ]);

        // Check CORS headers
        $response->assertHeader('Access-Control-Allow-Origin');
    }

    /** @test */
    public function components_list_endpoint_works()
    {
        // Create test components
        $this->createTestView('components.button', '<button>Button</button>');
        $this->createTestView('blocks.hero', '<section>Hero</section>');

        $response = $this->getJson('/storybook/components');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'components',
                'count'
            ]);

        $data = $response->json();
        $this->assertIsArray($data['components']);
        $this->assertIsInt($data['count']);
        $this->assertContains('components.button', $data['components']);
        $this->assertContains('blocks.hero', $data['components']);
    }

    /** @test */
    public function component_metadata_endpoint_works()
    {
        $this->createTestView('components.meta-button', '
            <button type="{{ $type ?? "button" }}">
                {{ $text ?? "Click me" }}
            </button>
        ');

        $response = $this->getJson('/storybook/components/components.meta-button/metadata');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'component',
                'exists',
                'path',
                'variables'
            ]);

        $data = $response->json();
        $this->assertEquals('components.meta-button', $data['component']);
        $this->assertTrue($data['exists']);
        $this->assertContains('type', $data['variables']);
        $this->assertContains('text', $data['variables']);
    }

    /** @test */
    public function component_render_endpoint_works()
    {
        $this->createTestView('components.render-test', '
            <div class="test-component {{ $class ?? "" }}">
                <h1>{{ $title ?? "Default Title" }}</h1>
                <p>{{ $description ?? "Default description" }}</p>
            </div>
        ');

        $requestData = [
            'args' => [
                'title' => 'Test Title',
                'description' => 'Test Description',
                'class' => 'custom-class'
            ],
            'context' => [
                'theme' => 'dark',
                'viewport' => 'mobile'
            ]
        ];

        $response = $this->postJson('/storybook/render/components.render-test', $requestData);

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertHeader('Access-Control-Allow-Origin');

        $content = $response->getContent();
        $this->assertStringContainsString('Test Title', $content);
        $this->assertStringContainsString('Test Description', $content);
        $this->assertStringContainsString('custom-class', $content);
        $this->assertStringContainsString('storybook-component', $content);
        $this->assertStringContainsString('data-theme="dark"', $content);
    }

    /** @test */
    public function render_endpoint_handles_missing_component()
    {
        $requestData = [
            'args' => ['title' => 'Test'],
            'context' => []
        ];

        $response = $this->postJson('/storybook/render/components.non-existent', $requestData);

        $response->assertStatus(200); // Still returns 200 with error HTML

        $content = $response->getContent();
        $this->assertStringContainsString('storybook-error', $content);
        $this->assertStringContainsString('View Error', $content);
        $this->assertStringContainsString('components.non-existent', $content);
    }

    /** @test */
    public function options_request_returns_cors_headers()
    {
        $response = $this->call('OPTIONS', '/storybook/health');

        $response->assertStatus(200)
            ->assertHeader('Access-Control-Allow-Origin')
            ->assertHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->assertHeader('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization')
            ->assertHeader('Access-Control-Max-Age', '86400');
    }

    /** @test */
    public function endpoints_respect_environment_restrictions()
    {
        // Set environment to production (not allowed by default)
        config(['app.env' => 'production']);
        config(['storybook.allowed_environments' => ['testing', 'local']]);

        // Re-register the service provider to apply new config
        $this->app->forgetInstance('ChrisVasey\SageStorybookBlade\StorybookServiceProvider');
        
        $response = $this->getJson('/storybook/health');
        $response->assertStatus(404); // Routes not registered
    }

    /** @test */
    public function endpoints_can_be_disabled_via_config()
    {
        config(['storybook.enabled' => false]);

        $response = $this->getJson('/storybook/health');
        $response->assertStatus(404); // Routes not registered
    }

    /** @test */
    public function nested_component_paths_work_in_urls()
    {
        $this->createTestView('components.forms.input', '
            <input type="text" class="form-input" />
        ');

        // Test with dots in URL
        $response = $this->getJson('/storybook/components/components.forms.input/metadata');
        $response->assertStatus(200);

        // Test rendering nested component
        $response = $this->postJson('/storybook/render/components.forms.input', [
            'args' => [],
            'context' => []
        ]);

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringContainsString('form-input', $content);
    }
}