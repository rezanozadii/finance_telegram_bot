interface TelegramWebAppUser {
  id: number;
  language_code?: string;
  first_name?: string;
  last_name?: string;
  username?: string;
}

interface TelegramWebApp {
  expand: () => void;
  ready: () => void;
  initDataUnsafe: {
    user?: TelegramWebAppUser;
  };
}

interface Window {
  Telegram?: {
    WebApp: TelegramWebApp;
  };
}
