/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_ALLOW_TENANT_QUERY?: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}
