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
import type { Lang } from './i18n';

const AI_PAGE_TITLES_EN: Record<AiPage, string> = {
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

const AI_PAGE_TITLES_FA: Record<AiPage, string> = {
  hub:           '',
  chat:          'چت هوشمند',
  health:        'امتیاز سلامت',
  goals:         'اهداف',
  budgets:       'بودجه‌ها',
  forecast:      'پیش‌بینی',
  subscriptions: 'اشتراک‌ها',
  insights:      'بینش‌های روزانه',
  habits:        'عادات هزینه‌ای',
  whatif:        'شبیه‌ساز فرضی',
};

// Detect initial language immediately from Telegram initData (before API call)
function detectInitialLang(): Lang {
  try {
    const tgLang = window.Telegram?.WebApp?.initDataUnsafe?.user?.language_code ?? '';
    if (tgLang.startsWith('fa') || tgLang === 'ir') return 'fa';
  } catch { /* ignore */ }
  return 'en';
}

function AppInner() {
  const [tab, setTab]         = useState<Tab>('dashboard');
  const [aiPage, setAiPage]   = useState<AiPage>('hub');
  const [dashPage, setDashPage] = useState<DashPage>('main');
  const [me, setMe]           = useState<Me | null>(null);
  const { lang, setLang, t, dir } = useLang();

  const platform = typeof window !== 'undefined' &&
    /iphone|ipad|mac/i.test(navigator.userAgent) ? 'ios' : 'base';

  useEffect(() => {
    api.me().then((data) => {
      setMe(data);
      if (data.language === 'fa' || data.language === 'en') {
        setLang(data.language as Lang);
      }
    }).catch(() => {});
  }, []);

  function handleTabChange(newTab: Tab) {
    setTab(newTab);
    setDashPage('main');
    if (newTab === 'ai') setAiPage('hub');
  }

  function toggleLang() {
    const next: Lang = lang === 'en' ? 'fa' : 'en';
    setLang(next);
    const newCurrency = next === 'fa' ? 'IRR' : 'USD';
    setMe((prev) => prev ? { ...prev, language: next, default_currency: newCurrency } : prev);
    api.updateMe({ language: next }).catch(() => {});
  }

  const currency = me?.default_currency ?? 'USD';
  const aiTitles = lang === 'fa' ? AI_PAGE_TITLES_FA : AI_PAGE_TITLES_EN;

  const showAiBack = tab === 'ai' && aiPage !== 'hub';

  return (
    <AppRoot platform={platform}>
      <div
        dir={dir}
        style={{
          height: '100dvh',
          display: 'flex',
          flexDirection: 'column',
          overflow: 'hidden',
          background: 'var(--tg-theme-bg-color, #fff)',
        }}
      >
        {/* Persistent top bar: language toggle + AI sub-page title */}
        <div style={{
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          padding: '8px 16px',
          borderBottom: '1px solid var(--tg-theme-hint-color, #e0e0e0)',
          background: 'var(--tg-theme-bg-color, #fff)',
          minHeight: 44,
          flexShrink: 0,
        }}>
          {showAiBack ? (
            <button
              onClick={() => setAiPage('hub')}
              style={{ background: 'none', border: 'none', fontSize: 22, cursor: 'pointer', color: '#007aff', padding: 0 }}
            >
              {dir === 'rtl' ? '→' : '←'}
            </button>
          ) : (
            <span style={{ fontWeight: 700, fontSize: 16 }}>
              {lang === 'fa' ? '💰 مدیریت مالی' : '💰 Finance'}
            </span>
          )}

          {showAiBack && (
            <span style={{ fontWeight: 600, fontSize: 16 }}>{aiTitles[aiPage]}</span>
          )}

          <button
            onClick={toggleLang}
            style={{
              background: 'none',
              border: '1px solid var(--tg-theme-hint-color, #ccc)',
              borderRadius: 14,
              padding: '3px 10px',
              cursor: 'pointer',
              fontSize: 13,
              fontWeight: 600,
              color: 'var(--tg-theme-text-color, #333)',
              display: 'flex',
              alignItems: 'center',
              gap: 4,
            }}
          >
            {lang === 'fa' ? '🇮🇷 FA' : '🇬🇧 EN'}
          </button>
        </div>

        {/* Scrollable content — overflow:hidden when AI chat is open so chat handles its own scroll */}
        <div style={{
          flex: 1,
          overflowY: tab === 'ai' && aiPage === 'chat' ? 'hidden' : 'auto',
          overflowX: 'hidden',
          WebkitOverflowScrolling: 'touch',
        }}>
          {tab !== 'ai' && (
            <>
              {tab === 'dashboard' && dashPage === 'main'       && <Dashboard onNavigate={setDashPage} />}
              {tab === 'dashboard' && dashPage === 'accounts'   && <Accounts onBack={() => setDashPage('main')} />}
              {tab === 'dashboard' && dashPage === 'categories' && <Categories onBack={() => setDashPage('main')} />}
              {tab === 'dashboard' && dashPage === 'settings'   && <Settings onBack={() => setDashPage('main')} />}
              {tab === 'transactions' && <Transactions />}
              {tab === 'report'       && <Report />}
              {tab === 'friends'      && <Friends />}
            </>
          )}

          {tab === 'ai' && (
            <>
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

        {/* Fixed bottom navigation */}
        <div style={{
          flexShrink: 0,
          borderTop: '1px solid var(--tg-theme-hint-color, #e0e0e0)',
          paddingBottom: 'env(safe-area-inset-bottom, 0px)',
          background: 'var(--tg-theme-bg-color, #fff)',
        }}>
          <BottomNav active={tab} onChange={handleTabChange} />
        </div>
      </div>
    </AppRoot>
  );
}

export default function App() {
  const initialLang = detectInitialLang();
  return (
    <LangProvider initialLang={initialLang}>
      <AppInner />
    </LangProvider>
  );
}
