type CaptchaTokenSetter = (token: string) => void;

const loadCaptchaScript = (): void => {
  if (document.querySelector('script[data-smart-captcha]')) {
    return;
  }

  const script = document.createElement('script');
  script.src = 'https://smartcaptcha.yandexcloud.net/captcha.js?render=onload&onload=onSmartCaptchaLoad';
  script.defer = true;
  script.dataset.smartCaptcha = 'true';
  document.head.appendChild(script);
};

export const mountCaptcha = (siteKey: string, setToken: CaptchaTokenSetter): void => {
  const renderWidget = (): void => {
    if (!window.smartCaptcha) {
      return;
    }

    window.smartCaptcha.render('captcha-container', {
      sitekey: siteKey,
      hl: document.documentElement.lang === 'en' ? 'en' : 'ru',
      callback: setToken,
    });
  };

  if (window.smartCaptcha) {
    renderWidget();
    return;
  }

  window.onSmartCaptchaLoad = renderWidget;
  loadCaptchaScript();
};
