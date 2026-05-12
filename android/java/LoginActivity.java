package com.example.bt;

import android.content.Intent;
import android.os.Bundle;
import android.widget.Toast;

import com.example.bt.fragments.login.ConfirmPhoneFragment;
import com.example.bt.fragments.login.EnterPhoneFragment;
import com.example.bt.interfaces.LoginListener;
import com.example.bt.modules.Accounts;
import com.example.bt.response.AuthResponse;

public class LoginActivity extends Activity implements LoginListener {

    private AuthResponse authResult;
    private String phone;

    @Override
    protected void onCreate(Bundle bundle) {
        
        super.onCreate(null);
        setContentView(R.layout.activity_login);

        getSupportFragmentManager().beginTransaction()
            .add(R.id.login_fragment, EnterPhoneFragment.class, null)
            .commit();
    }

    @Override
    public void setConfirmPhoneFragment(AuthResponse result, String phone) {
        
        this.authResult = result;
        this.phone = phone;

        getSupportFragmentManager().beginTransaction()
            .replace(R.id.login_fragment, ConfirmPhoneFragment.class, null)
            .addToBackStack(null)
            .commit();
    }

    @Override
    public AuthResponse getAuthResult() {
        return authResult;
    }

    @Override
    public void createAccount(int session_id, String token) {

        if (Accounts.add(this, authResult.getStringUserId(), String.valueOf(session_id), phone, token)) {
            startActivity(new Intent(this, MainActivity.class));
            finish();
        }
        else {
            Toast.makeText(this, "Не удалось создать аккаунт", Toast.LENGTH_SHORT).show();
        }
    }
}
