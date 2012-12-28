$(function() {
        Highcharts.setOptions({
                global : {
                        useUTC : false
                }
        });

        var seriesOptions = [],
                yAxisOptions = [],
                seriesCounter = 0,
                names = ['CO-zasilanie', 'CO-powrot', 'Wewnatrz', 'Zewnatrz'/*, 'CO-Spaliny'*/],
                uids = ['sup', 'ret', 'in', 'out'/*, 'ex'*/],
                colors = Highcharts.getOptions().colors;

        $.each(uids, function(i, uid) {

                $.getJSON('http://winkiel.treter.pl/data?uid='+ uid.toLowerCase() +'&callback=?',       function(data) {

                        seriesOptions[i] = {
                                name: names[i],
                                data: data
                        };

                        // As we're loading the data asynchronously, we don't know what order it will arrive. So
                        // we keep a counter and create the chart when all the data is loaded.                                                                                                                                   
                        seriesCounter++;                                                                                                                                                                                         
                                                                                                                                                                                                                                 
                        if (seriesCounter == uids.length) {                                                                                                                                                                      
                                createChart();                                                                                                                                                                                   
                        }                                                                                                                                                                                                        
                });                                                                                                                                                                                                              
        });                                                                                                                                                                                                                      
                                                                                                                                                                                                                                 
        // create the chart when all data is loaded                                                                                                                                                                              
        function createChart() {                                                                                                                                                                                                 
                                                                                                                                                                                                                                 
                chart = new Highcharts.StockChart({                                                                                                                                                                              
                    chart: {                                                                                                                                                                                                     
                        renderTo: 'container'                                                                                                                                                                                    
                    },                                                                                                                                                                                                           
                                                                                                                                                                                                                                 
                    xAxis: {                                                                                                                                                                                                     
                        type: 'datetime'                                                                                                                                                                                         
                    },                                                                                                                                                                                                           
                    yAxis: {                                                                                                                                                                                                     
                        plotLines: [{                                                                                                                                                                                            
                                value: 0,                                                                                                                                                                                        
                                width: 2,                                                                                                                                                                                        
                                color: 'silver'                                                                                                                                                                                  
                        }],                                                                                                                                                                                                      
                        labels: {                                                                                                                                                                                                
                            formatter: function() {                                                                                                                                                                              
                                return this.value +'°C'                                                                                                                                                                          
                            }                                                                                                                                                                                                    
                        }                                                                                                                                                                                                        
                    },                                                                                                                                                                                                           
            yAxis: [{ // left y axis                                                                                                                                                                                             
                title: {                                                                                                                                                                                                         
                    text: null
                },
                labels: {
                    align: 'left',
                    x: 3,
                    y: 16,
                    formatter: function() {
                        return Highcharts.numberFormat(this.value, 0);
                    }
                },
                showFirstLabel: false
            }, { // right y axis
                linkedTo: 0,
                gridLineWidth: 0,
                opposite: true,
                title: {
                    text: null
                },
                labels: {
                    align: 'right',
                    x: -3,
                    y: 16,
                    formatter: function() {
                        return Highcharts.numberFormat(this.value, 0);
                    }
                },
                showFirstLabel: false
            }],
            legend: {
                align: 'left',
                verticalAlign: 'top',
                y: 20,
                floating: true,
                borderWidth: 0
            },
                    
                    
                    tooltip: {
                        pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y}</b> ({point.change}%)<br/>',
                        valueDecimals: 2
                    },
                    
                    series: seriesOptions
                });
        }

});
