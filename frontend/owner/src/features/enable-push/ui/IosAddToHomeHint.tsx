/**
 * Hint для Safari iOS: пуши доступны только из standalone-режима
 * (запуск иконки с домашнего экрана). До A2HS — кнопка «Включить» не работает,
 * показываем пошаговую инструкцию.
 */
export function IosAddToHomeHint() {
  return (
    <div className="rounded-2xl border border-warning/30 bg-warning/10 p-4 text-sm text-ink-700">
      <p className="font-medium text-ink-900">
        Добавьте кабинет на главный экран, чтобы получать push-уведомления
      </p>
      <ol className="mt-2 list-decimal space-y-1 pl-5">
        <li>Нажмите «Поделиться» в строке Safari</li>
        <li>Выберите «На экран “Домой”»</li>
        <li>Откройте кабинет с иконки на главном экране</li>
      </ol>
    </div>
  );
}
