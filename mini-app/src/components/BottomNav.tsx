import { Tabbar } from '@telegram-apps/telegram-ui';

export type Tab = 'dashboard' | 'transactions' | 'report' | 'friends';

interface Props {
  active: Tab;
  onChange: (tab: Tab) => void;
}

const TABS: { id: Tab; icon: string; label: string }[] = [
  { id: 'dashboard',    icon: '🏠', label: 'Home' },
  { id: 'transactions', icon: '💳', label: 'Transactions' },
  { id: 'report',       icon: '📊', label: 'Report' },
  { id: 'friends',      icon: '👥', label: 'Friends' },
];

export function BottomNav({ active, onChange }: Props) {
  return (
    <Tabbar>
      {TABS.map((tab) => (
        <Tabbar.Item
          key={tab.id}
          text={tab.label}
          selected={active === tab.id}
          onClick={() => onChange(tab.id)}
        >
          <span style={{ fontSize: 22 }}>{tab.icon}</span>
        </Tabbar.Item>
      ))}
    </Tabbar>
  );
}
