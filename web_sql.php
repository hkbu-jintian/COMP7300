<?php
    function connectToMySQL() {
        $server = "localhost";
        $user = "root";
        $pwd = "";
        $schema = "COMP7300";
        $db = new mysqli($server, $user, $pwd, $schema);

        if (mysqli_connect_errno()) {
            printf("Connection failed: %s<br/>", mysqli_connect_error());
            $db->close();
            exit();
        }
        
        return $db;
    }

    function select($db, $stock) {
        $query = "select * from `$stock`";

        try {
            $result = $db->query($query);
            $fields = $result->fetch_fields();
            $num_rows = $result->num_rows;
            
            echo "<table>";
            echo "<thead>";
            echo "<tr>";
            foreach($fields as $i => $obj) {
                if ($i == 0) continue; // skip the first field because it is ID
                printf("<th>%s</th>", $obj->name);
            }
            echo "</tr>";
            echo "</thead>";

            echo "<tbody>";
            for ($i=0; $i < $num_rows; $i++) {
                $arr = $result->fetch_row();
                echo "<tr>";

                foreach($arr as $j => $val) {
                    if ($j == 0) continue; // skip the first value because it is ID
                    printf("<td>%s</td>", $val);
                }
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";

            $db->close();
        } catch (Exception) {
            $db->close();
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
            <form action="web_sql.php" method="get">
                <label for="stock-code">Stock Code:</label>
                <input type="text" id="stock-code" name="stock" required placeholder="Enter a stock code">
                <input type="submit" value="Query">
            </form>
        </div>
        <div class="right-section">
            <!-- A container for stock chart -->
            <div id="tableContainer">
                <?php
                    if (sizeof($_GET) == 0) // if there exists empty required fields, end the program
                        exit();
                
                    $stock = $_GET['stock'];
                    $db = connectToMySQL();
                    select($db, $stock);
                ?>
            </div>
        </div>
    </div>
</body>
</html>