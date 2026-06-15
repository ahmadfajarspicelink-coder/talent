import defaultTheme from 'tailwindcss/defaultTheme';
import colors from 'tailwindcss/colors';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                // NovaSpark uses Inter for headings & body.
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Remap the existing accent (indigo) to NovaSpark "Nova Blue"
                // so all current indigo-* classes adopt the brand color.
                indigo: colors.blue,

                // Named NovaSpark brand tokens.
                nova: {
                    blue: '#2563EB',
                    dark: '#0F172A',
                    light: '#F8FAFC',
                },
                spark: {
                    orange: '#F97316',
                    green: '#22C55E',
                    red: '#EF4444',
                },
            },
            boxShadow: {
                'nova-sm': '0 1px 2px rgba(0,0,0,0.05)',
                'nova-md': '0 4px 6px rgba(0,0,0,0.07)',
                'nova-lg': '0 10px 15px rgba(0,0,0,0.1)',
                'nova-xl': '0 20px 25px rgba(0,0,0,0.15)',
            },
            borderRadius: {
                xl: '16px',
            },
        },
    },

    plugins: [forms],
};
