<?php

class btDealSaveImageMethod extends btApiMethod
{
    protected $method = 'POST';
    
    public function execute()
    {
        if (waRequest::method() != 'post') return;

        ini_set('memory_limit', '-1');

        $deal_id = waRequest::post('deal_id', 0, 'int');
        $user_id = waRequest::post('user_id', 0, 'int');
        $is_camera = waRequest::post('is_camera', '') == 'true';
        $datetime = waRequest::post('datetime', btHelper::getDatetime());
        $created_datetime = waRequest::post('created_datetime', '');
        $ext = array_key_exists('image', $_FILES) ? strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION)) : '';

        if (!in_array($ext, ['jpg', 'jpeg']) || !$user_id || !$deal_id) return;

        if (!$created_datetime) {
            $created_datetime = null;
        }

        $imgModel = new btDealImagesModel();

        $img_id = $imgModel->insert([
            'deal_id' => $deal_id,
            'user_id' => $user_id,
            'load_datetime' => $datetime,
            'created_datetime' => $created_datetime,
            'original_name' => $_FILES['image']['name'],
            'is_camera' => $is_camera
        ]);

        $image_dir = 'deals_images/'.$deal_id.'/'.$img_id.'/';
        $image_path = $image_dir.$img_id.'.jpg';

        // Если фото успешно загружено и сохранено
        if (move_uploaded_file($_FILES['image']['tmp_name'], wa()->getDataPath($image_path, true, 'bt'))) {

            $this->response = [
                'id' => $img_id,
                'name' => $_FILES['image']['name'],
                'url' => wa()->getDataUrl($image_path, true, 'bt', true)
            ];

            // Создать квадратную миниатюру
            $img = waImage::factory(wa()->getDataPath($image_path, true, 'bt'), 'Bt');
            $crop_size = $img->width < $img->height ? $img->width : $img->height;
            $image_path = $image_dir.$img_id.'.min.jpg';
            
            $img->crop($crop_size, $crop_size)
                ->mresize(256, 256)
                ->save(wa()->getDataPath($image_path, true, 'bt'), 85);

            $this->response['min_url'] = wa()->getDataUrl($image_path, true, 'bt', true);
        }
        else {

            $imgModel->deleteById($img_id);
        }
    }
}