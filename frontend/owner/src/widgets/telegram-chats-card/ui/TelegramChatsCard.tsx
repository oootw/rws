import { useState } from 'react';

import { useTelegramChatsQuery } from '@/entities/telegram-chat';
import type { IssuedChatLink, TelegramChat } from '@/entities/telegram-chat';
import { IssueLinkButton } from '@/features/issue-telegram-chat-link';
import { UnlinkChatButton } from '@/features/unlink-telegram-chat';
import { Card, Skeleton } from '@/shared/ui';

const formatDate = (iso: string): string =>
  new Date(iso).toLocaleString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });

const chatLabel = (chat: TelegramChat): string =>
  chat.title !== null && chat.title !== '' ? chat.title : chat.chat_id;

function ChatRow({ chat }: { chat: TelegramChat }) {
  return (
    <li className="flex items-start justify-between gap-3 rounded-2xl border border-ink-100 p-3">
      <div className="min-w-0 flex-1">
        <p className="truncate text-sm font-medium text-ink-900">{chatLabel(chat)}</p>
        <p className="text-xs text-ink-500">Привязан {formatDate(chat.linked_at)}</p>
      </div>
      <UnlinkChatButton chatRowId={chat.id} chatTitle={chatLabel(chat)} />
    </li>
  );
}

function IssuedLinkHint({ link }: { link: IssuedChatLink }) {
  return (
    <div className="rounded-2xl border border-accent/30 bg-accent-soft/30 p-3 text-sm text-ink-700">
      <p className="font-medium text-ink-900">Откройте ссылку в Telegram</p>
      <p className="mt-1">
        Выберите чат, в который хотите добавить бота. После добавления чат
        появится в списке — это может занять несколько секунд.
      </p>
      <a
        href={link.deep_link}
        target="_blank"
        rel="noopener noreferrer"
        className="mt-2 inline-block break-all text-xs text-accent underline"
      >
        {link.deep_link}
      </a>
      <p className="mt-1 text-xs text-ink-500">
        Срок действия: до {formatDate(link.expires_at)}
      </p>
    </div>
  );
}

export function TelegramChatsCard() {
  const chats = useTelegramChatsQuery();
  const [issued, setIssued] = useState<IssuedChatLink | null>(null);

  if (chats.isPending) {
    return (
      <Card as="section" className="space-y-3" aria-busy="true">
        <Skeleton className="h-5 w-1/3" />
        <Skeleton className="h-10 w-full rounded-xl" />
      </Card>
    );
  }

  const rows = chats.data ?? [];

  return (
    <Card as="section" className="space-y-4">
      <header className="space-y-1">
        <h2 className="text-base font-semibold text-ink-900">Общий Telegram-чат</h2>
        <p className="text-sm text-ink-500">
          Привяжите групповой чат — туда будут приходить уведомления о негативных
          отзывах, видимые всей команде.
        </p>
      </header>

      {rows.length === 0 ? (
        <p className="text-sm text-ink-500">Чаты ещё не привязаны.</p>
      ) : (
        <ul className="space-y-2">
          {rows.map((chat) => (
            <ChatRow key={chat.id} chat={chat} />
          ))}
        </ul>
      )}

      <IssueLinkButton onIssued={setIssued} />

      {issued !== null && <IssuedLinkHint link={issued} />}
    </Card>
  );
}
