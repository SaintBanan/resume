<?php

class btShopCategories
{
    public static function getMainParentName($parent_id, $depth)
    {
        $parent_id = (int) $parent_id;
        $depth = (int) $depth;
        $join = '';

        if ($parent_id <= 0 || $depth <= 0) return null;

        for ($i = 2; $i <= $depth; $i++) {

            $pre_i = $i - 1;
            $join .= " JOIN shop_category c$i ON c$i.id = c$pre_i.parent_id";
        }

        $res = btHelper::query(
            "SELECT c$depth.name FROM shop_category c1$join WHERE c1.id = $parent_id"
        )->fetchField();

        return $res ? htmlspecialchars($res) : null;
    }

    public static function getCategories($depth = 2, $parent_id = 0)
    {
        $res = btHelper::query(
            "SELECT id, `name` FROM shop_category WHERE parent_id = i:0", [$parent_id]
        )->fetchAll();

        foreach ($res as &$item) {

            $item['name'] = htmlspecialchars($item['name']);
            $item['children'] = $depth > 1 ? self::getCategories($depth - 1, $item['id']) : [];
        }

        return $res;
    }

    public static function getSubIds($parent_id)
    {
        $res = array_column(btHelper::query(
            "SELECT id FROM shop_category WHERE parent_id = i:0", [$parent_id]
        )->fetchAll(), 'id');

        $subs = [];

        foreach ($res as $id) {

            $_subs = self::getSubIds($id);

            if ($_subs) {
                $subs = array_merge($subs, $_subs);
            }
        }

        return $subs ? array_merge($res, $subs) : $res;
    }
}