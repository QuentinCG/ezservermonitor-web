<?php
require '../autoload.php';
$Config = new Config();

$config = $Config->getAll();
$topConfig = array_key_exists('top_processes', $config) && is_array($config['top_processes']) ? $config['top_processes'] : array();
$maxItems = array_key_exists('max', $topConfig) ? (int)$topConfig['max'] : 5;

if ($maxItems <= 0)
    $maxItems = 5;

function collectProcesses($sortBy, $maxItems, $numCores)
{
    $psCmd = Misc::whichCommand(array('/bin/ps', '/usr/bin/ps', 'ps'), '', false);

    if (empty($psCmd))
        return array();

    $rows = array();
    exec($psCmd.' -eo pid,comm,%cpu,%mem 2>/dev/null', $rows);

    $processes = array();
    for ($i = 1; $i < count($rows); $i++)
    {
        $row = trim($rows[$i]);
        if ($row === '')
            continue;

        $parts = preg_split('/\s+/', $row, 4);
        if (count($parts) < 4)
            continue;

        // Normalize CPU % by core count, cap at 100% per process
        $cpuNormalized = min(100.0, (float)$parts[2] / $numCores);

        $processes[] = array(
            'pid'     => $parts[0],
            'command' => $parts[1],
            'cpu'     => $cpuNormalized,
            'mem'     => (float)$parts[3],
        );
    }

    $col = ($sortBy === 'pcpu') ? 'cpu' : 'mem';
    usort($processes, function($a, $b) use ($col) {
        if ($b[$col] == $a[$col]) return 0;
        return ($b[$col] > $a[$col]) ? 1 : -1;
    });

    $results = array();
    $limit = min($maxItems, count($processes));
    for ($i = 0; $i < $limit; $i++)
    {
        $results[] = array(
            'pid'     => $processes[$i]['pid'],
            'command' => $processes[$i]['command'],
            'cpu'     => number_format($processes[$i]['cpu'], 1),
            'mem'     => number_format($processes[$i]['mem'], 1),
        );
    }

    return $results;
}

$numCores = Misc::getCpuCoresNumber();

$datas = array(
    'cpu' => collectProcesses('pcpu', $maxItems, $numCores),
    'memory' => collectProcesses('pmem', $maxItems, $numCores),
);

echo json_encode($datas);
