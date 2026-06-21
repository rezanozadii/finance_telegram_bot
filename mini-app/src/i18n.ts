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
};

export const i18n: Record<Lang, Record<keyof typeof en, string>> = { en, fa };
