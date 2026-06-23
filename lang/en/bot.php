<?php

return [

    // ── General ──────────────────────────────────────────────────────────────
    'please_start_first'   => 'Please send /start first to register.',
    'cancelled'            => '❌ Cancelled.',
    'enter_valid_number'   => 'Please enter a valid number (e.g. 100 or 1500.50).',
    'enter_positive_number'=> 'Please enter a positive number.',

    // ── Start ────────────────────────────────────────────────────────────────
    'welcome_new'          => "👋 Welcome, :name!\n\nI'm your personal finance tracker. Let's start by creating your first account.",
    'welcome_back'         => "👋 Welcome back, :name!\n\nYou have :count account(s). Use /accounts to manage them or just send me a transaction like:\n\n_\"25 lunch cash\"_",

    // ── Language ─────────────────────────────────────────────────────────────
    'choose_language'      => '🌐 Choose your language:',
    'language_set'         => '✅ Language changed to English.',

    // ── Accounts ─────────────────────────────────────────────────────────────
    'account_ask_name'     => "What would you like to name this account?\n(e.g. Cash, Main Card, Savings)",
    'account_enter_name'   => 'Please enter a name for the account.',
    'account_ask_type'     => "Got it: *:name*\nWhat type of account is this?",
    'account_ask_currency' => 'What currency? (e.g. USD, EUR, GBP)',
    'account_invalid_currency' => 'Please enter a valid currency code (e.g. USD).',
    'account_ask_balance'  => "What's the current balance? (Enter 0 if starting fresh)",
    'account_created'      => "✅ Account created!\n\n:icon *:name*\nCurrency: :currency\nBalance: :balance",
    'account_none'         => 'You have no active accounts yet.',
    'account_list_title'   => 'Your Accounts',
    'account_no_accounts_for_txn' => 'You have no accounts yet. Use /accounts to create one first.',
    'account_ask_rename'   => 'Enter the new name for *:name*:',
    'account_enter_name_short' => 'Please enter a name.',
    'account_renamed'      => '✅ Renamed *:old* → *:new*',
    'account_confirm_archive' => 'Archive *:name*? It will be hidden from all views.',
    'account_archived'     => '🗃 *:name* has been archived.',

    // ── Categories ───────────────────────────────────────────────────────────
    'category_ask_type'    => "Create a new category.\n\nIs it an income or expense category?",
    'category_ask_name'    => "Creating a *:type* category.\n\nWhat should it be called?",
    'category_ask_icon'    => "Got it: *:name*\n\nAdd an emoji icon? (type one, e.g. 🏋️) or skip.",
    'category_ask_parent'  => 'Should this be a sub-category of an existing one?',
    'category_created'     => '✅ Category *:icon:name* created!',
    'category_tap_to_edit' => 'Tap a category to edit it:',
    'category_ask_rename'  => 'Enter the new name for *:name*:',
    'category_renamed'     => '✅ Renamed to *:name*',
    'category_ask_icon_edit' => 'Send the new emoji icon for *:name*:',
    'category_icon_updated'=> '✅ Icon updated to :icon',
    'category_confirm_delete' => 'Delete *:name*? This can\'t be undone.',
    'category_deleted'     => '🗑 Category deleted.',
    'category_has_transactions' => '❌ Cannot delete: this category has existing transactions.',
    'category_detail'      => '*:icon:name* (:type)',

    // ── Transactions ─────────────────────────────────────────────────────────
    'txn_ask_type'         => 'New transaction. What type?',
    'txn_ask_amount'       => "What's the amount? (:currency)",
    'txn_ask_transfer_amount' => 'Amount to transfer?',
    'txn_ask_category'     => 'Which category?',
    'txn_ask_to_account'   => 'Transfer *to* which account?',
    'txn_ask_note'         => 'Add a note? (optional)',
    'txn_created'          => "✅ Transaction logged!\n\n:icon :type\n:currency :amount\nAccount: :account\nCategory: :category",
    'txn_deleted'          => '🗑 Transaction deleted.',
    'txn_none'             => 'No transactions yet. Use /add to log one.',
    'txn_recent_title'     => 'Recent transactions',
    'txn_list_empty'       => 'No transactions found.',

    // ── AI Parsing ───────────────────────────────────────────────────────────
    'ai_not_configured'    => 'AI parsing is not configured. Use /add for manual entry.',
    'ai_choose_account'    => 'Choose the correct account:',
    'ai_choose_category'   => 'Choose the correct category:',
    'ai_parsing'           => '🤖 Parsing your transaction...',
    'ai_failed'            => "❌ Couldn't parse that. Try /add for manual entry.",
    'ai_confirm'           => "🤖 *AI Parsed Transaction*\n\n:summary\n\nLooks right?",
    'ai_saved'             => '✅ Transaction saved!',
    'ai_cancelled'         => '❌ Transaction cancelled.',

    // ── Recurring ────────────────────────────────────────────────────────────
    'rec_ask_name'         => "Set up a recurring transaction.\n\nWhat's it called? (e.g. Rent, Netflix, Salary)",
    'rec_ask_type'         => 'Is *:name* an income or expense?',
    'rec_ask_account'      => 'Which account should this be charged to?',
    'rec_ask_category'     => 'Which category?',
    'rec_ask_amount'       => "What's the amount?",
    'rec_ask_frequency'    => 'How often does this recur?',
    'rec_ask_start_date'   => "When is the first due date?\nEnter a date (YYYY-MM-DD) or type *today*.",
    'rec_invalid_date'     => '❌ Invalid date. Please use YYYY-MM-DD format or type *today*.',
    'rec_ask_reminder'     => 'Would you like a reminder before the due date?',
    'rec_created'          => "✅ Recurring template created!\n\n*:name*\n:currency :amount · :frequency",
    'rec_none'             => 'No recurring templates yet.',
    'rec_list_title'       => 'Recurring Templates',
    'rec_ask_confirm_amount' => 'Enter the actual amount for *:name* (default: :currency :amount):',
    'rec_confirmed'        => '✅ *:name* confirmed and transaction logged!',
    'rec_skipped'          => '⏭ Skipped.',
    'rec_confirm_deactivate' => 'Deactivate *:name*? You won\'t receive any more reminders or due-date prompts.',
    'rec_deactivated'      => '🔕 *:name* has been deactivated.',
    'rec_reminder_msg'     => "🔔 Reminder: *:name* (:currency :amount) is due on :date.",
    'rec_due_msg'          => "📅 *:name* (:currency :amount) is due today! Confirm or skip?",

    // ── Friends ───────────────────────────────────────────────────────────────
    'friend_no_friends'    => "👥 *Friends*\n\nYou have no friends on the bot yet.\nSend their username with /addfriend or tap Add Friend.",
    'friend_list_title'    => '👥 *Friends* (:count)',
    'friend_ask_username'  => 'Enter the Telegram username of the person you want to add (e.g. @alice):',
    'friend_not_found'     => '❌ User *:username* hasn\'t started this bot yet.',
    'friend_self'          => "You can't add yourself as a friend.",
    'friend_already'       => "You're already friends or a request already exists.",
    'friend_request_sent'  => '✅ Friend request sent to *:name*!',
    'friend_request_received' => '🤝 *:name* wants to be your friend on Finance Tracker!',
    'friend_accepted'      => '✅ You\'re now friends with *:name*!',
    'friend_accept_notify' => '✅ *:name* accepted your friend request!',
    'friend_declined'      => '❌ Friend request from *:name* declined.',
    'friend_settled'       => '✅ Settled',
    'friend_settle_done'   => '✅ Settled up with *:name*! (:count expense(s) marked as settled)',
    'friend_settle_notify' => '✅ *:name* marked all shared expenses between you as settled.',
    'friend_already_settled' => 'You and *:name* are already settled up! ✅',
    'friend_expense_who_paid' => "Log a shared expense with *:name*.\n\nWho paid?",
    'friend_expense_ask_amount' => 'How much? (enter amount)',
    'friend_expense_ask_note' => 'What was it for? (optional description)',
    'friend_expense_logged'=> "✅ Shared expense logged!\n\n:summary",
    'friend_no_pending'    => 'No pending friend requests.',
    'friend_pending_title' => '🔔 *Pending requests* (:count)',
    'friend_settle_confirm'=> "Settle up with *:name*?\n\nCurrent balance: :balance\n\nAll open shared expenses will be marked as settled.",
    'friend_they_owe'      => 'they owe you :currency :amount',
    'friend_you_owe'       => 'you owe them :currency :amount',
    'balance_no_friends'   => 'You have no friends yet. Use /addfriend to add one.',
    'balance_not_friend'   => "You're not friends with @:username.",

    // ── Transaction / Summary labels ─────────────────────────────────────────
    'txn_summary'           => '📝 Transaction Summary',
    'txn_transfer'          => 'Transfer',
    'txn_label_type'        => 'Type',
    'txn_label_from'        => 'From',
    'txn_label_to'          => 'To',
    'txn_label_account'     => 'Account',
    'txn_label_category'    => 'Category',
    'txn_label_amount'      => 'Amount',
    'txn_label_note'        => 'Note',
    'txn_label_merchant'    => 'Merchant',
    'txn_label_date'        => 'Date',

    // ── Friend / Shared expense labels ───────────────────────────────────────
    'friend_shared_expense' => '💸 Shared Expense',
    'friend_label_with'     => 'With',
    'friend_label_amount'   => 'Amount',
    'friend_label_note'     => 'Note',

    // ── Recurring template detail labels ─────────────────────────────────────
    'rec_label_amount'      => 'Amount',
    'rec_label_category'    => 'Category',
    'rec_label_account'     => 'Account',
    'rec_label_frequency'   => 'Frequency',
    'rec_label_next_due'    => 'Next due',

    // ── Report ────────────────────────────────────────────────────────────────
    'report_income'        => 'Income',
    'report_expenses'      => 'Expenses',
    'report_net'           => 'Net',
    'report_by_category'   => 'Expenses by Category',
    'report_vs'            => 'vs. :period',
    'report_transactions'  => '(:count transactions)',
    'report_other_currencies' => '_Note: report shows :currency only. Other currencies: :others_',

];
