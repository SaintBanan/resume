<?php

/**
 * Сохранить шаблон задачи.
 * @param int $id - ID шаблона
 * @param array $data - атрибуты шаблона
 * @param array $details - детали шаблона
 * @param array $remove_files - ID загруженных файлов для удаления
 * @param waRequestFile[] $files - прикрепленные файлы
 * @return array|null - созданный шаблон
 */
class crmCtasksPluginTempSaveController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::post('id', 0, 'int');
        $data = json_decode(waRequest::post('data', '{}'), true);
        $details = json_decode(waRequest::post('details', '{}'), true);
        $is_csrf_passed = crmCtasksPluginHelper::isCSRF(waRequest::post('_csrf', ''), $_COOKIE['_csrf']);

        $this->response = null;

        if (!$data || !$is_csrf_passed) return;

        $tempModel = new crmCtasksTempModel();
        $detailsModel = new crmCtasksTempDetailsModel();
        $fileModel = new crmCtasksTempFilesModel();

        $data['name'] = crmCtasksPluginHelper::firstCharUpper($data['name']);

        if ($id) {

            $tempModel->updateById($id, $data);
            $detailsModel->updateByField('temp_id', $id, $details);

            foreach(json_decode(waRequest::post('remove_files', '{}'), true) as $file_id) {

                $fileModel->remove($file_id);
            }
        }
        else {

            $data['create_user_id'] = wa()->getUser()->getId();
            $data['create_datetime'] = date('Y-m-d H:i:s');
            $id = $tempModel->insert($data);
            $details['temp_id'] = $id;
            $detailsModel->insert($details);
        }

        foreach(waRequest::file('files') as $file) {

            $fileModel->save($id, $file);
        }

        $this->response = $tempModel->getTemp($id, wa()->appExists('shop'));
    }
}
