package com.example.bt.modules;

import java.io.Serializable;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class FragmentController implements Serializable {

    private final Map<Integer, List<String>> dict;
    private final List<Integer> bottom_positions;

    public FragmentController() {

        dict = new HashMap<>();
        bottom_positions = new ArrayList<>();
    }

    // Добавить фрагмент в стек
    public void addFragment(String fragment_class, int bottom_position) {

        if (!dict.containsKey(bottom_position)) {
            dict.put(bottom_position, new ArrayList<>());
        }

        dict.get(bottom_position).add(fragment_class);
    }

    public void addFragment(String fragment_class) {

        addFragment(fragment_class, getCurrentBottom());
    }

    // Извлечь фрагмент из стека
    public void popFragment() {

        List<String> list = dict.get(getCurrentBottom());

        if (list == null || list.size() < 2) return;

        list.remove(list.size() - 1);
    }

    // Получить тег активного фрагмента
    public String getCurrentTag() {

        List<String> list = dict.get(getCurrentBottom());

        if (list == null || list.size() == 0) return "";

        return list.get(list.size() - 1);
    }

    public int getCurrentBottom() {

        return bottom_positions.size() != 0
            ? bottom_positions.get(bottom_positions.size() - 1) : -1;
    }

    // Установить позицию текущего пункта bottom-меню
    public void setCurrentBottom(int position) {

        int last_bottom = getCurrentBottom();

        if (last_bottom != -1) {

            bottom_positions.remove(bottom_positions.size() - 1);
        }

        bottom_positions.add(position);
    }

    // Изменить позицию текущего пункта bottom-меню при клике
    public void shiftCurrentBottom(int position) {

        int included_pos = bottom_positions.indexOf(position);

        //Если ссылка на выбранный пункт меню уже есть в списке, то удалить ее из списка
        if (included_pos != -1) {

            bottom_positions.remove(included_pos);
        }

        //Добавить ссылку на выбранный пункт меню в список
        bottom_positions.add(position);
    }

    public boolean isBottomEnd() {
        return bottom_positions.size() < 2;
    }

    public int prevBottom() {

        if (isBottomEnd()) return 0;

        bottom_positions.remove(bottom_positions.size() - 1);

        return bottom_positions.get(bottom_positions.size() - 1);
    }
}
