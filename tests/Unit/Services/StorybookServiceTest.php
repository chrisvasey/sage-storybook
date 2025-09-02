<?php

namespace ChrisVasey\SageStorybookBlade\Tests\Unit\Services;

use ChrisVasey\SageStorybookBlade\Services\StorybookService;
use ChrisVasey\SageStorybookBlade\Tests\TestCase\PackageTestCase;
use Illuminate\Support\Facades\View;

class StorybookServiceTest extends PackageTestCase
{
    protected StorybookService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StorybookService::class);
    }

    /** @test */
    public function it_can_render_a_simple_component()
    {
        // Create a test component
        $this->createTestView('components.test-button', '
            <button class="btn btn-{{ $variant ?? "primary" }}">
                {{ $slot ?? $text ?? "Button" }}
            </button>
        ');

        $result = $this->service->renderComponent('components.test-button', [
            'variant' => 'secondary',
            'text' => 'Click me'
        ]);

        $this->assertStringContainsString('btn-secondary', $result);
        $this->assertStringContainsString('Click me', $result);
        $this->assertStringContainsString('storybook-component', $result);
        $this->assertStringContainsString('data-component="components.test-button"', $result);
    }

    /** @test */
    public function it_sanitizes_component_paths()
    {
        $this->createTestView('components.safe-component', '<div>Safe content</div>');

        // Test various unsafe inputs
        $result1 = $this->service->renderComponent('../../../etc/passwd', []);
        $this->assertStringContainsString('Component view', $result1);
        $this->assertStringContainsString('not found', $result1);

        $result2 = $this->service->renderComponent('components<script>', []);
        $this->assertStringContainsString('not found', $result2);

        // Test that it auto-prefixes components
        $result3 = $this->service->renderComponent('safe-component', []);
        $this->assertStringContainsString('Safe content', $result3);
    }

    /** @test */
    public function it_adds_storybook_context_to_components()
    {
        $this->createTestView('components.context-test', '
            @if(isset($_storybook))
                <div class="storybook-mode theme-{{ $_storybook["theme"] }}">
                    Storybook context present
                </div>
            @endif
        ');

        $result = $this->service->renderComponent('components.context-test', [], [
            'theme' => 'dark',
            'viewport' => 'mobile'
        ]);

        $this->assertStringContainsString('theme-dark', $result);
        $this->assertStringContainsString('Storybook context present', $result);
    }

    /** @test */
    public function it_handles_component_errors_gracefully()
    {
        // Test non-existent component
        $result = $this->service->renderComponent('components.non-existent', []);
        
        $this->assertStringContainsString('storybook-error', $result);
        $this->assertStringContainsString('View Error', $result);
        $this->assertStringContainsString('components.non-existent', $result);
    }

    /** @test */
    public function it_can_get_component_metadata()
    {
        $this->createTestView('components.meta-test', '
            <button>{{ $title ?? "Default" }}</button>
        ');

        $metadata = $this->service->getComponentMetadata('components.meta-test');

        $this->assertEquals('components.meta-test', $metadata['component']);
        $this->assertTrue($metadata['exists']);
        $this->assertIsString($metadata['path']);
        $this->assertIsArray($metadata['variables']);
        $this->assertContains('title', $metadata['variables']);
    }

    /** @test */
    public function it_lists_available_components()
    {
        // Create test components in different directories
        $this->createTestView('components.button', '<button>Button</button>');
        $this->createTestView('components.card', '<div>Card</div>');
        $this->createTestView('blocks.hero', '<section>Hero</section>');
        $this->createTestView('partials.header', '<header>Header</header>');

        $components = $this->service->listComponents();

        $this->assertContains('components.button', $components);
        $this->assertContains('components.card', $components);
        $this->assertContains('blocks.hero', $components);
        $this->assertContains('partials.header', $components);
    }

    /** @test */
    public function it_respects_allowed_prefixes_configuration()
    {
        // Set custom allowed prefixes
        config(['storybook.allowed_prefixes' => ['components', 'custom']]);

        $this->createTestView('components.test', '<div>Component</div>');
        $this->createTestView('custom.widget', '<div>Widget</div>');
        $this->createTestView('blocks.hero', '<div>Hero</div>');

        $components = $this->service->listComponents();

        $this->assertContains('components.test', $components);
        $this->assertContains('custom.widget', $components);
        $this->assertNotContains('blocks.hero', $components);
    }

    /** @test */
    public function it_handles_nested_components()
    {
        $this->createTestView('components.forms.input', '
            <input type="{{ $type ?? "text" }}" class="form-input" />
        ');

        $result = $this->service->renderComponent('components.forms.input', [
            'type' => 'email'
        ]);

        $this->assertStringContainsString('type="email"', $result);
        $this->assertStringContainsString('form-input', $result);
    }

    /** @test */
    public function it_wraps_components_with_proper_metadata()
    {
        $this->createTestView('components.wrapper-test', '<span>Content</span>');

        $result = $this->service->renderComponent('components.wrapper-test', [], [
            'theme' => 'custom-theme'
        ]);

        $this->assertStringContainsString('<div class="storybook-component"', $result);
        $this->assertStringContainsString('data-component="components.wrapper-test"', $result);
        $this->assertStringContainsString('data-theme="custom-theme"', $result);
        $this->assertStringContainsString('<span>Content</span>', $result);
        $this->assertStringContainsString('</div>', $result);
    }
}