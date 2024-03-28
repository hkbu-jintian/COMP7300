<?php
    function download() {
        $stock = $_GET['stock'];
        $period1 = strtotime($_GET['start-date']); // start timestamp
        $period2 = strtotime($_GET['end-date']);   // end timestamp

        $url = "https://query1.finance.yahoo.com/v7/finance/download/$stock?period1=$period1&period2=$period2&interval=1d&events=history&includeAdjustedClose=true";

        $content = file_get_contents($url); // download data from the given url
        if ($content) {
            $data = explode("\n", $content);  // get data array splitted by \n
            return $data;
        }
        return array();
    }

    function listDataInFormat($data) {
        $formattedData = array(); // create an empty arrary for storing format stock data
        
        foreach ($data as $i => $row) {
            if ($i == 0) continue; // skip the first row because it's the header row
        
            // each row will be a string
            // split the row by comma to get an array that stores date and price information
            // each row array will contain information below
            // [Date, Open, High, Low, Close, Adj Close, Volume]
            $dataRowArr = explode(",", $row);
        
            // we need to rearrange the data to the format that canvasjs accepts
            // each candleRow is an associated array that contains key x and key y
            // x: date
            // y: open, high, low, close
            $formattedRow = array(
                "x" => null,
                "y" => []
            );
        
            $formattedRow["x"] = $dataRowArr[0];
            $formattedRow["y"] = array(
                "open" => $dataRowArr[1], // open
                "high" => $dataRowArr[2], // high
                "low" => $dataRowArr[3], // low
                "close" => $dataRowArr[4],  // close
                "AdjClose" => $dataRowArr[5], // adj close
                "volume" => $dataRowArr[6] // volume
            );
            array_push($formattedData, $formattedRow);
        }
        return $formattedData;
    }

    // In the javascript program, we will call this function to convert the PHP array to javascript array
    // so that it can be used to display in stock chart
    function listPriceDataFromPHP($arr) {
        foreach ($arr as $row) {
            $x = $row["x"];  // Date
            $y = $row["y"];
            $open = $y['open'];
            $high = $y['high'];
            $low = $y['low'];
            $close = $y['close']; 
            print("{
                    'x': new Date('$x'),
                    'y': [$open, $high, $low, $close]
                },");
        }
    }

    function listClosePriceFromPHP($arr) {
        foreach ($arr as $row) {
            $x = $row["x"];
            $y = $row["y"];
            $close = $y["close"];
            print("{
                    x: new Date('$x'),
                    y: $close
                },");
        }
    }

    function listVolumeFromPHP($arr) {
        foreach ($arr as $row) {
            $x = $row["x"];
            $y = $row["y"];
            $volume = $y["volume"];
            print("{
                    x: new Date('$x'),
                    y: $volume
                },");
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Chart</title>
    <script type="text/javascript" src="https://cdn.canvasjs.com/canvasjs.stock.min.js"></script>
    <link rel="stylesheet" href="web.css">
</head>
<body>
    <div class='container'>
        <div class="left-section">
             <!-- A form to input the range of date -->
            <form action="web.php" method="get">
                <label for="stock-code">Stock Code:</label>
                <input type="text" id="stock-code" name="stock" required placeholder="Enter a stock code">
                <label for="start-date">Start Date:</label>
                <input type="date" id="start-date" name="start-date" required>
                <label for="end-date">End Date:</label>
                <input type="date" id="end-date" name="end-date" required>
                <input type="submit" value="Submit">
            </form>
        </div>
        <div class="right-section">
            <!-- A container for stock chart -->
            <div id="chartContainer" style="height: 700px; width: 100%;"></div>
        </div>
    </div>

    <script type="text/javascript">
        <?php
            if (sizeof($_GET) == 0) // if there exists empty required fields, end the program
                exit();
        
            $stock = $_GET['stock'];
            $data = download();
            $formattedData = listDataInFormat($data);
        ?>
    
        var priceData = [<?php listPriceDataFromPHP($formattedData); ?>]
        var volumeData = [<?php listVolumeFromPHP($formattedData); ?>]
        var closedData = [<?php listClosePriceFromPHP($formattedData); ?>]
        
        if (priceData.length > 0) {
            var stockChart = new CanvasJS.StockChart("chartContainer", {
                theme: "light2",
                exportEnabled: true,
                title: { text: "COMP7300", fontSize: 25 },
                subtitles: [{
                    text: "Exercise"
                }],
                rangeSelector: {
                    buttonStyle: { labelFontSize: 18 },
                    inputFields: { style: { fontSize: 18 } }
                },
                charts: [
                    // Data 1: candle stick chart
                    {
                        legend: { verticalAlign: "top", fontSize: 14 },
                        axisX: {
                            labelFontSize: 11,
                            crosshair: {
                                enabled: true,
                                snapToDataPoint: true
                            }
                        },
                        axisY: { prefix: "$ "},
                        data: [{
                            showInLegend: true,
                            name: "Price (USD)",
                            yValueFormatString: "$#,###.##",
                            type: "candlestick",
                            dataPoints: priceData
                        }]
                    },
                    // Data 2: Bar chart
                    {
                        height: 120,
                        toolTip: { shared: true },
                        axisY: { prefix: "$" },
                        legend: { verticalAlign: "top", fontSize: 14 },
                        data: [{
                            showInLegend: true,
                            name: "Volume (USD)",
                            yValueFormatString: "$#,###.##",
                            dataPoints: volumeData
                        }]
                    }
                ],
                navigator: {
                    data: [{
                        dataPoints: closedData
                    }]
                }
            });
    
            stockChart.render();
        }
    </script>
</body>
</html>