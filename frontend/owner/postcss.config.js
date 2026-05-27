export default {
  plugins: {
    // Резолвит `@import '@guard-reviews/shared/styles/tokens.css'`.
    // Должен идти ДО tailwindcss (Tailwind иначе не увидит результирующий CSS).
    'postcss-import': {},
    tailwindcss: {},
    autoprefixer: {},
  },
};
