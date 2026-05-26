import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import boundaries from 'eslint-plugin-boundaries';

const FSD_LAYERS = ['app', 'pages', 'widgets', 'features', 'entities', 'shared'];

const allowedDownstream = {
  app: ['pages', 'widgets', 'features', 'entities', 'shared'],
  pages: ['widgets', 'features', 'entities', 'shared'],
  widgets: ['features', 'entities', 'shared'],
  features: ['entities', 'shared'],
  entities: ['shared'],
  shared: [],
};

const boundariesRules = FSD_LAYERS.map((from) => ({
  from,
  allow: allowedDownstream[from],
}));

const deepImportPatterns = FSD_LAYERS.flatMap((layer) => [
  `@/${layer}/*/ui/**`,
  `@/${layer}/*/model/**`,
  `@/${layer}/*/api/**`,
  `@/${layer}/*/lib/**`,
  `@/${layer}/*/config/**`,
]);

export default tseslint.config(
  {
    ignores: ['dist/**', 'node_modules/**', 'public/**', '*.config.js', '*.config.ts'],
  },
  js.configs.recommended,
  ...tseslint.configs.recommended,
  {
    files: ['src/**/*.{ts,tsx}'],
    plugins: {
      react,
      'react-hooks': reactHooks,
      boundaries,
    },
    languageOptions: {
      ecmaVersion: 2022,
      sourceType: 'module',
      globals: {
        window: 'readonly',
        document: 'readonly',
        navigator: 'readonly',
        console: 'readonly',
      },
      parserOptions: {
        ecmaFeatures: { jsx: true },
      },
    },
    settings: {
      react: { version: 'detect' },
      'boundaries/elements': FSD_LAYERS.map((layer) => ({
        type: layer,
        pattern: `src/${layer}/**`,
      })),
    },
    rules: {
      ...react.configs.recommended.rules,
      ...reactHooks.configs.recommended.rules,
      'react/react-in-jsx-scope': 'off',
      'react/prop-types': 'off',

      'no-restricted-syntax': [
        'error',
        {
          selector: 'ClassDeclaration',
          message:
            'FP-only: ES6 classes are banned. Use functions, Zustand stores, or React Query hooks.',
        },
        {
          selector: 'ClassExpression',
          message:
            'FP-only: ES6 classes are banned. Use functions, Zustand stores, or React Query hooks.',
        },
      ],

      'no-restricted-imports': [
        'error',
        {
          paths: [
            {
              name: 'axios',
              message:
                'Direct axios import is forbidden outside shared/api. Use shared/api/httpClient or a React Query hook.',
            },
          ],
          patterns: deepImportPatterns.map((pattern) => ({
            group: [pattern],
            message:
              'Import slice content only through its public index.ts (e.g. @/entities/session).',
          })),
        },
      ],

      'boundaries/element-types': [
        'error',
        {
          default: 'disallow',
          rules: boundariesRules,
        },
      ],
    },
  },
  {
    files: ['src/shared/api/**/*.{ts,tsx}'],
    rules: {
      'no-restricted-imports': 'off',
    },
  },
  {
    files: ['src/**/*.test.{ts,tsx}'],
    rules: {
      'boundaries/element-types': 'off',
      'no-restricted-imports': 'off',
    },
  },
);
