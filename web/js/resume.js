$(function () {
    var seriesOptions = [];
    var xAxisCategories = [];
    
    $.getJSON('http://winkiel.treter.pl/resume_chart_data?callback=?', function(data) {            
            seriesOptions = [{
                name: 'Temp. wewn.',
                data: data['in']
              },{
                name: 'Temp. zewn.',
                data: data['out']
            }];
            xAxisCategories = data['dates'];
            
            createChart();
    });
    
    function createChart() {
        $('#container').highcharts({
            chart: {
                type: 'line'
            },
            title: {
                text: 'Średnia dzienna temperatura wewn./zewn.'
            },
            subtitle: {
                text: 'grudzień 2012 - marzec 2013'
            },
            xAxis: {
                categories: xAxisCategories,
                labels: {
                    step: 5,
                    rotation: -45,
                    align: 'right',
                    style: {
                        fontSize: '10px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                }
            },
            yAxis: {
                title: {
                    text: 'Temperatura (°C)'
                },
                alternateGridColor: '#FDFFD5',
                labels: {
                    step: 2,
                    style: {
                        fontSize: '10px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                },
                tickInterval: 1.0
            },
            tooltip: {
                shared: true,
                crosshairs: true
            },
            plotOptions: {
                line: {
                    dataLabels: {
                        enabled: false
                    },
                    enableMouseTracking: true,
                    marker: {
                        enabled: false
                    }
                }
            },
            series: seriesOptions
        });
    }
});
