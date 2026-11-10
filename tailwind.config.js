import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

const v = (name) => `var(--${name})`;

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Manrope', ...defaultTheme.fontFamily.sans],
                heading: ['"Space Grotesk"', 'Manrope', ...defaultTheme.fontFamily.sans],
            },

            // Warna semantik dari token desain (variabel CSS di resources/css/app.css).
            colors: {
                page: v('bg-page'),
                surface: v('bg-surface'),
                muted: v('bg-muted'),
                tint: v('bg-tint'),
                'gold-tint': v('bg-gold-tint'),
                glass: v('bg-glass'),

                ink: {
                    DEFAULT: v('text-primary'),
                    secondary: v('text-secondary'),
                    muted: v('text-muted'),
                    disabled: v('text-disabled'),
                    brand: v('text-brand'),
                    gold: v('text-gold'),
                    'on-primary': v('text-on-primary'),
                },

                brand: {
                    DEFAULT: v('brand-primary'),
                    hover: v('brand-primary-hover'),
                    active: v('brand-primary-active'),
                    soft: v('brand-soft'),
                    border: v('brand-border'),
                    mint: v('brand-mint'),
                },

                gold: {
                    DEFAULT: v('gold-base'),
                    soft: v('gold-soft'),
                    border: v('gold-border'),
                },

                line: {
                    DEFAULT: v('border-default'),
                    subtle: v('border-subtle'),
                    strong: v('border-strong'),
                    control: v('border-control'),
                },

                success: { DEFAULT: v('status-success'), soft: v('status-success-soft'), text: v('status-success-text'), border: v('status-success-border') },
                warning: { DEFAULT: v('status-warning'), soft: v('status-warning-soft'), text: v('status-warning-text'), border: v('status-warning-border') },
                danger: { DEFAULT: v('status-danger'), soft: v('status-danger-soft'), text: v('status-danger-text'), border: v('status-danger-border') },
                info: { DEFAULT: v('status-info'), soft: v('status-info-soft'), text: v('status-info-text'), border: v('status-info-border') },
                neutral: { DEFAULT: v('status-neutral'), soft: v('status-neutral-soft'), text: v('status-neutral-text'), border: v('status-neutral-border') },

                state: { hover: v('state-hover'), pressed: v('state-pressed'), selected: v('state-selected') },

                // Halaman lama (Fase 1-6) memakai indigo bawaan Breeze; dipetakan ke hijau merek
                // sampai tiap halaman disesuaikan dengan desain barunya.
                indigo: {
                    50: '#f3faf6',
                    100: '#e8f1ee',
                    200: '#cfe3dc',
                    300: '#9cc5b8',
                    400: '#3f8a78',
                    500: '#278069',
                    600: '#1f6b5b',
                    700: '#185648',
                    800: '#134a3e',
                    900: '#0f3a31',
                },
            },

            borderRadius: {
                sm: '8px',
                md: '12px',
                lg: '18px',
                xl: '24px',
                '2xl': '32px',
            },

            minHeight: { 11: '2.75rem' },
        },
    },

    plugins: [forms],
};
