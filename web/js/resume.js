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
                categories: xAxisCategories
            },
            yAxis: {
                title: {
                    text: 'Temperatura (°C)'
                }
            },
            tooltip: {
                enabled: true,
                formatter: function() {
                    return '<b>'+ this.series.name +'</b><br/>'+
                        this.x +': '+ this.y +'°C';
                }
            },
            plotOptions: {
                line: {
                    dataLabels: {
                        enabled: true
                    },
                    enableMouseTracking: false
                }
            },
            series: seriesOptions
        });
    }
});
