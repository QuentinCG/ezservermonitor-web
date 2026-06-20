var esm = {};

esm.networkSnapshot = {
    timestamp: null,
    interfaces: {}
};

esm.serviceActionsInProgress = {};
esm.refreshHandles = {};
esm.serviceHistoryData = [];
esm.serviceHistoryFilters = {
    name: '',
    status: 'all'
};


esm.getSystem = function() {

    var module = 'system';

    esm.reloadBlock_spin(module);

    $.get('libs/'+module+'.php', function(data) {

        var $box = $('.box#esm-'+module+' .box-content tbody');

        esm.insertDatas($box, module, data);

        esm.reloadBlock_spin(module);

    }, 'json');

}


esm.getLoad_average = function() {

    var module = 'load_average';

    esm.reloadBlock_spin(module);

    $.get('libs/'+module+'.php', function(data) {

        var $box = $('.box#esm-'+module+' .box-content');

        esm.reconfigureGauge($('input#load-average_1', $box), data[0]);
        esm.reconfigureGauge($('input#load-average_5', $box), data[1]);
        esm.reconfigureGauge($('input#load-average_15', $box), data[2]);

        esm.reloadBlock_spin(module);

    }, 'json');

}


esm.getCpu = function() {

    var module = 'cpu';

    esm.reloadBlock_spin(module);

    $.get('libs/'+module+'.php', function(data) {

        var $box = $('.box#esm-'+module+' .box-content tbody');

        esm.insertDatas($box, module, data);

        esm.reloadBlock_spin(module);

    }, 'json');

}


esm.getMemory = function() {

    var module = 'memory';

    esm.reloadBlock_spin(module);

    $.get('libs/'+module+'.php', function(data) {

        var $box = $('.box#esm-'+module+' .box-content tbody');

        esm.insertDatas($box, module, data);

        esm.reloadBlock_spin(module);

        // Percent bar
        var $progress = $('.progressbar', $box);

        $progress
            .css('width', data.percent_used+'%')
            .html(data.percent_used+'%')
            .removeClass('green orange red');

        if (data.percent_used <= 50)
            $progress.addClass('green');
        else if (data.percent_used <= 75)
            $progress.addClass('orange');
        else
            $progress.addClass('red');

    }, 'json');

}


esm.getSwap = function() {

    var module = 'swap';

    esm.reloadBlock_spin(module);

    $.get('libs/'+module+'.php', function(data) {

        var $box = $('.box#esm-'+module+' .box-content tbody');

        esm.insertDatas($box, module, data);

        // Percent bar
        var $progress = $('.progressbar', $box);

        $progress
            .css('width', data.percent_used+'%')
            .html(data.percent_used+'%')
            .removeClass('green orange red');

        if (data.percent_used <= 50)
            $progress.addClass('green');
        else if (data.percent_used <= 75)
            $progress.addClass('orange');
        else
            $progress.addClass('red');

        esm.reloadBlock_spin(module);

    }, 'json');

}


esm.getDisk = function() {

    var module = 'disk';

    esm.reloadBlock_spin(module);

    $.get('libs/'+module+'.php', function(data) {

        var $box = $('.box#esm-'+module+' .box-content tbody');
        $box.empty();

        for (var line in data)
        {
            var bar_class = '';

            if (data[line].percent_used <= 50)
                bar_class = 'green';
            else if (data[line].percent_used <= 75)
                bar_class = 'orange';
            else
                bar_class = 'red';

            var html = '';
            html += '<tr>';

            if (typeof data[line].filesystem != 'undefined')
                html += '<td class="filesystem">'+data[line].filesystem+'</td>';

            html += '<td>'+data[line].mount+'</td>';
            html += '<td><div class="progressbar-wrap"><div class="progressbar '+bar_class+'" style="width: '+data[line].percent_used+'%;">'+data[line].percent_used+'%</div></div></td>';
            html += '<td class="t-center">'+data[line].free+'</td>';
            html += '<td class="t-center">'+data[line].used+'</td>';
            html += '<td class="t-center">'+data[line].total+'</td>';
            html += '</tr>';

            $box.append(html);
        }

        esm.reloadBlock_spin(module);

    }, 'json');

}


esm.getLast_login = function() {

    var module = 'last_login';

    esm.reloadBlock_spin(module);

    $.get('libs/'+module+'.php', function(data) {

        var $box = $('.box#esm-'+module+' .box-content tbody');
        $box.empty();

        if (!data || data.length === 0)
        {
            $box.append('<tr><td colspan="2" class="t-center">No login data</td></tr>');
            esm.reloadBlock_spin(module);
            return;
        }

        for (var line in data)
        {
            var html = '';
            html += '<tr>';
            html += '<td>'+data[line].user+'</td>';
            html += '<td class="w50p">'+data[line].date+'</td>';
            html += '</tr>';

            $box.append(html);
        }

        esm.reloadBlock_spin(module);

    }, 'json').fail(function() {
        var $box = $('.box#esm-'+module+' .box-content tbody');
        $box.empty();
        $box.append('<tr><td colspan="2" class="t-center">Unable to load last login data</td></tr>');
        esm.reloadBlock_spin(module);
    });

}


esm.getNetwork = function() {

    var module = 'network';

    esm.reloadBlock_spin(module);

    $.get('libs/'+module+'.php', function(data) {

        var $box = $('.box#esm-'+module+' .box-content tbody');
        $box.empty();

        var now = new Date().getTime();
        var previousSnapshot = esm.networkSnapshot;
        var elapsedSeconds = previousSnapshot.timestamp ? (now - previousSnapshot.timestamp) / 1000 : null;
        var currentInterfaces = {};

        for (var line in data)
        {
            var rxBytes = parseFloat(data[line].receive_bytes);
            var txBytes = parseFloat(data[line].transmit_bytes);
            var rxRateDisplay = 'N.A';
            var txRateDisplay = 'N.A';

            var receiveTotalDisplay = (typeof data[line].receive !== 'undefined' && data[line].receive !== null && data[line].receive !== '')
                ? data[line].receive
                : (!isNaN(rxBytes) ? esm.getHumanSize(rxBytes) : 'N.A');
            var transmitTotalDisplay = (typeof data[line].transmit !== 'undefined' && data[line].transmit !== null && data[line].transmit !== '')
                ? data[line].transmit
                : (!isNaN(txBytes) ? esm.getHumanSize(txBytes) : 'N.A');

            if (!isNaN(rxBytes) && !isNaN(txBytes))
            {
                currentInterfaces[data[line].interface] = {
                    receive: rxBytes,
                    transmit: txBytes
                };

                if (elapsedSeconds && elapsedSeconds > 0 && previousSnapshot.interfaces[data[line].interface])
                {
                    var prevRx = previousSnapshot.interfaces[data[line].interface].receive;
                    var prevTx = previousSnapshot.interfaces[data[line].interface].transmit;
                    var rxDelta = rxBytes - prevRx;
                    var txDelta = txBytes - prevTx;

                    if (rxDelta >= 0)
                        rxRateDisplay = esm.getHumanRate(rxDelta / elapsedSeconds);

                    if (txDelta >= 0)
                        txRateDisplay = esm.getHumanRate(txDelta / elapsedSeconds);
                }
            }

            var html = '';
            html += '<tr>';
            html += '<td>'+data[line].interface+'</td>';
            html += '<td>'+data[line].ip+'</td>';
            html += '<td class="t-center">'+receiveTotalDisplay+'</td>';
            html += '<td class="t-center">'+rxRateDisplay+'</td>';
            html += '<td class="t-center">'+transmitTotalDisplay+'</td>';
            html += '<td class="t-center">'+txRateDisplay+'</td>';
            html += '</tr>';

            $box.append(html);
        }

        esm.networkSnapshot = {
            timestamp: now,
            interfaces: currentInterfaces
        };

        esm.reloadBlock_spin(module);

    }, 'json');

}


esm.getPing = function() {

    var module = 'ping';

    esm.reloadBlock_spin(module);

    $.get('libs/'+module+'.php', function(data) {

        var $box = $('.box#esm-'+module+' .box-content tbody');
        $box.empty();

        for (var line in data)
        {
            var html = '';
            html += '<tr>';
            html += '<td><a href="http://'+data[line].host+'" target="_blank">'+data[line].host+'</a></td>';

            html += '<td class="w15p"><span class="label ';
            if (data[line].ping.indexOf('Inf') > -1) {
              html += 'error">OFFLINE';
            }
            else {
              html += 'success">'+data[line].ping+' ms';
            }
            html += '</span></td>'

            html += '</tr>';

            $box.append(html);
        }

        esm.reloadBlock_spin(module);

    }, 'json');

}


esm.getServices = function() {

    var module = 'services';

    esm.reloadBlock_spin(module);

    $.get('libs/'+module+'.php', function(data) {

        var $box = $('.box#esm-'+module+' .box-content tbody');
        $box.empty();

		var id = 0 ;

        for (var line in data)
        {
            var label_color  = data[line].status == 1 ? 'success' : 'error';
            var label_status = data[line].status == 1 ? 'online' : 'offline';

            var html = '';
            html += '<tr>';
            html += '<td class="w15p"><span class="label '+label_color+'">'+label_status+'</span></td>';
			html += '<td style="white-space:nowrap">'+esm.getServiceActionsHtml(data[line], id)+'</td>';

            html += '<td>'+data[line].name+'</td>';
            html += '<td class="w15p">'+data[line].port+'</td>';
            html += '</tr>';

            $box.append(html);

			id++;
        }

        esm.reloadBlock_spin(module);

    }, 'json');

}


esm.setServices = function(id, action) {
    var debug = true;

    if (esm.serviceActionsInProgress[id]) {
        if(debug) console.log('Service action prevented because a command is already running for service ' + id);
        return;
    }

    esm.serviceActionsInProgress[id] = true;
    $('.service-action[data-service-id="'+id+'"]').addClass('disabled');

    if(debug) console.log('Service '+id+' action requested: ' + action);
    $.get('libs/setservice.php?id='+id+'&action='+action, function(resultat){
        if(debug) console.log(resultat);
    }).always(function() {
        setTimeout(function() {
            delete esm.serviceActionsInProgress[id];
            esm.getServices();
            esm.getService_history();
        }, 300);
    });
}


esm.getAptStatus = function() {
    var module = 'apt';

    esm.reloadBlock_spin(module);

    $.get('libs/'+module+'.php', function(data) {
        var $box = $('.box#esm-'+module+' .box-content tbody');
        $box.empty();

	var html = '';

        if( data.status === 0 ) {
            console.log("apt-status", data);

            var package_color = data.standard > 0 ? 'label success' : '';
            var security_color = data.security > 0 ? 'label error' : '';

            html += '<tr>';
            html += '<td>Available Package Updates</td>';
            html += '<td class="w5p"><span class="'+package_color+'">'+data.standard+'</span></td>';
            html += '</tr>';
            html += '<tr>';
            html += '<td>Available Security Updates</td>';
            html += '<td class="w5p"><span class="'+security_color+'">'+data.security+'</span></td>';
            html += '</tr>';
        } else {
            // If the module isn't disabled, something else went wrong
            if( data.status !== 1 ) {
                console.error("Unable to retrieve package updates", data);
            }
	}

	$box.append(html);

        esm.reloadBlock_spin(module);
    }, 'json');
}


esm.getService_history = function() {

    var module = 'service_history';

    esm.reloadBlock_spin(module);

    $.get('libs/'+module+'.php', function(data) {
        esm.serviceHistoryData = data || [];
        esm.renderServiceHistory();

        esm.reloadBlock_spin(module);

    }, 'json').fail(function() {
        esm.serviceHistoryData = [];
        var $box = $('.box#esm-'+module+' .box-content tbody');
        $box.empty();
        $box.append('<tr><td colspan="5" class="t-center">Unable to load history data</td></tr>');
        esm.reloadBlock_spin(module);
    });

}


esm.renderServiceHistory = function() {
    var module = 'service_history';
    var $box = $('.box#esm-'+module+' .box-content tbody');
    $box.empty();

    var rows = esm.serviceHistoryData || [];
    var nameFilter = (esm.serviceHistoryFilters.name || '').toLowerCase();
    var statusFilter = esm.serviceHistoryFilters.status || 'all';

    var filtered = [];
    for (var i = 0; i < rows.length; i++)
    {
        var serviceName = rows[i].service ? String(rows[i].service).toLowerCase() : '';
        var status = rows[i].status ? String(rows[i].status).toLowerCase() : '';

        if (nameFilter !== '' && serviceName.indexOf(nameFilter) === -1)
            continue;

        if (statusFilter !== 'all' && status !== statusFilter)
            continue;

        filtered.push(rows[i]);
    }

    if (filtered.length === 0)
    {
        $box.append('<tr><td colspan="5" class="t-center">No matching history entries</td></tr>');
        return;
    }

    for (var j = 0; j < filtered.length; j++)
    {
        var statusClass = filtered[j].status === 'ok' ? 'success' : 'error';
        var output = filtered[j].output ? String(filtered[j].output).replace(/\n/g, ' | ') : '';

        var html = '';
        html += '<tr>';
        html += '<td>'+filtered[j].time+'</td>';
        html += '<td><span class="label '+statusClass+'">'+filtered[j].status+'</span></td>';
        html += '<td>'+filtered[j].service+'</td>';
        html += '<td>'+filtered[j].action+'</td>';
        html += '<td title="'+esm.escapeHtml(output)+'">'+esm.escapeHtml(output.substr(0, 120))+'</td>';
        html += '</tr>';

        $box.append(html);
    }
}


esm.getTop_processes = function() {

    var module = 'top_processes';

    esm.reloadBlock_spin(module);

    $.get('libs/'+module+'.php', function(data) {

        var $cpuBox = $('.box#esm-'+module+' .box-content table.top-processes-cpu tbody');
        var $memBox = $('.box#esm-'+module+' .box-content table.top-processes-memory tbody');

        $cpuBox.empty();
        $memBox.empty();

        var cpuRows = data && data.cpu ? data.cpu : [];
        var memRows = data && data.memory ? data.memory : [];

        esm.renderProcessRows($cpuBox, cpuRows);
        esm.renderProcessRows($memBox, memRows);

        esm.reloadBlock_spin(module);

    }, 'json').fail(function() {
        var $cpuBox = $('.box#esm-'+module+' .box-content table.top-processes-cpu tbody');
        var $memBox = $('.box#esm-'+module+' .box-content table.top-processes-memory tbody');

        $cpuBox.empty().append('<tr><td colspan="4" class="t-center">Unable to load processes</td></tr>');
        $memBox.empty().append('<tr><td colspan="4" class="t-center">Unable to load processes</td></tr>');
        esm.reloadBlock_spin(module);
    });

}


esm.getAll = function() {
    esm.getSystem();
    esm.getCpu();
    esm.getLoad_average();
    esm.getMemory();
    esm.getSwap();
    esm.getDisk();
    esm.getLast_login();
    esm.getNetwork();
    esm.getPing();
    esm.getServices();
    esm.getService_history();
    esm.getTop_processes();
    esm.getAptStatus();
    esm.getApp_errors();
}

esm.reloadBlock = function(block) {

    esm.mapping[block]();

}

esm.reloadBlock_spin = function(block) {

    var $module = $('.box#esm-'+block);

    $('.reload', $module).toggleClass('spin disabled');
    $('.box-content', $module).toggleClass('faded');

}

esm.insertDatas = function($box, block, datas) {
    for (var item in datas)
    {
        $('#'+block+'-'+item, $box).html(datas[item]);
    }
}

esm.getServiceActionsHtml = function(service, id) {
    var hasStart = service.start != null && service.start !== '';
    var hasStop = service.stop != null && service.stop !== '';
    var hasReload = service.reload != null && service.reload !== '';
    var isOnline = service.status == 1;
    var inProgress = !!esm.serviceActionsInProgress[id];
    var disabledClass = inProgress ? ' disabled' : '';
    var actions = [];

    if (!isOnline && hasStart)
        actions.push('<a href="#" class="service-action'+disabledClass+'" title="Start" data-service-id="'+id+'" onclick="esm.setServices('+id+',\'start\'); return false;"><i class="fa fa-play"></i></a>');

    if (isOnline && hasStop)
        actions.push('<a href="#" class="service-action'+disabledClass+'" title="Stop" data-service-id="'+id+'" onclick="esm.setServices('+id+',\'stop\'); return false;"><i class="fa fa-stop"></i></a>');

    if (isOnline && hasReload)
        actions.push('<a href="#" class="service-action'+disabledClass+'" title="Reload" data-service-id="'+id+'" onclick="esm.setServices('+id+',\'reload\'); return false;"><i class="fa fa-refresh"></i></a>');

    if (actions.length === 0)
        return '<span class="label">N.A</span>';

    return actions.join(' ');
}

esm.getHumanRate = function(rateBytesPerSec) {
    if (!isFinite(rateBytesPerSec) || rateBytesPerSec < 0)
        return 'N.A';

    var units = ['B/s', 'KB/s', 'MB/s', 'GB/s', 'TB/s'];
    var value = rateBytesPerSec;
    var unitIndex = 0;

    while (value >= 1024 && unitIndex < units.length - 1) {
        value = value / 1024;
        unitIndex++;
    }

    return value.toFixed(unitIndex === 0 ? 0 : 2) + ' ' + units[unitIndex];
}

esm.getHumanSize = function(bytes) {
    if (!isFinite(bytes) || bytes < 0)
        return 'N.A';

    var units = ['B', 'KB', 'MB', 'GB', 'TB'];
    var value = bytes;
    var unitIndex = 0;

    while (value >= 1024 && unitIndex < units.length - 1)
    {
        value = value / 1024;
        unitIndex++;
    }

    return value.toFixed(unitIndex === 0 ? 0 : 2) + ' ' + units[unitIndex];
}

esm.renderProcessRows = function($box, rows) {
    if (!rows || rows.length === 0)
    {
        $box.append('<tr><td colspan="4" class="t-center">N.A</td></tr>');
        return;
    }

    for (var i = 0; i < rows.length; i++)
    {
        var html = '';
        html += '<tr>';
        html += '<td>'+rows[i].pid+'</td>';
        html += '<td>'+rows[i].command+'</td>';
        html += '<td class="t-center">'+rows[i].cpu+'</td>';
        html += '<td class="t-center">'+rows[i].mem+'</td>';
        html += '</tr>';
        $box.append(html);
    }
}

esm.escapeHtml = function(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

esm.setupRefresh = function(refreshConfig, defaultRefresh) {
    var parsedDefault = parseInt(defaultRefresh, 10);
    var defaultSeconds = isNaN(parsedDefault) ? 0 : parsedDefault;

    for (var key in esm.refreshHandles)
    {
        clearInterval(esm.refreshHandles[key]);
    }
    esm.refreshHandles = {};

    for (var block in esm.mapping)
    {
        if (block === 'all')
            continue;

        var intervalSeconds = defaultSeconds;

        if (refreshConfig && typeof refreshConfig[block] !== 'undefined')
        {
            var parsed = parseInt(refreshConfig[block], 10);
            if (!isNaN(parsed))
                intervalSeconds = parsed;
        }

        if (intervalSeconds > 0)
        {
            (function(blockName, seconds) {
                esm.refreshHandles[blockName] = setInterval(function() {
                    esm.reloadBlock(blockName);
                }, seconds * 1000);
            })(block, intervalSeconds);
        }
    }
}

esm.setupUi = function() {
    $('#service-history-filter-name').on('keyup change', function() {
        esm.serviceHistoryFilters.name = $(this).val() || '';
        esm.renderServiceHistory();
    });

    $('#service-history-filter-status').on('change', function() {
        esm.serviceHistoryFilters.status = $(this).val() || 'all';
        esm.renderServiceHistory();
    });

    $('.top-process-tabs a').on('click', function(e) {
        e.preventDefault();

        var target = $(this).data('target');
        $('.top-process-tabs a').removeClass('active');
        $(this).addClass('active');

        $('.top-process-panel').addClass('hidden');
        $('.top-process-panel[data-panel="'+target+'"]').removeClass('hidden');
    });
}

esm.reconfigureGauge = function($gauge, newValue) {
    // Change colors according to the percentages
    var colors = { green : '#7BCE6C', orange : '#E3BB80', red : '#CF6B6B' };
    var color  = '';

    if (newValue <= 50)
        color = colors.green;
    else if (newValue <= 75)
        color = colors.orange;
    else
        color = colors.red;

    $gauge.trigger('configure', {
        'fgColor': color,
        'inputColor': color,
        'fontWeight': 'normal',
        'format' : function (value) {
            return value + '%';
        }
    });

    // Change gauge value
    $gauge.val(newValue).trigger('change');
}


esm.getApp_errors = function() {

    var module = 'app_errors';

    esm.reloadBlock_spin(module);

    $.get('libs/'+module+'.php', function(data) {
        esm.renderAppErrors(data || []);
        esm.reloadBlock_spin(module);
    }, 'json').fail(function() {
        var $box = $('.box#esm-'+module+' .box-content tbody');
        $box.empty().append('<tr><td colspan="3" class="t-center">Unable to load error data</td></tr>');
        esm.reloadBlock_spin(module);
    });

}

esm.renderAppErrors = function(rows) {

    var $box = $('.box#esm-app_errors .box-content tbody');
    $box.empty();

    if (!rows || rows.length === 0)
    {
        $box.append('<tr><td colspan="3" class="t-center">No issues recorded</td></tr>');
        return;
    }

    for (var i = 0; i < rows.length; i++)
    {
        var html = '';
        html += '<tr>';
        html += '<td class="w20p">'+rows[i].time+'</td>';
        html += '<td class="w20p">'+esm.escapeHtml(String(rows[i].source))+'</td>';
        html += '<td>'+esm.escapeHtml(String(rows[i].message))+'</td>';
        html += '</tr>';
        $box.append(html);
    }

}

esm.toggleEditMode = function() {

    var active = $('body').toggleClass('edit-mode').hasClass('edit-mode');
    $('#edit-layout-btn').toggleClass('active', active);

    // Column-level drag only enabled in edit mode
    if (esm.sortableInstance)
        esm.sortableInstance.option('disabled', !active);

    // Remove empty columns when leaving edit mode
    if (!active) {
        $('#main-container > .esm-column').each(function() {
            if ($(this).find('> .box').length === 0)
                $(this).remove();
        });
    }

}

esm.getWidthLabel = function(width) {
    var labels = { half: '1/2', third: '1/3', quarter: '1/4', full: '1/1' };
    return labels[width] || '1/2';
}

esm._makeColumn = function(colWidth) {
    return $('<div class="esm-column" data-col-width="' + (colWidth || 'half') + '">' +
             '<div class="esm-col-bar">' +
             '<span class="esm-col-drag" title="Drag column"><i class="fa fa-arrows"></i></span>' +
             '<a href="#" class="esm-col-width-toggle">' + esm.getWidthLabel(colWidth || 'half') + '</a>' +
             '<a href="#" class="esm-col-break-btn" title="New line before this column">&#8629;</a>' +
             '</div>' +
             '</div>');
}

esm._makeBreak = function() {
    return $('<div class="esm-break">' +
             '<span class="esm-break-label">&#8212; new row &#8212;</span>' +
             '<span class="esm-break-remove" title="Remove line break">&times; remove</span>' +
             '</div>');
}

esm._initCardSortable = function($col) {
    var s = Sortable.create($col[0], {
        group: 'cards',
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        draggable: '.box',
        emptyInsertThreshold: 10,
        onAdd: function(evt) {
            // Remove placeholder text when first card is dropped in
            $(evt.to).find('.esm-col-empty').remove();
        },
        onEnd: function(evt) {
            var $from = $(evt.from);
            if ($from.hasClass('esm-column') && $from.find('> .box').length === 0)
                $from.remove();
            esm.saveLayout();
        }
    });
    $col.data('cardSortable', s);
    return s;
}

esm.applyLayout = function(layout) {

    var $container = $('#main-container');

    // Unwrap existing columns back to flat
    $container.find('.esm-column > .box').appendTo($container);
    $container.find('.esm-column').remove();

    if (!layout || !Array.isArray(layout) || layout.length === 0) {
        // Default: one column per card
        var defaultBoxes = $container.find('> .box').get();
        for (var d = 0; d < defaultBoxes.length; d++) {
            var cw = $(defaultBoxes[d]).attr('data-width') || 'half';
            esm._makeColumn(cw).append(defaultBoxes[d]).appendTo($container);
        }
        return;
    }

    // Build lookup map of detached boxes
    var $detached = $container.find('> .box').detach();
    var boxMap = {};
    $detached.each(function() { boxMap[$(this).attr('id')] = this; });

    if (layout[0] && (layout[0].cards || layout[0].type === 'break')) {
        // New column format
        for (var i = 0; i < layout.length; i++) {
            var col = layout[i];
            if (col.type === 'break') {
                $container.append(esm._makeBreak());
                continue;
            }
            var $col = esm._makeColumn(col.colWidth || 'half');
            for (var j = 0; j < col.cards.length; j++) {
                var el = boxMap[col.cards[j]];
                if (el) { $col.append(el); delete boxMap[col.cards[j]]; }
            }
            if ($col.find('> .box').length > 0) $container.append($col);
        }
    } else {
        // Old flat format: one column per entry
        for (var k = 0; k < layout.length; k++) {
            var el2 = boxMap[layout[k].id];
            if (el2) {
                esm._makeColumn(layout[k].width || 'half').append(el2).appendTo($container);
                delete boxMap[layout[k].id];
            }
        }
    }

    // Remaining cards get their own column
    for (var id in boxMap) {
        var rem = boxMap[id];
        var remCw = $(rem).attr('data-width') || 'half';
        esm._makeColumn(remCw).append(rem).appendTo($container);
    }

}

esm.saveLayout = function() {

    var layout = [];
    $('#main-container').children('.esm-column, .esm-break').each(function() {
        if ($(this).hasClass('esm-break')) {
            layout.push({ type: 'break' });
            return;
        }
        var colWidth = $(this).attr('data-col-width') || 'half';
        var cards = [];
        $(this).find('> .box').each(function() {
            var id = $(this).attr('id');
            if (id) cards.push(id);
        });
        if (cards.length > 0) layout.push({ colWidth: colWidth, cards: cards });
    });

    $.ajax({
        url: 'libs/save_layout.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(layout)
    });

}

esm.initSortable = function() {

    if (typeof Sortable === 'undefined') return;

    var $container = $('#main-container');

    // Column-level sortable (drag whole columns), disabled until edit mode
    esm.sortableInstance = Sortable.create($container[0], {
        handle: '.esm-col-drag',
        animation: 150,
        ghostClass: 'sortable-ghost',
        draggable: '.esm-column',
        disabled: true,
        onEnd: function() { esm.saveLayout(); }
    });

    // Card-level sortable for each column
    $container.find('> .esm-column').each(function() {
        esm._initCardSortable($(this));
    });

    var widthCycle = ['half', 'third', 'quarter', 'full'];

    // Column width toggle
    $(document).on('click', '.esm-col-width-toggle', function(e) {
        e.preventDefault();
        var $col = $(this).closest('.esm-column');
        var cur = $col.attr('data-col-width') || 'half';
        var idx = widthCycle.indexOf(cur);
        var newWidth = widthCycle[(idx + 1) % widthCycle.length];
        $col.attr('data-col-width', newWidth);
        $(this).text(esm.getWidthLabel(newWidth));
        esm.saveLayout();
    });

    // Add column button
    $(document).on('click', '#add-column-btn', function(e) {
        e.preventDefault();
        var $col = esm._makeColumn('half');
        $col.append('<div class="esm-col-empty">Drag cards here</div>');
        $container.append($col);
        esm._initCardSortable($col);
    });

    // Insert line break before a column
    $(document).on('click', '.esm-col-break-btn', function(e) {
        e.preventDefault();
        var $col = $(this).closest('.esm-column');
        esm._makeBreak().insertBefore($col);
        esm.saveLayout();
    });

    // Remove line break
    $(document).on('click', '.esm-break-remove', function(e) {
        e.preventDefault();
        $(this).closest('.esm-break').remove();
        esm.saveLayout();
    });

}

esm.mapping = {
    all: esm.getAll,
    system: esm.getSystem,
    load_average: esm.getLoad_average,
    cpu: esm.getCpu,
    memory: esm.getMemory,
    swap: esm.getSwap,
    disk: esm.getDisk,
    last_login: esm.getLast_login,
    network: esm.getNetwork,
    ping: esm.getPing,
    services: esm.getServices,
    service_history: esm.getService_history,
    top_processes: esm.getTop_processes,
    apt: esm.getAptStatus,
    app_errors: esm.getApp_errors,
};
