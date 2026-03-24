<?php

// Метообновление

$model = new waModel();

try {
    $model->exec("SELECT `route_id` FROM `btslider_slider_products`");
} catch (waDbException $e) {

    $model->exec("ALTER TABLE `btslider_slider_products` ADD `route_id` INT (11)");
    $model->exec("ALTER TABLE `btslider_slider_products` MODIFY `domain` VARCHAR (255)");

    $slider_products = $model->query(
        "SELECT slider_id, product_id, domain FROM btslider_slider_products WHERE `type` = 1"
    )->fetchAll();

    $routes = btsliderShopHelper::getRoutes();
    $sliderProductsModel = new btsliderSliderProductsModel();

    foreach ($slider_products as &$item) {

        if (!isset($routes[$item['domain']])) continue;

        $product_type_id = (int) $model->query(
            'SELECT type_id FROM shop_product WHERE id = i:0', [$item['product_id']]
        )->fetchField();
        
        $selected_route = null;

        foreach ($routes[$item['domain']] as $id => &$route) {

            if (!isset($route['type_id'])) continue;

            if (!is_array($route['type_id']) || in_array($product_type_id, $route['type_id'])) {

                $selected_route = $id;
            }

            if (!isset($route['private']) || !$route['private']) break;
        }

        if ($selected_route) {

            $sliderProductsModel->updateByField(
                ['slider_id' => $item['slider_id'], 'product_id' => $item['product_id'], 'type' => 1],
                ['route_id' => $selected_route]
            );
        }
    }
}
