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

  // contentSafeAreaInset.top = pixels Telegram's own overlay (close button) covers.
  // Telegram already places the viewport below the device notch/status bar, so we
  // must NOT add safeAreaInset.top — that would double-count it and push the header
  // too far down. Only use contentSafeAreaInset for the top.
  const ci = tg.contentSafeAreaInset ?? { top: 0, bottom: 0 };
  // For the bottom, use the larger of Telegram overlay and device home-bar inset.
  const si = tg.safeAreaInset ?? { bottom: 0 };

  document.documentElement.style.setProperty('--tg-safe-top',    `${ci.top}px`);
  document.documentElement.style.setProperty('--tg-safe-bottom', `${Math.max(ci.bottom, si.bottom)}px`);
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
