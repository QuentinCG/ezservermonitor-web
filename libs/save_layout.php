<?php
header('Content-Type: application/json');
require '../autoload.php';

$input = file_get_contents('php://input');
$layout = json_decode($input, true);

if (!is_array($layout))
{
    echo json_encode(array('status' => 'error', 'message' => 'Invalid data'));
    exit;
}

$clean        = array();
$isColFormat = !empty($layout) && isset($layout[0]['cards']);

if ($isColFormat)
{
    // New column format: [{colWidth, cards:[id,...]}, {type:"break"}, ...]
    foreach ($layout as $col)
    {
        // Line-break entry
        if (is_array($col) && isset($col['type']) && $col['type'] === 'break')
        {
            $clean[] = array('type' => 'break');
            continue;
        }

        if (!is_array($col) || !isset($col['colWidth']) || !isset($col['cards']) || !is_array($col['cards']))
            continue;

        $colWidth = in_array($col['colWidth'], array('half', 'third', 'quarter', 'full')) ? $col['colWidth'] : 'half';
        $cards    = array();

        foreach ($col['cards'] as $cardId)
        {
            $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$cardId);
            if ($id !== '') $cards[] = $id;
        }

        if (!empty($cards))
            $clean[] = array('colWidth' => $colWidth, 'cards' => $cards);
    }
}
else
{
    // Old flat format: [{id, width}]
    foreach ($layout as $item)
    {
        if (!is_array($item) || !isset($item['id']) || !isset($item['width']))
            continue;

        $id    = preg_replace('/[^a-zA-Z0-9_-]/', '', $item['id']);
        $width = in_array($item['width'], array('half', 'third', 'quarter', 'full')) ? $item['width'] : 'half';

        if ($id !== '')
            $clean[] = array('id' => $id, 'width' => $width);
    }
}

$storageDir = __DIR__.'/../storage';
if (!file_exists($storageDir))
    @mkdir($storageDir, 0755, true);

$ok = @file_put_contents($storageDir.'/layout.json', json_encode($clean), LOCK_EX);
echo json_encode(array('status' => $ok !== false ? 'ok' : 'error'));
