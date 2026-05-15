package com.example.bt.model;

import androidx.lifecycle.LiveData;
import androidx.lifecycle.MutableLiveData;
import androidx.lifecycle.ViewModel;

public class AccountViewModel extends ViewModel {
    private final MutableLiveData<UserAccount> accountLiveData = new MutableLiveData<>(null);
    private final MutableLiveData<ProfileState> profileStateLiveData = new MutableLiveData<>(null);

    public LiveData<UserAccount> getAccountLive() {
        return accountLiveData;
    }

    public LiveData<ProfileState> getProfileStateLive() {
        return profileStateLiveData;
    }

    public void setAccount(UserAccount data) {
        accountLiveData.setValue(data);
    }

    public void setProfileState(ProfileState data) {
        profileStateLiveData.setValue(data);
    }
}
