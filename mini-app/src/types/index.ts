export interface Account {
  id: number;
  name: string;
  type: string;
  currency: string;
  balance: number;
  is_active: boolean;
}

export interface Category {
  id: number;
  name: string;
  type: 'income' | 'expense';
  icon: string | null;
  parent_id: number | null;
}

export interface Transaction {
  id: number;
  type: 'income' | 'expense' | 'transfer';
  amount: number;
  currency: string;
  merchant: string | null;
  description: string | null;
  occurred_at: string;
  source: string;
  account: { id: number; name: string; type: string } | null;
  category: { id: number; name: string; icon: string | null; type: string } | null;
  to_account: { id: number; name: string } | null;
}

export interface TransactionList {
  data: Transaction[];
  meta: { total: number; limit: number; offset: number };
}

export interface Friend {
  id: number;
  username: string | null;
  display_name: string;
  balances: Record<string, number>;
}

export interface CategoryStat {
  name: string;
  icon: string | null;
  amount: number;
  pct: number;
}

export interface Report {
  label: string;
  prev_label: string;
  currency: string;
  income: number;
  expenses: number;
  net: number;
  count: number;
  by_category: CategoryStat[];
  prev_income: number;
  prev_expenses: number;
  income_change: string;
  expense_change: string;
}

export interface Me {
  id: number;
  telegram_id: number;
  username: string | null;
  display_name: string;
  default_currency: string;
  account_count: number;
  total_balance: number;
}
