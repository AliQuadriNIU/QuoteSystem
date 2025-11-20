<html><head><title>Headquarters Home</title></head><body>
<?php
    include("secret.php");
    include("library.php");

    try
    {
        $pdo = new PDO($dsn, $username, $password);
        
        echo "<h1>Headquarters - Quote Management</h1>";
        
        // Navigation
        echo "<p><strong>Navigation:</strong> ";
        echo "<a href=\"convert_to_purchase_order.php\">Convert to PO</a> | ";
        echo "<a href=\"administrate_associates.php\">Admin</a> | ";
        echo "<a href=\"login.php\">Logout</a></p>";
        echo "<hr/>";
        
        // Get all finalized quotes
        echo "<h2>Finalized Quotes (Pending Review):</h2>";
        $rs = $pdo->query("SELECT q.QUOTE_ID, q.CUSTOMER_ID, q.CUSTOMER_EMAIL, q.PRICE, q.STATUS, q.DATE, sa.NAME as ASSOCIATE_NAME
                          FROM QUOTES q
                          JOIN SALES_ASSOCIATES sa ON q.ASSOCIATE_ID = sa.ASSOCIATE_ID
                          WHERE q.STATUS = 'Finalized'
                          ORDER BY q.DATE DESC;");
        $finalized = $rs->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($finalized))
        {
            echo "<p><em>No finalized quotes pending review.</em></p>";
        }
        else
        {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Quote ID</th><th>Customer ID</th><th>Email</th><th>Associate</th><th>Price</th><th>Date</th><th>Action</th></tr>";
            
            foreach ($finalized as $quote)
            {
                echo "<tr>";
                echo "<td>" . $quote["QUOTE_ID"] . "</td>";
                echo "<td>" . $quote["CUSTOMER_ID"] . "</td>";
                echo "<td>" . $quote["CUSTOMER_EMAIL"] . "</td>";
                echo "<td>" . $quote["ASSOCIATE_NAME"] . "</td>";
                echo "<td>$" . number_format($quote["PRICE"], 2) . "</td>";
                echo "<td>" . $quote["DATE"] . "</td>";
                echo "<td>";
                echo "<form action=\"view_quote_details.php\" method=\"POST\" style='display:inline;'>";
                echo "<input type=\"hidden\" name=\"quote_id\" value=\"" . $quote["QUOTE_ID"] . "\">";
                echo "<input type=\"submit\" value=\"Review\">";
                echo "</form>";
                echo "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
        
        // Get all sanctioned quotes
        echo "<br/><h2>Sanctioned Quotes (Ready for PO):</h2>";
        $rs = $pdo->query("SELECT q.QUOTE_ID, q.CUSTOMER_ID, q.CUSTOMER_EMAIL, q.PRICE, q.STATUS, q.DATE, sa.NAME as ASSOCIATE_NAME
                          FROM QUOTES q
                          JOIN SALES_ASSOCIATES sa ON q.ASSOCIATE_ID = sa.ASSOCIATE_ID
                          WHERE q.STATUS = 'Sanctioned'
                          ORDER BY q.DATE DESC;");
        $sanctioned = $rs->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($sanctioned))
        {
            echo "<p><em>No sanctioned quotes ready for purchase orders.</em></p>";
        }
        else
        {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Quote ID</th><th>Customer ID</th><th>Email</th><th>Associate</th><th>Price</th><th>Date</th><th>Action</th></tr>";
            
            foreach ($sanctioned as $quote)
            {
                echo "<tr>";
                echo "<td>" . $quote["QUOTE_ID"] . "</td>";
                echo "<td>" . $quote["CUSTOMER_ID"] . "</td>";
                echo "<td>" . $quote["CUSTOMER_EMAIL"] . "</td>";
                echo "<td>" . $quote["ASSOCIATE_NAME"] . "</td>";
                echo "<td>$" . number_format($quote["PRICE"], 2) . "</td>";
                echo "<td>" . $quote["DATE"] . "</td>";
                echo "<td>";
                echo "<form action=\"convert_to_purchase_order.php\" method=\"POST\" style='display:inline;'>";
                echo "<input type=\"hidden\" name=\"quote_id\" value=\"" . $quote["QUOTE_ID"] . "\">";
                echo "<input type=\"submit\" value=\"Convert to PO\">";
                echo "</form>";
                echo "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
        
        // Recent Orders
        echo "<br/><h2>Recent Orders:</h2>";
        $rs = $pdo->query("SELECT q.QUOTE_ID, q.CUSTOMER_ID, q.CUSTOMER_EMAIL, q.PRICE, q.DATE, sa.NAME as ASSOCIATE_NAME
                          FROM QUOTES q
                          JOIN SALES_ASSOCIATES sa ON q.ASSOCIATE_ID = sa.ASSOCIATE_ID
                          WHERE q.STATUS = 'Ordered'
                          ORDER BY q.DATE DESC
                          LIMIT 10;");
        $orders = $rs->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($orders))
        {
            echo "<p><em>No recent orders.</em></p>";
        }
        else
        {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Quote ID</th><th>Customer ID</th><th>Email</th><th>Associate</th><th>Price</th><th>Date</th></tr>";
            
            foreach ($orders as $quote)
            {
                echo "<tr>";
                echo "<td>" . $quote["QUOTE_ID"] . "</td>";
                echo "<td>" . $quote["CUSTOMER_ID"] . "</td>";
                echo "<td>" . $quote["CUSTOMER_EMAIL"] . "</td>";
                echo "<td>" . $quote["ASSOCIATE_NAME"] . "</td>";
                echo "<td>$" . number_format($quote["PRICE"], 2) . "</td>";
                echo "<td>" . $quote["DATE"] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    }
    catch(PDOexception $e)
    {
        echo "Connection to database failed: ".$e->getMessage();
    }
?>
</body></html>