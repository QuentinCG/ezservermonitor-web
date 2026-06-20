<?php
require '../autoload.php';
$Config = new Config();


$datas = array();

if ($Config->get('last_login:enable'))
{
    $users = array();
    $lastlogCmd = Misc::whichCommand(array('/usr/bin/lastlog', '/bin/lastlog', 'lastlog'), ' --time 365', false);

    if (!empty($lastlogCmd))
    {
        exec($lastlogCmd.' --time 365', $rows, $returnCode);

        if ($returnCode === 0 && count($rows) > 1)
        {
            for ($i = 1; $i < count($rows); $i++)
            {
                $line = trim($rows[$i]);
                if ($line === '')
                    continue;

                // Skip users that never logged in
                if (stripos($line, 'Never logged in') !== false)
                    continue;

                $parts = preg_split('/\s+/', $line);
                if (!is_array($parts) || count($parts) < 5)
                    continue;

                $user = $parts[0];
                $date = implode(' ', array_slice($parts, 3));

                $users[] = array(
                    'user' => $user,
                    'date' => trim($date),
                );
            }
        }
    }

    // Fallback for distributions where lastlog output format/path differs.
    if (count($users) === 0)
    {
        $max = $Config->get('last_login:max');
        $lastCmd = Misc::whichCommand(array('/usr/bin/last', '/bin/last', 'last'), '', false);

        if (!empty($lastCmd))
        {
            $rows = array();
            exec($lastCmd.' -n '.(int)$max.' 2>/dev/null', $rows, $returnCode);

            if ($returnCode === 0)
            {
                foreach ($rows as $line)
                {
                    $line = trim($line);
                    if ($line === '' || stripos($line, 'wtmp begins') === 0)
                        continue;

                    $parts = preg_split('/\s+/', $line, 4);
                    if (!is_array($parts) || count($parts) < 4)
                        continue;

                    $users[] = array(
                        'user' => $parts[0],
                        'date' => $parts[3],
                    );
                }
            }
        }
    }

    if (count($users) === 0)
    {
        $datas[] = array(
            'user' => 'N.A',
            'date' => 'N.A',
        );
    }
    else
    {
        $max = $Config->get('last_login:max');

        for ($i = 0; $i < count($users) && $i < $max; $i++)
        {
            $datas[] = $users[$i];
        }
    }
}

echo json_encode($datas);
