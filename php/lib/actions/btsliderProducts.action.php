<?php

class btsliderProductsAction extends btsliderViewAction
{    
    public function execute()
    {
        if (!$this->getRights('products')) {
            throw new waRightsException('Нет доступа к разделу');
        }

        $prodModel = new btsliderProductsModel();
        $prodFeaturesModel = new btsliderProductsFeaturesModel();
        $products = (new waModel())->query('SELECT * FROM btslider_products')->fetchAll();
        $currencies = include(wa()->getConfig()->getConfigPath('currencies.php', false, 'btslider'));
        $res = [];

        foreach ($products as &$product) {

            $product['name'] = htmlspecialchars($product['name']);
            $product['features'] = $prodFeaturesModel->getByField('product_id', $product['id'], true);
            $product['price'] = round($product['price'], 2);
            $product['img'] = $prodModel->getImgUrl($product['id'], $product['img'], false);
            $res[$product['id']] = $product;
        }

        $this->view->assign('products', $res);
        $this->view->assign('currencies', $currencies);
    }
}