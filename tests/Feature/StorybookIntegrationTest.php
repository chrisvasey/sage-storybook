<?php

namespace ChrisVasey\SageStorybookBlade\Tests\Feature;

use ChrisVasey\SageStorybookBlade\Tests\TestCase\PackageTestCase;

class StorybookIntegrationTest extends PackageTestCase
{
    /** @test */
    public function complete_storybook_workflow_works()
    {
        // Create a realistic component
        $this->createTestView('components.card', '
            @php
            $title = $title ?? "Default Title";
            $description = $description ?? "Default description";
            $variant = $variant ?? "default";
            $image = $image ?? null;
            @endphp

            <div class="card card--{{ $variant }} {{ $class ?? "" }}">
                @if($image)
                    <img src="{{ $image }}" alt="{{ $title }}" class="card__image" />
                @endif
                
                <div class="card__content">
                    <h3 class="card__title">{{ $title }}</h3>
                    <p class="card__description">{{ $description }}</p>
                    
                    @if(isset($slot) && !empty($slot))
                        <div class="card__actions">
                            {{ $slot }}
                        </div>
                    @endif
                </div>
                
                @if(isset($_storybook) && $_storybook["isStorybook"])
                    <div class="storybook-indicator">
                        Theme: {{ $_storybook["theme"] }}
                    </div>
                @endif
            </div>
        ');

        // 1. Test component listing
        $listResponse = $this->getJson('/storybook/components');
        $listResponse->assertStatus(200);
        $components = $listResponse->json('components');
        $this->assertContains('components.card', $components);

        // 2. Test component metadata
        $metadataResponse = $this->getJson('/storybook/components/components.card/metadata');
        $metadataResponse->assertStatus(200)
            ->assertJson([
                'component' => 'components.card',
                'exists' => true,
            ]);

        $metadata = $metadataResponse->json();
        $this->assertContains('title', $metadata['variables']);
        $this->assertContains('description', $metadata['variables']);
        $this->assertContains('variant', $metadata['variables']);

        // 3. Test component rendering with various scenarios

        // Basic render
        $basicRender = $this->postJson('/storybook/render/components.card', [
            'args' => [
                'title' => 'Test Card',
                'description' => 'This is a test card',
            ],
            'context' => [
                'theme' => 'light',
            ],
        ]);

        $basicRender->assertStatus(200);
        $basicContent = $basicRender->getContent();
        $this->assertStringContainsString('Test Card', $basicContent);
        $this->assertStringContainsString('This is a test card', $basicContent);
        $this->assertStringContainsString('card--default', $basicContent);
        $this->assertStringContainsString('Theme: light', $basicContent);

        // Advanced render with all props
        $advancedRender = $this->postJson('/storybook/render/components.card', [
            'args' => [
                'title' => 'Advanced Card',
                'description' => 'Card with image and variant',
                'variant' => 'featured',
                'image' => 'https://example.com/image.jpg',
                'class' => 'custom-class',
                'slot' => '<button>Action Button</button>',
            ],
            'context' => [
                'theme' => 'dark',
            ],
        ]);

        $advancedRender->assertStatus(200);
        $advancedContent = $advancedRender->getContent();
        $this->assertStringContainsString('Advanced Card', $advancedContent);
        $this->assertStringContainsString('card--featured', $advancedContent);
        $this->assertStringContainsString('custom-class', $advancedContent);
        $this->assertStringContainsString('https://example.com/image.jpg', $advancedContent);
        $this->assertStringContainsString('Action Button', $advancedContent);
        $this->assertStringContainsString('Theme: dark', $advancedContent);
        $this->assertStringContainsString('data-theme="dark"', $advancedContent);

        // 4. Test CORS headers are present
        $basicRender->assertHeader('Access-Control-Allow-Origin');

        // 5. Test error handling
        $errorRender = $this->postJson('/storybook/render/components.nonexistent', [
            'args' => [],
            'context' => [],
        ]);

        $errorRender->assertStatus(200); // Still 200, but with error content
        $errorContent = $errorRender->getContent();
        $this->assertStringContainsString('storybook-error', $errorContent);
        $this->assertStringContainsString('View Error', $errorContent);
    }

    /** @test */
    public function block_components_work_when_configured()
    {
        $this->createTestView('blocks.hero', '
            @php
            $title = $title ?? "Hero Title";
            $subtitle = $subtitle ?? "Hero subtitle";
            $backgroundImage = $background_image ?? null;
            $textColor = $text_colour ?? "#000000";
            @endphp

            <section class="hero" style="color: {{ $textColor }}; @if($backgroundImage) background-image: url({{ $backgroundImage }}); @endif">
                <div class="hero__content">
                    <h1 class="hero__title">{{ $title }}</h1>
                    @if($subtitle)
                        <p class="hero__subtitle">{{ $subtitle }}</p>
                    @endif
                </div>
            </section>
        ');

        // Test that blocks are listed
        $response = $this->getJson('/storybook/components');
        $response->assertStatus(200);
        $components = $response->json('components');
        $this->assertIsArray($components);
        $this->assertContains('blocks.hero', $components);

        // Test block rendering
        $render = $this->postJson('/storybook/render/blocks.hero', [
            'args' => [
                'title' => 'Welcome to Our Site',
                'subtitle' => 'Amazing things await',
                'background_image' => 'https://example.com/hero.jpg',
                'text_colour' => '#ffffff',
            ],
            'context' => [],
        ]);

        $render->assertStatus(200);
        $content = $render->getContent();
        $this->assertStringContainsString('Welcome to Our Site', $content);
        $this->assertStringContainsString('Amazing things await', $content);
        $this->assertStringContainsString('color: #ffffff', $content);
        $this->assertStringContainsString('background-image: url(https://example.com/hero.jpg)', $content);
    }

    /** @test */
    public function partial_components_work()
    {
        $this->createTestView('partials.navigation', '
            <nav class="navigation">
                <ul class="nav-list">
                    @foreach($items ?? [] as $item)
                        <li class="nav-item">
                            <a href="{{ $item["url"] }}" class="nav-link">
                                {{ $item["text"] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        ');

        $render = $this->postJson('/storybook/render/partials.navigation', [
            'args' => [
                'items' => [
                    ['text' => 'Home', 'url' => '/'],
                    ['text' => 'About', 'url' => '/about'],
                    ['text' => 'Contact', 'url' => '/contact'],
                ],
            ],
            'context' => [],
        ]);

        $render->assertStatus(200);
        $content = $render->getContent();
        $this->assertStringContainsString('Home', $content);
        $this->assertStringContainsString('About', $content);
        $this->assertStringContainsString('Contact', $content);
        $this->assertStringContainsString('href="/"', $content);
        $this->assertStringContainsString('href="/about"', $content);
    }
}
