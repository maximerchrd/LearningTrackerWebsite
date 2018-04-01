jQuery(document).ready(function() {
    loadReports();

    jQuery("#edit-actions").hide();
});

// Get data by type and id
function couchDbView(id, view, callback, one, type, attempt) {
    //id = '138c209c-3b28-f831-de29-4d05fcb328ed:hz2mc57m';
    var key;
    if (type) {
        key = JSON.stringify([type, "id", id+""]);
    } else {
        key = '"'+id+'"';
    }
    var url = likebtn_couch_db_url+'/'+view+'?key='+key;

    jQuery.ajax({
        url: url,
        type: 'get',
        dataType: 'jsonp',
        //timeout: likebtn_couch_db_timeout,
        success: function(response) {
            var data = null;
            if (typeof(response.rows) !== "undefined" && 
                typeof(response.rows[0]) !== "undefined" && 
                typeof(response.rows[0].value) !== "undefined")
            {
                if (typeof(one) !== "undefined" && one) {
                    data = response.rows[0].value;
                } else {
                    data = [];
                    for (var i in response.rows) {
                        if (typeof(response.rows[i].value) !== "undefined") {
                            data.push(response.rows[i].value);
                        } else {
                            data.push(response.rows[i]);
                        }
                    }
                }
            }
            callback("success", data);
        },
        error: function(status) {
            if (typeof(attempt) === "undefined") {
                attempt = 1;
            }
            if (attempt < likebtn_couch_db_retry) {
                // Retry
                attempt++;
                couchDbView(id, view, callback, one, type, attempt);
            } else {
                callback("error", status);
            }
        }
    });
}

// Load website reports
function loadReports()
{
    // Hide errors
    jQuery(".reports-error:first").hide();

    // Check if report is already loaded
    if (jQuery("#likebtn_reports").hasClass("reports-loaded")) {
        return;
    }

    if (!likebtn_reports_id) {
        jQuery("#likebtn_reports .reports-total:first").html(0);
        jQuery("#likebtn_reports .reports-like:first").html(0);
        jQuery("#likebtn_reports .reports-dislike:first").html(0);
    }

    // Load stats from storage
    couchDbView(likebtn_reports_id, likebtn_couch_db_view_main,
        function(result, response) {

            if (result !== 'success') {
                jQuery("#likebtn_reports .reports-error:first").show();
                return;
            }
            jQuery("#likebtn_reports").addClass("reports-loaded");

            if (!response) {
                response = {};
            }

            var like = parseInt(response.like);
            if (isNaN(like)) {
                like = '0'
            }
            var dislike = parseInt(response.dislike);
            if (isNaN(dislike)) {
                dislike = '0'
            }
            var total = parseInt(like+dislike);
            if (isNaN(total)) {
                total = '0'
            }
            jQuery("#likebtn_reports .reports-total:first").html(total);
            jQuery("#likebtn_reports .reports-like:first").html(like);
            jQuery("#likebtn_reports .reports-dislike:first").html(dislike);

            var stats = {};
            if (response.stats) {
                try {
                    stats = JSON.parse(response.stats);
                } catch (e) {}
            }
            
            // Graphs
            Highcharts.setOptions({
                lang: global_highcharts_lang
            });
            
            var chart_options = {
                series: reportsGetSeries(stats.d),
                chart: {
                    renderTo: jQuery("#likebtn_reports .reports-graph-d:first")[0]
                },
                title : {
                    text : ''
                },
                plotOptions: {
                    line: {
                        cursor: 'pointer'
                    }
                },
                rangeSelector : {
                    inputEnabled: false
                },
                rangeSelector: {
                    buttons: [],
                    inputDateFormat: '%d.%m.%Y',
                    inputEditDateFormat: '%d.%m.%Y',
                    inputBoxBorderColor: 'white'
                },
                navigator: {
                    enabled: false
                },
                scrollbar: {
                    enabled: false
                }
            };
            
            var reports_chart = new Highcharts.StockChart(chart_options);

            // Year
            chart_options.series = reportsGetSeries(stats.m, 'm');
            chart_options.chart.renderTo = jQuery("#likebtn_reports .reports-graph-m:first")[0];
            chart_options.rangeSelector.inputDateFormat = '%m.%Y';
            chart_options.rangeSelector.inputEditDateFormat = '%m.%Y';
            chart_options.title.text = '';
            reports_chart = new Highcharts.StockChart(chart_options);
        },
        true,
        likebtn_couch_db_type
    );
}

// Get series from data
function reportsGetSeries(data, mode)
{
    if (typeof(mode) == "undefined") {
        mode = 'd';
    }
    // Build series
    var series = [
        {
            name: likebtn_msg_votes, 
            data: [], 
            color: "#337ab7",
            marker: {
                enabled: true,
                radius: 4,
                symbol: "circle"
            }
        },
        {
            name: likebtn_msg_likes,
            data: [],
            color: "#5cb85c",
            marker: {
                enabled: true,
                radius: 4,
                symbol: "circle"
            }
        },
        {
            name: likebtn_msg_dislikes,
            data: [],
            color: "#f0ad4e",
            marker: {
                enabled: true,
                radius: 4,
                symbol: "circle"
            }
        }
    ];

    if (!data) {
        return series;
    }

    var i = 0;
    var last_ts = 0;
    for (date_str in data) {
        var date = reportsStrToDate(date_str);
        if (!date) {
            continue;
        }
        last_ts = date.getTime();

        // Broken date
        if (!last_ts) {
            continue;
        }

        var votes = data[date_str];
        var like = parseInt(votes[0]) || 0;
        var dislike = parseInt(votes[1]) || 0;
        var total = like+dislike;

        series[0].data[i] = [last_ts, total];
        series[1].data[i] = [last_ts, like];
        series[2].data[i] = [last_ts, dislike];
        i++;
    }

    // No data
    if (series[0].data.length == 0) {
        return series;
    }

    // Add zero values
    var ts = series[0].data[0][0];
    var i = 0;
    while (ts < last_ts) {
        if (mode == 'd') {
            ts = ts + 86400000;
        } else if (mode == 'm') {
            var date = new Date();
            date.setTime(ts);
            date = addMonths(date, 1);
            ts = date.getTime();
        }
        if (series[0].data[i+1] && series[0].data[i+1][0] !== ts) {
            arrayInsertAfter(series[0].data, i, [ts, 0]);
            arrayInsertAfter(series[1].data, i, [ts, 0]);
            arrayInsertAfter(series[2].data, i, [ts, 0]);
        }
        i++;
    }

    // Prepend empty values
    var ts = series[0].data[0][0];
    var diff = 0;
 
    if (mode == 'd') {
        diff = likebtn_report_store_days - series[0].data.length;
    } else if (mode == 'm') {
        diff = 12 - series[0].data.length;
    }

    for (i=0; i<diff; i++) {
        if (mode == 'd') {
            ts = ts - 86400000;
        } else if (mode == 'm') {
            var date = new Date();
            date.setTime(ts);
            date = addMonths(date, -1);
            ts = date.getTime();
        }
        
        arrayInsertBefore(series[0].data, 0, [ts, 0]);
        arrayInsertBefore(series[1].data, 0, [ts, 0]);
        arrayInsertBefore(series[2].data, 0, [ts, 0]);
    }

    return series;
}

// Convet str to date
function reportsStrToDate(str)
{
    if (str.length == 8) {
        return new Date(str.replace(/(\d{4})(\d{2})(\d{2})/,'$1-$2-$3T00:00:00Z'));
    } else if (str.length == 6) {
        return new Date(str.replace(/(\d{4})(\d{2})/,'$1-$2-01T00:00:00Z'));
    }

    return null;
}

// Add/diff month
function isLeapYearByValue(year) { 
    return (((year % 4 === 0) && (year % 100 !== 0)) || (year % 400 === 0)); 
};

function getDaysInMonthByValue(year, month) {
    return [31, (isLeapYearByValue(year) ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][month];
};

function isLeapYear(date) { 
    return isLeapYearByValue(date.getFullYear()); 
};

function getDaysInMonth(date) { 
    return getDaysInMonthByValue(date.getFullYear(), date.getMonth());
};

function addMonths(date, value) {
    var n = date.getDate();
    date.setDate(1);
    date.setMonth(date.getMonth() + value);
    date.setDate(Math.min(n, getDaysInMonth(date)));
    return date;
};

// Insert element after index
function arrayInsertAfter(arr, index, item) {
    arr.splice(index+1, 0, item);
};
function arrayInsertBefore(arr, index, item) {
    arr.splice(index, 0, item);
};