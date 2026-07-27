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
import com.example.bt.request.Request;
import com.example.bt.response.AuthResponse;

public class EnterPhoneFragment extends Fragment {

    private Context context;
    private EditText phone_edit;
    private Button next_btn;
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
        return inflater.inflate(R.layout.fragment_enter_phone, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);

        phone_edit = view.findViewById(R.id.phone);
        next_btn = view.findViewById(R.id.next_btn);
        progress_bar = view.findViewById(R.id.progress_bar);

        next_btn.setOnClickListener(v -> nextButtonClick());
        phone_edit.setInputType(InputType.TYPE_CLASS_NUMBER);
    }

    // Обработать нажатие кнопки "Далее"
    private void nextButtonClick() {

        String phone = phone_edit.getText().toString();

        if (phone.length() == 0) {

            Toast.makeText(context, "Введите номер телефона", Toast.LENGTH_SHORT).show();
            return;
        }

        if (phone.length() < 10) {

            Toast.makeText(context, "Номер недостаточной длины", Toast.LENGTH_SHORT).show();
            return;
        }

        phone_edit.setEnabled(false);
        next_btn.setEnabled(false);
        progress_bar.setVisibility(View.VISIBLE);

        sendPhone(phone);
    }

    // Отправить номер телефона
    private void sendPhone(String phone) {
        
        Request.sendPhone(phone, context, res -> {

            AuthResponse authResult = (AuthResponse) res;

            if (authResult.isSuccess()) {
                loginListener.setConfirmPhoneFragment(authResult, phone);
            }
            else {
                Toast.makeText(context, authResult.getMessage(), Toast.LENGTH_SHORT).show();
            }

            phone_edit.setEnabled(true);
            next_btn.setEnabled(true);
            progress_bar.setVisibility(View.GONE);
        });
    }
}
