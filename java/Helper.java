package com.example.bt.modules;

import android.app.Activity;
import android.app.ActivityManager;
import android.content.Context;
import android.os.Build;
import android.util.DisplayMetrics;
import android.view.Display;
import android.view.View;
import android.view.Window;
import android.view.WindowManager;

import androidx.core.content.ContextCompat;
import androidx.fragment.app.FragmentActivity;

import com.example.bt.R;

public class Helper {

    public static int pxToDp(Context context, int px) {
        return Math.round(px / context.getResources().getDisplayMetrics().density);
    }

    public static int dpToPx(Context context, int dp) {
        return Math.round(dp * context.getResources().getDisplayMetrics().density);
    }

    public static void setStatusBarColor(Activity activity, boolean light) {
        
        Window window = activity.getWindow();
        window.clearFlags(WindowManager.LayoutParams.FLAG_TRANSLUCENT_STATUS);
        window.addFlags(WindowManager.LayoutParams.FLAG_DRAWS_SYSTEM_BAR_BACKGROUNDS);

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            window.getDecorView().setSystemUiVisibility(light ? View.SYSTEM_UI_FLAG_LIGHT_STATUS_BAR : 0);
            window.setStatusBarColor(ContextCompat.getColor(activity, light ? R.color.white : R.color.bt_blue_dark));
        }
        else {
            window.setStatusBarColor(ContextCompat.getColor(activity, R.color.material_on_background_disabled));
        }
    }

    public static DisplayMetrics getDisplayMetrics(FragmentActivity activity) {

        Display display = activity.getWindowManager().getDefaultDisplay();
        DisplayMetrics display_metrics = new DisplayMetrics();
        display.getMetrics(display_metrics);
        return display_metrics;
    }

    // Флаг работы приложения на переднем плане
    public static boolean isAppRunning() {

        ActivityManager.RunningAppProcessInfo myProcess = new ActivityManager.RunningAppProcessInfo();
        ActivityManager.getMyMemoryState(myProcess);
        return myProcess.importance == ActivityManager.RunningAppProcessInfo.IMPORTANCE_FOREGROUND;
    }

    // Получить модель устройства
    public static String getDevice() {

        String device = Build.MANUFACTURER.toLowerCase();
        String model = Build.MODEL.toLowerCase();

        if (model.contains(device)) return Build.MODEL;
        if (device.contains(model)) return Build.MANUFACTURER;

        return Build.MANUFACTURER + " " + Build.MODEL;
    }
}
