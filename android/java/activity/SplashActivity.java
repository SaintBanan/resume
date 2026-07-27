package com.example.bt;

import android.annotation.SuppressLint;
import android.content.Intent;
import android.os.Build;
import android.os.Bundle;
import android.os.Handler;
import android.view.View;
import android.widget.LinearLayout;
import android.widget.ProgressBar;

import androidx.constraintlayout.widget.ConstraintLayout;
import androidx.core.app.ActivityCompat;

import com.example.bt.interfaces.SplashListener;
import com.example.bt.model.UserAccount;
import com.example.bt.modules.Async;
import com.example.bt.modules.AppSettings;
import com.example.bt.modules.Notification;
import com.example.bt.request.Request;

import java.io.BufferedInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.net.URL;

import static com.example.bt.modules.File.APP_URL;
import static com.example.bt.modules.File.DOWNLOADS_PATH;
import static com.example.bt.modules.File.createFolder;
import static com.example.bt.modules.File.remove;
import static com.example.bt.modules.RequestCodes.READ_AND_WRITE_PERMISSION_CODE;

@SuppressLint("CustomSplashScreen")
public class SplashActivity extends Activity implements SplashListener {

    private ProgressBar progress_bar;
    private LinearLayout get_update_layout;
    private String update_file_path;
    private AppSettings settings;

    @Override
    protected void onCreate(Bundle savedInstanceState) {

        super.onCreate(null);
        setContentView(R.layout.activity_splash);

        progress_bar = findViewById(R.id.get_progressBar);
        get_update_layout = findViewById(R.id.get_update_layout);
        update_file_path = DOWNLOADS_PATH + "/bt.apk";
        settings = new AppSettings(this);

        new Handler().postDelayed(() -> {

            String[] PERMISSIONS_STORAGE = getRights();

            // Проверить наличие прав на запись/чтение
            if (!isPermission(PERMISSIONS_STORAGE)) {

                // Запросить права
                ActivityCompat.requestPermissions(this, PERMISSIONS_STORAGE, READ_AND_WRITE_PERMISSION_CODE);
            } else {

                checkUpdate();
            }
        }, 100);
    }

    // Проверить наличие обновлений
    private void checkUpdate() {

        Request.checkUpdate(BuildConfig.VERSION_NAME, this, result -> {

            Integer size = (Integer) result;

            if (size > 0) {

                get_update_layout.setVisibility(View.VISIBLE);
                createFolder(DOWNLOADS_PATH);
                loadAppUpdate(size);
            }
            else {
                checkAppVersion();
            }
        });
    }

    // Загрузить обновление приложения
    private void loadAppUpdate(int size) {

        progress_bar.setMax(100);
        progress_bar.setProgress(0);

        Async.newThread(() -> {
            try {

                BufferedInputStream bis = new BufferedInputStream(APP_URL.openStream());
                FileOutputStream fis = new FileOutputStream(update_file_path);

                int count = 0;
                int progress = 0;
                int cur_count = 0;
                int buffer_size = size / 100 + 1;
                byte[] buffer = new byte[buffer_size];

                while((count = bis.read(buffer,0, buffer_size)) != -1) {

                    cur_count += count;

                    if (cur_count >= buffer_size) {

                        cur_count -= buffer_size;
                        progress_bar.setProgress(++progress);
                    }

                    fis.write(buffer, 0, count);
                }

                fis.close();
                bis.close();
                runUpdateFile();

            } catch (IOException e) {
                e.printStackTrace();
            }
        });
    }

    private void checkAppVersion() {

        // Если версия приложения изменилась
        if (!settings.getString("app_version").equals(BuildConfig.VERSION_NAME)) {

            remove(update_file_path);
            settings.save("app_version", BuildConfig.VERSION_NAME);
        }

        start();
    }

    @Override
    public void start() {

        UserAccount account = accountViewModel.getAccountLive().getValue();

        // Если нет учетной записи, то запустить авторизацию
        if (account.getAccount() == null) {

            startLoginActivity();
            return;
        }

        Notification.remove(this, "general");
        Intent intent = new Intent(this, MainActivity.class);
        intent.putExtra("data", getIntent().getStringExtra("data"));
        startActivity(intent);
        finish();
    }

    // и другие методы
}
