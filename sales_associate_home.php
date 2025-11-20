<html><head><title>Sales Associate Home</title></head><body>
<?php
    include("secret.php");
	
    try
    {
		$pdo = new PDO($dsn, $username, $password);
		
		// If the associate's identification number was not sent, get it.
		if (empty($_POST["associate_id"]))
		{
			// Get the identification number associated with the name and password.
			$prepared = $pdo->prepare("SELECT ASSOCIATE_ID 
		                               FROM SALES_ASSOCIATES 
		                               WHERE NAME = ? AND PASSWORD = ?;");
			$prepared->execute(array($_POST["name"], $_POST["password"]));
			$associate_id = ($prepared->fetch())[0];
		}
		// If the identification number was sent, store it in a variable.
		else
		{
			$associate_id = $_POST["associate_id"];
		}
		// If the name and password combination is invalid, complain.
		if (empty($associate_id))
		{
			echo "Invalid name or password.";
			echo "<form action=\"login.php\" method=\"POST\">";
			echo "<br/><input type=\"submit\" name=\"submit\" value=\"Try again\">";
			echo "</form>";
		}
		else
		{
			echo "Choose a quote:";
			
			// Get all quotes associated with this user.
			$prepared = $pdo->prepare("SELECT QUOTE_ID, CUSTOMER_ID, CUSTOMER_EMAIL, PRICE, STATUS, DATE 
			                           FROM QUOTES 
			                           WHERE ASSOCIATE_ID = ?;");
			$prepared->execute(array($associate_id));
			$quotes = $prepared->fetchAll(PDO::FETCH_ASSOC);
			
			// A form to go to the quote editing page.
			echo "<form action=\"edit_quote.php\" method=\"POST\">";
			
			// Each current quote gets it's own radio button.
			foreach($quotes as $row)
			{
				echo "<br/><input type=\"radio\" name=\"quote_id\" value=\"".$row["QUOTE_ID"]."\" required> Quote number ".$row["QUOTE_ID"].": Customer: ".$row["CUSTOMER_ID"];
			}
			
			// Also send the user's identification number.
			echo "<input type=\"hidden\" name=\"associate_id\" value=\"".$associate_id."\">";
			
			// Submit button and end of form.
			echo "<br/><input type=\"submit\" value=\"Edit quote\">"; 
			echo "</form>";
			
			// A form for creating a new quote.
			echo "<form action=\"edit_quote.php\" method=\"POST\">";
			
			echo "Choose a customer:";
			// A field for the email.
			echo "<br/>Customer email: <input type=\"email\" name=\"customer_email\" required>";
			
			// Send the user's identification number.
			echo "<input type=\"hidden\" name=\"associate_id\" value=\"".$associate_id."\">";
			
			// Send a quote identification number of -1.
			echo "<input type=\"hidden\" name=\"quote_id\" value=\"-1\">"; 
			
			// Submit button and end of form.
			echo "<br/><input type=\"submit\" value=\"Create a new quote\">";
			echo "</form>";
			
			// Button to logout.
			echo "<form>";
			echo "<br/><br/><br/><br/><input type=\"submit\" value=\"Logout\" formaction=\"login.php\">";
			echo "</form>";
		}
    }
    catch(PDOexception $e)
    {
		echo "Connection to database failed: ".$e->getMessage();
    }
?>
</body></html>
