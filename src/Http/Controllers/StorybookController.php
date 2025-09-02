<?php

namespace ChrisVasey\SageStorybookBlade\Http\Controllers;

use ChrisVasey\SageStorybookBlade\Services\StorybookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class StorybookController
{
    protected StorybookService $storybookService;

    public function __construct(StorybookService $storybookService)
    {
        $this->storybookService = $storybookService;
    }

    /**
     * Render a specific component
     *
     * @return \Illuminate\Http\Response
     */
    public function render(Request $request, string $component)
    {
        // Get the request data
        $data = $request->json()->all();
        $args = $data['args'] ?? [];
        $context = $data['context'] ?? [];

        // Render the component
        $html = $this->storybookService->renderComponent($component, $args, $context);

        // Return HTML response with CORS headers
        return Response::make($html, 200, $this->getCorsHeaders());
    }

    /**
     * Get metadata for a component
     */
    public function metadata(string $component): JsonResponse
    {
        $metadata = $this->storybookService->getComponentMetadata($component);

        return response()->json($metadata, 200, $this->getCorsHeaders());
    }

    /**
     * List all available components
     */
    public function list(): JsonResponse
    {
        $components = $this->storybookService->listComponents();

        return response()->json([
            'components' => $components,
            'count' => count($components),
        ], 200, $this->getCorsHeaders());
    }

    /**
     * Health check endpoint
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'Sage Storybook',
            'timestamp' => now()->toISOString(),
            'version' => config('storybook.version', '1.0.0'),
        ], 200, $this->getCorsHeaders());
    }

    /**
     * Handle preflight OPTIONS requests
     *
     * @return \Illuminate\Http\Response
     */
    public function options()
    {
        return Response::make('', 200, array_merge($this->getCorsHeaders(), [
            'Access-Control-Max-Age' => '86400',
        ]));
    }

    /**
     * Get CORS headers
     */
    private function getCorsHeaders(): array
    {
        $allowedOrigins = config('storybook.cors.allowed_origins', ['*']);
        
        return [
            'Access-Control-Allow-Origin' => implode(', ', $allowedOrigins),
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept, Authorization',
        ];
    }
}