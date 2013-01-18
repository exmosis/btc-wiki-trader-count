<html>
<head>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
<script src="http://code.highcharts.com/highcharts.js"></script>
<script src="http://code.highcharts.com/modules/exporting.js"></script>
<script type="text/javascript">
<?php
include('stats.hc.js');
?>
var chart;
$(document).ready(function() {
  chart = new Highcharts.Chart({
    chart: {
      renderTo: 'container',
      type: 'column',
      marginRight: 300
    },
    title: {
      text: 'Number of BTC-accepting merchants'
    },
    subtitle: {
      text: 'Source: https://en.bitcoin.it/wiki/Trade'
    },
    legend: {
      align: 'right',
      layout: 'vertical',
      width: 250
    },
    xAxis: {
      categories: wikiMonthsCovered,
      tickmarkPlacement: 'on',
      title: {
        enabled: false
      }
    },
    yAxis: {
      title: {
        text: 'Sites'
      },
      labels: {
        formatter: function() {
          return this.value / 1000;
        }
      },
      stackLabels: {
        enabled: true
      }
    },
    tooltip: {
      formatter: function() {
        return ''+
          this.x +': '+ Highcharts.numberFormat(this.y, 0, ',') +' entries';
      }
    },
    plotOptions: {
      column: {
        stacking: 'normal',
        lineColor: '#666666',
        lineWidth: 1,
        marker: {
          lineWidth: 1,
          lineColor: '#666666'
        },
        dataLabels: {
          enabled: false
        }
      },
      series: {
        stacking: 'normal'
      }
    },
    series: wikiHistoryStats,
    exporting: {
      buttons: {
        printButton: {
          enabled: false
        }
      }
    }
  });

  // chart.exportChart();

});

</script>
</head>
<body>
<div id="container" style="width: 100%; height: 100%;">
</div>
</body>
</html>

