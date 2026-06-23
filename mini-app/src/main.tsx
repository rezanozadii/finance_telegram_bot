import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { init, retrieveRawInitData } from '@telegram-apps/sdk-react';
import { setInitDataRaw } from './api/client';
import App from './App';
import '@telegram-apps/telegram-ui/dist/styles.css';
import './index.css';

function applyTelegramSafeAreas() {
  const tg = window.Telegram?.WebApp;
  if (!tg) return;

  const ci = tg.contentSafeAreaInset ?? { top: 0, bottom: 0 };
  const si = tg.safeAreaInset       ?? { top: 0, bottom: 0 };

  // TOP: prefer contentSafeAreaInset (the Telegram close-button overlay height).
  // Fall back to safeAreaInset (device notch) only when content inset is absent.
  // If BOTH are 0 (old Telegram that doesn't expose these APIs), do NOT write
  // the variable — leave the CSS default env(safe-area-inset-top, 0px) in place
  // so the iOS system notch value is still used.
  const top = ci.top > 0 ? ci.top : si.top;
  if (top > 0) {
    document.documentElement.style.setProperty('--tg-safe-top', `${top}px`);
  }

  // BOTTOM: always take the larger of both (home indicator / bottom overlay).
  document.documentElement.style.setProperty(
    '--tg-safe-bottom',
    `${Math.max(ci.bottom, si.bottom)}px`,
  );
}

try {
  init();
  const raw = retrieveRawInitData();
  setInitDataRaw(raw ?? '');

  if (window.Telegram?.WebApp) {
    const tg = window.Telegram.WebApp;
    tg.expand();
    tg.ready();

    applyTelegramSafeAreas();
    tg.onEvent('safeAreaChanged',        applyTelegramSafeAreas);
    tg.onEvent('contentSafeAreaChanged', applyTelegramSafeAreas);
    tg.onEvent('viewportChanged',        applyTelegramSafeAreas);
  }
} catch {
  console.warn('Telegram SDK init failed — running outside Telegram?');
}

const root = document.getElementById('root')!;
createRoot(root).render(
  <StrictMode>
    <App />
  </StrictMode>,
);
