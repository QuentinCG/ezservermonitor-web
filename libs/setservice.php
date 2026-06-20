<?php

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id']))
{
    echo json_encode(array('status' => 'error', 'message' => 'Invalid service id'));
    exit;
}

$id = (int)$_GET['id'];
$requestedAction = isset($_GET['action']) ? strtolower(trim($_GET['action'])) : 'toggle';
$allowedActions = array('toggle', 'start', 'stop', 'reload');

if (!in_array($requestedAction, $allowedActions))
{
    echo json_encode(array('status' => 'error', 'message' => 'Invalid action'));
    exit;
}

require '../autoload.php';
$Config = new Config();
$config = $Config->getAll();
$historyConfig = array_key_exists('service_history', $config) && is_array($config['service_history']) ? $config['service_history'] : array();

$services = $Config->get('services:list');
if (!is_array($services) || !isset($services[$id]))
{
    echo json_encode(array('status' => 'error', 'message' => 'Service not found'));
    exit;
}

$service = $services[$id];
$serviceName = isset($service['name']) ? $service['name'] : 'Service #'.$id;

$available_protocols = array('tcp', 'udp');
$host     = $service['host'];
$port     = $service['port'];
$start    = isset($service['start']) ? trim($service['start']) : '';
$stop     = isset($service['stop']) ? trim($service['stop']) : '';
$reload   = isset($service['reload']) ? trim($service['reload']) : '';
$protocol = isset($service['protocol']) && in_array($service['protocol'], $available_protocols) ? $service['protocol'] : 'tcp';

$isOnline = Misc::scanPort($host, $port, $protocol);
$action = $requestedAction;

if ($action === 'toggle')
{
    if ($isOnline)
    {
        if ($stop !== '')
            $action = 'stop';
        elseif ($reload !== '')
            $action = 'reload';
    }
    else
    {
        $action = 'start';
    }
}

$commandMap = array(
    'start' => $start,
    'stop' => $stop,
    'reload' => $reload,
);

$command = isset($commandMap[$action]) ? $commandMap[$action] : '';

if ($command === '')
{
    echo json_encode(array(
        'status' => 'error',
        'message' => 'Action not configured for this service',
        'action' => $action,
    ));
    exit;
}

$output_exec = array();
$returnCode = 0;
exec('sudo '.$command.' 2>&1', $output_exec, $returnCode);

$outputText = implode("\n", $output_exec);
$historyDir = __DIR__.'/../storage';
$historyFile = $historyDir.'/service-actions.log';
$maxHistoryLines = array_key_exists('max_file_lines', $historyConfig) ? (int)$historyConfig['max_file_lines'] : 1000;

if ($maxHistoryLines <= 0)
    $maxHistoryLines = 1000;

if (!file_exists($historyDir))
    @mkdir($historyDir, 0755, true);

if (file_exists($historyFile) && is_readable($historyFile))
{
    $existingLines = @file($historyFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($existingLines) && count($existingLines) >= $maxHistoryLines)
    {
        $keptLines = array_slice($existingLines, -($maxHistoryLines - 1));
        @file_put_contents($historyFile, implode(PHP_EOL, $keptLines).PHP_EOL, LOCK_EX);
    }
}

$historyEntry = array(
    'time' => date('Y-m-d H:i:s'),
    'service' => $serviceName,
    'action' => $action,
    'status' => $returnCode === 0 ? 'ok' : 'error',
    'command' => $command,
    'return_code' => $returnCode,
    'output' => $outputText,
);

@file_put_contents($historyFile, json_encode($historyEntry).PHP_EOL, FILE_APPEND | LOCK_EX);

if ($returnCode !== 0)
    Misc::logAppError('service['.$serviceName.']', 'Action "'.$action.'" failed (code '.$returnCode.'): '.trim($outputText));

echo json_encode(array(
    'status' => $returnCode === 0 ? 'ok' : 'error',
    'action' => $action,
    'command' => $command,
    'return_code' => $returnCode,
    'output' => $outputText,
));


