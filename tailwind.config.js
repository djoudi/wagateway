/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php',
        './app/Filament/**/*.php',
    ],
    theme: {
        extend: {
            colors: {
                'wa-green': '#25D366',
                'wa-dark':  '#128C7E',
            },
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                mono: ['JetBrains Mono', 'ui-monospace'],
            },
        },
    },
};
