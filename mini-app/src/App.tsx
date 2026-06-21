import { useState } from 'react';
import { AppRoot } from '@telegram-apps/telegram-ui';
import { BottomNav, type Tab } from './components/BottomNav';
import { Dashboard } from './pages/Dashboard';
import { Friends } from './pages/Friends';
import { Report } from './pages/Report';
import { Transactions } from './pages/Transactions';

export default function App() {
  const [tab, setTab] = useState<Tab>('dashboard');

  const platform = typeof window !== 'undefined' &&
    /iphone|ipad|mac/i.test(navigator.userAgent) ? 'ios' : 'base';

  return (
    <AppRoot platform={platform}>
      <div style={{ minHeight: '100dvh', display: 'flex', flexDirection: 'column' }}>
        <div style={{ flex: 1, overflowY: 'auto', paddingBottom: 80 }}>
          {tab === 'dashboard'    && <Dashboard />}
          {tab === 'transactions' && <Transactions />}
          {tab === 'report'       && <Report />}
          {tab === 'friends'      && <Friends />}
        </div>

        <div style={{ position: 'fixed', bottom: 0, left: 0, right: 0, zIndex: 100 }}>
          <BottomNav active={tab} onChange={setTab} />
        </div>
      </div>
    </AppRoot>
  );
}
