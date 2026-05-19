package com.example.bt;

import android.content.Context;
import android.content.Intent;

import androidx.annotation.NonNull;

import com.example.bt.push.Push;
import com.example.bt.modules.Helper;
import com.example.bt.modules.Json;
import com.example.bt.modules.Notification;
import com.google.firebase.messaging.FirebaseMessagingService;
import com.google.firebase.messaging.RemoteMessage;

public class BtFirebaseMessagingService extends FirebaseMessagingService {

    @Override
    public void onMessageReceived(RemoteMessage remoteMessage) {

        if (remoteMessage.getData().size() == 0) return;

        try {
            String pushJson = remoteMessage.getData().get("push");
            Push push = Json.from(pushJson, Push.class);
            Context context = getApplicationContext();

            if (push == null) return;

            // Отображение уведомлений вне зависимости от режима работы приложения
            switch (push.getData().getType()) {
                // Новая заявка
                case "new_deal":
                    Notification.newDeal(context, push.getTitle(), push.getBody(), push.getData());
                    break;
                // Отмена push по новой заявке
                case "cancel_deal":
                    Notification.remove(context, "new_deal", push.getData().getData());
                    break;
                // Изменения по сделке
                case "deal":
                // Общие уведомления
                case "general":
                    Notification.notify(context, push.getTitle(), push.getBody(), push.getData());
            }

            Intent intent = new Intent("MainActivityPush");
            intent.putExtra("data", pushJson);

            // Показать push для чата, если приложение в фоне
            if (!Helper.isAppRunning() && push.getData().getType().equals("deal_chat_message")) {

                Notification.dealChatMessage(context, push.getTitle(), push.getBody(), push.getData());
                intent.putExtra("chat_notified", true);
            }
            else {
                intent.putExtra("chat_notified", false);
            }

            sendBroadcast(intent);
        }
        catch (Exception ignored) {}
    }
}
