/** @type { import('@storybook/html-vite').StorybookConfig } */
const config = {
  stories: ['../resources/stories/**/*.stories.@(js|jsx|ts|tsx|mdx)'],
  addons: [
    '@storybook/addon-controls',
    '@storybook/addon-viewport',
    '@storybook/addon-backgrounds',
    '@storybook/addon-a11y',
    '@storybook/addon-docs'
  ],
  framework: {
    name: '@storybook/html-vite',
    options: {},
  },
  typescript: {
    reactDocgen: false,
  },
  core: {
    disableTelemetry: true,
  },
  viteFinal: async (config) => {
    const { default: tailwindcss } = await import('@tailwindcss/vite');
    const fs = await import('fs');
    const path = await import('path');
    
    // Add Tailwind CSS plugin
    config.plugins = config.plugins || [];
    config.plugins.push(tailwindcss());
    
    // Find the blade loader file dynamically
    function findBladeLoader(startPath) {
      const vendorPaths = [
        'vendor/chrisvasey/sage-storybook/resources/js/blade-loader.js',
        '../vendor/chrisvasey/sage-storybook/resources/js/blade-loader.js',
        '../../vendor/chrisvasey/sage-storybook/resources/js/blade-loader.js',
        '../../../vendor/chrisvasey/sage-storybook/resources/js/blade-loader.js',
        '../../../../vendor/chrisvasey/sage-storybook/resources/js/blade-loader.js',
        '../../../../../vendor/chrisvasey/sage-storybook/resources/js/blade-loader.js'
      ];
      
      for (const vendorPath of vendorPaths) {
        const fullPath = path.resolve(startPath, vendorPath);
        if (fs.existsSync(fullPath)) {
          return fullPath;
        }
      }
      
      // Default fallback
      return path.resolve(startPath, '../../../../../vendor/chrisvasey/sage-storybook/resources/js/blade-loader.js');
    }
    
    const bladeLoaderPath = findBladeLoader(path.dirname(new URL(import.meta.url).pathname));
    
    config.resolve = config.resolve || {};
    config.resolve.alias = {
      ...config.resolve.alias,
      '@storybook/blade-loader': bladeLoaderPath,
      '@styles': new URL('../resources/css', import.meta.url).pathname,
      '@scripts': new URL('../resources/js', import.meta.url).pathname,
      '@fonts': new URL('../resources/fonts', import.meta.url).pathname,
      '@images': new URL('../resources/images', import.meta.url).pathname,
    };
    return config;
  },
};

export default config;