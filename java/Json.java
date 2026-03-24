package com.example.bt.modules;

import com.google.gson.Gson;
import com.google.gson.JsonSyntaxException;

public class Json {

    private static Gson gson = null;

    private static Gson getGson() {

        if (gson == null) gson = new Gson();

        return gson;
    }

    public static <T> T from(String json, Class<T> requiredClass) {

        try {
            return getGson().fromJson(json, requiredClass);
        } catch (JsonSyntaxException ex) {
            return null;
        }
    }

    public static String to(Object obj) {

        return getGson().toJson(obj);
    }
}
