<?php
require '../autoload.php';
$Config = new Config();

$datas = array();
$config = $Config->getAll();
$historyConfig = array_key_exists('service_history', $config) && is_array($config['service_history']) ? $config['service_history'] : array();
$maxItems = array_key_exists('max', $historyConfig) ? (int)$historyConfig['max'] : 20;
$maxAgeDays = array_key_exists('max_age_days', $historyConfig) ? (int)$historyConfig['max_age_days'] : 2;

if ($maxItems <= 0)
    $maxItems = 20;

$cutoff = $maxAgeDays > 0 ? time() - ($maxAgeDays * 86400) : 0;

$historyFile = __DIR__.'/../storage/service-actions.log';

if (file_exists($historyFile) && is_readable($historyFile))
{
    $lines = file($historyFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (is_array($lines) && count($lines) > 0)
    {
        $lines = array_slice($lines, -$maxItems);

        foreach (array_reverse($lines) as $line)
        {
            $entry = json_decode($line, true);
            if (is_array($entry))
            {
                $entryTime = isset($entry['time']) ? strtotime($entry['time']) : 0;
                if ($cutoff > 0 && ($entryTime === false || $entryTime < $cutoff))
                    continue;

                $datas[] = array(
                    'time' => array_key_exists('time', $entry) ? $entry['time'] : 'N.A',
                    'service' => array_key_exists('service', $entry) ? $entry['service'] : 'N.A',
                    'action' => array_key_exists('action', $entry) ? $entry['action'] : 'N.A',
                    'status' => array_key_exists('status', $entry) ? $entry['status'] : 'N.A',
                    'output' => array_key_exists('output', $entry) ? $entry['output'] : '',
                    'command' => array_key_exists('command', $entry) ? $entry['command'] : '',
                    'return_code' => array_key_exists('return_code', $entry) ? $entry['return_code'] : null,
                );
            }
        }
    }
}

echo json_encode($datas);
