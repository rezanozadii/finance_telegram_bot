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
  language: 'en' | 'fa';
}

export type AiPage = 'hub' | 'chat' | 'health' | 'goals' | 'budgets' | 'forecast' | 'subscriptions' | 'insights' | 'habits' | 'whatif';

export type DashPage = 'main' | 'accounts' | 'categories' | 'settings';

export interface Habit {
  merchant: string;
  transaction_count: number;
  frequency: number;
  avg_amount: number;
  monthly_cost: number;
  yearly_cost: number;
  first_seen: string;
  last_seen: string;
  currency: string;
}

export interface WhatIfGoalImpact {
  goal: string;
  remaining: number;
  months_now: number | null;
  months_with_change: number | null;
}

export interface WhatIfResult {
  scenario: string;
  monthly_savings?: number;
  yearly_savings?: number;
  monthly_income_increase?: number;
  yearly_income_increase?: number;
  monthly_savings_increase?: number;
  yearly_total_savings?: number;
  monthly_cost?: number;
  yearly_savings_from_cancel?: number;
  goal_impact?: WhatIfGoalImpact[];
  currency: string;
  error?: string;
}

export interface HealthScoreComponent {
  label: string;
  score: number;
  weighted_score: number;
  explanation: string;
}

export interface HealthScore {
  total: number;
  personality: string;
  components: Record<string, HealthScoreComponent>;
  account_age_days?: number;
}

export interface UserGoal {
  id: number;
  name: string;
  target_amount: number;
  current_amount: number;
  currency: string;
  deadline: string | null;
  status: 'active' | 'completed' | 'paused';
}

export interface Budget {
  id: number;
  name: string;
  amount: number;
  spent_amount: number;
  currency: string;
  period: 'monthly' | 'weekly' | 'yearly';
  pct_used: number;
  status: 'safe' | 'warning' | 'critical' | 'exceeded';
  category?: { id: number; name: string } | null;
}

export interface Forecast {
  projected_monthly_expense: number;
  projected_monthly_income: number;
  projected_eom_balance: number;
  savings_potential: number;
  overspending_risk: boolean;
  currency: string;
  goal_forecasts: Array<{ name: string; months_remaining: number | null }>;
}

export interface Subscription {
  merchant: string;
  amount: number;
  currency: string;
  frequency: 'monthly' | 'yearly' | 'weekly';
  monthly_cost: number;
  yearly_cost: number;
  last_payment_at: string | null;
  next_predicted_at: string | null;
}

export interface AiInsight {
  id: number;
  type: string;
  content: string;
  insights_date: string;
}

export interface ChatMessage {
  role: 'user' | 'ai';
  content: string;
}
