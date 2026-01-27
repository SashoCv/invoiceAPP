import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    safelist: [
        // Invoice status colors
        'bg-gray-100', 'text-gray-700',
        'bg-blue-100', 'text-blue-700',
        'bg-yellow-100', 'text-yellow-700',
        'bg-green-100', 'text-green-700',
        'bg-amber-100', 'text-amber-700',
        'bg-red-100', 'text-red-700',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
