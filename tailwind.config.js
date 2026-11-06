/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        forest: {
          950: '#051F20',
          900: '#0B2B26',
          800: '#163832',
          700: '#235347',
          300: '#8EB69B',
          100: '#DAF1DE',
        },
        'primary-dark': '#051F20',
        'surface-dark': '#0B2B26',
        'secondary-surface': '#163832',
        'primary-accent': '#235347',
        'soft-accent': '#8EB69B',
        'light-background': '#DAF1DE',
      },
    },
  },
  plugins: [],
};
