<html><head><title>Quote Details</title></head><body>
<?php
    include("secret.php");
    include("library.php");

    try
    {
        $pdo = new PDO($dsn, $username, $password);
        
        if (empty($_POST["quote_id"]))
        {
            echo "No quote selected.";
            echo "<br/><a href=\"headquarters_home.php\">Back to HQ Home</a>";
            exit;
        }
        
        $quote_id = $_POST["quote_id"];
        
        // Handle adding a line item
        if (!empty($_POST["add_line_item"]))
        {
            // Get next item number
            $prepared = $pdo->prepare("SELECT MAX(ITEM_NUMBER) as MAX_NUM FROM LINE_ITEMS WHERE QUOTE_ID = ?;");
            $prepared->execute(array($quote_id));
            $max = $prepared->fetch(PDO::FETCH_ASSOC);
            $item_number = ($max['MAX_NUM'] ? $max['MAX_NUM'] : 0) + 1;
            
            // Insert line item
            $prepared = $pdo->prepare("INSERT INTO LINE_ITEMS(QUOTE_ID, ITEM_NUMBER, DESCRIPTION, PRICE)
                                       VALUES(?, ?, ?, ?);");
            $prepared->execute(array($quote_id, $item_number, $_POST["description"], $_POST["item_price"]));
            
            // Update quote total
            $total = calculate_quote_total($pdo, $quote_id);
            $prepared = $pdo->prepare("UPDATE QUOTES SET PRICE = ? WHERE QUOTE_ID = ?;");
            $prepared->execute(array($total, $quote_id));
            
            echo "<p style='color: green;'>Line item added successfully!</p>";
        }
        // Handle editing a line item
        else if (!empty($_POST["edit_line_item"]))
        {
            $prepared = $pdo->prepare("UPDATE LINE_ITEMS SET DESCRIPTION = ?, PRICE = ? WHERE QUOTE_ID = ? AND ITEM_NUMBER = ?;");
            $prepared->execute(array($_POST["description"], $_POST["item_price"], $quote_id, $_POST["item_number"]));
            
            // Update quote total
            $total = calculate_quote_total($pdo, $quote_id);
            $prepared = $pdo->prepare("UPDATE QUOTES SET PRICE = ? WHERE QUOTE_ID = ?;");
            $prepared->execute(array($total, $quote_id));
            
            echo "<p style='color: green;'>Line item updated successfully!</p>";
        }
        // Handle deleting a line item
        else if (!empty($_POST["delete_line_item"]))
        {
            $prepared = $pdo->prepare("DELETE FROM LINE_ITEMS WHERE QUOTE_ID = ? AND ITEM_NUMBER = ?;");
            $prepared->execute(array($quote_id, $_POST["item_to_delete"]));
            
            // Update quote total
            $total = calculate_quote_total($pdo, $quote_id);
            $prepared = $pdo->prepare("UPDATE QUOTES SET PRICE = ? WHERE QUOTE_ID = ?;");
            $prepared->execute(array($total, $quote_id));
            
            echo "<p style='color: green;'>Line item deleted successfully!</p>";
        }
        // Handle applying discount
        else if (!empty($_POST["apply_discount"]))
        {
            $current_price = $_POST["current_price"];
            $new_price = apply_discount($current_price, $_POST["discount"], $_POST["discount_type"]);
            
            $prepared = $pdo->prepare("UPDATE QUOTES SET PRICE = ? WHERE QUOTE_ID = ?;");
            $prepared->execute(array($new_price, $quote_id));
            
            echo "<p style='color: green;'>Discount applied! New price: $" . number_format($new_price, 2) . "</p>";
        }
        // Handle adding a secret note
        else if (!empty($_POST["add_note"]))
        {
            // Get next note number
            $prepared = $pdo->prepare("SELECT MAX(NOTE_NUMBER) as MAX_NUM FROM SECRET_NOTES WHERE QUOTE_ID = ?;");
            $prepared->execute(array($quote_id));
            $max = $prepared->fetch(PDO::FETCH_ASSOC);
            $note_number = ($max['MAX_NUM'] ? $max['MAX_NUM'] : 0) + 1;
            
            // Insert note
            $prepared = $pdo->prepare("INSERT INTO SECRET_NOTES(QUOTE_ID, NOTE_NUMBER, NOTE)
                                       VALUES(?, ?, ?);");
            $prepared->execute(array($quote_id, $note_number, $_POST["note_text"]));
            
            echo "<p style='color: green;'>Secret note added successfully!</p>";
        }
        // Handle sanctioning quote
        else if (!empty($_POST["sanction_quote"]))
        {
            // Update status to Sanctioned
            $prepared = $pdo->prepare("UPDATE QUOTES SET STATUS = 'Sanctioned' WHERE QUOTE_ID = ?;");
            $prepared->execute(array($quote_id));
            
            // Get quote data for email
            $prepared = $pdo->prepare("SELECT * FROM QUOTES WHERE QUOTE_ID = ?;");
            $prepared->execute(array($quote_id));
            $quote = $prepared->fetch(PDO::FETCH_ASSOC);
            
            // Get line items for email
            $prepared = $pdo->prepare("SELECT * FROM LINE_ITEMS WHERE QUOTE_ID = ?;");
            $prepared->execute(array($quote_id));
            $line_items = $prepared->fetchAll(PDO::FETCH_ASSOC);
            
            // Send email to customer
            $email_body = format_quote_email($quote, $line_items, false);
            $email_sent = send_quote_email($quote['CUSTOMER_EMAIL'], "Quote Approved - Quote #$quote_id", $email_body);
            
            echo "<p style='color: green;'><strong>Quote #$quote_id has been sanctioned!</strong></p>";
            
            if ($email_sent)
            {
                echo "<p style='color: green;'>Quote email sent to customer successfully.</p>";
            }
            else
            {
                echo "<p style='color: orange;'>Warning: Email sending failed. Please contact customer manually.</p>";
            }
            
            echo "<br/><a href=\"headquarters_home.php\">Back to HQ Home</a>";
            exit;
        }
        
        // Get quote details
        $prepared = $pdo->prepare("SELECT q.*, sa.NAME as ASSOCIATE_NAME 
                                   FROM QUOTES q 
                                   JOIN SALES_ASSOCIATES sa ON q.ASSOCIATE_ID = sa.ASSOCIATE_ID 
                                   WHERE q.QUOTE_ID = ?;");
        $prepared->execute(array($quote_id));
        $quote = $prepared->fetch(PDO::FETCH_ASSOC);
        
        if (!$quote)
        {
            echo "<p style='color: red;'>Quote not found.</p>";
            echo "<br/><a href=\"headquarters_home.php\">Back to HQ Home</a>";
            exit;
        }
        
        // Check if we're editing a specific line item
        $editing_item = null;
        if (!empty($_POST["edit_item_number"]))
        {
            $prepared = $pdo->prepare("SELECT * FROM LINE_ITEMS WHERE QUOTE_ID = ? AND ITEM_NUMBER = ?;");
            $prepared->execute(array($quote_id, $_POST["edit_item_number"]));
            $editing_item = $prepared->fetch(PDO::FETCH_ASSOC);
        }
        
        echo "<h2>Quote #" . $quote_id . " - Details</h2>";
        echo "<p><strong>Status:</strong> " . $quote["STATUS"] . "</p>";
        echo "<p><strong>Customer ID:</strong> " . $quote["CUSTOMER_ID"] . "</p>";
        echo "<p><strong>Customer Email:</strong> " . $quote["CUSTOMER_EMAIL"] . "</p>";
        echo "<p><strong>Sales Associate:</strong> " . $quote["ASSOCIATE_NAME"] . "</p>";
        echo "<p><strong>Date:</strong> " . $quote["DATE"] . "</p>";
        
        // Display line items
        echo "<h3>Line Items:</h3>";
        $prepared = $pdo->prepare("SELECT * FROM LINE_ITEMS WHERE QUOTE_ID = ? ORDER BY ITEM_NUMBER;");
        $prepared->execute(array($quote_id));
        $line_items = $prepared->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($line_items))
        {
            echo "<p><em>No line items.</em></p>";
        }
        else
        {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Item #</th><th>Description</th><th>Price</th><th>Actions</th></tr>";
            foreach ($line_items as $item)
            {
                echo "<tr>";
                echo "<td>" . $item["ITEM_NUMBER"] . "</td>";
                echo "<td>" . htmlspecialchars($item["DESCRIPTION"]) . "</td>";
                echo "<td>$" . number_format($item["PRICE"], 2) . "</td>";
                echo "<td>";
                
                // Edit button
                echo "<form action=\"view_quote_details.php\" method=\"POST\" style='display:inline;'>";
                echo "<input type=\"hidden\" name=\"quote_id\" value=\"$quote_id\">";
                echo "<input type=\"hidden\" name=\"edit_item_number\" value=\"" . $item["ITEM_NUMBER"] . "\">";
                echo "<input type=\"submit\" value=\"Edit\">";
                echo "</form> ";
                
                // Delete button
                echo "<form action=\"view_quote_details.php\" method=\"POST\" style='display:inline;'>";
                echo "<input type=\"hidden\" name=\"quote_id\" value=\"$quote_id\">";
                echo "<input type=\"hidden\" name=\"item_to_delete\" value=\"" . $item["ITEM_NUMBER"] . "\">";
                echo "<input type=\"submit\" name=\"delete_line_item\" value=\"Delete\" onclick=\"return confirm('Delete this item?');\">";
                echo "</form>";
                
                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "<p><strong>Current Total: $" . number_format($quote["PRICE"], 2) . "</strong></p>";
        
        // Edit line item form (if editing)
        if ($editing_item)
        {
            echo "<hr/>";
            echo "<h4>Edit Line Item #" . $editing_item["ITEM_NUMBER"] . ":</h4>";
            echo "<form action=\"view_quote_details.php\" method=\"POST\">";
            echo "Description: <input type=\"text\" name=\"description\" value=\"" . htmlspecialchars($editing_item["DESCRIPTION"]) . "\" required size=\"40\"><br/>";
            echo "Price: $<input type=\"number\" name=\"item_price\" step=\"0.01\" min=\"0\" value=\"" . $editing_item["PRICE"] . "\" required><br/>";
            echo "<input type=\"hidden\" name=\"quote_id\" value=\"$quote_id\">";
            echo "<input type=\"hidden\" name=\"item_number\" value=\"" . $editing_item["ITEM_NUMBER"] . "\">";
            echo "<input type=\"submit\" name=\"edit_line_item\" value=\"Save Changes\"> ";
            echo "<input type=\"submit\" value=\"Cancel\">";
            echo "</form>";
        }
        else
        {
            // Add new line item form
            echo "<hr/>";
            echo "<h4>Add New Line Item:</h4>";
            echo "<form action=\"view_quote_details.php\" method=\"POST\">";
            echo "Description: <input type=\"text\" name=\"description\" required size=\"40\"> ";
            echo "Price: $<input type=\"number\" name=\"item_price\" step=\"0.01\" min=\"0\" required> ";
            echo "<input type=\"hidden\" name=\"quote_id\" value=\"$quote_id\">";
            echo "<input type=\"submit\" name=\"add_line_item\" value=\"Add Item\">";
            echo "</form>";
        }
        
        // Apply discount
        echo "<hr/>";
        echo "<h4>Apply Discount:</h4>";
        echo "<form action=\"view_quote_details.php\" method=\"POST\">";
        echo "Discount: <input type=\"number\" name=\"discount\" step=\"0.01\" min=\"0\" required> ";
        echo "<input type=\"radio\" name=\"discount_type\" value=\"amount\" checked> Amount ";
        echo "<input type=\"radio\" name=\"discount_type\" value=\"percentage\"> Percentage ";
        echo "<input type=\"hidden\" name=\"quote_id\" value=\"$quote_id\">";
        echo "<input type=\"hidden\" name=\"current_price\" value=\"" . $quote["PRICE"] . "\">";
        echo "<input type=\"submit\" name=\"apply_discount\" value=\"Apply Discount\">";
        echo "</form>";
        
        // Display secret notes
        echo "<hr/>";
        echo "<h3>Secret Notes:</h3>";
        $prepared = $pdo->prepare("SELECT * FROM SECRET_NOTES WHERE QUOTE_ID = ? ORDER BY NOTE_NUMBER;");
        $prepared->execute(array($quote_id));
        $notes = $prepared->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($notes))
        {
            echo "<p><em>No secret notes.</em></p>";
        }
        else
        {
            echo "<ol>";
            foreach ($notes as $note)
            {
                echo "<li>" . htmlspecialchars($note["NOTE"]) . "</li>";
            }
            echo "</ol>";
        }
        
        // Add secret note form
        echo "<h4>Add Secret Note:</h4>";
        echo "<form action=\"view_quote_details.php\" method=\"POST\">";
        echo "<textarea name=\"note_text\" rows=\"3\" cols=\"50\" required></textarea><br/>";
        echo "<input type=\"hidden\" name=\"quote_id\" value=\"$quote_id\">";
        echo "<input type=\"submit\" name=\"add_note\" value=\"Add Note\">";
        echo "</form>";
        
        // Sanction quote button (only if Finalized)
        if ($quote["STATUS"] == "Finalized")
        {
            echo "<hr/>";
            echo "<h3>Sanction Quote:</h3>";
            echo "<form action=\"view_quote_details.php\" method=\"POST\">";
            echo "<input type=\"hidden\" name=\"quote_id\" value=\"$quote_id\">";
            echo "<input type=\"submit\" name=\"sanction_quote\" value=\"Sanction Quote and Send to Customer\" onclick=\"return confirm('Are you sure you want to sanction this quote? An email will be sent to the customer.');\">";
            echo "</form>";
        }
        
        // Back button
        echo "<br/><br/><form action=\"headquarters_home.php\" method=\"POST\">";
        echo "<input type=\"submit\" value=\"Back to HQ Home\">";
        echo "</form>";
    }
    catch(PDOexception $e)
    {
        echo "Connection to database failed: ".$e->getMessage();
    }
?>
</body></html>