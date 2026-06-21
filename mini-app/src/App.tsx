import { useEffect, useState } from 'react';
import { AppRoot } from '@telegram-apps/telegram-ui';
import { LangProvider, useLang } from './LangContext';
import { BottomNav, type Tab } from './components/BottomNav';
import { Dashboard } from './pages/Dashboard';
import { Friends } from './pages/Friends';
import { Report } from './pages/Report';
import { Transactions } from './pages/Transactions';
import { api } from './api/client';

function AppInner() {
  const [tab, setTab] = useState<Tab>('dashboard');
  const { setLang, dir } = useLang();

  const platform = typeof window !== 'undefined' &&
    /iphone|ipad|mac/i.test(navigator.userAgent) ? 'ios' : 'base';

  useEffect(() => {
    api.me().then((me) => {
      if (me.language === 'fa') setLang('fa');
    }).catch(() => {});
  }, []);

  return (
    <AppRoot platform={platform}>
      <div dir={dir} style={{ minHeight: '100dvh', display: 'flex', flexDirection: 'column' }}>
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

export default function App() {
  return (
    <LangProvider>
      <AppInner />
    </LangProvider>
  );
}
