<?php

class btParser
{
    const SET_DISCOUNT = 'vendor-discounts';

    // Parser data
    protected $id;
    protected $name;
    protected $adapter;
    protected $set_id;
    protected $state;
    protected $auth;
    protected $method;

    // Models
    protected $model;
    protected $eyeErrorModel;
    protected $vendorProductsModel;
    protected $vendorStocksModel;
    protected $vendorProductStocksModel;
    protected $logModel;
    protected $discountModel;
    protected $skuDiscountsModel;
    protected $vendorCategoryModel;

    protected $shopProductModel;
    protected $shopSkusModel;
    protected $shopProductStocksModel;
    protected $setModel;
    protected $setProductsModel;

    // Other fields
    protected $parse_data;
    private $shop_stocks;
    private $stocks = [];
    private $discountList;

    protected function __construct($data)
    {        
        wa('shop');

        $this->id = (int) $data['id'];
        $this->name = $data['name'];
        $this->adapter = $data['adapter'];
        $this->set_id = $data['set_id'];
        $this->auth = $data['auth'];
        $this->method = $data['method'];
        $this->state = $data['state'];
        $this->parse_data = ['sku_count' => 0, 'vendor_count' => 0];

        $this->model = self::getModel();
        
        // инициализация других моделей

        if (!$this->setModel->idExists(self::SET_DISCOUNT)) {
            $this->setModel->add(['id' => self::SET_DISCOUNT, 'name' => 'Скидки']);
        }
    }

    // Получить адаптер запрашиваемого парсера
    public static function getInstance($parser_id, $adapter = null)
    {        
        try {
            if (!btHelper::isShopAndEye() || !$parser_id && !$adapter) return null;

            $data = $parser_id
                ? self::getModel()->getById($parser_id)
                : self::getModel()->getByField('adapter', $adapter);

            if (!$data) return null;

            $class = self::class;

            if (!$data['adapter']) {
                return new $class($data);
            }

            $adapterClass = $class.$data['adapter'];
        
            return class_exists($adapterClass) ? new $adapterClass($data) : new $class($data);
        } catch (Throwable $th) {
            return null;
        }
    }

    // ### Методы для переопределения ###

    public function loadCategories() {}

    public function parseAll() {}

    public function initPartParse() { return null; }

    public function parsePart($page, $data) { return null; }

    public function parseOne($product_id, $param, $sku_id) { return null; }

    public function getImages($param) { return []; }

    public function createDump() {}

    // ### END ###

    private static function getModel()
    {
        return new shopEyeVendorModel();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAdapter()
    {
        return $this->adapter;
    }

    public function getSetId()
    {
        return $this->set_id;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function isActive()
    {
        return $this->state;
    }

    /**
     * @param array $data - спарсенные данные товара поставщика
     * @param array $stocks - склады, на которых есть товар поставщика
     * @param int|null $vendor_product_id - ID товара поставщика
     * @param array $images - массив ссылок на изображения товара поставщика
     */
    public function updateProducts($data, $stocks = [], $vendor_product_id = 0, $images = null)
    {
        if (!$this->isActive()) return;

        $data = $this->getFilteredData($data);

        if ($vendor_product_id === 0) {
            $vendor_product_id = $this->getProductIdByParam($data['param']);
        }

        $is_related = $this->vendorProductsModel->isRelated($vendor_product_id);
        $old_prices = $this->vendorProductsModel->getPrices($vendor_product_id);

        // Добавить/обновить товар поставщика
        $stocks = $this->saveVendorProduct($data, $stocks, $vendor_product_id, $is_related);

        // Не продолжать, если товар новый/не связан с артикулами
        if (!$is_related) return;

        // Получить артикулы, связанные с товаром поставщика, и их данные для автообновления
        $skus = $this->model->query(
            'SELECT sa.* FROM shop_eye_sku_vendors sv
                JOIN shop_eye_sku_autoupdate sa ON sv.sku_id = sa.sku_id
             WHERE sv.vendor_product_id = i:0 AND sv.is_default = 1', [$vendor_product_id]
        )->fetchAll();

        $old_price = ifset($old_prices['price']);
        $old_p_price = ifset($old_prices['purchase_price']);

        $price_discount = btEyePrices::calcDiscount($data['price'], $old_price);
        $p_price_discount = btEyePrices::calcDiscount($data['purchase_price'], $old_p_price);

        $discount = null;
        $existing_discounts = [];
        $prod_disc_push = [];

        foreach ($skus as $sku_data) {

            $sku_id = $sku_data['sku_id'];

            // Получить данные товара по ID артикула
            $product = $this->model->query(
                'SELECT sp.id, sp.sku_id as main_sku_id, sp.category_id, sp.name, sps.name as sku_name,
                    sps.sku, sps.compare_price as sku_compare_price, sps.bt_disc_id as discount_id
                 FROM shop_product_skus sps
                    JOIN shop_product sp ON sps.product_id = sp.id
                 WHERE sps.id = i:0', [$sku_id]
            )->fetchAssoc();

            if (!$product) continue;

            $this->parse_data['sku_count']++;

            $sku_name = $product['sku_name'] ? $product['sku_name'] : ($product['sku'] ? $product['sku'] : $sku_id);

            if ($images) {
                $this->saveImages($sku_id, $product['id'], $images);
            }

            // Получить порог отслеживаемых скидок для категории товара
            $category_discount = $this->discountList->getDiscount($product['category_id']);
            $sku_has_discount = false;

            // Если артикул попадает под скидку
            if ($category_discount && ($price_discount >= $category_discount || $p_price_discount >= $category_discount)) {

                $sku_has_discount = true;

                // Создать запись о скидке
                if (!$discount) {

                    $discount = $this->discountModel->create($vendor_product_id, $data, $old_price, $old_p_price);
                }                

                // Связать артикул со скидкой
                $this->shopSkusModel->updateById($sku_id, ['bt_disc_id' => $discount['id']]);
                $this->skuDiscountsModel->insert(['sku_id' => $sku_id, 'discount_id' => $discount['id']]);

                if (!isset($prod_disc_push[$product['id']])) {
                    $prod_disc_push[$product['id']] = ['name' => $product['name'], 'skus' => []];
                }

                // Сохранить артикул со скидкой для push
                $prod_disc_push[$product['id']]['skus'][] = $sku_name;
            }
            elseif ($product['discount_id']) {

                // Получить действующую скидку артикула
                if (!isset($existing_discounts[$product['discount_id']])) {

                    $_disc = $this->discountModel->getById($product['discount_id']);
                    $existing_discounts[$product['discount_id']] = $this->discountModel->getUpdated($_disc, $data);
                }

                $_disc = &$existing_discounts[$product['discount_id']];
                $not_disc = $_disc['disc'] < $category_discount && $_disc['pur_disc'] < $category_discount;
                $two_months_later = time() > strtotime('+2 month', strtotime($_disc['datetime']));

                // Если розница или закуп близки к старым ценам скидки или выше их или прошел срок действия скидки
                if (!$category_discount || $not_disc || $two_months_later) {

                    // Отменить скидку у артикула
                    $this->shopSkusModel->updateById($sku_id, ['bt_disc_id' => null]);
                }
                elseif (!$_disc['updated']) {

                    $_disc['updated'] = true;

                    // Обновить действующую скидку
                    $this->discountModel->update($product['discount_id'], $data, $_disc);
                }
            }

            $this->updateStocks($stocks, $product['id'], $sku_id);

            if (!$data['price']) continue;

            $primary_price = $data['price'];
            $purchase_price = $data['purchase_price'];
            $compare_price = ifset($discount['old_price']);

            if ($sku_has_discount && $discount['discount']) {

                // Добавить товар в список розничных скидок
                $this->setProductsModel->replace(['set_id' => self::SET_DISCOUNT, 'product_id' => $product['id']]);
            }

            // Обновить главную цену товара, если текущий артикул и основной равны
            $product_prices = $sku_id == $product['main_sku_id'] && $primary_price > 0
                ? ['price' => $primary_price, 'base_price' => $primary_price] : [];

            $sku_prices = $primary_price > 0
                ? ['price' => $primary_price, 'primary_price' => $primary_price] : [];

            if ($compare_price) {
                $product_prices['compare_price'] = $sku_prices['compare_price'] = $compare_price;
            }

            if ($purchase_price) {
                $sku_prices['purchase_price'] = $purchase_price;
            }

            // Сначала обновим цены артикула
            if ($sku_prices) {

                $this->shopSkusModel->updateById($sku_id, $sku_prices);
            }

            $this->saveProdPrice($product['id'], $product_prices);
        }

        $this->parse_data['vendor_count']++;

        $this->setModel->recount(self::SET_DISCOUNT);

        if ($discount) {
            btEyePush::pushDiscount($discount, $prod_disc_push, $data['param'], $this->name, $stocks['discount']);
        }
    }

    private function saveVendorProduct($data, $stocks, $vendor_product_id, $is_related)
    {
        if (!$vendor_product_id) {

            // Не добавлять новые товары без параметра или наименования
            if (!$data['param'] || !$data['name']) return [];

            $data['vendor_id'] = $this->id;
            $vendor_product_id = $this->vendorProductsModel->add($data);
        }
        else {

            unset($data['param']);

            if (!$data['name']) {
                unset($data['name']);
            }

            $this->vendorProductsModel->update($vendor_product_id, $data);
            $this->vendorProductStocksModel->deleteByField('vendor_product_id', $vendor_product_id);
        }

        return $this->saveVendorStocks($stocks, $vendor_product_id, $data['last_parse_datetime'], $is_related);
    }

    // Сохранить склады поставщика, вернуть связанные с ними склады магазина с новым наличием
    private function saveVendorStocks($stocks, $vendor_product_id, $datetime, $is_related)
    {
        $shop_states = [];
        $disc_stocks = '';
        $pre_disc_stocks = false;
        
        foreach ($stocks as $stock_name => $stock_status) {

            if (!isset($this->stocks[$stock_name])) {

                $id = $this->vendorStocksModel->add($this->id, $stock_name);

                $this->stocks[$stock_name] = [
                    'id' => $id,
                    'shop_id' => $this->getShopStockId($id)
                ];
            }

            $_stock = &$this->stocks[$stock_name];
            $status = shopEyePluginStatusUtil::getFormatStatus($stock_status);

            $this->vendorProductStocksModel->add($_stock['id'], $vendor_product_id, $status, $datetime);

            if (!$is_related) continue;

            // Склады для скидок
            if ($disc_stocks && !$pre_disc_stocks) {
                $disc_stocks = '%0A'.$disc_stocks;
                $pre_disc_stocks = true;
            }

            $numb_status = shopEyePluginStatusUtil::getNumbStatus($stock_status);
            $stock_html = btEyePush::escape($stock_name)." → $status";
            $disc_stocks .= ($pre_disc_stocks ? '%0A' : '').$stock_html;

            // Если склад поставщика связан со складом магазина
            if ($_stock['shop_id']) {

                if (!isset($shop_states[$_stock['shop_id']])) {

                    $shop_states[$_stock['shop_id']] = $numb_status;
                }
                else {

                    $count = $shop_states[$_stock['shop_id']];

                    // Если неопределенность или очень мало
                    if ($count == -1 && $numb_status > 0 || $count == -3 && $numb_status) {
                        $shop_states[$_stock['shop_id']] = $numb_status;
                    }
                    elseif ($count >= 0 && $numb_status > 0) {
                        $shop_states[$_stock['shop_id']] += $numb_status;
                    }
                }
            }
        }

        return ['shop' => $shop_states, 'discount' => $disc_stocks];
    }

    private function updateStocks($stocks, $product_id, $sku_id)
    {
        // Обновить у артикула данные по складам
        foreach ($this->shop_stocks as &$stock) {

            $state = ifset($stocks['shop'][$stock['id']], 0);

            // Если бесконечно много
            if ($state == -1) {
                $this->shopProductStocksModel->deleteByField(['sku_id' => $sku_id, 'stock_id' => $stock['id']]);
            }
            else {
                $this->shopProductStocksModel->replace([
                    'sku_id' => $sku_id, 
                    'product_id' => $product_id, 
                    'stock_id' => $stock['id'], 
                    'count' => abs($state)
                ]);
            }
        }
    }

    private function saveProdPrice($product_id, $product_prices)
    {
        // Получить актуальные MIN и MAX цены товара
        $product_prices['min_price'] = (float) $this->model->query(
            'SELECT MIN(price) FROM shop_product_skus WHERE product_id = i:0', [$product_id]
        )->fetchField();

        $product_prices['max_price'] = (float) $this->model->query(
            'SELECT MAX(price) FROM shop_product_skus WHERE product_id = i:0', [$product_id]
        )->fetchField();

        $product_prices['min_base_price'] = $product_prices['min_price'];
        $product_prices['max_base_price'] = $product_prices['max_price'];

        // Обновить доп. цены товара
        $this->shopProductModel->updateById($product_id, $product_prices);
    }

    // и другие методы
}