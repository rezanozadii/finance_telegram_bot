import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { init, retrieveRawInitData } from '@telegram-apps/sdk-react';
import { setInitDataRaw } from './api/client';
import App from './App';
import '@telegram-apps/telegram-ui/dist/styles.css';
import './index.css';

try {
  init();
  const raw = retrieveRawInitData();
  setInitDataRaw(raw ?? '');

  // Expand to full screen and apply Telegram theme
  if (window.Telegram?.WebApp) {
    window.Telegram.WebApp.expand();
    window.Telegram.WebApp.ready();
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
