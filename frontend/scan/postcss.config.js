export default {
  plugins: {
    // Резолвит `@import '@guard-reviews/shared/styles/tokens.css'`.
    // Должен идти ДО tailwindcss.
    'postcss-import': {},
    tailwindcss: {},
    autoprefixer: {},
  },
};
