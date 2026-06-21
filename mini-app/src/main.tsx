import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { init, retrieveRawInitData } from '@telegram-apps/sdk-react';
import { setInitDataRaw } from './api/client';
import App from './App';
import '@telegram-apps/telegram-ui/dist/styles.css';

try {
  init();
  const raw = retrieveRawInitData();
  setInitDataRaw(raw ?? '');
} catch {
  // Running outside Telegram (dev mode)
  console.warn('Telegram SDK init failed — running outside Telegram?');
}

const root = document.getElementById('root')!;
createRoot(root).render(
  <StrictMode>
    <App />
  </StrictMode>,
);
