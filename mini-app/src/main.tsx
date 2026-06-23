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

  // contentSafeAreaInset = space occupied by Telegram's own UI (close button, etc.)
  const ci = tg.contentSafeAreaInset ?? { top: 0, bottom: 0, left: 0, right: 0 };
  // safeAreaInset = device safe area (notch, home bar)
  const si = tg.safeAreaInset ?? { top: 0, bottom: 0, left: 0, right: 0 };

  const top    = Math.max(ci.top,    si.top);
  const bottom = Math.max(ci.bottom, si.bottom);

  document.documentElement.style.setProperty('--tg-safe-top',    `${top}px`);
  document.documentElement.style.setProperty('--tg-safe-bottom', `${bottom}px`);
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
