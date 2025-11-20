global$password;
<html><head><title>Edit Sales Associate</title></head><body>
<?php
    include("secret.php");

    try
    {
		$pdo = new PDO($dsn, $username, $password);
		
		// If the identification number was not sent, complain.
		if (empty($_POST["associate_id"]))
		{
			echo "";
			// Button to go to the login page.
			echo "<form>";
			echo "<input type=\"submit\" value=\"Choose a sales associate\" formaction=\"administrate_associates.php\">";
			echo "</form>";
		}
		// If everything is valid,
		else 
		{
			// If the associate was edited, update the table.
			if ($_POST["associate_edited"])
			{
				// Update the table to the new values.
				$prepared = $pdo->prepare("UPDATE SALES_ASSOCIATES
				                           SET NAME = ?,
				                               PASSWORD = ?,
				                               ADDRESS = ?,
				                               SALES_COMMISSION = ?
				                           WHERE ASSOCIATE_ID = ?;");
				$prepared->execute(array($_POST["name"], $_POST["password"], $_POST["address"], $_POST["sales_commission"], $_POST["associate_id"]));
			}
			
			// Get the sales associate information.
			$prepared = $pdo->prepare("SELECT NAME, PASSWORD, ADDRESS, SALES_COMMISSION
				                       FROM SALES_ASSOCIATES
				                       WHERE ASSOCIATE_ID = ?;");
			$prepared->execute(array($_POST["associate_id"]));
			$associate = $prepared->fetch(PDO::FETCH_ASSOC);
				
			// A form to change sales associate information.
			echo "<form action=\"edit_associate.php\" method=\"POST\">";
			echo "Sales associate identification number: ".$_POST["associate_id"];
				
			// A field for the name.
			echo "<br/>Name: <input type=\"text\" name=\"name\" value=\"".$associate["NAME"]."\" required>";
				
			// A field for the password.
			echo "<br/>Password: <input type=\"text\" name=\"password\" value=\"".$associate["PASSWORD"]."\" required>";
				
			// A field for the address.
			echo "<br/>Address: <input type=\"text\" name=\"address\" value=\"".$associate["ADDRESS"]."\" required>";
				
			// A field for the sales commission.
			echo "<br/>Sales commission: $<input type=\"number\" min=\"0\" step=\"0.01\"name=\"sales_commission\" value=\"".$associate["SALES_COMMISSION"]."\" required>";
				
			// Also send the sales associate's identification number.
			echo "<input type=\"hidden\" name=\"associate_id\" value=\"".$_POST["associate_id"]."\">";
				
			// Submit button and end of form.
			echo "<br/><br/><input type=\"submit\" name=\"associate_edited\" value=\"Save\">";
			echo "</form>";
			
			// Button to go back.
			echo "<form action=\"administrate_associates.php\" method=\"POST\">";
			echo "<br/><br/><br/><br/><input type=\"submit\" value=\"Back\">";
			echo "</form>";
		}
    }
    catch(PDOexception $e)
    {
		echo "Connection to database failed: ".$e->getMessage();
    }
?>
</body></html>
