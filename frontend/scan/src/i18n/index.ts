export type Locale = 'ru' | 'en';

export type Messages = {
  loading: string;
  rateExperience: string;
  choosePlatform: string;
  negativeTitle: string;
  negativeText: string;
  contact: string;
  consent: string;
  submit: string;
  thanks: string;
  thanksRedirect: string;
  errorGeneric: string;
  errorExpired: string;
  errorNotFound: string;
  privacy: string;
  starLabel: (value: number) => string;
};

const ru: Messages = {
  loading: 'Загрузка…',
  rateExperience: 'Как вам у нас?',
  choosePlatform: 'Куда хотите оставить отзыв?',
  negativeTitle: 'Расскажите, что пошло не так',
  negativeText: 'Ваш отзыв',
  contact: 'Контакт для связи',
  consent: 'Я согласен на обработку персональных данных',
  submit: 'Отправить',
  thanks: 'Спасибо! Мы свяжемся с вами.',
  thanksRedirect: 'Спасибо! Переходим на площадку…',
  errorGeneric: 'Извините, что-то пошло не так.',
  errorExpired: 'Сервис временно недоступен.',
  errorNotFound: 'Страница не найдена.',
  privacy: 'Политика конфиденциальности',
  starLabel: (value: number) => `${value} из 5`,
};

const en: Messages = {
  loading: 'Loading…',
  rateExperience: 'How was your experience?',
  choosePlatform: 'Where would you like to leave a review?',
  negativeTitle: 'Tell us what went wrong',
  negativeText: 'Your feedback',
  contact: 'Contact details',
  consent: 'I agree to the processing of personal data',
  submit: 'Submit',
  thanks: 'Thank you! We will contact you.',
  thanksRedirect: 'Thank you! Redirecting…',
  errorGeneric: 'Sorry, something went wrong.',
  errorExpired: 'Service is temporarily unavailable.',
  errorNotFound: 'Page not found.',
  privacy: 'Privacy policy',
  starLabel: (value: number) => `${value} of 5`,
};

const catalogs: Record<Locale, Messages> = { ru, en };

export function detectLocale(): Locale {
  const languages = navigator.languages.length > 0 ? navigator.languages : [navigator.language];

  for (const language of languages) {
    if (language.toLowerCase().startsWith('ru')) {
      return 'ru';
    }
  }

  return 'en';
}

export function t(locale: Locale): Messages {
  return catalogs[locale];
}
