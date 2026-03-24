<?php

class btEyeParser
{
    const NOT_PARAM = 'Не указан важный параметр';
    const NOT_VENDOR_PRODUCT = 'Товар не найден у поставщика';
    const NOT_VENDOR_PRODUCTS = 'Не получены товары поставщика';

    const SET_DISCOUNT = 'vendor-discounts';
    const SET_ARRIVED = 'vendor-arrived';
    const SET_NEWS = 'vendor-news';

    // Parser data
    protected $id;
    protected $name;
    protected $adapter;
    protected $set_id;
    protected $parse_set_id;
    protected $state;
    protected $auth;
    protected $method;

    // Models
    protected $model;
    protected $skuPricesModel;
    protected $eyeErrorModel;
    protected $vendorProductsModel;
    protected $vendorStocksModel;
    protected $vendorProductStocksModel;
    protected $logModel;
    protected $discountModel;
    protected $skuDiscountsModel;
    protected $vendorCategoryModel;
    protected $vendorArrivedModel;
    protected $shopArrivedModel;

    protected $shopProductModel;
    protected $shopSkusModel;
    protected $shopProductStocksModel;
    protected $setModel;
    protected $setProductsModel;

    // Other fields
    protected $parse_data;
    private $shop_stocks;
    private $stocks = [];
    private $product_images = [];
    private $categories = [];
    private $discountList;
    private $imagesHandler;

    protected function __construct($data)
    {        
        wa('shop');

        $this->id = (int) $data['id'];
        $this->name = $data['name'];
        $this->adapter = $data['adapter'];
        $this->set_id = $data['set_id'];
        $this->parse_set_id = $data['set_id'] ? $data['set_id'] : $data['adapter'];
        $this->auth = $data['auth'];
        $this->method = $data['method'];
        $this->state = $data['state'];
        $this->parse_data = ['sku_count' => 0, 'vendor_count' => 0, 'saved_prices' => 0];

        $this->model = self::getModel();
        $this->skuPricesModel = new shopEyeSkuPricesModel();
        $this->vendorStocksModel = new shopEyeVendorStocksModel();
        $this->vendorProductStocksModel = new shopEyeVendorProductStocksModel();
        $this->vendorProductsModel = new shopEyeVendorProductsModel();
        $this->eyeErrorModel = new shopEyeErrorModel();
        $this->logModel = new btEyeParseModel();
        $this->discountModel = new btEyeVendorDiscountModel();
        $this->skuDiscountsModel = new btEyeSkuDiscountsModel();
        $this->vendorCategoryModel = new btEyeVendorCategoryModel();
        $this->vendorArrivedModel = new btEyeVendorArrivedModel();
        $this->shopArrivedModel = new btEyeShopArrivedModel();

        $this->shopProductModel = new shopProductModel();
        $this->shopSkusModel = new shopProductSkusModel();
        $this->shopProductStocksModel = new shopProductStocksModel();
        $this->setModel = new shopSetModel();
        $this->setProductsModel = new shopSetProductsModel();

        $this->discountList = new btEyeDiscountList();
        $this->imagesHandler = new btVendorImagesHandler();

        if (!$this->setModel->idExists(self::SET_DISCOUNT)) {
            $this->setModel->add(['id' => self::SET_DISCOUNT, 'name' => 'Скидки']);
        }

        if (!$this->setModel->idExists(self::SET_ARRIVED)) {
            $this->setModel->add(['id' => self::SET_ARRIVED, 'name' => 'Поступления']);
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

    ### И ряд других методов...
}