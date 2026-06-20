<?php
require 'autoload.php';
$Config = new Config();
$update = $Config->checkUpdate();
$allConfig = $Config->getAll();
$refreshConfig = array_key_exists('refresh', $allConfig) && is_array($allConfig['refresh']) ? $allConfig['refresh'] : array();
$defaultRefresh = (int)$Config->get('esm:auto_refresh');
$assetVersionParts = array(
    filemtime(__DIR__.'/js/esm.js'),
    filemtime(__DIR__.'/web/css/frontend.css'),
    filemtime(__DIR__.'/index.php')
);
$assetVersion = implode('-', $assetVersionParts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>eZ Server Monitor - <?php echo Misc::getHostname(); ?></title>
    <link rel="stylesheet" href="web/css/utilities.css?v=<?php echo $assetVersion; ?>" type="text/css">
    <link rel="stylesheet" href="web/css/frontend.css?v=<?php echo $assetVersion; ?>" type="text/css">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <!--[if IE]>
    <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
    <script src="js/plugins/jquery-2.1.0.min.js?v=<?php echo $assetVersion; ?>" type="text/javascript"></script>
    <script src="js/plugins/jquery.knob.js?v=<?php echo $assetVersion; ?>" type="text/javascript"></script>
    <script src="js/esm.js?v=<?php echo $assetVersion; ?>" type="text/javascript"></script>
    <script>
    $(function(){
        $('.gauge').knob({
            'fontWeight': 'normal',
            'format' : function (value) {
                return value + '%';
            }
        });

        $('a.reload').click(function(e){
            e.preventDefault();
        });

        esm.applyLayout(window.esmSavedLayout || null);
        esm.setupUi();
        esm.getAll();
        esm.setupRefresh(<?php echo json_encode($refreshConfig); ?>, <?php echo $defaultRefresh; ?>);
        esm.initSortable();
    });
    </script>
</head>

<body class="theme-<?php echo $Config->get('esm:theme'); ?>">

<nav role="main">
    <div id="appname">
        <a href="index.php"><span class="icon-gauge"></span>eSM</a>
        <a href="<?php echo $Config->get('esm:website'); ?>"><span class="subtitle">eZ Server Monitor - v<?php echo $Config->get('esm:version'); ?></span></a>
    </div>

    <div id="hostname">
        <?php
        if ($Config->get('esm:custom_title') != '')
            echo $Config->get('esm:custom_title');
        else
            echo Misc::getHostname().' - '.Misc::getLanIP();
        ?>
        (with also: <!-- adminer.org --><b><a href="adminer.php" target=_blank>Adminer</a></b>, <!-- github.com/Arrexel/phpbash --><b><a href="phpbash.min.php" target=_blank>Bash</a></b>, <b><a href="phpinfo.php" target=_blank>Php-Info</a></b>, <b><a href="log/" target=_blank>Logs</a>)
    </div>


    <?php if (!is_null($update)): ?>
        <div id="update">
            <a href="<?php echo $update['fullpath']; ?>">New version available (<?php echo $update['availableVersion']; ?>) ! Click here to download</a>
        </div>
    <?php endif; ?>

    <ul>
        <li><a href="#" id="edit-layout-btn" onclick="esm.toggleEditMode(); return false;" title="Edit display"><i class="fa fa-pencil"></i><span class="esm-edit-label"> Edit</span></a></li>
        <li><a href="#" id="add-column-btn" title="Add empty column"><i class="fa fa-plus"></i></a></li>
        <li><a href="#" class="reload" onclick="esm.reloadBlock('all');"><span class="icon-cycle"></span></a></li>
    </ul>
</nav>


<div id="main-container" class="esm-grid">

    <div class="box" id="esm-system" data-width="half">
        <div class="box-header">
            <h1>System</h1>
            <ul>
                <li><a href="#" class="width-toggle" title="Toggle width"><i class="fa fa-expand"></i></a></li>
                <li><a href="#" class="reload" onclick="esm.reloadBlock('system');"><span class="icon-cycle"></span></a></li>
                <li class="drag-handle" title="Drag to reorder"><i class="fa fa-arrows"></i></li>
            </ul>
        </div>

        <div class="box-content">
            <table class="firstBold">
                <tbody>
                    <tr>
                        <td>Hostname</td>
                        <td id="system-hostname"></td>
                    </tr>
                    <tr>
                        <td>OS</td>
                        <td id="system-os"></td>
                    </tr>
                    <tr>
                        <td>Kernel version</td>
                        <td id="system-kernel"></td>
                    </tr>
                    <tr>
                        <td>Uptime</td>
                        <td id="system-uptime"></td>
                    </tr>
                    <tr>
                        <td>Last boot</td>
                        <td id="system-last_boot"></td>
                    </tr>
                    <tr>
                        <td>Current user(s)</td>
                        <td id="system-current_users"></td>
                    </tr>
                    <tr>
                        <td>Server date & time</td>
                        <td id="system-server_date"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="box" id="esm-load_average" data-width="half">
        <div class="box-header">
            <h1>Load Average</h1>
            <ul>
                <li><a href="#" class="width-toggle" title="Toggle width"><i class="fa fa-expand"></i></a></li>
                <li><a href="#" class="reload" onclick="esm.reloadBlock('load_average');"><span class="icon-cycle"></span></a></li>
                <li class="drag-handle" title="Drag to reorder"><i class="fa fa-arrows"></i></li>
            </ul>
        </div>

        <div class="box-content t-center">
            <div class="f-left w33p">
                <h3>1 min</h3>
                <input type="text" class="gauge" id="load-average_1" value="0" data-height="100" data-width="150" data-min="0" data-max="100" data-readOnly="true" data-fgColor="#BED7EB" data-angleOffset="-90" data-angleArc="180">
            </div>

            <div class="f-left w33p">
                <h3>5 min</h3>
                <input type="text" class="gauge" id="load-average_5" value="0" data-height="100" data-width="150" data-min="0" data-max="100" data-readOnly="true" data-fgColor="#BED7EB" data-angleOffset="-90" data-angleArc="180">
            </div>

            <div class="f-left w33p">
                <h3>15 min</h3>
                <input type="text" class="gauge" id="load-average_15" value="0" data-height="100" data-width="150" data-min="0" data-max="100" data-readOnly="true" data-fgColor="#BED7EB" data-angleOffset="-90" data-angleArc="180">
            </div>

            <div class="cls"></div>
        </div>
    </div>

    <div class="box" id="esm-network" data-width="half">
        <div class="box-header">
            <h1>Network usage</h1>
            <ul>
                <li><a href="#" class="width-toggle" title="Toggle width"><i class="fa fa-expand"></i></a></li>
                <li><a href="#" class="reload" onclick="esm.reloadBlock('network');"><span class="icon-cycle"></span></a></li>
                <li class="drag-handle" title="Drag to reorder"><i class="fa fa-arrows"></i></li>
            </ul>
        </div>

        <div class="box-content">
            <table>
                <thead>
                    <tr>
                        <th class="w15p">Interface</th>
                        <th class="w20p">IP</th>
                        <th>Receive total</th>
                        <th>Receive rate</th>
                        <th>Transmit total</th>
                        <th>Transmit rate</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="box" id="esm-cpu" data-width="half">
        <div class="box-header">
            <h1>CPU</h1>
            <ul>
                <li><a href="#" class="width-toggle" title="Toggle width"><i class="fa fa-expand"></i></a></li>
                <li><a href="#" class="reload" onclick="esm.reloadBlock('cpu');"><span class="icon-cycle"></span></a></li>
                <li class="drag-handle" title="Drag to reorder"><i class="fa fa-arrows"></i></li>
            </ul>
        </div>

        <div class="box-content">
            <table class="firstBold">
                <tbody>
                    <tr>
                        <td>Model</td>
                        <td id="cpu-model"></td>
                    </tr>
                    <tr>
                        <td>Cores</td>
                        <td id="cpu-num_cores"></td>
                    </tr>
                    <tr>
                        <td>Speed</td>
                        <td id="cpu-frequency"></td>
                    </tr>
                    <tr>
                        <td>Cache</td>
                        <td id="cpu-cache"></td>
                    </tr>
                    <tr>
                        <td>Bogomips</td>
                        <td id="cpu-bogomips"></td>
                    </tr>
                    <?php if ($Config->get('cpu:enable_temperature')): ?>
                        <tr>
                            <td>Temperature</td>
                            <td id="cpu-temp"></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="box" id="esm-disk" data-width="full">
        <div class="box-header">
            <h1>Disk usage</h1>
            <ul>
                <li><a href="#" class="width-toggle" title="Toggle width"><i class="fa fa-expand"></i></a></li>
                <li><a href="#" class="reload" onclick="esm.reloadBlock('disk');"><span class="icon-cycle"></span></a></li>
                <li class="drag-handle" title="Drag to reorder"><i class="fa fa-arrows"></i></li>
            </ul>
        </div>

        <div class="box-content">
            <table>
                <thead>
                    <tr>
                        <?php if ($Config->get('disk:show_filesystem')): ?>
                            <th class="w10p filesystem">Filesystem</th>
                        <?php endif; ?>
                        <th class="w20p">Mount</th>
                        <th>Use</th>
                        <th class="w15p">Free</th>
                        <th class="w15p">Used</th>
                        <th class="w15p">Total</th>
                    </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>

    <div class="box" id="esm-memory" data-width="half">
        <div class="box-header">
            <h1>Memory</h1>
            <ul>
                <li><a href="#" class="width-toggle" title="Toggle width"><i class="fa fa-expand"></i></a></li>
                <li><a href="#" class="reload" onclick="esm.reloadBlock('memory');"><span class="icon-cycle"></span></a></li>
                <li class="drag-handle" title="Drag to reorder"><i class="fa fa-arrows"></i></li>
            </ul>
        </div>

        <div class="box-content">
            <table class="firstBold">
                <tbody>
                    <tr>
                        <td class="w20p">Used %</td>
                        <td><div class="progressbar-wrap"><div class="progressbar" style="width: 0%;">0%</div></div></td>
                    </tr>
                    <tr>
                        <td class="w20p">Used</td>
                        <td id="memory-used"></td>
                    </tr>
                    <tr>
                        <td class="w20p">Free</td>
                        <td id="memory-free"></td>
                    </tr>
                    <tr>
                        <td class="w20p">Total</td>
                        <td id="memory-total"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="box" id="esm-swap" data-width="half">
        <div class="box-header">
            <h1>Swap</h1>
            <ul>
                <li><a href="#" class="width-toggle" title="Toggle width"><i class="fa fa-expand"></i></a></li>
                <li><a href="#" class="reload" onclick="esm.reloadBlock('swap');"><span class="icon-cycle"></span></a></li>
                <li class="drag-handle" title="Drag to reorder"><i class="fa fa-arrows"></i></li>
            </ul>
        </div>

        <div class="box-content">
            <table class="firstBold">
                <tbody>
                    <tr>
                        <td class="w20p">Used %</td>
                        <td><div class="progressbar-wrap"><div class="progressbar" style="width: 0%;">0%</div></div></td>
                    </tr>
                    <tr>
                        <td class="w20p">Used</td>
                        <td id="swap-used"></td>
                    </tr>
                    <tr>
                        <td class="w20p">Free</td>
                        <td id="swap-free"></td>
                    </tr>
                    <tr>
                        <td class="w20p">Total</td>
                        <td id="swap-total"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="box" id="esm-services" data-width="half">
        <div class="box-header">
            <h1>Services status</h1>
            <ul>
                <li><a href="#" class="width-toggle" title="Toggle width"><i class="fa fa-expand"></i></a></li>
                <li><a href="#" class="reload" onclick="esm.reloadBlock('services');"><span class="icon-cycle"></span></a></li>
                <li class="drag-handle" title="Drag to reorder"><i class="fa fa-arrows"></i></li>
            </ul>
        </div>

        <div class="box-content">
            <table>
                <thead>
                    <tr>
                        <th class="w15p">Status</th>
                        <th class="w20p">Actions</th>
                        <th>Service</th>
                        <th class="w15p">Port</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="box" id="esm-service_history" data-width="half">
        <div class="box-header">
            <h1>Service action history</h1>
            <ul>
                <li><a href="#" class="width-toggle" title="Toggle width"><i class="fa fa-expand"></i></a></li>
                <li><a href="#" class="reload" onclick="esm.reloadBlock('service_history');"><span class="icon-cycle"></span></a></li>
                <li class="drag-handle" title="Drag to reorder"><i class="fa fa-arrows"></i></li>
            </ul>
        </div>

        <div class="box-content">
            <div class="service-history-filters">
                <input type="text" id="service-history-filter-name" placeholder="Filter by service" />
                <select id="service-history-filter-status">
                    <option value="all">All status</option>
                    <option value="ok">OK</option>
                    <option value="error">Error</option>
                </select>
            </div>
            <table>
                <thead>
                    <tr>
                        <th class="w20p">Time</th>
                        <th class="w15p">Status</th>
                        <th class="w20p">Service</th>
                        <th class="w15p">Action</th>
                        <th>Output</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="box" id="esm-top_processes" data-width="half">
        <div class="box-header">
            <h1>Top processes</h1>
            <ul>
                <li><a href="#" class="width-toggle" title="Toggle width"><i class="fa fa-expand"></i></a></li>
                <li><a href="#" class="reload" onclick="esm.reloadBlock('top_processes');"><span class="icon-cycle"></span></a></li>
                <li class="drag-handle" title="Drag to reorder"><i class="fa fa-arrows"></i></li>
            </ul>
        </div>

        <div class="box-content">
            <div class="top-process-tabs">
                <a href="#" class="active" data-target="cpu">CPU heavy</a>
                <a href="#" data-target="memory">Memory heavy</a>
            </div>

            <div class="top-process-panel" data-panel="cpu">
                <table class="top-processes-cpu">
                    <thead>
                        <tr>
                            <th class="w10p">PID</th>
                            <th>Command</th>
                            <th class="w15p">CPU %</th>
                            <th class="w15p">MEM %</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="top-process-panel hidden" data-panel="memory">
                <table class="top-processes-memory">
                    <thead>
                        <tr>
                            <th class="w10p">PID</th>
                            <th>Command</th>
                            <th class="w15p">CPU %</th>
                            <th class="w15p">MEM %</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </div>
    </div>

    <div class="box" id="esm-ping" data-width="half">
        <div class="box-header">
            <h1>Ping</h1>
            <ul>
                <li><a href="#" class="width-toggle" title="Toggle width"><i class="fa fa-expand"></i></a></li>
                <li><a href="#" class="reload" onclick="esm.reloadBlock('ping');"><span class="icon-cycle"></span></a></li>
                <li class="drag-handle" title="Drag to reorder"><i class="fa fa-arrows"></i></li>
            </ul>
        </div>

        <div class="box-content">
            <table>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="box" id="esm-last_login" data-width="half">
        <div class="box-header">
            <h1>Last login</h1>
            <ul>
                <li><a href="#" class="width-toggle" title="Toggle width"><i class="fa fa-expand"></i></a></li>
                <li><a href="#" class="reload" onclick="esm.reloadBlock('last_login');"><span class="icon-cycle"></span></a></li>
                <li class="drag-handle" title="Drag to reorder"><i class="fa fa-arrows"></i></li>
            </ul>
        </div>

        <div class="box-content">
            <?php if ($Config->get('last_login:enable') == true): ?>
                <table>
                    <tbody></tbody>
                </table>
            <?php else: ?>
                <p>Disabled</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($Config->get('package_management:apt') == true): ?>
        <div class="box" id="esm-apt" data-width="full">
            <div class="box-header">
                <h1>Package Update Status</h1>
                <ul>
                    <li><a href="#" class="width-toggle" title="Toggle width"><i class="fa fa-expand"></i></a></li>
                    <li><a href="#" class="reload" onclick="esm.reloadBlock('apt');"><span class="icon-cycle"></span></a></li>
                    <li class="drag-handle" title="Drag to reorder"><i class="fa fa-arrows"></i></li>
                </ul>
            </div>

            <div class="box-content">
                <table>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="box" id="esm-app_errors" data-width="full">
        <div class="box-header">
            <h1>App issues</h1>
            <ul>
                <li><a href="#" class="width-toggle" title="Toggle width"><i class="fa fa-expand"></i></a></li>
                <li><a href="#" class="reload" onclick="esm.reloadBlock('app_errors');"><span class="icon-cycle"></span></a></li>
                <li class="drag-handle" title="Drag to reorder"><i class="fa fa-arrows"></i></li>
            </ul>
        </div>

        <div class="box-content">
            <table>
                <thead>
                    <tr>
                        <th class="w20p">Time</th>
                        <th class="w20p">Source</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>

<script>
window.esmSavedLayout = <?php
$layoutFile = __DIR__.'/storage/layout.json';
if (file_exists($layoutFile) && is_readable($layoutFile)) {
    $content = file_get_contents($layoutFile);
    echo ($content !== false && trim($content) !== '') ? $content : 'null';
} else {
    echo 'null';
}
?>;
</script>

</body>
</html>
