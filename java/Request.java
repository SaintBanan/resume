package com.example.bt.request;

import android.content.Context;
import android.os.Handler;
import android.widget.Toast;

import com.example.bt.interfaces.ActivityListener;
import com.example.bt.interfaces.RequestCallback;
import com.example.bt.request.post.CheckPhoneBody;
import com.example.bt.request.post.ConfirmCodeBody;
import com.example.bt.request.post.SessionBody;

import okhttp3.MultipartBody;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class Request {

    private static Callback getCallback(Context context, RequestCallback callback, boolean return_null) {

        return new Callback<Object>() {

            @Override
            public void onResponse(Call<Object> call, Response<Object> response) {
                try {
                    Object result = response.body();

                    if (result == null) {

                        // Сессия закрыта удаленно или отобран/истек токен
                        if (response.message().equals("Unauthorized")) {
                            ((ActivityListener) context).removeAccount();
                            return;
                        }

                        if (!return_null) {
                            Toast.makeText(context, response.message() + " " + response.code(), Toast.LENGTH_LONG).show();
                            return;
                        }
                    }

                    if (callback != null) {
                        callback.execute(result);
                    }
                }
                catch (Exception ex) {
                    ex.printStackTrace();
                }
            }

            @Override
            public void onFailure(Call<Object> call, Throwable t) {

                if (t.getMessage() == null) return;

                if (t.getMessage().equals("timeout") || t.getMessage().contains("resolve host")) {
                    Toast.makeText(context, "Проблема с сетью, переподключение...", Toast.LENGTH_SHORT).show();

                    // Повторить запрос
                    new Handler().postDelayed(() -> call.clone().enqueue(this), 1500);
                }
                else {
                    Toast.makeText(context, "Ошибка: " + t.getMessage(), Toast.LENGTH_LONG).show();
                }
            }
        };
    }

    private static Callback getCallback(Context context, RequestCallback callback) {

        return getCallback(context, callback, false);
    }

    // Проверить наличие обновлений приложения
    public static void checkUpdate(String version, Context context, RequestCallback callback) {

        wRetrofit.getApi().checkUpdate(version, wRetrofit.getAuthToken()).enqueue(getCallback(context, callback));
    }

    // Отправить номер телефона для авторизации
    public static void sendPhone(String phone, Context context, RequestCallback callback) {

        wRetrofit.getApi().sendPhone(new CheckPhoneBody(phone), wRetrofit.getAuthToken()).enqueue(getCallback(context, callback));
    }
}
