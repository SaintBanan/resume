package com.example.bt.fragments;

import android.app.Activity;
import android.content.Intent;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;

import com.example.bt.interfaces.ActivityLauncherCallback;

public class Fragment extends androidx.fragment.app.Fragment {

    public boolean is_loading = false;

    public String getTitle() { return getClass().getSimpleName(); }

    public void scrollToStart() {}

    public ActivityResultLauncher<Intent> getActivityLauncher(ActivityLauncherCallback callback) {
        return registerForActivityResult(
            new ActivityResultContracts.StartActivityForResult(), result -> {
                if (result.getResultCode() == Activity.RESULT_OK && callback != null && result.getData() != null) {
                    callback.execute(result.getData());
                }
            });
    }
}
