import { fetchPlace, getPlaceIdFromPath, isApiError } from './api/client';
import { detectLocale, t } from './i18n';
import { createInitialState } from './scan/render';
import { renderMessagePage } from './scan/render';
import { createScanApp } from './scan/create-scan-app';
import './styles/main.css';

const renderLoading = (root: HTMLElement, text: string): void => {
  root.innerHTML = renderMessagePage(text);
};

const bootstrap = async (root: HTMLElement): Promise<void> => {
  const locale = detectLocale();
  document.documentElement.lang = locale;

  const messages = t(locale);
  const placeId = getPlaceIdFromPath();

  if (!placeId) {
    root.innerHTML = renderMessagePage(messages.errorNotFound);
    return;
  }

  renderLoading(root, messages.loading);

  const scanApp = createScanApp(root, placeId, messages, createInitialState());

  try {
    const response = await fetchPlace(placeId);
    await scanApp.init(response.data);
  } catch (error: unknown) {
    if (isApiError(error) && error.code === 'subscription_expired') {
      scanApp.showExpired();
      return;
    }

    if (isApiError(error) && error.status === 404) {
      root.innerHTML = renderMessagePage(messages.errorNotFound);
      return;
    }

    const message = isApiError(error) ? error.message : messages.errorGeneric;
    scanApp.showError(message);
  }
};

const app = document.querySelector<HTMLElement>('#app');

if (!app) {
  throw new Error('Root element #app not found');
}

void bootstrap(app);
