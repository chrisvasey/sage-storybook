import { renderBlade } from '@storybook/blade-loader';

export default {
  title: 'Example/Button',
  render: renderBlade,
  parameters: {
    server: {
      component: 'components.button', // Path to your Blade component
    },
  },
  argTypes: {
    text: { 
      control: 'text',
      description: 'Button text content'
    },
    variant: { 
      control: 'select', 
      options: ['primary', 'secondary', 'danger'],
      description: 'Button style variant'
    },
    size: { 
      control: 'select', 
      options: ['sm', 'md', 'lg'],
      description: 'Button size'
    },
    disabled: { 
      control: 'boolean',
      description: 'Disable the button'
    },
  },
  args: {
    text: 'Click me',
    variant: 'primary',
    size: 'md',
    disabled: false,
  },
};

export const Default = {};

export const Secondary = {
  args: {
    variant: 'secondary',
  },
};

export const Large = {
  args: {
    size: 'lg',
  },
};

export const Disabled = {
  args: {
    disabled: true,
  },
};