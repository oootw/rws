/**
 * Пользователь нажал «Не разрешать» в браузерном prompt'е. Заново вызвать
 * permission() нельзя — нужно вручную сбросить в настройках браузера.
 */
export function PushPermissionDeniedHint() {
  return (
    <div className="rounded-2xl border border-danger/30 bg-danger/10 p-4 text-sm text-ink-700">
      <p className="font-medium text-ink-900">
        Уведомления заблокированы в браузере
      </p>
      <p className="mt-1">
        Чтобы получать пуши, откройте настройки сайта в адресной строке и
        разрешите уведомления. После этого нажмите «Включить» ещё раз.
      </p>
    </div>
  );
}
