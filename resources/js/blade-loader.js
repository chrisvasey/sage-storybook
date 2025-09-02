/**
 * Blade Loader for Storybook
 * 
 * This utility renders Blade components server-side via a PHP endpoint
 * and injects the rendered HTML into the Storybook canvas.
 */

// Base configuration - will be overridden by user config
let STORYBOOK_CONFIG = {
    apiBaseUrl: 'http://localhost',
    assetsUrl: null,
};

/**
 * Configure the Blade loader
 * @param {Object} config Configuration object
 */
export function configure(config) {
    STORYBOOK_CONFIG = { ...STORYBOOK_CONFIG, ...config };
}

/**
 * Cache for rendered components to improve performance
 */
const componentCache = new Map();

/**
 * Generate a cache key for a component render
 */
function getCacheKey(component, args) {
    return `${component}-${JSON.stringify(args)}`;
}

/**
 * Render a Blade component with the given args
 */
export async function renderBladeComponent(component, args = {}, context = {}) {
    const cacheKey = getCacheKey(component, args);
    
    // Return cached version if available (in development only)
    if (process.env.NODE_ENV === 'development' && componentCache.has(cacheKey)) {
        return componentCache.get(cacheKey);
    }

    try {
        const response = await fetch(`${STORYBOOK_CONFIG.apiBaseUrl}/storybook/render/${component}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'text/html',
            },
            body: JSON.stringify({
                args,
                context: {
                    theme: context.globals?.theme || 'light',
                    viewport: context.viewMode || 'story',
                }
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const html = await response.text();
        
        // Cache the result
        componentCache.set(cacheKey, html);
        
        return html;
        
    } catch (error) {
        console.error(`Failed to render Blade component "${component}":`, error);
        
        // Return error state HTML
        return `
            <div style="padding: 20px; border: 2px solid #ff6b6b; border-radius: 4px; background: #ffe0e0; color: #d63031;">
                <h3>❌ Component Render Error</h3>
                <p><strong>Component:</strong> ${component}</p>
                <p><strong>Error:</strong> ${error.message}</p>
                <details style="margin-top: 10px;">
                    <summary>Arguments passed to component:</summary>
                    <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 5px; font-size: 12px;">${JSON.stringify(args, null, 2)}</pre>
                </details>
            </div>
        `;
    }
}

/**
 * Standard render function for Storybook stories
 * Usage in stories: render: renderBlade
 */
export function renderBlade(args, context) {
    const component = context.parameters?.server?.component;
    
    if (!component) {
        return `
            <div style="padding: 20px; border: 2px solid #fdcb6e; background: #fff8e1; color: #e17055;">
                <h3>⚠️ Configuration Error</h3>
                <p>No component specified in story parameters.</p>
                <p>Add <code>parameters: { server: { component: 'component.name' } }</code> to your story.</p>
            </div>
        `;
    }

    // Create a container element
    const container = document.createElement('div');
    container.innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">🔄 Loading component...</div>';

    // Render the component asynchronously
    renderBladeComponent(component, args, context)
        .then(html => {
            container.innerHTML = html;
            
            // Add theme class to container for styling
            const theme = context.globals?.theme || 'light';
            container.className = `storybook-theme-${theme}`;
            
            // Dispatch custom event for component loaded
            container.dispatchEvent(new CustomEvent('storybook:blade-loaded', {
                detail: { component, args, html }
            }));
        })
        .catch(error => {
            container.innerHTML = `
                <div style="padding: 20px; border: 2px solid #ff6b6b; border-radius: 4px; background: #ffe0e0; color: #d63031;">
                    <h3>❌ Render Error</h3>
                    <p>${error.message}</p>
                </div>
            `;
        });

    return container.outerHTML;
}

/**
 * Decorator to add Sage theme assets to stories
 */
export const withSageAssets = (storyFn, context) => {
    // Check if assets are already loaded
    if (!document.querySelector('#sage-storybook-assets')) {
        // Create a container for our assets
        const assetsContainer = document.createElement('div');
        assetsContainer.id = 'sage-storybook-assets';
        
        // Load the theme CSS
        const cssLink = document.createElement('link');
        cssLink.rel = 'stylesheet';
        cssLink.href = STORYBOOK_CONFIG.assetsUrl || `${STORYBOOK_CONFIG.apiBaseUrl}/app/themes/sage/public/build/assets/app.css`;
        assetsContainer.appendChild(cssLink);
        
        // Add to head
        document.head.appendChild(assetsContainer);
    }
    
    return storyFn();
};

/**
 * Clear the component cache (useful for development)
 */
export function clearBladeCache() {
    componentCache.clear();
    console.log('🧹 Blade component cache cleared');
}

// Expose cache clearing function globally for debugging
if (typeof window !== 'undefined') {
    window.clearBladeCache = clearBladeCache;
}