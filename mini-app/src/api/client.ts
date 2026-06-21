import type { Account, Category, Friend, Me, Report, Transaction, TransactionList } from '../types';

let _initDataRaw = '';

export function setInitDataRaw(raw: string): void {
  _initDataRaw = raw;
}

async function request<T>(path: string, options?: RequestInit): Promise<T> {
  const res = await fetch(`/api${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      Authorization: `tma ${_initDataRaw}`,
      ...(options?.headers ?? {}),
    },
  });

  if (!res.ok) {
    const body = await res.text().catch(() => '');
    throw new Error(body || `HTTP ${res.status}`);
  }

  return res.json() as Promise<T>;
}

export const api = {
  me: () => request<Me>('/me'),

  accounts: () => request<Account[]>('/accounts'),

  categories: (type?: 'income' | 'expense') =>
    request<Category[]>(`/categories${type ? `?type=${type}` : ''}`),

  transactions: (params: {
    limit?: number;
    offset?: number;
    account_id?: number;
    type?: string;
    from?: string;
    to?: string;
  } = {}) => {
    const qs = new URLSearchParams();
    if (params.limit)      qs.set('limit',      String(params.limit));
    if (params.offset)     qs.set('offset',     String(params.offset));
    if (params.account_id) qs.set('account_id', String(params.account_id));
    if (params.type)       qs.set('type',       params.type);
    if (params.from)       qs.set('from',       params.from);
    if (params.to)         qs.set('to',         params.to);
    return request<TransactionList>(`/transactions?${qs}`);
  },

  createTransaction: (data: {
    account_id: number;
    type: string;
    amount: number;
    currency: string;
    category_id?: number | null;
    merchant?: string;
    description?: string;
    occurred_at?: string;
  }) =>
    request<Transaction>('/transactions', {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  deleteTransaction: (id: number) =>
    request<{ success: boolean }>(`/transactions/${id}`, { method: 'DELETE' }),

  report: (period: string = 'month', currency?: string) =>
    request<Report>(`/report?period=${period}${currency ? `&currency=${currency}` : ''}`),

  friends: () => request<Friend[]>('/friends'),
};
