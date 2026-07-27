package com.example.bt.modules;

public class Async {

    public static void newThread(boolean wait, com.example.bt.interfaces.Async callback) {

        try {
            Thread thread = new Thread(callback::execute);
            thread.start();

            if (wait) {
                thread.join();
            }
        }
        catch (Exception ignored) {}
    }

    public static void newThread(com.example.bt.interfaces.Async callback) {

        newThread(false, callback);
    }
}
