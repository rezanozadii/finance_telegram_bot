interface TelegramWebAppUser {
  id: number;
  language_code?: string;
  first_name?: string;
  last_name?: string;
  username?: string;
}

interface TelegramSafeAreaInset {
  top: number;
  bottom: number;
  left: number;
  right: number;
}

interface TelegramWebApp {
  expand: () => void;
  ready: () => void;
  initDataUnsafe: {
    user?: TelegramWebAppUser;
  };
  /** Outer safe area (Telegram native UI — e.g. close button). Available Bot API 7.7+ */
  safeAreaInset?: TelegramSafeAreaInset;
  /** Content safe area (where mini-app content should live). Available Bot API 7.7+ */
  contentSafeAreaInset?: TelegramSafeAreaInset;
  onEvent: (event: string, callback: () => void) => void;
  offEvent: (event: string, callback: () => void) => void;
}

interface Window {
  Telegram?: {
    WebApp: TelegramWebApp;
  };
}
