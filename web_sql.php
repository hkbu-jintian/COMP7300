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
        $query = "select * from $stock";

        try {
            $result = $db->query($query);
            // $all = $result->fetch_all(MYSQLI_ASSOC);
            $fields = $result->fetch_fields();
            $fields_count = $result->field_count;

            $r = $result->num_rows;
            
            echo "<table>";
            echo "<thead>";
            echo "<tr>";
            for ($i=1; $i<$fields_count; $i++) {
                printf("<th>%s</th>", $fields[$i]->name);
            }
            echo "</tr>";
            echo "</thead>";

            echo "<tbody>";
            for ($i=0; $i<$r; $i++) {
                $arr = $result->fetch_row();
                echo "<tr>";
                for ($j=1; $j<$fields_count; $j++) {
                    printf("<td>%s</td>", $arr[$j]);
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
    <link rel="stylesheet" href="web_sql.css">
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
                    $data = select($db, $stock);
                ?>
            </div>
        </div>
    </div>
</body>
</html>