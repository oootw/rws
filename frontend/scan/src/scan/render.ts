import type { PlacePublicResponse } from '@guard-reviews/shared/types';
import type { Messages } from '../i18n';
import { escapeAttribute, escapeHtml } from '../lib/escape';

export type PlaceData = PlacePublicResponse['data'];

export type Screen =
  | 'stars'
  | 'platforms'
  | 'negative'
  | 'thanks'
  | 'thanks-redirect'
  | 'error'
  | 'expired';

export type ScanState = {
  screen: Screen;
  place: PlaceData | null;
  selectedStars: number;
  captchaToken: string;
};

export const createInitialState = (): ScanState => ({
  screen: 'stars',
  place: null,
  selectedStars: 0,
  captchaToken: '',
});

const renderStars = (place: PlaceData, messages: Messages): string => {
  const stars = Array.from({ length: 5 }, (_, index) => {
    const value = index + 1;

    return `
      <button
        type="button"
        class="star-button"
        data-star="${value}"
        aria-label="${escapeAttribute(messages.starLabel(value))}"
      >★</button>
    `;
  }).join('');

  return `
    <h1 class="title">${escapeHtml(place.title)}</h1>
    <p class="subtitle">${escapeHtml(messages.rateExperience)}</p>
    <div class="stars" role="group" aria-label="${escapeAttribute(messages.rateExperience)}">
      ${stars}
    </div>
  `;
};

const renderPlatforms = (place: PlaceData, messages: Messages): string => {
  const buttons = place.platforms
    .map(
      (platform) => `
        <button type="button" class="platform-button" data-platform="${escapeAttribute(platform.type)}">
          ${escapeHtml(platform.label)}
        </button>
      `,
    )
    .join('');

  return `
    <h1 class="title">${escapeHtml(place.title)}</h1>
    <p class="subtitle">${escapeHtml(messages.choosePlatform)}</p>
    <div class="platform-list">${buttons}</div>
  `;
};

const renderNegativeForm = (place: PlaceData, messages: Messages): string => {
  const captchaBlock = place.captcha_client_key
    ? '<div id="captcha-container"></div>'
    : '';

  return `
    <h1 class="title">${escapeHtml(messages.negativeTitle)}</h1>
    <form id="negative-form">
      <div class="field">
        <label for="review-text">${escapeHtml(messages.negativeText)}</label>
        <textarea id="review-text" name="text" required maxlength="5000"></textarea>
      </div>
      <div class="field">
        <label for="review-contact">${escapeHtml(messages.contact)}</label>
        <input id="review-contact" name="contact" type="text" required maxlength="255" autocomplete="tel" />
      </div>
      <label class="consent">
        <input type="checkbox" name="consent" required />
        <span>
          ${escapeHtml(messages.consent)}
          (<a href="${escapeAttribute(place.privacy_url)}" target="_blank" rel="noopener noreferrer">${escapeHtml(messages.privacy)}</a>)
        </span>
      </label>
      ${captchaBlock}
      <button type="submit" class="primary-button">${escapeHtml(messages.submit)}</button>
    </form>
  `;
};

const renderScreenContent = (
  state: ScanState,
  messages: Messages,
  errorMessage?: string,
): string => {
  const { screen, place } = state;

  if (!place) {
    return '';
  }

  const screens: Record<Screen, () => string> = {
    stars: () => renderStars(place, messages),
    platforms: () => renderPlatforms(place, messages),
    negative: () => renderNegativeForm(place, messages),
    thanks: () => `<p class="message">${escapeHtml(messages.thanks)}</p>`,
    'thanks-redirect': () => `<p class="message">${escapeHtml(messages.thanksRedirect)}</p>`,
    expired: () => `<p class="message">${escapeHtml(messages.errorExpired)}</p>`,
    error: () => `<p class="message">${escapeHtml(errorMessage ?? messages.errorGeneric)}</p>`,
  };

  return screens[screen]();
};

export const renderScanPage = (
  state: ScanState,
  messages: Messages,
  errorMessage?: string,
): string => {
  const place = state.place;

  if (!place) {
    return '';
  }

  const cardClass = place.background_image_url ? 'card card--background' : 'card';
  const style = place.background_image_url
    ? ` style="background-image:url('${escapeAttribute(place.background_image_url)}')"`
    : '';

  return `
    <div class="page">
      <section class="${cardClass}"${style}>
        ${renderScreenContent(state, messages, errorMessage)}
      </section>
    </div>
  `;
};

export const renderMessagePage = (message: string): string => `
  <div class="page">
    <section class="card">
      <p class="message">${escapeHtml(message)}</p>
    </section>
  </div>
`;
