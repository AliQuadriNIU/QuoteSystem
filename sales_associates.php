<html><head><title>Sales Associates Administration</title></head><body>
<?php
    include("secret.php");

    try
    {
        $pdo = new PDO($dsn, $username, $password);
        
        echo "<h1>Sales Associates Administration</h1>";
        
        // Navigation menu
        echo "<p><strong>Navigation:</strong> ";
        echo "<a href=\"admin_search_quotes.php\">Search Quotes</a> | ";
        echo "<a href=\"login.php\">Logout</a></p>";
        echo "<hr/>";
        
        // If the user wanted to add a new sales associate, then add it.
        if (! empty($_POST["add_associate"]))
        {
            $prepared = $pdo->prepare("INSERT INTO SALES_ASSOCIATES(NAME, PASSWORD)
                                       VALUES(?, ?);");
            $prepared->execute(array($_POST["name"], $_POST["password"]));
            echo "<p style='color: green;'>Sales associate added successfully!</p>";
        }
        // If the user wanted to delete an associate, do so.
        else if (! empty($_POST["delete_associate"]))
        {
            // Check if associate has quotes
            $prepared = $pdo->prepare("SELECT COUNT(*) as COUNT FROM QUOTES WHERE ASSOCIATE_ID = ?;");
            $prepared->execute(array($_POST["associate_id"]));
            $count = $prepared->fetch(PDO::FETCH_ASSOC);
            
            if ($count['COUNT'] > 0)
            {
                echo "<p style='color: red;'>Cannot delete sales associate - they have " . $count['COUNT'] . " quote(s) in the system.</p>";
            }
            else
            {
                $prepared = $pdo->prepare("DELETE FROM SALES_ASSOCIATES WHERE ASSOCIATE_ID = ?;");
                $prepared->execute(array($_POST["associate_id"]));
                echo "<p style='color: green;'>Sales associate deleted successfully!</p>";
            }
        }
        
        echo "<h2>Sales Associates:</h2>";
        
        // Get all the sales associates' information.
        $rs = $pdo->query("SELECT * FROM SALES_ASSOCIATES ORDER BY ASSOCIATE_ID;");
        $associates = $rs->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($associates))
        {
            echo "<p><em>No sales associates in the system.</em></p>";
        }
        else
        {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Name</th><th>Password</th><th>Address</th><th>Accumulated Commission</th><th>Actions</th></tr>";
            
            foreach($associates as $row)
            {
                echo "<tr>";
                echo "<td>" . $row["ASSOCIATE_ID"] . "</td>";
                echo "<td>" . $row["NAME"] . "</td>";
                echo "<td>" . $row["PASSWORD"] . "</td>";
                echo "<td>" . ($row["ADDRESS"] ? $row["ADDRESS"] : "<em>Not set</em>") . "</td>";
                echo "<td>$" . number_format($row["SALES_COMMISSION"], 2) . "</td>";
                echo "<td>";
                
                // Edit button
                echo "<form action=\"edit_associate.php\" method=\"POST\" style='display:inline;'>";
                echo "<input type=\"hidden\" name=\"associate_id\" value=\"" . $row["ASSOCIATE_ID"] . "\">";
                echo "<input type=\"submit\" name=\"edit_associate\" value=\"Edit\">";
                echo "</form> ";
                
                // Delete button
                echo "<form action=\"administrate_associates.php\" method=\"POST\" style='display:inline;'>";
                echo "<input type=\"hidden\" name=\"associate_id\" value=\"" . $row["ASSOCIATE_ID"] . "\">";
                echo "<input type=\"submit\" name=\"delete_associate\" value=\"Delete\" onclick=\"return confirm('Are you sure you want to delete this sales associate?');\">";
                echo "</form>";
                
                echo "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
        
        // A form to add a new sales associate.
        echo "<br/><h3>Add New Sales Associate:</h3>";
        echo "<form action=\"administrate_associates.php\" method=\"POST\">";
        echo "Name: <input type=\"text\" name=\"name\" required> ";
        echo "Password: <input type=\"password\" name=\"password\" required> ";
        echo "<input type=\"submit\" name=\"add_associate\" value=\"Add Sales Associate\">";
        echo "</form>";
    }
    catch(PDOexception $e)
    {
        echo "Connection to database failed: ".$e->getMessage();
    }
?>
</body></html>
