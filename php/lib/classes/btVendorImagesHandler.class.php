<?php

class btVendorImagesHandler
{
    private $is_shop;
    private $save_size = 900;
    private $load_img_count = 0;
    private $product_count = 0;
    private $products = [];

    // Models
    private $imageModel;
    private $prodSkusModel;

    function __construct()
    {
        $this->is_shop = btHelper::isApp('shop');

        if ($this->is_shop) {

            wa('shop');

            $this->imageModel = new shopProductImagesModel();
            $this->prodSkusModel = new shopProductSkusModel();
        }
    }

    public function run()
    {
        if (!$this->is_shop) return;

        ini_set('memory_limit', '-1');

        // Получить товары, у которых нет ни одной картинки
        $products = btHelper::query(
            'SELECT DISTINCT sp.id FROM shop_product sp
                LEFT JOIN shop_product_images spi ON sp.id = spi.product_id
             WHERE spi.id IS NULL ORDER BY sp.id'
        )->fetchAll();

        foreach ($products as $product) {

            $images = [];

            if (!$images) {
                $images = $this->getFeatureImages($product['id']);
            }

            $this->start($product['id'], $images);
        }

        $this->log();
    }

    private function getParsersImages($product_id)
    {
        $parsers = btHelper::query(
            'SELECT sv.sku_id, sv.vendor_id, vp.param
             FROM shop_eye_sku_vendors sv
                JOIN shop_eye_vendor_products vp ON vp.id = sv.vendor_product_id
                JOIN shop_product_skus ps ON ps.id = sv.sku_id
             WHERE ps.product_id = i:id', ['id' => $product_id]
        )->fetchAll();

        $res = [];

        foreach ($parsers as $item) {

            $parser = btEyeParser::getInstance($item['vendor_id']);

            if (!$parser) continue;

            $images = $parser->getImages($item['param']);

            if ($images) {
                $res[] = ['sku_id' => $item['sku_id'], 'urls' => $images];
            }
        }

        return $res;
    }

    // Получить url'ы изображений товара по харке
    private function getFeatureImages($product_id)
    {
        $res = btHelper::query(
            'SELECT spf.sku_id, sft.value as urls
             FROM shop_feature_values_text sft
                JOIN shop_product_features spf ON sft.id = spf.feature_value_id
                JOIN shop_feature sf ON sf.id = spf.feature_id
             WHERE sf.code = "izobrazheniya_tovara" AND spf.product_id = i:id',
             ['id' => $product_id]
        )->fetchAll();

        foreach ($res as &$item) {

            $item['urls'] = explode(',', trim(preg_replace('/\s+/', "", $item['urls']), ','));

            if (count($item['urls']) != 1) continue;

            $item['urls'] = explode(';', trim($item['urls'][0], ';'));

            if (count($item['urls']) != 1) continue;

            $item['urls'] = explode('http', trim($item['urls'][0], 'http'));

            foreach ($item['urls'] as &$url) {

                $url = 'http'.$url;
            }
        }

        return $res;
    }

    public function start($product_id, $images)
    {
        if (!$product_id || !$images) return;
        
        $product_images_dir = $this->getProdImagesPath($product_id);
        $product_image_dir = wa()->getDataPath($product_images_dir, false, 'shop'); // Каталог для оригинальных фото
        $miniatures_dir = wa()->getDataPath($product_images_dir, true, 'shop'); // Каталог для миниатюр

        // Если скрипт запущен под root (например, через cron)
        if (!posix_getuid()) {
            $this->changeRoot($product_image_dir);
            $this->changeRoot($miniatures_dir);
        }

        if (!isset($this->products[$product_id])) {
            $this->products[$product_id] = ['urls' => [], 'sort' => 1, 'load' => false];
        }

        foreach ($images as $img_item) {

            $sku_id = $img_item['sku_id'];

            foreach ($img_item['urls'] as $url) {

                // Если url пустой или уже использовался для текущего товара
                if (!$url || in_array($url, $this->products[$product_id]['urls'])) continue;

                $this->products[$product_id]['urls'][] = $url;

                // Загрузить файл по ссылке
                $target_dir = $this->load($url);

                if (!$target_dir) continue;

                foreach (glob($target_dir."*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}", \GLOB_BRACE) as $file_path) {

                    try {

                        $img_id = 0;
                        $img = waImage::factory($file_path, 'Bt');
                        $jpg_file = $this->getFileNameFromLink($file_path).'.jpg';

                        // if source image not .jpg
                        if(!in_array(strtolower($img->getExt()), ['jpg', 'jpeg'])) {

                            $jpg_file_path = $target_dir.$jpg_file;
                            $img->save($jpg_file_path);
                            $img = waImage::factory($jpg_file_path, 'Bt');
                            unlink($file_path);
                            $file_path = $jpg_file_path;
                        }

                        // Если фото больше ожидаемого размера - сжать
                        if ($img->width > $this->save_size || $img->height > $this->save_size) {
                            $img->mresize($this->save_size, $this->save_size);
                        }

                        $width = (int) $img->width;
                        $height = (int) $img->height;

                        // Если фото прямоугольное
                        if ($height != $width) {

                            // Определить большую сторону изображения
                            $is_max_height = $height > $width;
                            $max_size = $is_max_height ? $height : $width;

                            $size_diff = $max_size - ($is_max_height ? $width : $height); 

                            // Определить смещение изображения по меньшей стороне внутри квадрата
                            $width_shift = $is_max_height ? (int) ($size_diff / 2) : 0;
                            $height_shift = !$is_max_height ? (int) ($size_diff / 2) : 0;

                            $back_path = $this->getBackgroundPath($max_size);
                            $back_img = waImage::factory($back_path, 'Bt');

                            // Вписать фото в белый квадрат 
                            $img->format($back_img, $width_shift, $height_shift);

                            if ($max_size < $this->save_size) {
                                unlink($back_path);
                            }
                        }

                        $img->save();

                        $img_id = $this->imageModel->insert([
                            'product_id' => $product_id,
                            'sort' => $this->products[$product_id]['sort'],
                            'upload_datetime' => waDateTime::date('Y-m-d H:i:s', null, 'Europe/Moscow'),
                            'ext' => 'jpg',
                            'width' => $img->width,
                            'height' => $img->height,
                            'size' => filesize($file_path),
                            'original_filename' => $jpg_file
                        ]);

                        $save_path = $product_image_dir.$img_id.'.jpg';
                        $img->save($save_path, 95);
                        $this->imageModel->updateById($img_id, ['size' => filesize($save_path)]);

                        // Расширение каталога для миниатюр
                        $ext_miniatures_dir = wa()->getDataPath($product_images_dir.$img_id.'/', true, 'shop');

                        if (!posix_getuid()) {
                            $this->changeRoot($ext_miniatures_dir);
                        }

                        // Указать товару ID первого фото
                        if ($this->products[$product_id]['sort']++ == 1) {
                
                            btHelper::query(
                                "UPDATE shop_product SET ext = 'jpg', image_id = $img_id WHERE id = i:id",
                                ['id' => $product_id]
                            );
                        }

                        // Связать фото с артикулом
                        if ($sku_id) {
                            $this->prodSkusModel->updateById($sku_id, ['image_id' => $img_id]);
                            $sku_id = null;
                        }

                        $this->load_img_count++;

                        if (!$this->products[$product_id]['load']) {

                            $this->products[$product_id]['load'] = true;
                            $this->product_count++;
                        }

                    } catch (Throwable $th) {

                        if ($img_id) {
                            $this->imageModel->deleteById($img_id);
                        }

                        //btHelper::log("Фото не сохранено - ".$th->getMessage().", #$product_id", 'cron/images.log');
                    }
                }

                waFiles::delete($target_dir, true);
            }
        }
    }

    // Скачать фото/архив по URL. Вернуть путь к рабочему каталогу.
    private function load($url)
    {
        try {
            $file_exp = $this->getFileExpFromHeaders($url);

            if (!$file_exp) return null;

            $file_name = $this->getFileNameFromLink($url);
            $target_dir = wa()->getDataPath("tmp/$file_name/", true, 'bt');
            $source_path = $target_dir."$file_name.$file_exp";
            $content = file_get_contents($url);

            if ($content === false) return null;

            if (file_put_contents($source_path, $content) === false) return null;

            // Если файл является архивом - распаковать
            if ($file_exp == 'zip') {

                $zip = new ZipArchive();
                $zip->open($source_path);
                $zip->extractTo($target_dir);
                $zip->close();

                unlink($source_path);
            }

            return $target_dir;

        } catch (Throwable $th) {
            btHelper::log("ImagesHandler->load $url: ".$th->getMessage(), 'cron/images.log');
            return null;
        }
    }

    private function getProdImagesPath($product_id)
    {
        $id_len = strlen($product_id);
        $id_str = $product_id;

        if ($id_len == 1) {

            $id_str = '000'.$id_str;
        }
        elseif ($id_len == 2) {

            $id_str = '00'.$id_str;
        }
        elseif ($id_len == 3) {

            $id_str = '0'.$id_str;
        }

        return 'products/'.substr($id_str, -2).'/'.substr($id_str, -4, 2).'/'.$product_id.'/images/';
    }

    // Получить квадрат с белым фоном
    private function getBackgroundPath($size)
    {
        $back_jpg_path = wa()->getDataPath("tmp/background$size.jpg", true, 'bt');

        // Создать фон
        if (!file_exists($back_jpg_path)) {

            // Создать белый квадрат заданного размера
            $back_png_path = wa()->getDataPath('tmp/background.png', true, 'bt');
            $img = imagecreatetruecolor($size, $size);
            $background_color = imagecolorallocate($img, 0, 0, 0);

            // Сохранить в png для сохранения качества
            imagecolortransparent($img, $background_color);
            imagepng($img, $back_png_path);
            imagedestroy($img);

            // Пересохранить в jpg
            $img = waImage::factory($back_png_path);
            $img->save($back_jpg_path);

            unlink($back_png_path);
        }

        return $back_jpg_path;
    }

    // Получить наименование изображения из url
    private function getFileNameFromLink($url)
    {
        $tmp = explode('/', trim($url, '/'));
        $tmp = explode('.', array_pop($tmp));
        return $tmp[0];
    }

    private function getFileExpFromHeaders($url)
    {
        $type = ifset(get_headers($url, 1)['Content-Type']);

        if (!is_array($type)) {
            $type = [$type];
        }

        foreach ($type as $t) {

            switch (strtolower($t)) {
                case 'image/jpeg': return 'jpg';
                case 'image/png': return 'png';
                case 'image/webp': return 'webp';
                case 'application/zip': return 'zip';
            }
        }

        return null;
    }

    private function changeRoot($dir)
    {
        chown($dir, 'u0147169');
        chgrp($dir, 'u0147169');
    }

    private function getFormatedUrl($url)
    {
        if (!preg_match('/:\/{2,2}/', $img_url)) {

            $sep_pos = mb_strpos($img_url, ':/');
            
            if ($sep_pos === false) return 0;
            
            $img_url = mb_substr($img_url, 0, $sep_pos + 1).'/'.mb_substr($img_url, $sep_pos + 1);
        }

        return str_replace('/www.iek', '/old.iek', $img_url);
    }

    public function log($pre = null)
    {
        if ($this->load_img_count) {
            btHelper::log(($pre ? $pre.' ' : '')."CRON: загружено {$this->load_img_count} фото для {$this->product_count} товаров.", 'cron/images.log');
        }
    }
}