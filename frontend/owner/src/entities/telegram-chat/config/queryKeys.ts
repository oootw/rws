export const telegramChatsQueryKeys = {
  all: ['telegram-chats'] as const,
  list: () => [...telegramChatsQueryKeys.all, 'list'] as const,
};
