package com.example.bt.fragments.login;

import android.content.Context;
import android.os.Bundle;
import android.text.InputType;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;

import com.example.bt.R;
import com.example.bt.interfaces.LoginListener;
import com.example.bt.modules.FirebaseToken;
import com.example.bt.request.Request;
import com.example.bt.response.AuthResponse;

public class ConfirmPhoneFragment extends Fragment {
    private Context context;
    private EditText code_edit;
    private Button confirm_btn;
    private ProgressBar progress_bar;
    private LoginListener loginListener;

    @Override
    public void onAttach(@NonNull Context context) {
        super.onAttach(context);
        this.context = context;
        loginListener = (LoginListener) context;
    }

    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_confirm_phone, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);

        code_edit = view.findViewById(R.id.code);
        confirm_btn = view.findViewById(R.id.confirm_btn);
        progress_bar = view.findViewById(R.id.progress_bar);

        confirm_btn.setOnClickListener(v -> confirmButtonClick());
        code_edit.setInputType(InputType.TYPE_CLASS_NUMBER);

        view.findViewById(R.id.back).setOnClickListener(v -> requireActivity().onBackPressed());
    }

    // Обработать нажатие кнопки "Подтвердить"
    private void confirmButtonClick() {
        String code = code_edit.getText().toString();

        if (code.length() == 0) {
            Toast.makeText(context, "Введите код", Toast.LENGTH_SHORT).show();
            return;
        }

        code_edit.setEnabled(false);
        confirm_btn.setEnabled(false);
        progress_bar.setVisibility(View.VISIBLE);
        confirmPhone(code);
    }

    public void confirmPhone(String code) {

        AuthResponse authData = loginListener.getAuthResult();

        FirebaseToken.getToken(token -> Request.confirmCode(
            authData.getUserId(), authData.getDeviceId(), code, token, context, res -> {

                AuthResponse result = (AuthResponse) res;

                if (result.isSuccess()) {
                    loginListener.createAccount(result.getDeviceId(), result.getData());
                    return;
                }

                Toast.makeText(context, result.getMessage(), Toast.LENGTH_SHORT).show();

                confirm_btn.setEnabled(true);
                code_edit.setEnabled(true);
                progress_bar.setVisibility(View.GONE);
            }
        ));
    }
}
