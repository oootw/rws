import { create } from 'zustand';

export type OwnerMe = {
  id: string;
  name: string;
  email: string;
  subdomain: string | null;
};

type AuthState = {
  me: OwnerMe | null;
  setMe: (me: OwnerMe | null) => void;
  clear: () => void;
};

/**
 * Каркас auth-стора. Реальная инициализация (GET /api/owner/me + 401-редирект)
 * подключается в Фазе 1.
 */
export const useAuthStore = create<AuthState>((set) => ({
  me: null,
  setMe: (me) => set({ me }),
  clear: () => set({ me: null }),
}));
