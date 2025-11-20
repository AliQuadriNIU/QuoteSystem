<html><head><title>Search Quotes</title></head><body>
<?php
    include("secret.php");
    include("library.php");

    try
    {
        $pdo = new PDO($dsn, $username, $password);
        
        echo "<h1>Search and View Quotes</h1>";
        
        // Search form
        echo "<form action=\"admin_search_quotes.php\" method=\"POST\">";
        echo "<h3>Search Criteria:</h3>";
        
        // Status filter
        echo "<p><strong>Status:</strong><br/>";
        echo "<input type=\"checkbox\" name=\"status[]\" value=\"Unfinalized\"> Unfinalized ";
        echo "<input type=\"checkbox\" name=\"status[]\" value=\"Finalized\"> Finalized ";
        echo "<input type=\"checkbox\" name=\"status[]\" value=\"Sanctioned\"> Sanctioned ";
        echo "<input type=\"checkbox\" name=\"status[]\" value=\"Ordered\"> Ordered</p>";
        
        // Date range filter
        echo "<p><strong>Date Range:</strong><br/>";
        echo "From: <input type=\"date\" name=\"date_from\"> ";
        echo "To: <input type=\"date\" name=\"date_to\"></p>";
        
        // Sales associate filter
        echo "<p><strong>Sales Associate:</strong><br/>";
        echo "<select name=\"associate_id\">";
        echo "<option value=\"\">All Associates</option>";
        
        $rs = $pdo->query("SELECT ASSOCIATE_ID, NAME FROM SALES_ASSOCIATES ORDER BY NAME;");
        $associates = $rs->fetchAll(PDO::FETCH_ASSOC);
        foreach ($associates as $assoc)
        {
            $selected = (!empty($_POST["associate_id"]) && $_POST["associate_id"] == $assoc["ASSOCIATE_ID"]) ? "selected" : "";
            echo "<option value=\"" . $assoc["ASSOCIATE_ID"] . "\" $selected>" . $assoc["NAME"] . "</option>";
        }
        echo "</select></p>";
        
        // Customer ID filter
        echo "<p><strong>Customer ID:</strong><br/>";
        echo "<input type=\"number\" name=\"customer_id\" value=\"" . (isset($_POST["customer_id"]) ? $_POST["customer_id"] : "") . "\"></p>";
        
        echo "<input type=\"submit\" name=\"search\" value=\"Search\">";
        echo " <input type=\"submit\" name=\"clear\" value=\"Clear Filters\">";
        echo "</form>";
        
        echo "<hr/>";
        
        // Process search
        if (!empty($_POST["search"]))
        {
            $where_clauses = array();
            $params = array();
            
            // Status filter
            if (!empty($_POST["status"]))
            {
                $status_placeholders = array();
                foreach ($_POST["status"] as $status)
                {
                    $status_placeholders[] = "?";
                    $params[] = $status;
                }
                $where_clauses[] = "q.STATUS IN (" . implode(",", $status_placeholders) . ")";
            }
            
            // Date range filter
            if (!empty($_POST["date_from"]))
            {
                $where_clauses[] = "DATE(q.DATE) >= ?";
                $params[] = $_POST["date_from"];
            }
            if (!empty($_POST["date_to"]))
            {
                $where_clauses[] = "DATE(q.DATE) <= ?";
                $params[] = $_POST["date_to"];
            }
            
            // Sales associate filter
            if (!empty($_POST["associate_id"]))
            {
                $where_clauses[] = "q.ASSOCIATE_ID = ?";
                $params[] = $_POST["associate_id"];
            }
            
            // Customer ID filter
            if (!empty($_POST["customer_id"]))
            {
                $where_clauses[] = "q.CUSTOMER_ID = ?";
                $params[] = $_POST["customer_id"];
            }
            
            // Build query
            $query = "SELECT q.QUOTE_ID, q.CUSTOMER_ID, q.CUSTOMER_EMAIL, q.PRICE, q.STATUS, q.DATE, sa.NAME as ASSOCIATE_NAME
                      FROM QUOTES q
                      JOIN SALES_ASSOCIATES sa ON q.ASSOCIATE_ID = sa.ASSOCIATE_ID";
            
            if (!empty($where_clauses))
            {
                $query .= " WHERE " . implode(" AND ", $where_clauses);
            }
            
            $query .= " ORDER BY q.DATE DESC;";
            
            // Execute query
            $prepared = $pdo->prepare($query);
            $prepared->execute($params);
            $quotes = $prepared->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h2>Search Results:</h2>";
            
            if (empty($quotes))
            {
                echo "<p><em>No quotes found matching your criteria.</em></p>";
            }
            else
            {
                echo "<p>Found " . count($quotes) . " quote(s).</p>";
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>Quote ID</th><th>Customer ID</th><th>Email</th><th>Associate</th><th>Price</th><th>Status</th><th>Date</th><th>Action</th></tr>";
                
                foreach ($quotes as $quote)
                {
                    echo "<tr>";
                    echo "<td>" . $quote["QUOTE_ID"] . "</td>";
                    echo "<td>" . $quote["CUSTOMER_ID"] . "</td>";
                    echo "<td>" . $quote["CUSTOMER_EMAIL"] . "</td>";
                    echo "<td>" . $quote["ASSOCIATE_NAME"] . "</td>";
                    echo "<td>$" . number_format($quote["PRICE"], 2) . "</td>";
                    echo "<td>" . $quote["STATUS"] . "</td>";
                    echo "<td>" . $quote["DATE"] . "</td>";
                    echo "<td>";
                    echo "<form action=\"view_quote_details.php\" method=\"POST\" style='display:inline;'>";
                    echo "<input type=\"hidden\" name=\"quote_id\" value=\"" . $quote["QUOTE_ID"] . "\">";
                    echo "<input type=\"submit\" value=\"View Details\">";
                    echo "</form>";
                    echo "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            }
        }
        
        // Back button
        echo "<br/><br/><a href=\"administrate_associates.php\">Back to Administration</a>";
        echo " | <a href=\"login.php\">Logout</a>";
    }
    catch(PDOexception $e)
    {
        echo "Connection to database failed: ".$e->getMessage();
    }
?>
</body></html>
