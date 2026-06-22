export type Lang = 'en' | 'fa';

const en = {
  // Nav
  nav_home: 'Home',
  nav_transactions: 'Transactions',
  nav_report: 'Report',
  nav_friends: 'Friends',

  // Dashboard
  accounts: 'Accounts',
  recent_transactions: 'Recent transactions',
  no_transactions: 'No transactions yet',

  // Transactions page
  all_types: 'All types',
  income: 'Income',
  expense: 'Expense',
  transfer: 'Transfer',
  load_more: 'Load more',
  add: '+ Add',
  add_transaction: 'Add Transaction',
  type: 'Type',
  amount: 'Amount',
  account: 'Account',
  category: 'Category',
  uncategorized: 'Uncategorized',
  description: 'Description',
  optional_note: 'Optional note',
  cancel: 'Cancel',
  save: 'Save',
  amount_required: 'Amount and account are required',

  // Report page
  period_month: 'Month',
  period_last_month: 'Last Month',
  period_quarter: 'Quarter',
  period_year: 'Year',
  report_income: 'Income',
  report_expenses: 'Expenses',
  report_net: 'Net',
  expenses_by_category: 'Expenses by Category',

  // Friends page
  no_friends: 'No friends yet',
  add_friend_hint: 'Use /addfriend in the bot to add friends',
  open_balances: 'Open balances',
  settled: 'Settled',
  settled_label: '✅ Settled',
  they_owe: 'they owe you',
  you_owe: 'you owe',

  // AI nav
  nav_ai: 'AI',

  // AI Hub
  ai_hub_title: 'AI Financial Coach',
  ai_chat: 'AI Chat',
  ai_chat_sub: 'Ask anything about your finances',
  health_score: 'Health Score',
  health_score_sub: 'Your financial wellness score',
  goals: 'Goals',
  goals_sub: 'Track your financial goals',
  budgets: 'Budgets',
  budgets_sub: 'Monitor your spending limits',
  forecast: 'Forecast',
  forecast_sub: 'See where you are headed',
  subscriptions: 'Subscriptions',
  subscriptions_sub: 'Auto-detected recurring payments',
  daily_insights: 'Daily Insights',
  daily_insights_sub: 'Proactive financial tips',

  // AI Chat
  type_message: 'Type your question...',
  send: 'Send',
  ai_thinking: 'AI is thinking...',

  // Health Score
  your_score: 'Your Score',
  personality: 'Personality',
  score_components: 'Score Breakdown',

  // Goals
  add_goal: 'Add Goal',
  goal_name: 'Goal Name',
  target_amount: 'Target Amount',
  deadline: 'Deadline (optional)',
  no_goals: 'No goals yet',
  progress: 'Progress',
  remaining: 'Remaining',
  completed: 'Completed',

  // Budgets
  add_budget: 'Add Budget',
  budget_name: 'Budget Name',
  budget_amount: 'Budget Amount',
  period: 'Period',
  monthly: 'Monthly',
  weekly: 'Weekly',
  yearly: 'Yearly',
  spent: 'Spent',
  no_budgets: 'No budgets yet',
  status_safe: 'On track',
  status_warning: 'Watch out',
  status_critical: 'Almost exceeded',
  status_exceeded: 'Exceeded',

  // Forecast
  month_end_balance: 'Projected Month-End',
  projected_income: 'Projected Income',
  projected_expenses: 'Projected Expenses',
  savings_potential: 'Savings Potential',
  overspending_warning: 'Overspending Risk',
  goal_forecasts: 'Goal Timeline',
  months_to_complete: 'months to complete',

  // Subscriptions
  total_monthly: 'Total Monthly',
  total_yearly: 'Total Yearly',
  next_payment: 'Next payment',
  no_subscriptions: 'No subscriptions detected',

  // Insights
  no_insights: 'No insights today',
  refresh: 'Refresh',
} as const;

const fa: Record<keyof typeof en, string> = {
  // Nav
  nav_home: 'خانه',
  nav_transactions: 'تراکنش‌ها',
  nav_report: 'گزارش',
  nav_friends: 'دوستان',

  // Dashboard
  accounts: 'حساب‌ها',
  recent_transactions: 'تراکنش‌های اخیر',
  no_transactions: 'هنوز تراکنشی ثبت نشده',

  // Transactions page
  all_types: 'همه',
  income: 'درآمد',
  expense: 'هزینه',
  transfer: 'انتقال',
  load_more: 'بارگذاری بیشتر',
  add: '+ افزودن',
  add_transaction: 'افزودن تراکنش',
  type: 'نوع',
  amount: 'مبلغ',
  account: 'حساب',
  category: 'دسته‌بندی',
  uncategorized: 'بدون دسته‌بندی',
  description: 'توضیحات',
  optional_note: 'یادداشت (اختیاری)',
  cancel: 'انصراف',
  save: 'ذخیره',
  amount_required: 'مبلغ و حساب الزامی است',

  // Report page
  period_month: 'این ماه',
  period_last_month: 'ماه گذشته',
  period_quarter: 'سه‌ماهه',
  period_year: 'سالانه',
  report_income: 'درآمد',
  report_expenses: 'هزینه',
  report_net: 'خالص',
  expenses_by_category: 'هزینه بر اساس دسته‌بندی',

  // Friends page
  no_friends: 'هنوز دوستی ندارید',
  add_friend_hint: 'با /addfriend در ربات دوست اضافه کنید',
  open_balances: 'مانده‌های باز',
  settled: 'تسویه شده',
  settled_label: '✅ تسویه',
  they_owe: 'باید به شما بپردازد',
  you_owe: 'باید بپردازید',

  // AI nav
  nav_ai: 'هوش مصنوعی',

  // AI Hub
  ai_hub_title: 'مربی مالی هوشمند',
  ai_chat: 'گفتگو با هوش مصنوعی',
  ai_chat_sub: 'هر سوال مالی بپرسید',
  health_score: 'امتیاز مالی',
  health_score_sub: 'امتیاز سلامت مالی شما',
  goals: 'اهداف',
  goals_sub: 'اهداف مالی خود را دنبال کنید',
  budgets: 'بودجه‌ها',
  budgets_sub: 'محدودیت‌های هزینه را کنترل کنید',
  forecast: 'پیش‌بینی',
  forecast_sub: 'آینده مالی خود را ببینید',
  subscriptions: 'اشتراک‌ها',
  subscriptions_sub: 'پرداخت‌های دوره‌ای تشخیص داده شده',
  daily_insights: 'بینش‌های روزانه',
  daily_insights_sub: 'نکات مالی پیشگیرانه',

  // AI Chat
  type_message: 'سوال خود را بنویسید...',
  send: 'ارسال',
  ai_thinking: 'هوش مصنوعی در حال فکر کردن است...',

  // Health Score
  your_score: 'امتیاز شما',
  personality: 'شخصیت مالی',
  score_components: 'جزئیات امتیاز',

  // Goals
  add_goal: 'افزودن هدف',
  goal_name: 'نام هدف',
  target_amount: 'مبلغ هدف',
  deadline: 'مهلت (اختیاری)',
  no_goals: 'هنوز هدفی ندارید',
  progress: 'پیشرفت',
  remaining: 'باقیمانده',
  completed: 'تکمیل شده',

  // Budgets
  add_budget: 'افزودن بودجه',
  budget_name: 'نام بودجه',
  budget_amount: 'مبلغ بودجه',
  period: 'دوره',
  monthly: 'ماهانه',
  weekly: 'هفتگی',
  yearly: 'سالانه',
  spent: 'خرج شده',
  no_budgets: 'هنوز بودجه‌ای ندارید',
  status_safe: 'در مسیر',
  status_warning: 'مراقب باشید',
  status_critical: 'نزدیک به پایان',
  status_exceeded: 'از حد گذشته',

  // Forecast
  month_end_balance: 'پیش‌بینی پایان ماه',
  projected_income: 'درآمد پیش‌بینی شده',
  projected_expenses: 'هزینه پیش‌بینی شده',
  savings_potential: 'پتانسیل پس‌انداز',
  overspending_warning: 'خطر هزینه بیش از حد',
  goal_forecasts: 'زمان‌بندی اهداف',
  months_to_complete: 'ماه تا تکمیل',

  // Subscriptions
  total_monthly: 'کل ماهانه',
  total_yearly: 'کل سالانه',
  next_payment: 'پرداخت بعدی',
  no_subscriptions: 'اشتراکی تشخیص داده نشد',

  // Insights
  no_insights: 'امروز بینشی موجود نیست',
  refresh: 'بازنشانی',
};

export const i18n: Record<Lang, Record<keyof typeof en, string>> = { en, fa };
