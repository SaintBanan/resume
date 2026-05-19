package com.example.bt.modules;

import android.accounts.Account;
import android.accounts.AccountManager;
import android.content.Context;

import com.example.bt.R;

public class Accounts {

    // Ссылка на менеджера аккаунтов
    public static AccountManager getManager(Context context) {
        return AccountManager.get(context);
    }

    // Получить учетную запись
    public static Account getAccount(Context context) {

        Account[] accounts = getManager(context)
            .getAccountsByType(context.getResources().getString(R.string.ACCOUNT_TYPE));

        return accounts.length > 0 ? accounts[0] : null;
    }

    // Создать аккаунт
    public static boolean add(Context context, String user_id, String session_id, String phone, String token) {
        
        AccountManager manager = getManager(context);
        Account account = new Account(phone, context.getResources().getString(R.string.ACCOUNT_TYPE));

        if (!manager.addAccountExplicitly(account, null, null)) return false;

        manager.setUserData(account, "id", user_id);
        manager.setUserData(account, "session_id", session_id);
        manager.setUserData(account, "token", token);

        return true;
    }

    // Удалить аккаунт
    public static boolean remove(Context context, Account account) {
        return getManager(context).removeAccountExplicitly(account);
    }
}
