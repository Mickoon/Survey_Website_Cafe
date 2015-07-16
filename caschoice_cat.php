<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
	session_start();
	ini_set("error_reporting", E_ALL & ~E_NOTICE);
	ini_set("display_errors", 1);
	
	/* Get a name of Cafe from the previous page, 
		so we can show an appropriate cafe image in this page */
	$cafe = $_SESSION["cafe"];
	
	/* Setting up the connection to SQL server*/
	$user = '';
	$pass = '';
	$server = 'SERVER_NAME';
	$database = 'DATABASE_NAME';
	$connection_string = "DRIVER={SQL Server};SERVER=$server;DATABASE=$database"; 
	$db = odbc_connect($connection_string,$user,$pass);
	$access = 0;
	
	date_default_timezone_set('NZ');
	
	/* Find questions from questions ID*/
	$qry = "SELECT ID, Question, question_label FROM Survey_Questions 
			WHERE ID like ";
	$choiceques = explode(" ", $_SESSION["choicequestions"]);
	$i = 0;
	foreach($choiceques as $ques) 
	{
		if ($ques != "" || $ques != null)
		{
			if ($i == 0)
				$qry .= $ques;
			else
				$qry .= " OR ID like ".$ques;
			$i++;
		}
	}
	$queryresult = odbc_exec($db,$qry);

	/* This function is conducted when validation is gone successfully after clicking the submit button */
	if($_REQUEST["submitbutt"] == "Submit" && $_SESSION["depth"] == 0)
	{
		$constructs = explode(" ", $_REQUEST["questionid_construct"]);
		foreach($constructs as $constr) 
		{
			if ($constr != "" || $constr != null)
				$_SESSION["ctrs"][] = $constr;
		}
		
		/* Check if next query is empty or not */
		/* Assign the first element of Construct, and delete it from the array */
		$construct = array_shift($_SESSION["ctrs"]);	// e.g. 71 = Question_ID
		$_SESSION["depth"] = 1; // Depth to 1
		
		function goNextPage($db,$construct)
		{
			/* 1. Find Subcategory ID of this Question_ID in the Survey_Questions table */
			$qry = "SELECT ID, Subcategory_ID FROM Survey_Questions 
				WHERE ID like ".$construct."";
			$queryresult = odbc_exec($db,$qry);
			$data = odbc_fetch_array($queryresult);
			$subcategory = $data["Subcategory_ID"];
			
			/* 2. Find belongs_to_survey = $subcategory in the Survey_Questions table */
			$qry = "SELECT Subcategory_ID, belongs_to_survey FROM Survey_Questions 
				WHERE belongs_to_survey like ".$subcategory."";
			$queryresult = odbc_exec($db,$qry);
			$isempty = TRUE;
			while ($data = odbc_fetch_array($queryresult))
			{
				$isempty = FALSE;
				break;
			}
			
			if ($isempty)
			{
				/* If it is empty, delete the first construct in the array.
				   Then, check if the $_SESSION["ctrs"][0] is null or "",
				   if it is, End this survey
				*/
				$construct = array_shift($_SESSION["ctrs"]);	
				$_SESSION["depth"] = 1; 
				if ($construct == "" or $construct == null)
				{
					header("Location:casexit.php");
				}
				else
				{
					$_SESSION["current_constr"] = $construct;
					goNextPage($db,$construct);
				}
			}
			else
			{
				/* If it is not empty, Go to next page */
				$_SESSION["current_constr"] = $construct;
				$_SESSION["refresh"] = TRUE;
				header("Location:cassurvey.php");
			}
		}
		
		goNextPage($db,$construct);
	}
	
	/* The number of choices that the participant needs to select */
	$howmanytochoose = $_SESSION["howmanytochoose"];
	if ($howmanytochoose == 1)
		$howmanytochoose = "ONE";
	if ($howmanytochoose == 2)
		$howmanytochoose = "TWO";
	if ($howmanytochoose == 3)
		$howmanytochoose = "THREE";
	if ($howmanytochoose == 4)
		$howmanytochoose = "FOUR";
	if ($howmanytochoose == 5)
		$howmanytochoose = "FIVE";
?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Customer Satisfaction Survey</title>
<meta name="Keywords" content="" />
<meta name="Description" content="" />
<link href="default.css" rel="stylesheet" type="text/css" />
<!-- Connect to JavaScript file in order to use functions -->
<script src="http://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.11.2.min.js"></script>
<script src="scripts/myscript.js"></script>
<script>
/* This function is used to confirm that the participant answers all questions */
function validation()
{	
	var errormsg =  "<?php echo "Please select ".$howmanytochoose." question(s)!";?>";
	var checkboxes = document.getElementsByName("option");	// checkboxs
	var selections = "";
	
	var c = 0;
	for (var i = 0; i < checkboxes.length; i++) 
	{
		if (checkboxes[i].checked)
		{
			selections += checkboxes[i].value + " ";
			c++;
		}
	}
	
	if (c == <?php if ($_SESSION["howmanytochoose"] == null) echo 0; else echo $_SESSION["howmanytochoose"]; ?>)
	{
		/* If the user selects the same number of questions as what the system requires, save the selection in HTML*/
		document.getElementById("questionid_construct").value = selections;
		return true;
	}
	else
	{
		alert(errormsg);
		return false;
	}
}
</script>
</head>

<body>
<form name="choiceques_category" method="post" action="caschoice_cat.php" onsubmit="return validation();">
<div id="header">
	<h1>Customer Satisfaction for Cafés</h1>
</div>
	<div style="clear: both;">&nbsp;</div>
<div id="content">
  <!-- Change a cafe image depending on what the participant chose previously -->
	<div>
		<?php if (strpos(($cafe), "Excel") !== false) { ?>
		<img src="images/excel_cafe.jpg" alt="" name="coverimage" width="100%"/>
		<?php } else if (strpos(($cafe), "Strata") !== false) { ?>
		<img src="images/strata1.jpg" alt="" name="coverimage" width="100%"/>
		<?php } else if (strpos(($cafe),"kebab") !== false) { ?>
		<img src="images/unikebab1.jpg" alt="" name="coverimage" width="100%"/>
		<?php } else if (strpos(($cafe), "sushi") !== false) { ?>
		<img src="images/sushi.jpg" alt="" name="coverimage" width="100%"/>
		<?php } else if (strpos(($cafe), "Slurp") !== false) { ?>
		<img src="images/slurp1.jpg" alt="" name="coverimage" width="100%"/>
		<?php } else { ?>
		<img src="images/main.jpg" alt="" name="coverimage" width="100%"/>
		<?php } ?>
	</div>
	
  <div id="colThree">
<h2>&nbsp;</h2>
		<p>&nbsp;</p>
		<h2> Which of the <?php echo $howmanytochoose; ?> following constructs are you least satisfied with ?</h2>
		<!-- VERY IMPORTANT PART! - Making Questions according to the query -->
		<table>
			<?php
			$k = 1;
			while ($data = odbc_fetch_array($queryresult))
			{
				$label = $data["question_label"];
				if ($label == null || $label == "")
					$label = utf8_encode($data["Question"]);
				echo "<tr>
					  <td>
						".$label."
					  </td>
					  <td>
						<input type='checkbox' name='option' value='".$data["ID"]."' />
					  </td>
					</tr>";
				$k++;
			}
			?>
		</table>
  <p>&nbsp;</p>
</div>
<p align="center"> </p>
<p align="center"> </p>
<p align="center">
<input type="submit" name="submitbutt" value="Submit"/>
<!-- Hidden type objects -->
<input type="hidden" id="questionid_construct" name="questionid_construct"/> 
</p>
<div style="clear: both;">&nbsp;</div>
</div>
<div id="footer">
	<p>Copyright &copy; 2015</p>
</div>
</form>
</body>
</html>
