<?php

// Проверка номера телефона, введенного пользователем
// В случае успеха возвращает ID пользователя и временный ID авторизующегося устройства 
class btAuthCheckPhoneMethod extends btApiAuth
{
    protected $method = 'POST';

    public function execute()
    {
        try {

            $this->response = ['status' => false, 'message' => 'Отказано в доступе'];

            $post = json_decode(file_get_contents('php://input'), true);
            $phone = substr(ifset($post['phone'], ''), -10);

            if (strlen($phone) < 10 || preg_match('/[^\d]/', $phone)) return;

            $phone = btHelper::waModel()->escape($phone);

            /* 
                Получить ID пользователя по номеру телефона, если:
                1) указан доступ к мобильному приложению;
                2) или есть полный доступ к приложению БТ;
                3) или является админом.
            */
            $user_id = (int) btHelper::query(
                "SELECT wc.id FROM wa_contact wc
                    JOIN wa_contact_data cd ON wc.id = cd.contact_id
                    JOIN wa_contact_rights cr ON wc.id = -cr.group_id
                WHERE wc.is_user = 1 AND cd.field = 'phone' AND cd.value LIKE '%$phone' AND
                    (cr.app_id = 'bt' AND cr.name = 'mobile' AND cr.value = 1
                        OR cr.name = 'backend' AND (cr.app_id = 'bt' AND cr.value = 2 OR cr.app_id = 'webasyst' AND cr.value = 1)
                    )"
            )->fetchField();

            if (!$user_id) return;

            $authDeviceModel = new btAuthDeviceModel();
            $user_auth_data = $authDeviceModel->getByField('user_id', $user_id);
            $next_req_wait_time = $user_auth_data['next_request_allowed_at'] - time();

            if ($next_req_wait_time > 0) {

                $this->response['message'] = "Повторите попытку через $next_req_wait_time сек.";
                return;
            }

            if ($user_auth_data) {
                $authDeviceModel->deleteById($user_auth_data['id']);
            }

            $code_challenge = $this->generateCodeChallenge($phone);
            $device_id = $authDeviceModel->insert(['user_id' => $user_id, 'code_challenge' => $code_challenge]);

            // Отправить код подтверждения через сервер авторизации Webasyst
            $result = $this->req($device_id, $phone, $code_challenge);

            if (isset($result['code_expires_at']) && isset($result['next_request_allowed_at'])) {

                // Сохранить время действия кода
                $authDeviceModel->updateById($device_id, [
                    'code_expires_at' => $result['code_expires_at'],
                    'next_request_allowed_at' => $result['next_request_allowed_at']
                ]);

                $this->response = [
                    'status' => true,
                    'user_id' => $user_id,
                    'device_id' => $device_id
                ];
            }
            else {

                $authDeviceModel->deleteById($device_id);
                $this->response['message'] = 'Нет связи с сервером авторизации';
            }
        } catch (Throwable $th) {

            btHelper::log(get_class($this).': '.$th->getMessage());
        }
    }

    // Генерация одноразового пароля
    private function generateCodeChallenge($phone)
    {
        $str = str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.-_$phone");
        $len = strlen($str);
        $res = '';

        for ($i = 0; $i < 45; $i++) {
            $res .= $str[random_int(0, $len - 1)];
        }

        return $res;
    }

    private function req($device_id, $phone, $code_challenge)
    {
        $data = [
            'client_id' => $this->getDevelopId(),
            'device_id' => 'bt'.$device_id,
            'scope' => 'token:bt',
            'code_challenge' => $code_challenge,
            'code_challenge_method' => 'plain',
            'phone' => '+7'.$phone
        ];

        return $this->request($data, 'code');
    }
}
