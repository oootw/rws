export type Platform = {
  type: string;
  url: string;
  label: string;
};

/**
 * Scan-side фичи (whitelist на бэке: только `custom_branding`/`qr_themes`).
 * Owner-only фичи сюда не утекают — backend сам фильтрует.
 */
export type ScanFeature = 'custom_branding' | 'qr_themes';

export type PlacePublicResponse = {
  data: {
    id: string;
    title: string;
    background_image_url: string | null;
    platforms: Platform[];
    subscription_active: boolean;
    captcha_client_key: string | null;
    privacy_url: string;
    tariff_features: ScanFeature[];
  };
};

export type RedirectResponse = {
  ok: true;
  url: string;
};

export type OkResponse = {
  ok: true;
};

export type ApiErrorResponse = {
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
};

export type ReviewPayload = {
  stars: number;
  text: string;
  contact: string;
  consent_accepted: boolean;
  captcha_token: string;
};

declare global {
  interface Window {
    smartCaptcha?: {
      render: (
        containerId: string,
        options: {
          sitekey: string;
          hl?: string;
          callback?: (token: string) => void;
        },
      ) => string;
      getResponse?: (widgetId: string) => string;
    };
    onSmartCaptchaLoad?: () => void;
  }
}

export {};
