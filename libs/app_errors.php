<?php
require '../autoload.php';
$Config = new Config();

$datas = array();
$config = $Config->getAll();
$errorsConfig = array_key_exists('app_errors', $config) && is_array($config['app_errors']) ? $config['app_errors'] : array();
$maxItems = array_key_exists('max', $errorsConfig) ? (int)$errorsConfig['max'] : 50;
$maxAgeDays = array_key_exists('max_age_days', $errorsConfig) ? (int)$errorsConfig['max_age_days'] : 2;

if ($maxItems <= 0)
    $maxItems = 50;

$cutoff = $maxAgeDays > 0 ? time() - ($maxAgeDays * 86400) : 0;
$errorsFile = __DIR__.'/../storage/app-errors.log';

if (file_exists($errorsFile) && is_readable($errorsFile))
{
    $lines = file($errorsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (is_array($lines) && count($lines) > 0)
    {
        // Over-fetch before age filtering, then reverse for newest-first
        $lines = array_slice($lines, -($maxItems * 4));

        foreach (array_reverse($lines) as $line)
        {
            $entry = json_decode($line, true);
            if (!is_array($entry))
                continue;

            $entryTime = isset($entry['time']) ? strtotime($entry['time']) : 0;
            if ($cutoff > 0 && ($entryTime === false || $entryTime < $cutoff))
                continue;

            $datas[] = array(
                'time'    => isset($entry['time'])    ? $entry['time']    : 'N.A',
                'source'  => isset($entry['source'])  ? $entry['source']  : 'N.A',
                'message' => isset($entry['message']) ? $entry['message'] : '',
            );

            if (count($datas) >= $maxItems)
                break;
        }
    }
}

echo json_encode($datas);
