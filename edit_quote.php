<html><head><title>Edit Quote</title></head><body>
<?php
include("secret.php");
include("library.php");
include("customer_db_config.php");

try
{
    $pdo = new PDO($dsn, $username, $password);

    // If the identification numbers were not sent, go back to login.
    if (empty($_POST["associate_id"]))
    {
        echo "Session expired. Please login again.";
        echo "<form>";
        echo "<input type=\"submit\" value=\"Login\" formaction=\"login.php\">";
        echo "</form>";
    }
    // Handle creating a new quote
    else if (!empty($_POST["quote_id"]) && $_POST["quote_id"] == -1)
    {
        // Validate customer ID
        if (empty($_POST["customer_id"]))
        {
            echo "<p style='color: red;'>Please select a customer.</p>";

            // Show customer selection form
            echo "<h2>Create New Quote</h2>";
            echo "<form action=\"edit_quote.php\" method=\"POST\">";
            echo "<p><strong>Search Customer by Name:</strong><br/>";
            echo "<input type=\"text\" name=\"customer_search\" placeholder=\"Enter customer name\">";
            echo "<input type=\"submit\" name=\"search_customer\" value=\"Search\"></p>";

            // Show search results if available
            if (!empty($_POST["search_customer"]) && !empty($_POST["customer_search"]))
            {
                $customers = search_customers_by_name($_POST["customer_search"]);
                if (!empty($customers))
                {
                    echo "<p><strong>Select a customer:</strong></p>";
                    foreach ($customers as $customer)
                    {
                        echo "<input type=\"radio\" name=\"customer_id\" value=\"" . $customer['id'] . "\" required>";
                        echo " " . $customer['name'] . " - " . $customer['city'] . "<br/>";
                    }
                }
                else
                {
                    echo "<p><em>No customers found.</em></p>";
                }
            }
            else
            {
                // Show all customers
                $customers = get_all_customers();
                if (!empty($customers))
                {
                    echo "<p><strong>Or select from all customers:</strong></p>";
                    echo "<select name=\"customer_id\" required>";
                    echo "<option value=\"\">-- Select Customer --</option>";
                    foreach ($customers as $customer)
                    {
                        echo "<option value=\"" . $customer['id'] . "\">" . $customer['name'] . " - " . $customer['city'] . "</option>";
                    }
                    echo "</select>";
                }
            }

            echo "<br/><br/><p><strong>Customer Email:</strong><br/>";
            echo "<input type=\"email\" name=\"customer_email\" required></p>";
            echo "<input type=\"hidden\" name=\"associate_id\" value=\"" . $_POST["associate_id"] . "\">";
            echo "<input type=\"hidden\" name=\"quote_id\" value=\"-1\">";
            echo "<input type=\"hidden\" name=\"create_quote\" value=\"1\">";
            echo "<input type=\"submit\" value=\"Create Quote\">";
            echo "</form>";

            echo "<br/><form action=\"sales_associate_home.php\" method=\"POST\">";
            echo "<input type=\"submit\" value=\"Back to Home\">";
            echo "<input type=\"hidden\" name=\"associate_id\" value=\"" . $_POST["associate_id"] . "\">";
            echo "</form>";
        }
        else
        {
            // Validate customer exists
            if (!validate_customer_id($_POST["customer_id"]))
            {
                echo "<p style='color: red;'>Invalid customer ID.</p>";
                echo "<br/><a href=\"sales_associate_home.php\">Back to Home</a>";
            }
            else
            {
                // Create new quote
                $prepared = $pdo->prepare("INSERT INTO QUOTES(CUSTOMER_ID, ASSOCIATE_ID, CUSTOMER_EMAIL, PRICE)
                                               VALUES(?, ?, ?, 0);");
                $prepared->execute(array($_POST["customer_id"], $_POST["associate_id"], $_POST["customer_email"]));

                $quote_id = $pdo->lastInsertId();
                $_POST["quote_id"] = $quote_id;

                echo "<p style='color: green;'><strong>New quote #$quote_id created successfully!</strong></p>";
            }
        }
    }
    // Handle adding a line item
    else if (!empty($_POST["add_line_item"]) && !empty($_POST["quote_id"]))
    {
        $quote_id = $_POST["quote_id"];

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
    // Handle deleting a line item
    else if (!empty($_POST["delete_line_item"]) && !empty($_POST["quote_id"]))
    {
        $quote_id = $_POST["quote_id"];

        $prepared = $pdo->prepare("DELETE FROM LINE_ITEMS WHERE QUOTE_ID = ? AND ITEM_NUMBER = ?;");
        $prepared->execute(array($quote_id, $_POST["item_to_delete"]));

        // Update quote total
        $total = calculate_quote_total($pdo, $quote_id);
        $prepared = $pdo->prepare("UPDATE QUOTES SET PRICE = ? WHERE QUOTE_ID = ?;");
        $prepared->execute(array($total, $quote_id));

        echo "<p style='color: green;'>Line item deleted successfully!</p>";
    }
    // Handle adding a secret note
    else if (!empty($_POST["add_note"]) && !empty($_POST["quote_id"]))
    {
        $quote_id = $_POST["quote_id"];

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
    // Handle updating customer email
    else if (!empty($_POST["update_email"]) && !empty($_POST["quote_id"]))
    {
        $quote_id = $_POST["quote_id"];

        $prepared = $pdo->prepare("UPDATE QUOTES SET CUSTOMER_EMAIL = ? WHERE QUOTE_ID = ?;");
        $prepared->execute(array($_POST["customer_email"], $quote_id));

        echo "<p style='color: green;'>Customer email updated successfully!</p>";
    }
    // Handle finalizing quote
    else if (!empty($_POST["finalize_quote"]) && !empty($_POST["quote_id"]))
    {
        $quote_id = $_POST["quote_id"];

        $prepared = $pdo->prepare("UPDATE QUOTES SET STATUS = 'Finalized' WHERE QUOTE_ID = ?;");
        $prepared->execute(array($quote_id));

        echo "<p style='color: green;'><strong>Quote #$quote_id has been finalized!</strong></p>";
    }

    // Display quote editor if we have a valid quote_id
    if (!empty($_POST["quote_id"]) && $_POST["quote_id"] != -1)
    {
        $quote_id = $_POST["quote_id"];

        // Get the quote information
        $prepared = $pdo->prepare("SELECT * FROM QUOTES WHERE QUOTE_ID = ?;");
        $prepared->execute(array($quote_id));
        $quote = $prepared->fetch(PDO::FETCH_ASSOC);

        if (!$quote)
        {
            echo "<p style='color: red;'>Quote not found.</p>";
        }
        else
        {
            echo "<h2>Quote #" . $quote_id . "</h2>";
            echo "<p><strong>Status:</strong> " . $quote["STATUS"] . "</p>";
            echo "<p><strong>Customer ID:</strong> " . $quote["CUSTOMER_ID"] . "</p>";
            echo "<p><strong>Date:</strong> " . $quote["DATE"] . "</p>";

            // Customer email form
            echo "<form action=\"edit_quote.php\" method=\"POST\">";
            echo "<p><strong>Customer Email:</strong> ";
            echo "<input type=\"email\" name=\"customer_email\" value=\"" . $quote["CUSTOMER_EMAIL"] . "\" required>";
            echo "<input type=\"hidden\" name=\"quote_id\" value=\"$quote_id\">";
            echo "<input type=\"hidden\" name=\"associate_id\" value=\"" . $_POST["associate_id"] . "\">";
            echo " <input type=\"submit\" name=\"update_email\" value=\"Update Email\"></p>";
            echo "</form>";

            // Display line items
            echo "<h3>Line Items:</h3>";
            $prepared = $pdo->prepare("SELECT * FROM LINE_ITEMS WHERE QUOTE_ID = ? ORDER BY ITEM_NUMBER;");
            $prepared->execute(array($quote_id));
            $line_items = $prepared->fetchAll(PDO::FETCH_ASSOC);

            if (empty($line_items))
            {
                echo "<p><em>No line items yet.</em></p>";
            }
            else
            {
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>Item #</th><th>Description</th><th>Price</th><th>Action</th></tr>";
                foreach ($line_items as $item)
                {
                    echo "<tr>";
                    echo "<td>" . $item["ITEM_NUMBER"] . "</td>";
                    echo "<td>" . htmlspecialchars($item["DESCRIPTION"]) . "</td>";
                    echo "<td>$" . number_format($item["PRICE"], 2) . "</td>";
                    echo "<td>";
                    if ($quote["STATUS"] == "Unfinalized")
                    {
                        echo "<form action=\"edit_quote.php\" method=\"POST\" style='display:inline;'>";
                        echo "<input type=\"hidden\" name=\"quote_id\" value=\"$quote_id\">";
                        echo "<input type=\"hidden\" name=\"associate_id\" value=\"" . $_POST["associate_id"] . "\">";
                        echo "<input type=\"hidden\" name=\"item_to_delete\" value=\"" . $item["ITEM_NUMBER"] . "\">";
                        echo "<input type=\"submit\" name=\"delete_line_item\" value=\"Delete\" onclick=\"return confirm('Delete this item?');\">";
                        echo "</form>";
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }

            echo "<p><strong>Total Price: $" . number_format($quote["PRICE"], 2) . "</strong></p>";

            // Add line item form
            if ($quote["STATUS"] == "Unfinalized")
            {
                echo "<h4>Add Line Item:</h4>";
                echo "<form action=\"edit_quote.php\" method=\"POST\">";
                echo "Description: <input type=\"text\" name=\"description\" required size=\"40\"> ";
                echo "Price: $<input type=\"number\" name=\"item_price\" step=\"0.01\" min=\"0\" required> ";
                echo "<input type=\"hidden\" name=\"quote_id\" value=\"$quote_id\">";
                echo "<input type=\"hidden\" name=\"associate_id\" value=\"" . $_POST["associate_id"] . "\">";
                echo "<input type=\"submit\" name=\"add_line_item\" value=\"Add Item\">";
                echo "</form>";
            }

            // Display secret notes
            echo "<h3>Secret Notes:</h3>";
            $prepared = $pdo->prepare("SELECT * FROM SECRET_NOTES WHERE QUOTE_ID = ? ORDER BY NOTE_NUMBER;");
            $prepared->execute(array($quote_id));
            $notes = $prepared->fetchAll(PDO::FETCH_ASSOC);

            if (empty($notes))
            {
                echo "<p><em>No secret notes yet.</em></p>";
            }
            else
            {
                echo "<ul>";
                foreach ($notes as $note)
                {
                    echo "<li>" . htmlspecialchars($note["NOTE"]) . "</li>";
                }
                echo "</ul>";
            }

            // Add secret note form
            if ($quote["STATUS"] == "Unfinalized")
            {
                echo "<h4>Add Secret Note:</h4>";
                echo "<form action=\"edit_quote.php\" method=\"POST\">";
                echo "<textarea name=\"note_text\" rows=\"3\" cols=\"50\" required></textarea><br/>";
                echo "<input type=\"hidden\" name=\"quote_id\" value=\"$quote_id\">";
                echo "<input type=\"hidden\" name=\"associate_id\" value=\"" . $_POST["associate_id"] . "\">";
                echo "<input type=\"submit\" name=\"add_note\" value=\"Add Note\">";
                echo "</form>";

                // Finalize quote button
                echo "<br/><h4>Finalize Quote:</h4>";
                echo "<form action=\"edit_quote.php\" method=\"POST\">";
                echo "<input type=\"hidden\" name=\"quote_id\" value=\"$quote_id\">";
                echo "<input type=\"hidden\" name=\"associate_id\" value=\"" . $_POST["associate_id"] . "\">";
                echo "<input type=\"submit\" name=\"finalize_quote\" value=\"Finalize Quote\" onclick=\"return confirm('Are you sure you want to finalize this quote? You will not be able to edit it after finalizing.');\">";
                echo "</form>";
            }
        }
    }

    // Button to go back
    echo "<br/><form action=\"sales_associate_home.php\" method=\"POST\">";
    echo "<input type=\"submit\" value=\"Back to Home\">";
    echo "<input type=\"hidden\" name=\"associate_id\" value=\"" . $_POST["associate_id"] . "\">";
    echo "</form>";
}
catch(PDOexception $e)
{
    echo "Connection to database failed: ".$e->getMessage();
}
?>
</body></html>