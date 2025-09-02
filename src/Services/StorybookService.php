<?php

namespace ChrisVasey\SageStorybookBlade\Services;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\View;
use Illuminate\View\ViewException;

class StorybookService
{
    /**
     * Render a Blade component with the given arguments
     *
     * @param  string  $component  The Blade component path (e.g., 'components.button')
     * @param  array  $args  Arguments to pass to the component
     * @param  array  $context  Additional context (theme, viewport, etc.)
     * @return string Rendered HTML
     *
     * @throws Exception
     */
    public function renderComponent(string $component, array $args = [], array $context = []): string
    {
        try {
            // Sanitize component path
            $component = $this->sanitizeComponentPath($component);

            // Prepare view data
            $viewData = $this->prepareViewData($args, $context);

            // Check if view exists
            if (! View::exists($component)) {
                throw new ViewException("Component view '{$component}' not found");
            }

            // Render the component
            $rendered = View::make($component, $viewData)->render();

            // Wrap in container with metadata
            return $this->wrapWithContainer($rendered, $component, $args, $context);

        } catch (ViewException $e) {
            return $this->renderError('View Error', $e->getMessage(), $component, $args);
        } catch (Exception $e) {
            return $this->renderError('Render Error', $e->getMessage(), $component, $args);
        }
    }

    /**
     * Get component metadata (for introspection)
     */
    public function getComponentMetadata(string $component): array
    {
        try {
            $component = $this->sanitizeComponentPath($component);

            return [
                'component' => $component,
                'exists' => View::exists($component),
                'path' => $this->getComponentFilePath($component),
                'variables' => $this->extractComponentVariables($component),
            ];
        } catch (Exception $e) {
            return [
                'component' => $component,
                'exists' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * List available components
     */
    public function listComponents(): array
    {
        $components = [];
        $viewPaths = config('view.paths', [resource_path('views')]);

        foreach ($viewPaths as $viewPath) {
            $allowedPrefixes = config('storybook.allowed_prefixes', ['components', 'blocks', 'partials']);

            foreach ($allowedPrefixes as $prefix) {
                $componentPath = $viewPath.'/'.$prefix;
                if (is_dir($componentPath)) {
                    $components = array_merge($components, $this->scanComponents($componentPath, $prefix));
                }
            }
        }

        return array_unique($components);
    }

    /**
     * Sanitize and validate component path
     */
    private function sanitizeComponentPath(string $component): string
    {
        // Remove any potentially dangerous characters
        $component = preg_replace('/[^a-zA-Z0-9\.\-_\/]/', '', $component);

        // Convert slashes to dots for Laravel view notation
        $component = str_replace('/', '.', $component);

        // Ensure component starts with a valid prefix
        $allowedPrefixes = config('storybook.allowed_prefixes', ['components', 'blocks', 'partials']);
        $hasValidPrefix = false;

        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($component, $prefix.'.')) {
                $hasValidPrefix = true;
                break;
            }
        }

        // If no valid prefix, assume it's a component
        if (! $hasValidPrefix && ! empty($component)) {
            $component = 'components.'.$component;
        }

        return $component;
    }

    /**
     * Prepare view data from arguments and context
     */
    private function prepareViewData(array $args, array $context): array
    {
        // Start with the component arguments
        $viewData = $args;

        // Add context data
        $viewData['_storybook'] = [
            'theme' => Arr::get($context, 'theme', 'light'),
            'viewport' => Arr::get($context, 'viewport', 'story'),
            'isStorybook' => true,
        ];

        return $viewData;
    }

    /**
     * Wrap rendered content in a container with metadata
     */
    private function wrapWithContainer(string $content, string $component, array $args, array $context): string
    {
        $theme = Arr::get($context, 'theme', 'light');

        return sprintf(
            '<div class="storybook-component" data-component="%s" data-theme="%s">%s</div>',
            htmlspecialchars($component),
            htmlspecialchars($theme),
            $content
        );
    }

    /**
     * Render an error message
     */
    private function renderError(string $title, string $message, string $component, array $args): string
    {
        return sprintf(
            '<div class="storybook-error" style="padding: 20px; border: 2px solid #ff6b6b; border-radius: 4px; background: #ffe0e0; color: #d63031; font-family: system-ui, sans-serif;">
                <h3>❌ %s</h3>
                <p><strong>Component:</strong> %s</p>
                <p><strong>Error:</strong> %s</p>
                <details style="margin-top: 10px;">
                    <summary>Component arguments:</summary>
                    <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 5px; font-size: 12px; overflow-x: auto;">%s</pre>
                </details>
            </div>',
            htmlspecialchars($title),
            htmlspecialchars($component),
            htmlspecialchars($message),
            htmlspecialchars(json_encode($args, JSON_PRETTY_PRINT))
        );
    }

    /**
     * Get the file path for a component
     */
    private function getComponentFilePath(string $component): ?string
    {
        $viewFinder = app('view.finder');

        try {
            return $viewFinder->find($component);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Extract variables from a component file (basic implementation)
     */
    private function extractComponentVariables(string $component): array
    {
        $filePath = $this->getComponentFilePath($component);

        if (! $filePath || ! file_exists($filePath)) {
            return [];
        }

        $content = file_get_contents($filePath);
        $variables = [];

        // Simple regex to find variables (this could be improved)
        if (preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', $content, $matches)) {
            $variables = array_unique($matches[1]);
        }

        return $variables;
    }

    /**
     * Recursively scan for component files
     */
    private function scanComponents(string $path, string $prefix): array
    {
        $components = [];

        if (! is_dir($path)) {
            return $components;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php' && str_contains($file->getFilename(), '.blade.')) {
                $relativePath = str_replace($path.'/', '', $file->getPathname());
                $componentName = str_replace(['/', '.blade.php'], ['.', ''], $relativePath);
                $components[] = $prefix.'.'.$componentName;
            }
        }

        return $components;
    }
}
