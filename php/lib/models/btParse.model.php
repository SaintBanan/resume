<?php

class btParseModel extends waModel
{
    protected $table = 'bt_parse';

    public function initLog($parser_id, $user_id, $datetime)
    {
        return $this->insert(['vendor_id' => $parser_id, 'user_id' => $user_id, 'start_datetime' => $datetime]);
    }

    public function getLastLog($parser_id)
    {
        $data = $this->query(
            "SELECT * FROM {$this->table} WHERE vendor_id = i:0 ORDER BY `start_datetime` DESC LIMIT 1", [$parser_id]
        )->fetchAssoc();

        if (!$data) return null;

        if ($data['end_datetime']) {
            $data['end_datetime'] = btHelper::formatDatetime($data['end_datetime'], true);
        }
        
        $data['start_datetime'] = btHelper::formatDatetime($data['start_datetime'], true);

        if ($data['user_id'] > 0) {
            $data['user'] = $this->query('SELECT `name`, `login` FROM wa_contact WHERE id = i:0', [$data['user_id']])->fetchAssoc();
            $data['user'] = $data['user'] ? $data['user'] : null;
        }
        else {
            $data['user'] = ['name' => 'CRON'];
        }

        unset($data['user_id']);

        return $data;
    }
}