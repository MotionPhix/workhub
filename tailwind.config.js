import forms from '@tailwindcss/forms'
import defaultTheme from 'tailwindcss/defaultTheme'

// /** @type {import('tailwindcss').Config} */
// export default {
//   darkMode: 'class',
//   theme: {
//     container: {
//       center: true,
//       padding: '2rem',
//       screens: {
//         '2xl': '1400px',
//       },
//     },
//     extend: {
//       colors: {
//         border: 'hsl(var(--border) / <alpha-value>)',
//         input: 'hsl(var(--input) / <alpha-value>)',
//         ring: 'hsl(var(--ring) / <alpha-value>)',
//         background: 'hsl(var(--background) / <alpha-value>)',
//         foreground: 'hsl(var(--foreground) / <alpha-value>)',
//         primary: {
//           DEFAULT: 'hsl(var(--primary) / <alpha-value>)',
//           foreground: 'hsl(var(--primary-foreground) / <alpha-value>)',
//         },
//         secondary: {
//           DEFAULT: 'hsl(var(--secondary) / <alpha-value>)',
//           foreground: 'hsl(var(--secondary-foreground) / <alpha-value>)',
//         },
//         destructive: {
//           DEFAULT: 'hsl(var(--destructive) / <alpha-value>)',
//           foreground: 'hsl(var(--destructive-foreground) / <alpha-value>)',
//         },
//         muted: {
//           DEFAULT: 'hsl(var(--muted) / <alpha-value>)',
//           foreground: 'hsl(var(--muted-foreground) / <alpha-value>)',
//         },
//         accent: {
//           DEFAULT: 'hsl(var(--accent) / <alpha-value>)',
//           foreground: 'hsl(var(--accent-foreground) / <alpha-value>)',
//         },
//         popover: {
//           DEFAULT: 'hsl(var(--popover) / <alpha-value>)',
//           foreground: 'hsl(var(--popover-foreground) / <alpha-value>)',
//         },
//         card: {
//           DEFAULT: 'hsl(var(--card) / <alpha-value>)',
//           foreground: 'hsl(var(--card-foreground) / <alpha-value>)',
//         },
//       },
//       borderRadius: {
//         lg: 'var(--radius)',
//         md: 'calc(var(--radius) - 2px)',
//         sm: 'calc(var(--radius) - 4px)',
//       },
//       fontFamily: {
//         sans: ['Inter', ...defaultTheme.fontFamily.sans],
//         headings: ['DM Serif Display', ...defaultTheme.fontFamily.serif],
//         figures: ['Eczar', ...defaultTheme.fontFamily.serif],
//       },
//       keyframes: {
//         'accordion-down': {
//           from: { height: 0 },
//           to: { height: 'var(--radix-accordion-content-height)' },
//         },
//         'accordion-up': {
//           from: { height: 'var(--radix-accordion-content-height)' },
//           to: { height: 0 },
//         },
//       },
//       animation: {
//         'accordion-down': 'accordion-down 0.2s ease-out',
//         'accordion-up': 'accordion-up 0.2s ease-out',
//       },
//       scrollBehavior: {
//         'smooth': 'smooth'
//       }
//     },
//   },
// }

/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',

  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './node_modules/@inertiaui/modal-vue/src/**/*.{js,vue}',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    './resources/js/**/*.{js,ts,tsx,vue}',
  ],

  theme: {
    extend: {
      colors: {
        border: "var(--border)",
        input: "var(--input)",
        ring: "var(--ring)",
        background: "var(--background)",
        foreground: "var(--foreground)",
        primary: {
          DEFAULT: "var(--primary)",
          foreground: "var(--primary-foreground)",
        },
        secondary: {
          DEFAULT: "var(--secondary)",
          foreground: "var(--secondary-foreground)",
        },
        destructive: {
          DEFAULT: "var(--destructive)",
          foreground: "var(--destructive-foreground)",
        },
        muted: {
          DEFAULT: "var(--muted)",
          foreground: "var(--muted-foreground)",
        },
        accent: {
          DEFAULT: "var(--accent)",
          foreground: "var(--accent-foreground)",
        },
        popover: {
          DEFAULT: "var(--popover)",
          foreground: "var(--popover-foreground)",
        },
        card: {
          DEFAULT: "var(--card)",
          foreground: "var(--card-foreground)",
        },
        sidebar: {
          DEFAULT: "var(--sidebar)",
          foreground: "var(--sidebar-foreground)",
          primary: "var(--sidebar-primary)",
          "primary-foreground": "var(--sidebar-primary-foreground)",
          accent: "var(--sidebar-accent)",
          "accent-foreground": "var(--sidebar-accent-foreground)",
          border: "var(--sidebar-border)",
          ring: "var(--sidebar-ring)",
        },
        chart: {
          1: "var(--chart-1)",
          2: "var(--chart-2)",
          3: "var(--chart-3)",
          4: "var(--chart-4)",
          5: "var(--chart-5)",
        },
      },
      borderRadius: {
        lg: "var(--radius)",
        md: "calc(var(--radius) - 2px)",
        sm: "calc(var(--radius) - 4px)",
      },
      fontFamily: {
        sans: ["var(--font-sans)"],
        serif: ["var(--font-serif)"],
        mono: ["var(--font-mono)"],
        headings: ["var(--font-headings)"],
        figures: ["var(--font-figures)"],
      },
    },
  },

  plugins: [
    forms,
    require('tailwindcss-animate'),
    require('tailwind-scrollbar')
  ],
}
