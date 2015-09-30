<?php

namespace DpTools\ImportWriter;

class Writer
{
    /**
     * @var string
     */
    private $data_path;

    /**
     * @var array
     */
    private $id_batch_map = array();

    /**
     * @var int
     */
    private $count = 0;

    /**
     * @var int
     */
    private $batch = 0;

    /**
     * @var array
     */
    private $done_ids = array();

    /**
     * @var bool
     */
    private $batched_mode = false;

    /**
     * @var int
     */
    private $batch_size = 1000;

    /**
     * @var array
     */
    private $data_keys = array(
        '@global' => array(
            'remap' => array(
                'id' => 'oid', 'user' => 'person'
            )
        ),

        'ticket' => array(
            'allowed' => array(
                'oid' => 1, 'ref' => 1, 'department' => 1, 'person' => 1, 'agent' => 1,
                'agent_team' => 1, 'status' => 1, 'date_created' => 1, 'date_resolved' => 1,
                'date_archived' => 1, 'subject' => 1, 'language' => 1, 'category' => 1,
                'workflow' => 1, 'product' => 1, 'organization' => 1, 'is_hold' => 1,
                'priority' => 1,
                'urgency' => 1, 'participants' => 1,  'labels' => 1, 'messages' => 1,
                'custom_fields' => 1, 'log_message' => 1,
            ),
            'process' => array(
                'messages' => 'message',
            )
        ),

        'message' => array(
            'super_type' => 'ticket',
            'super_id' => 'ticket_id',
            'allowed' => array(
                'oid' => 1, 'person' => 1, 'date_created' => 1, 'message_text' => 1, 'message_html' => 1,
                'is_note' => 1, 'attachments' => 1,
            ),
            'process' => array(
                'attachments' => 'attachment',
            ),
            'remap' => array(
                'message' => 'message_text', 'reply' => 'message_text',
                'html' => 'message_html', 'email' => 'message_text',
                'ticket' => 'ticket_id',
            )
        ),

        'attachment' => array(
            'allowed' => array(
                'oid' => 1, 'person' => 1, 'blob_data' => 1, 'blob_url' => 1, 'blob_path' => 1, 'file_name' => 1,
                'content_type' => 1, 'is_inline' => 1,
            ),
            'remap' => array(
                'data' => 'blob_data', 'file' => 'file_name', 'path' => 'blob_path',  'name' => 'file_name',
                'mimetype' => 'content_type', 'type' => 'content_type'
            )
        ),

        'person' => array(
            'allowed' => array(
                'oid' => 1, 'is_agent' => 1, 'is_user' => 1, 'is_admin' => 1, 'first_name' => 1,
                'last_name' => 1, 'name' => 1, 'timezone' => 1, 'date_created' => 1,
                'language' => 1, 'organization' => 1, 'organization_position' => 1,
                'emails' => 1, 'labels' => 1, 'user_groups' => 1, 'contact_data' => 1, 'custom_fields' => 1,
            )
        ),

        'organization' => array(
            'allowed' => array(
                'oid' => 1, 'name' => 1, 'contact_data' => 1, 'custom_fields' => 1, 'labels' => 1
            )
        )
    );

    /**
     * @param $data_path
     * @throws \Exception
     */
    public function __construct($data_path)
    {
        $this->data_path = rtrim($data_path, '/\\');

        if (!is_dir($this->data_path) || !is_writable($this->data_path)) {
            throw new \Exception("The data path does not exist or is not writable: " . $this->data_path);
        }
    }

    /**
     * Enable batched mode.
     */
    public function enableBatchedMode()
    {
        $this->batched_mode = true;
    }

    /**
     * @param $type
     * @param array $data
     * @param array $super_type
     * @param array $extracted_data
     * @return array
     */
    private function processDataArray($type, array $data, array &$super_type = null, array &$extracted_data = null)
    {
        $real_data = array();

        if (!$super_type) {
            $super_type = array();
        }

        if (!$extracted_data) {
            $extracted_data = array();
        }

        foreach ($data as $k => $v) {
            if (isset($this->data_keys[$type]['remap'][$k])) {
                $k = $this->data_keys[$type]['remap'][$k];
            } elseif (isset($this->data_keys['@global']['remap'][$k])) {
                $k = $this->data_keys['@global']['remap'][$k];
            }

            if (isset($this->data_keys[$type]['super_id']) && $k == $this->data_keys[$type]['super_id']) {
                $super_type = array(
                    'id'   => $v,
                    'type' => $this->data_keys[$type]['super_type'],
                );
                continue;
            }

            if (!isset($this->data_keys[$type]['allowed'][$k])) {
                echo "[$type] Unknown key: $k\n";
                echo "Data array:\n";
                print_r($v);
                echo "\n";
                exit(1);
            }

            if (isset($this->data_keys[$type]['process'][$k])) {
                $proc_type = $this->data_keys[$type]['process'][$k];
                $new_v = array();
                foreach ($v as $proc_v) {
                    $new_v[] = $this->processDataArray($proc_type, $proc_v);
                }
                $v = $new_v;
            }

            if (is_array($v)) {
                if ($k === 'person' || $k === 'agent') {
                    if ($k === 'agent') {
                        $v['is_agent'] = true;
                    }
                    $v = $this->processPerson($v);

                    if (!isset($extracted_data['person'])) {
                        $extracted_data['person'] = array();
                    }
                    $extracted_data['person'][] = $v;
                    continue;
                }
            } else {
                if ($v && ($k === 'person' || $k === 'agent')) {
                    if (!strpos($v, '@')) {
                        $v = $v . '@generated.example.com';
                    }
                }
            }

            $real_data[$k] = $v;
        }

        $m = 'process' . ucfirst($type);
        if (method_exists($this, $m)) {
            $real_data = $this->$m($real_data);
        }

        return $real_data;
    }

    private function processTicket(array $data)
    {
        if (empty($data['oid'])) {
            echo "Tickets require an `oid` property.\n";
            echo "Data array:\n";
            print_r($data);
            echo "\n";
            exit(1);
        }

        return $data;
    }

    private function processPerson(array $data)
    {
        if (empty($data['emails']) && !empty($data['email'])) {
            $data['emails'] = array($data['email']);
            unset($data['email']);
        }
        if (empty($data['emails'])) {
            echo "People require an `email` property.\n";
            echo "Data array:\n";
            print_r($data);
            echo "\n";
            exit(1);
        }
        foreach ($data['emails'] as &$eml) {
            if (!strpos($eml, '@')) {
                $eml = $eml . '@generated.example.com';
            }
        }
        if (empty($data['oid'])) {
            $data['oid'] = $data['emails'][0];
        }

        return $data;
    }

    private function processOrganization(array $data)
    {
        if (empty($data['labels'])) {
            $data['labels'] = array();
        }

        return $data;
    }

    /**
     * @param array $grouped_items
     */
    public function writeArray(array $grouped_items)
    {
        foreach ($grouped_items as $type => $items) {
            foreach ($items as $item) {
                $this->$type($item);
            }
        }
    }

    /**
     * @param array $data
     */
    public function ticket(array $data)
    {
        $extracted = $super = array();
        $data = $this->processDataArray('ticket', $data, $super, $extracted);
        $this->writeFile('tickets', $data['oid'], $data);
        $this->writeArray($extracted);
    }

    /**
     * @param array $data
     * @param bool|false $skip_if_done
     */
    public function person(array $data, $skip_if_done = false)
    {
        $extracted = $super = array();
        $data = $this->processDataArray('person', $data, $super, $extracted);

        if ($skip_if_done && isset($this->done_ids['people'][$data['oid']])) {
            return;
        }

        $this->writeFile('people', $data['oid'], $data);
        $this->writeArray($extracted);
    }

    /**
     * @param array $data
     * @param bool|false $skip_if_done
     */
    public function organization(array $data, $skip_if_done = false)
    {
        $extracted = $super = array();
        $data = $this->processDataArray('organization', $data, $super, $extracted);

        if ($skip_if_done && isset($this->done_ids['organizations'][$data['oid']])) {
            return;
        }

        $this->writeFile('organizations', $data['oid'], $data);
        $this->writeArray($extracted);
    }

    /**
     * @param array $data
     */
    public function message(array $data)
    {
        $extracted = $super = array();
        $data = $this->processDataArray('message', $data, $super, $extracted);
        if (!empty($super['id'])) {
            echo "Message data must include `ticket_id`\n";
            print_r($data);
            exit(1);
        }
        $this->writeFile('ticket', $data['id'], array('oid' => $super['id'], 'messages' => array($data)));
        $this->writeArray($extracted);
    }

    /**
     * Write data to a file. Merges contents if the file exists already.
     *
     * @param string $type
     * @param string $id
     * @param array $data
     * @return int
     */
    private function writeFile($type, $id, array $data)
    {
        $full_path = $this->getFilePath($type, $id);
        $dir = dirname($full_path);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                echo "Failed to create directory: $dir\n";
                exit(1);
            }
        }
        $exist_data = null;

        if (file_exists($full_path)) {
            $exist_data = @json_decode(file_get_contents($full_path), true);
        } else {
            $this->count++;
        }

        if ($exist_data) {
            $data = self::arrayMergeDeep($exist_data, $data);
            foreach (array('emails', 'labels') as $f) {
                if (!empty($data[$f])) {
                    $data[$f] = array_unique($data[$f]);
                }
            }
        }

        $v = $this->encodeData($data);
        if (!$v) {
            print_r($data);
            var_dump(json_encode($data));
            exit;
        }
        $res = file_put_contents($full_path, $v);

        if (!isset($this->done_ids[$type])) {
            $this->done_ids[$type] = array();
        }

        $this->done_ids[$type][$id] = true;

        if ($res === false) {
            echo "Failed to write: $full_path\n";
            exit(1);
        }

        return $res;
    }

    /**
     * @param array $array1
     * @param array $array2
     * @return array
     */
    private static function arrayMergeDeep(array $array1, array $array2)
    {
        $merged = $array1;

        if (is_array($array2)) {
            foreach ($array2 as $key => $val) {
                if (is_array($array2[$key])) {
                    $merged[$key] = is_array($merged[$key]) ? self::arrayMergeDeep($merged[$key], $array2[$key]) : $array2[$key];
                } else {
                    $merged[$key] = $val;
                }
            }
        }

        return $merged;
    }

    /**
     * Writes data to a file only if the file doesn't exist yet.
     *
     * @param string $type
     * @param string $id
     * @param array $data
     * @return bool|int
     */
    private function initFile($type, $id, array $data)
    {
        $full_path = $this->getFilePath($type, $id);
        if (file_exists($full_path)) {
            return false;
        }

        return $this->writeFile($type, $id, $data);
    }

    /**
     * @param string $type
     * @param string $id
     * @return string
     */
    private function getFilePath($type, $id)
    {
        switch ($type) {
            case 'tickets':          $prefix = 'ticket'; break;
            case 'people':           $prefix = 'person'; break;
            case 'articles':         $prefix = 'article'; break;
            case 'downloads':        $prefix = 'download'; break;
            case 'organizations':    $prefix = 'organization'; break;
            case 'news':             $prefix = 'news'; break;
            case 'feedback':         $prefix = 'feedback'; break;
        }
        $id_enc = ctype_digit($id) ? $id : md5($id);

        if (isset($this->id_batch_map[$type][$id])) {
            $batch = $this->id_batch_map[$type][$id];
        } else {
            if ($this->batched_mode) {
                if ($this->count % $this->batch_size === 0) {
                    $this->batch++;
                }
            }
            $batch = $this->batch;
            if (!isset($this->id_batch_map[$type])) {
                $this->id_batch_map[$type] = array();
            }
            $this->id_batch_map[$type][$id] = $batch;
        }

        return $this->data_path . DIRECTORY_SEPARATOR . $batch . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $prefix . $id_enc . '.json';
    }

    /**
     * @return int
     */
    public function getBatchSize()
    {
        return $this->batch_size;
    }

    /**
     * @param array $data
     * @return string
     */
    private function encodeData(array $data)
    {
        if (defined('JSON_PRETTY_PRINT')) {
            return json_encode($data, \JSON_PRETTY_PRINT);
        }

        return json_encode($data);
    }
}