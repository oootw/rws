import type { Config } from 'tailwindcss';

/**
 * Kvell Merchant — светлая, мягкая, типографически-щедрая палитра.
 * Эти токены — единственная точка правды для брендинга Owner-панели.
 */
const config: Config = {
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        canvas: '#FAFAF7',
        surface: '#FFFFFF',
        ink: {
          900: '#0F172A',
          700: '#334155',
          500: '#64748B',
          400: '#94A3B8',
          200: '#E2E8F0',
          100: '#F1F5F9',
        },
        accent: {
          DEFAULT: '#10B981',
          fg: '#FFFFFF',
          soft: '#ECFDF5',
        },
        danger: {
          DEFAULT: '#F97066',
          fg: '#FFFFFF',
          soft: '#FEF2F2',
        },
        warning: {
          DEFAULT: '#F59E0B',
          fg: '#1F2937',
          soft: '#FFFBEB',
        },
      },
      fontFamily: {
        sans: [
          'Inter',
          'Manrope',
          '-apple-system',
          'BlinkMacSystemFont',
          'Segoe UI',
          'sans-serif',
        ],
      },
      boxShadow: {
        soft: '0 1px 3px rgba(15, 23, 42, 0.04), 0 8px 24px rgba(15, 23, 42, 0.04)',
        lift: '0 4px 12px rgba(15, 23, 42, 0.06), 0 12px 36px rgba(15, 23, 42, 0.08)',
      },
      borderRadius: {
        '2xl': '1rem',
        '3xl': '1.5rem',
      },
      maxWidth: {
        shell: '1200px',
      },
    },
  },
  plugins: [],
};

export default config;
