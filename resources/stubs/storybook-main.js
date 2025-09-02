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
    // Ensure we can resolve our blade loader
    config.resolve = config.resolve || {};
    config.resolve.alias = {
      ...config.resolve.alias,
      '@storybook/blade-loader': new URL('../node_modules/@roots/sage-storybook-blade/resources/js/blade-loader.js', import.meta.url).pathname,
    };
    return config;
  },
};

export default config;