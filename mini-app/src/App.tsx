import { useEffect, useState } from 'react';
import { AppRoot } from '@telegram-apps/telegram-ui';
import { LangProvider, useLang } from './LangContext';
import { BottomNav, type Tab } from './components/BottomNav';
import { Dashboard } from './pages/Dashboard';
import { Friends } from './pages/Friends';
import { Report } from './pages/Report';
import { Transactions } from './pages/Transactions';
import { AiHub } from './pages/AiHub';
import { AiChat } from './pages/AiChat';
import { HealthScore } from './pages/HealthScore';
import { Goals } from './pages/Goals';
import { Budgets } from './pages/Budgets';
import { Forecast } from './pages/Forecast';
import { Subscriptions } from './pages/Subscriptions';
import { DailyInsights } from './pages/DailyInsights';
import { Accounts } from './pages/Accounts';
import { Categories } from './pages/Categories';
import { Settings } from './pages/Settings';
import { Habits } from './pages/Habits';
import { WhatIf } from './pages/WhatIf';
import { api } from './api/client';
import type { AiPage, DashPage, Me } from './types';

const AI_PAGE_TITLES: Record<AiPage, string> = {
  hub:           '',
  chat:          'AI Chat',
  health:        'Health Score',
  goals:         'Goals',
  budgets:       'Budgets',
  forecast:      'Forecast',
  subscriptions: 'Subscriptions',
  insights:      'Daily Insights',
  habits:        'Spending Habits',
  whatif:        'What-If Simulator',
};

function AppInner() {
  const [tab, setTab] = useState<Tab>('dashboard');
  const [aiPage, setAiPage] = useState<AiPage>('hub');
  const [dashPage, setDashPage] = useState<DashPage>('main');
  const [me, setMe] = useState<Me | null>(null);
  const { setLang, dir } = useLang();

  const platform = typeof window !== 'undefined' &&
    /iphone|ipad|mac/i.test(navigator.userAgent) ? 'ios' : 'base';

  useEffect(() => {
    api.me().then((data) => {
      setMe(data);
      if (data.language === 'fa') setLang('fa');
    }).catch(() => {});
  }, []);

  function handleTabChange(newTab: Tab) {
    setTab(newTab);
    setDashPage('main');
    if (newTab === 'ai') setAiPage('hub');
  }

  const currency = me?.default_currency ?? 'USD';

  return (
    <AppRoot platform={platform}>
      <div dir={dir} style={{ minHeight: '100dvh', display: 'flex', flexDirection: 'column' }}>
        <div style={{ flex: 1, overflowY: 'auto', paddingBottom: 80 }}>
          {tab !== 'ai' && (
            <>
              {tab === 'dashboard' && dashPage === 'main' && (
                <Dashboard onNavigate={setDashPage} />
              )}
              {tab === 'dashboard' && dashPage === 'accounts' && (
                <Accounts onBack={() => setDashPage('main')} />
              )}
              {tab === 'dashboard' && dashPage === 'categories' && (
                <Categories onBack={() => setDashPage('main')} />
              )}
              {tab === 'dashboard' && dashPage === 'settings' && (
                <Settings onBack={() => setDashPage('main')} />
              )}
              {tab === 'transactions' && <Transactions />}
              {tab === 'report'       && <Report />}
              {tab === 'friends'      && <Friends />}
            </>
          )}

          {tab === 'ai' && (
            <>
              {aiPage !== 'hub' && (
                <div style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: 10,
                  padding: '12px 16px',
                  borderBottom: '1px solid var(--tg-theme-hint-color, #ddd)',
                  background: 'var(--tg-theme-bg-color, #fff)',
                  position: 'sticky',
                  top: 0,
                  zIndex: 50,
                }}>
                  <button
                    onClick={() => setAiPage('hub')}
                    style={{
                      background: 'none', border: 'none', fontSize: 22,
                      cursor: 'pointer', color: '#007aff', padding: 0, lineHeight: 1,
                    }}
                  >
                    ←
                  </button>
                  <span style={{ fontWeight: 600, fontSize: 17 }}>{AI_PAGE_TITLES[aiPage]}</span>
                </div>
              )}

              {aiPage === 'hub'           && <AiHub onNavigate={setAiPage} />}
              {aiPage === 'chat'          && <AiChat onBack={() => setAiPage('hub')} defaultCurrency={currency} />}
              {aiPage === 'health'        && <HealthScore onBack={() => setAiPage('hub')} />}
              {aiPage === 'goals'         && <Goals onBack={() => setAiPage('hub')} />}
              {aiPage === 'budgets'       && <Budgets onBack={() => setAiPage('hub')} />}
              {aiPage === 'forecast'      && <Forecast onBack={() => setAiPage('hub')} defaultCurrency={currency} />}
              {aiPage === 'subscriptions' && <Subscriptions onBack={() => setAiPage('hub')} />}
              {aiPage === 'insights'      && <DailyInsights onBack={() => setAiPage('hub')} />}
              {aiPage === 'habits'        && <Habits onBack={() => setAiPage('hub')} defaultCurrency={currency} />}
              {aiPage === 'whatif'        && <WhatIf onBack={() => setAiPage('hub')} defaultCurrency={currency} />}
            </>
          )}
        </div>

        <div style={{ position: 'fixed', bottom: 0, left: 0, right: 0, zIndex: 100 }}>
          <BottomNav active={tab} onChange={handleTabChange} />
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
