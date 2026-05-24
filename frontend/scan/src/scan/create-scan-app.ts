import type { Messages } from '../i18n';
import {
  getFirstValidationError,
  isApiError,
} from '../api/errors';
import {
  logScan,
  redirectToPlatform,
  reportCriticalError,
  submitReview,
} from '../api/client';
import { mountCaptcha } from './captcha';
import type { PlaceData, ScanState } from './render';
import { renderScanPage } from './render';

type ScanAppContext = {
  root: HTMLElement;
  placeId: string;
  messages: Messages;
  state: ScanState;
};

type ScanApp = {
  init: (place: PlaceData) => Promise<void>;
  showExpired: () => void;
  showError: (message?: string) => void;
};

const setScreen = (ctx: ScanAppContext, screen: ScanState['screen'], errorMessage?: string): void => {
  ctx.state.screen = screen;
  ctx.root.innerHTML = renderScanPage(ctx.state, ctx.messages, errorMessage);
  bindEvents(ctx);
};

const handleStarSelection = async (ctx: ScanAppContext, stars: number): Promise<void> => {
  const { state, placeId } = ctx;
  const place = state.place;

  if (!place) {
    return;
  }

  state.selectedStars = stars;

  if (stars >= 4) {
    if (place.platforms.length === 0) {
      try {
        await reportCriticalError(placeId, 'no_platforms');
      } catch {
        // Показываем ошибку пользователю даже если отчёт не отправился.
      }

      setScreen(ctx, 'error');
      return;
    }

    setScreen(ctx, 'platforms');
    return;
  }

  setScreen(ctx, 'negative');
};

const handlePlatformSelection = async (ctx: ScanAppContext, platformType: string): Promise<void> => {
  const { placeId, messages } = ctx;

  try {
    const response = await redirectToPlatform(placeId, platformType);
    setScreen(ctx, 'thanks-redirect');
    window.location.assign(response.url);
  } catch (error: unknown) {
    const message = isApiError(error) ? error.message : messages.errorGeneric;
    setScreen(ctx, 'error', message);
  }
};

const handleNegativeSubmit = async (ctx: ScanAppContext, form: HTMLFormElement): Promise<void> => {
  const { state, placeId, messages } = ctx;
  const submitButton = form.querySelector<HTMLButtonElement>('button[type="submit"]');

  submitButton?.setAttribute('disabled', 'true');

  const formData = new FormData(form);
  const text = String(formData.get('text') ?? '').trim();
  const contact = String(formData.get('contact') ?? '').trim();
  const consentAccepted = formData.get('consent') === 'on';
  const captchaToken = state.captchaToken || (state.place?.captcha_client_key ? '' : 'local-dev-token');

  try {
    await submitReview(placeId, {
      stars: state.selectedStars,
      text,
      contact,
      consent_accepted: consentAccepted,
      captcha_token: captchaToken,
    });

    setScreen(ctx, 'thanks');
  } catch (error: unknown) {
    submitButton?.removeAttribute('disabled');

    if (isApiError(error)) {
      const validationMessage = getFirstValidationError(error);
      setScreen(ctx, 'error', validationMessage ?? error.message);
      return;
    }

    setScreen(ctx, 'error', messages.errorGeneric);
  }
};

const bindEvents = (ctx: ScanAppContext): void => {
  const { root, state } = ctx;

  root.querySelectorAll<HTMLButtonElement>('[data-star]').forEach((button) => {
    button.addEventListener('click', () => {
      void handleStarSelection(ctx, Number(button.dataset.star));
    });
  });

  root.querySelectorAll<HTMLButtonElement>('[data-platform]').forEach((button) => {
    button.addEventListener('click', () => {
      void handlePlatformSelection(ctx, button.dataset.platform ?? '');
    });
  });

  const form = root.querySelector<HTMLFormElement>('#negative-form');
  form?.addEventListener('submit', (event) => {
    event.preventDefault();
    void handleNegativeSubmit(ctx, form);
  });

  if (state.screen === 'negative' && state.place?.captcha_client_key) {
    mountCaptcha(state.place.captcha_client_key, (token) => {
      state.captchaToken = token;
    });
  }
};

export const createScanApp = (
  root: HTMLElement,
  placeId: string,
  messages: Messages,
  state: ScanState,
): ScanApp => {
  const ctx: ScanAppContext = { root, placeId, messages, state };

  return {
    async init(place: PlaceData): Promise<void> {
      ctx.state.place = place;
      await logScan(placeId);
      setScreen(ctx, 'stars');
    },

    showExpired(): void {
      setScreen(ctx, 'expired');
    },

    showError(message?: string): void {
      setScreen(ctx, 'error', message);
    },
  };
};

export type { ScanApp };
