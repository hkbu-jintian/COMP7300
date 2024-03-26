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
            $rowArr = explode(",", $row);
        
            // we need to rearrange the data to the format that canvasjs accepts
            // each candleRow is an associated array that contains key x and key y
            // x: date
            // y: open, high, low, close
            $candleRowArr = array(
                "x" => null,
                "y" => []
            );
        
            $candleRowArr["x"] = $rowArr[0];
            $candleRowArr["y"] = array(
                "open" => $rowArr[1], // open
                "high" => $rowArr[2], // high
                "low" => $rowArr[3], // low
                "close" => $rowArr[4],  // close
                "AdjClose" => $rowArr[5], // adj close
                "volume" => $rowArr[6] // volume
            );
            array_push($formattedData, $candleRowArr);
        }
        return $formattedData;
    }

    // In the javascript program, we will call this function to convert the PHP array to javascript array
    // so that it can be used to display in stock chart
    function listPriceDataFromPHP($arr) {
        foreach ($arr as $row) {
            $x = $row["x"];
            $y = $row["y"];
            printf(
                "{ x: new Date('%s'), y: [%.2f, %.2f, %.2f, %.2f] },", // print an object with format
                $x,
                $y["open"],
                $y["high"],
                $y["low"],
                $y["close"]
            );
        }
    }

    function listClosePriceFromPHP($arr) {
        foreach ($arr as $row) {
            $x = $row["x"];
            $y = $row["y"];
            printf("{ x: new Date('%s'), y: %.2f },", $x, $y["close"]);
        }
    }

    function listVolumeFromPHP($arr) {
        foreach ($arr as $row) {
            $x = $row["x"];
            $y = $row["y"];
            printf("{ x: new Date('%s'), y: %.2f },", $x, $y["volume"]);
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
                return;
        
            $stock = $_GET['stock'];
            $data = download();
            $candleData = listDataInFormat($data);
        ?>
        
        const priceData = [<?php listPriceDataFromPHP($candleData); ?>]
        const volumeData = [<?php listVolumeFromPHP($candleData); ?>]
        const closedData = [<?php listClosePriceFromPHP($candleData); ?>]

        console.log(priceData)
        console.log(volumeData)
        console.log(closedData)

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
                            name: "<?php echo $stock; ?> Price (USD)",
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
                            name: "<?php echo $stock; ?> Volume (USD)",
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