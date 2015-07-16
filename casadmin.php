<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
	session_start();
		
	ini_set("error_reporting", E_ALL & ~E_NOTICE);
	ini_set("display_errors", 1);

	//echo $_SESSION["username"]." ".$_SESSION["password"];
	
	if ($_SESSION["username"] == "" || $_SESSION["password"] == "")
		header("Location: cashome.php");
	
	/* Setting up the connection to SQL server*/
	$user = '';
	$pass = '';
	$server = 'SERVER_NAME';
	$database = 'DATABASE_NAME';
	$connection_string = "DRIVER={SQL Server};SERVER=$server;DATABASE=$database"; 
	$db = odbc_connect($connection_string,$user,$pass);
	$access = 0;
	
	/* MODES
	- default: Home Admin
	- constructs: Update the number of Constructs
	- questions: Survey_Questions
		1: Add
		2: Edit
		3: Delete
	- subcat: Survey_Subcategories
		11: Add
		12: Edit
		13: Delete
	- category: Survey_Category
		21: Add
		22: Edit
		23: Delete
	*/
	$mode = "default";
	
	date_default_timezone_set('NZ');
	
	/******************************* Constructs **************************************************/
	/* Construct Value from the database */
	function getConstruct($db)
	{
		$qry = "SELECT ID, Constructs FROM Constructs 
				WHERE ID = 1";
		$queryresult = odbc_exec($db,$qry);
		$data = odbc_fetch_array($queryresult);
		$construct = $data["Constructs"];
		return $construct;
	}
	
	/* Construct Value from the database */
	$construct = 0; 	// default value
	$construct = getConstruct($db);
	
	/* Check the maximum number of constructs that the website and database can handle */
	$qry = "SELECT Count(*) AS counter FROM Survey_Questions 
			WHERE belongs_to_survey = 0";
	$queryresult = odbc_exec($db,$qry);
	$data = odbc_fetch_array($queryresult);
	$maxnum = $data["counter"];
	
	/* "Update" button for Construct value */
	if($_REQUEST["constbutt"])
	{
		$newnum = $_REQUEST["construct"];
		$qry = "UPDATE Constructs
				SET Constructs= $newnum
				WHERE ID = 1";
		$queryresult = odbc_exec($db,$qry);
		$alertmessage = "alert('Construct value is updated to ".$newnum."');";
		$construct = getConstruct($db);
	}
	
	/////////////////////////////////////////* All data */////////////////////////////////////////
	$_SESSION["quesID"] = 0;
	$qry = "SELECT ID, Question, Subcategory_ID, Question_Type_ID, belongs_to_survey, question_label FROM Survey_Questions 
			order by ID";
	$quesdataresult = odbc_exec($db,$qry);

	$_SESSION["subcatID"] = 0;
	$qry = "SELECT Subcategory_ID, Trigger_Formula, SubCategory_description, Belongs_To_Subcategory, CategoryID FROM Survey_SubCategories
			order by Subcategory_ID";
	$subcatdataresult = odbc_exec($db,$qry);
	
	$_SESSION["catID"] = 0;
	$qry = "SELECT Category_ID, Category_Description, Name FROM Survey_Category
			order by Category_ID";
	$catdataresult = odbc_exec($db,$qry);
	
	/******************************* Questions **************************************************/
	/* Add a question button in Mode "questions" */
	if ($_REQUEST["quesAdd"])
		$mode = 1;
	
	/* Edit & Delete buttons in Mode "questions" */
	$qry = "SELECT Count(*) AS counter FROM Survey_Questions";
	$qrycount = odbc_exec($db,$qry);
	$quesdata = odbc_fetch_array($qrycount);
	$totalquesnum = $quesdata["counter"];
	
	for ($a = 1; $a <= $totalquesnum; $a++)
	{
		/* Edit buttons */
		if ($_REQUEST["quesEdit".$a])
		{
			$quesID = $_REQUEST["hidden".$a];
			$mode = 2;
			$_SESSION["quesID"] = $quesID;
		}
		
		/* Delete buttons */
		if ($_REQUEST["quesDelete".$a])
		{
			$quesID = $_REQUEST["hidden".$a];
			$mode = 3;
			$_SESSION["quesID"] = $quesID;
		}
	}
	
	/* Mode 1 - Confirm button */
	if ($_REQUEST["addConfirm"])
	{
		$question = $_REQUEST["mode1_q"];
		$question = str_replace("'","`", $question);
		$subcat = $_REQUEST["mode1_sub"];
		$questype = $_REQUEST["mode1_qtype"];
		$belong = $_REQUEST["mode1_belong"];
		$label = $_REQUEST["mode1_label"];
		$label = str_replace("'","`", $label);
		
		// Check Subcategory_ID
		$qry = "SELECT Count(*) AS counter FROM Survey_SubCategories 
			WHERE Subcategory_ID = $subcat";
		$subcatresqry = odbc_exec($db,$qry);
		$subcatresdata = odbc_fetch_array($subcatresqry);
		$subcatres = $subcatresdata["counter"];
		
		// Check Question Type ID
		$qry = "SELECT Count(*) AS counter FROM Question_Type 
			WHERE ID = $questype";
		$qtyperesqry = odbc_exec($db,$qry);
		$qtyperesdata = odbc_fetch_array($qtyperesqry);
		$qtyperes = $qtyperesdata["counter"];
		
		if ($subcatres == 0 || $subcatres == null)
		{
			$alertmessage = "alert('Subcategory ID ".$subcat." does not exist in the database!');";
			$mode = 1;
		}
		else if ($qtyperes == 0 || $qtyperes == null)
		{
			$alertmessage = "alert('Question Type ID ".$questype." does not exist in the database!');";
			$mode = 1;
		}
		else
		{
			// INSERT!!
			$qry = "INSERT INTO Survey_Questions 
					(Question, Subcategory_ID, Question_Type_ID, belongs_to_survey, question_label) 
					VALUES ('$question', $subcat, $questype, $belong, '$label')";
			$addnewques = odbc_exec($db,$qry);
			$alertmessage = "alert('A new question is successfully created!');";
			/* Reload the page to refresh data */
			$mode = "questions";
			$qry = "SELECT ID, Question, Subcategory_ID, Question_Type_ID, belongs_to_survey, question_label FROM Survey_Questions 
				order by ID";
			$quesdataresult = odbc_exec($db,$qry);
		}
	}
	
	/* Mode 2 - Edit the selected question */
	if ($mode == 2)
	{
		$quesid = $_SESSION["quesID"];
		$qry = "SELECT ID, Question, Subcategory_ID, Question_Type_ID, belongs_to_survey, question_label 
			FROM Survey_Questions 
			WHERE ID = $quesid";
		$queryresult = odbc_exec($db,$qry);
		$mode2data = odbc_fetch_array($queryresult);
	}
	
	/* Update button in Mode 2 */
	if ($_REQUEST["editupdate"])
	{
		$quesid = $_REQUEST["mode2_id"];
		$question = $_REQUEST["mode2_q"];
		$question = str_replace("'","`", $question);
		$subcat = $_REQUEST["mode2_sub"];
		$questype = $_REQUEST["mode2_qtype"];
		$belong = $_REQUEST["mode2_belong"];
		$label = $_REQUEST["mode2_label"];
		$label = str_replace("'","`", $label);
		
		// Check Subcategory_ID
		$qry = "SELECT Count(*) AS counter FROM Survey_SubCategories 
			WHERE Subcategory_ID = $subcat";
		$subcatresqry = odbc_exec($db,$qry);
		$subcatresdata = odbc_fetch_array($subcatresqry);
		$subcatres = $subcatresdata["counter"];
		
		// Check Question Type ID
		$qry = "SELECT Count(*) AS counter FROM Question_Type 
			WHERE ID = $questype";
		$qtyperesqry = odbc_exec($db,$qry);
		$qtyperesdata = odbc_fetch_array($qtyperesqry);
		$qtyperes = $qtyperesdata["counter"];
		
		if ($subcatres == 0 || $subcatres == null)
		{
			$alertmessage = "alert('Subcategory ID ".$subcat." does not exist in the database!');";
			$mode = 2;
		}
		else if ($qtyperes == 0 || $qtyperes == null)
		{
			$alertmessage = "alert('Question Type ID ".$questype." does not exist in the database!');";
			$mode = 2;
		}
		else
		{
			$qry = "UPDATE Survey_Questions 
					SET Question = '$question', belongs_to_survey = $belong, question_label='$label', 
						Question_Type_ID=$questype, Subcategory_ID=$subcat
					WHERE ID = $quesid";
			$quesupdateres = odbc_exec($db,$qry);
			$alertmessage = "alert('Question ID #".$quesid." data is successfully updated!');";
			/* Reload the page to refresh data */
			$mode = "questions";
			$qry = "SELECT ID, Question, Subcategory_ID, Question_Type_ID, belongs_to_survey, question_label FROM Survey_Questions 
				order by ID";
			$quesdataresult = odbc_exec($db,$qry);
		}
	}
	
	/* Yes(confirm) button in Mode 3 */
	if ($_REQUEST["deleteconfirm"])
	{
		$quesid = $_REQUEST["mode3_id"];
		
		$qry = "DELETE FROM Survey_Questions 
				WHERE ID = $quesid";
		$quesupdateres = odbc_exec($db,$qry);
		$alertmessage = "alert('Question ID #".$quesid." data is permanently removed from the database!!');";
		/* Reload the page to refresh data */
		$mode = "questions";
		$qry = "SELECT ID, Question, Subcategory_ID, Question_Type_ID, belongs_to_survey, question_label FROM Survey_Questions 
			order by ID";
		$quesdataresult = odbc_exec($db,$qry);
	}
	
	/******************************* Subcategory **************************************************/
	/* Add a data button in Mode "subcat" */
	if ($_REQUEST["subcatAdd"])
		$mode = 11;
	
	/* Edit & Delete buttons in Mode "subcat" */
	$qry = "SELECT Count(*) AS counter FROM Survey_SubCategories";
	$qrycount = odbc_exec($db,$qry);
	$quesdata = odbc_fetch_array($qrycount);
	$totalsubcatnum = $quesdata["counter"];
	
	for ($b = 1; $b <= $totalsubcatnum; $b++)
	{
		/* Edit buttons */
		if ($_REQUEST["subcatEdit".$b])
		{
			$subcatID = $_REQUEST["hidden".$b];
			$mode = 12;
			$_SESSION["subcatID"] = $subcatID;
		}
		
		/* Delete buttons */
		if ($_REQUEST["subcatDelete".$b])
		{
			$subcatID = $_REQUEST["hidden".$b];
			$mode = 13;
			$_SESSION["subcatID"] = $subcatID;
		}
	}
	
	/* Mode 11 - Confirm button */
	if ($_REQUEST["subcatadd"])
	{
		$trigger = $_REQUEST["mode11_tf"];
		$trigger = str_replace("'","`", $trigger);
		$subcatdes = $_REQUEST["mode11_sub"];
		$subcatdes = str_replace("'","`", $subcatdes);
		$belong = $_REQUEST["mode11_belong"];
		$cat = $_REQUEST["mode11_cat"];
		
		// Check Category ID
		$qry = "SELECT Count(*) AS counter FROM Survey_Category 
			WHERE Category_ID = $cat";
		$qtyperesqry = odbc_exec($db,$qry);
		$qtyperesdata = odbc_fetch_array($qtyperesqry);
		$qtyperes = $qtyperesdata["counter"];
		
		if ($qtyperes == 0 || $qtyperes == null)
		{
			$alertmessage = "alert('Category ID ".$cat." does not exist in the database!');";
			$mode = 11;
		}
		else
		{
			// INSERT!!
			$qry = "INSERT INTO Survey_SubCategories 
					(Trigger_Formula, SubCategory_description, Belongs_To_Subcategory, CategoryID) 
					VALUES ('$trigger', '$subcatdes', $belong, $cat)";
			$addnewques = odbc_exec($db,$qry);
			$alertmessage = "alert('A new data is successfully created!');";
			/* Reload the page to refresh data */
			$mode = "subcat";
			$qry = "SELECT Subcategory_ID, Trigger_Formula, SubCategory_description, Belongs_To_Subcategory, CategoryID FROM Survey_SubCategories
				order by Subcategory_ID";
			$subcatdataresult = odbc_exec($db,$qry);
		}
	}
	
	/* Mode 12 - Edit the selected question */
	if ($mode == 12)
	{
		$subcatid = $_SESSION["subcatID"];
		$qry = "SELECT Subcategory_ID, Trigger_Formula, SubCategory_description, Belongs_To_Subcategory, CategoryID 
			FROM Survey_SubCategories 
			WHERE Subcategory_ID = $subcatid";
		$queryresult = odbc_exec($db,$qry);
		$mode12data = odbc_fetch_array($queryresult);
	}
	
	/* Update button in Mode 12 */
	if ($_REQUEST["subcatupdate"])
	{
		$subcatid = $_REQUEST["mode12_id"];
		$trigger = $_REQUEST["mode12_tf"];
		$trigger = str_replace("'","`", $trigger);
		$subcatdes = $_REQUEST["mode12_sub"];
		$subcatdes = str_replace("'","`", $subcatdes);
		$belong = $_REQUEST["mode12_belong"];
		$cat = $_REQUEST["mode12_cat"];
		
		// Check Category ID
		$qry = "SELECT Count(*) AS counter FROM Survey_Category 
			WHERE Category_ID = $cat";
		$qtyperesqry = odbc_exec($db,$qry);
		$qtyperesdata = odbc_fetch_array($qtyperesqry);
		$qtyperes = $qtyperesdata["counter"];
		
		if ($qtyperes == 0 || $qtyperes == null)
		{
			$alertmessage = "alert('Category ID ".$cat." does not exist in the database!');";
			$mode = 12;
		}
		else
		{
			$qry = "UPDATE Survey_SubCategories 
					SET Trigger_Formula = '$trigger', SubCategory_description = '$subcatdes', 
						Belongs_To_Subcategory=$belong, CategoryID=$cat 
					WHERE Subcategory_ID = $subcatid";
			$subcatupdateres = odbc_exec($db,$qry);
			$alertmessage = "alert('Subcategory ID #".$subcatid." data is successfully updated!');";
			/* Reload the page to refresh data */
			$mode = "subcat";
			$qry = "SELECT Subcategory_ID, Trigger_Formula, SubCategory_description, Belongs_To_Subcategory, CategoryID 
				FROM Survey_SubCategories
				order by Subcategory_ID";
			$subcatdataresult = odbc_exec($db,$qry);
		}
	}
	
	/* Yes(confirm) button in Mode 13 */
	if ($_REQUEST["subcatdelete"])
	{
		$subcatid = $_REQUEST["mode13_id"];
		
		$qry = "DELETE FROM Survey_SubCategories 
				WHERE Subcategory_ID = $subcatid";
		$quesupdateres = odbc_exec($db,$qry);
		$alertmessage = "alert('Subcategory ID #".$subcatid." data is permanently removed from the database!!');";
		/* Reload the page to refresh data */
		$mode = "subcat";
		$qry = "SELECT Subcategory_ID, Trigger_Formula, SubCategory_description, Belongs_To_Subcategory, CategoryID FROM Survey_SubCategories
			order by Subcategory_ID";
		$subcatdataresult = odbc_exec($db,$qry);
	}
	
	
	/******************************* Category **************************************************/
	/* Add a data button in Mode "category" */
	if ($_REQUEST["catAdd"])
		$mode = 21;
	
	/* Edit & Delete buttons in Mode "category" */
	$qry = "SELECT Count(*) AS counter FROM Survey_Category";
	$qrycount = odbc_exec($db,$qry);
	$quesdata = odbc_fetch_array($qrycount);
	$totalcatnum = $quesdata["counter"];
	
	for ($c = 1; $c <= $totalcatnum; $c++)
	{
		/* Edit buttons */
		if ($_REQUEST["catEdit".$c])
		{
			$catID = $_REQUEST["hidden".$c];
			$mode = 22;
			$_SESSION["catID"] = $catID;
		}
		
		/* Delete buttons */
		if ($_REQUEST["catDelete".$c])
		{
			$catID = $_REQUEST["hidden".$c];
			$mode = 23;
			$_SESSION["catID"] = $catID;
		}
	}
	
	/* Mode 21 - Confirm button */
	if ($_REQUEST["catadd"])
	{
		$catdes = $_REQUEST["mode21_catdes"];
		$catdes = str_replace("'","`", $catdes);
		$name = $_REQUEST["mode21_name"];
		$name = str_replace("'","`", $name);
		
		// INSERT!!
		$qry = "INSERT INTO Survey_Category
				(Category_Description, name) 
				VALUES ('$catdes', '$name')";
		$addnewques = odbc_exec($db,$qry);
		$alertmessage = "alert('A new data is successfully created!');";
		/* Reload the page to refresh data */
		$mode = "category";
		$qry = "SELECT Category_ID, Category_Description, Name FROM Survey_Category
			order by Category_ID";
		$catdataresult = odbc_exec($db,$qry);
	}
	
	/* Mode 22 - Edit the selected question */
	if ($mode == 22)
	{
		$catid = $_SESSION["catID"];
		$qry = "SELECT Category_ID, Category_Description, Name 
			FROM Survey_Category 
			WHERE Category_ID = $catid";
		$queryresult = odbc_exec($db,$qry);
		$mode22data = odbc_fetch_array($queryresult);
	}
	
	/* Update button in Mode 22 */
	if ($_REQUEST["catupdate"])
	{
		$catid = $_REQUEST["mode22_id"];
		$catdes = $_REQUEST["mode22_catdes"];
		$catdes = str_replace("'","`", $catdes);
		$name = $_REQUEST["mode22_name"];
		$name = str_replace("'","`", $name);
		
		$qry = "UPDATE Survey_Category 
				SET Category_Description = '$catdes', Name = '$name' 
				WHERE Category_ID = $catid";
		$catupdateres = odbc_exec($db,$qry);
		$alertmessage = "alert('Category ID #".$catid." data is successfully updated!');";
		/* Reload the page to refresh data */
		$mode = "category";
		$qry = "SELECT Category_ID, Category_Description, Name FROM Survey_Category
			order by Category_ID";
		$catdataresult = odbc_exec($db,$qry);
	}
	
	/* Yes(confirm) button in Mode 23 */
	if ($_REQUEST["catdelete"])
	{
		$catid = $_REQUEST["mode23_id"];
		
		$qry = "DELETE FROM Survey_Category 
				WHERE Category_ID = $catid";
		$catupdateres = odbc_exec($db,$qry);
		$alertmessage = "alert('Category ID #".$catid." data is permanently removed from the database!!');";
		/* Reload the page to refresh data */
		$mode = "category";
		$qry = "SELECT Category_ID, Category_Description, Name FROM Survey_Category
			order by Category_ID";
		$catdataresult = odbc_exec($db,$qry);
	}
	
	
	/* Buttons in Default */
	if ($_REQUEST["goConstructs"])
		$mode = "constructs";
	if ($_REQUEST["goQuestions"])
		$mode = "questions";
	if ($_REQUEST["goSubCat"])
		$mode = "subcat";
	if ($_REQUEST["goCategory"])
		$mode = "category";
	
	/* Back buttons */
	if ($_REQUEST["back"])
		$mode = "default";
	if ($_REQUEST["quesback"])
		$mode = "questions";
	if ($_REQUEST["subcatback"])
		$mode = "subcat";
	if ($_REQUEST["catback"])
		$mode = "category";
	
	/* Logout */
	if ($_REQUEST["logoutbutt"])
	{
		session_destroy();
		header("Location: cashome.php");
	}
?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Customer Satisfaction Survey</title>
<meta name="Keywords" content="" />
<meta name="Description" content="" />
<link href="default.css" rel="stylesheet" type="text/css" />
<style>
table, td, th {
    border: 1px solid black;
	border-collapse: collapse;
	text-align: center;
}

table {
    width: 100%;
}

th {
    height: 50px;
}
</style>
<!-- Connect to JavaScript file in order to use functions -->
<script src="http://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.11.2.min.js"></script>
<script src="scripts/myscript.js"></script>
<script>
function validateMax(whichcontrol)
{
	var value = document.getElementById("construct").value;
	var max = <?php if ($maxnum == null) echo 2; else echo $maxnum; ?>;
	if (isNaN(value))
	{
		alert("Please enter a number only!");
		whichcontrol.focus();
		whichcontrol.select();
		return false;
	}
	else if (value > max)
	{
		alert("The number is too large!\n" + "The maximum number is " + max);
		whichcontrol.focus();
		whichcontrol.select();
		return false;
	}
	return true;
}
function alertmessage(){
	<?php echo $alertmessage;?>
}
</script>
</head>

<body onload="alertmessage()">
<form name="admin" method="post" action="casadmin.php">
<div id="header">
	<h1>Customer Satisfaction for Cafés</h1>
</div>
	<div style="clear: both;">&nbsp;</div>
<div id="content">
  <div><img src="images/main.jpg" alt=""/></div>
  <br/>
  <div>
	<input type="submit" name="logoutbutt" value="Logout"/>
  </div>
  <br />
  <div id="colThree">
	<h1> Admin Page </h1>
	<p>
		<!-- Home View -->
		<?php if ($mode == "default"){ ?>
			<p><input type="submit" name="goConstructs" value="Update the number of constructs" /></p>
			<p><input type="submit" name="goQuestions" value="Add/Edit/Delete data in Survey_Questions Table" /></p>
			<p><input type="submit" name="goSubCat" value="Add/Edit/Delete data in Survey_SubCategories Table " /></p>
			<p><input type="submit" name="goCategory" value="Add/Edit/Delete data in Survey_Category Table " /></p>
		
		<!-- Update Construct -->
		<?php } else if ($mode == "constructs") { ?>
		<h2>
			Constructs: <input type="text" id="construct" name="construct" size="1" 
			value="<?php echo $construct; ?>" onblur="validateMax(admin.construct);"/>
			<input type="submit" name="constbutt" value="Update"/>
		</h2>
		<p style="color: red;">
		|-> Max <?php echo $maxnum;?> constructs are allowed.
		</p>
		
		<!-- Survey_Questions Table updates -->
		<?php } else if ($mode == "questions"){ ?>
			<h2>
				Survey_Questions Table 
			</h2>
			<input type="submit" name="back" value="Back"/>
			<input type="submit" name="quesAdd" value="Add a new question"/><br/><br/>
			<table>
				<tr>
					<th> Edit </th>
					<th> ID </th>
					<th> Question </th>
					<th> Subcategory ID </th>
					<th> Question Type ID </th>
					<th> Belongs To Survey </th>
					<th> Question Label </th>
					<th> DELETE </th>
				</tr>
				
				<?php
				$i = 1;
				while ($quesdata = odbc_fetch_array($quesdataresult))
				{
					echo "
					 <tr>
						  <input type='hidden' id='hidden".$i."' name='hidden".$i."' value='".$quesdata['ID']."'/>
						  <td><input type='submit' name='quesEdit".$i."' value='Edit'/></td>
						  <td>".$quesdata['ID']."</td>
						  <td>".utf8_encode($quesdata['Question'])."</td>
						  <td>".$quesdata['Subcategory_ID']."</td>
						  <td>".$quesdata['Question_Type_ID']."</td>
						  <td>".$quesdata['belongs_to_survey']."</td>
						  <td>".$quesdata['question_label']."</td>
						  <td><input type='submit' name='quesDelete".$i."' value='Delete'/></td>
						</tr>
					";
					$i++;
				}
				?>
			</table>
		
		<!-- Create a new Question -->
		<?php } else if ($mode == 1){ ?>	
			<h2> Create a new question </h2>
			<br/>
			<table>
				<tr>
					<td><strong>Question:</strong></td> 
					<td><input type="text" name="mode1_q" size="75%" value='<?php echo $question; ?>'/></td>
				</tr>
				<tr>
					<td><strong>Subcategory ID:</strong></td> 
					<td><input type="text" name="mode1_sub" value='<?php echo $subcat; ?>' size="75%"/></td>
				</tr>
				<tr>
					<td><strong>Question Type ID: </strong></td>
					<td><input type="text" name="mode1_qtype" value='<?php echo $questype; ?>' size="75%"/></td>
				</tr>
				<tr>
					<td><strong>Belongs to Survey: </strong></td>
					<td><input type="text" name="mode1_belong" value='<?php echo $belong; ?>' size="75%"/></td>
				</tr>
				<tr>
					<td><strong>Question Label: </strong></td>
					<td><input type="text" name="mode1_label" value='<?php echo $label; ?>' size="75%"/></td>
				</tr>
			</table>
			<br/>
			<p style="color: red">Please double-check all data before pressing Confirm button!</p>
			<input type="submit" name="quesback" value="Back"/>
			<input type="submit" name="addConfirm" value="Confirm"/>
		
		<!-- Update a Question -->
		<?php } else if ($mode == 2){ ?>	
			<h2> Update a question </h2>
			<h3> Question ID <?php echo $_SESSION["quesID"];?> </h3>
			<br/>
			<table>
				<input type="hidden" name="mode2_id" value='<?php echo $_SESSION["quesID"];?>'/>
				<tr>
					<td><strong>Question:</strong></td> 
					<td><input type="text" name="mode2_q" size="75%" 
						value="<?php if ($mode2data["Question"] == "") echo $question; else echo $mode2data["Question"]; ?>"/></td>
				</tr>
				<tr>
					<td><strong>Subcategory ID:</strong></td> 
					<td><input type="text" name="mode2_sub" 
						value='<?php if ($mode2data["Subcategory_ID"] == "") echo $subcat; else echo $mode2data["Subcategory_ID"]; ?>' size="75%"/></td>
				</tr>
				<tr>
					<td><strong>Question Type ID: </strong></td>
					<td><input type="text" name="mode2_qtype" 
						value='<?php if ($mode2data["Question_Type_ID"] == "") echo $questype; else echo $mode2data["Question_Type_ID"]; ?>' size="75%" /></td>
				</tr>
				<tr>
					<td><strong>Belongs to Survey: </strong></td>
					<td><input type="text" name="mode2_belong" 
						value='<?php if ($mode2data["belongs_to_survey"] == "") echo $belong; else echo $mode2data["belongs_to_survey"]; ?>' size="75%"/></td>
				</tr>
				<tr>
					<td><strong>Question Label: </strong></td>
					<td><input type="text" name="mode2_label" 
						value="<?php if ($mode2data["question_label"] == "") echo $label; else echo $mode2data["question_label"]; ?>" size="75%"/></td>
				</tr>
			</table>
			<br/>
			<input type="submit" name="quesback" value="Back"/>
			<input type="submit" name="editupdate" value="Update"/>
		
		<!-- Delete a Question -->
		<?php } else if ($mode == 3){ ?>	
			<h3> Are you really sure to permanently delete the question #<?php echo $_SESSION["quesID"];?> from the database? </h3>
			<br/>
			<input type="submit" name="quesback" value="No"/>
			<input type="submit" name="deleteconfirm" value="Yes, I want to delete this question"/>
			<input type="hidden" name="mode3_id" value='<?php echo $_SESSION["quesID"];?>'/>
		
		<!-- Survey_SubCategories table updates-->
		<?php } else if ($mode == "subcat"){  ?>
			<h2>
				Survey_SubCategories Table
			</h2>
			<input type="submit" name="back" value="Back"/>
			<input type="submit" name="subcatAdd" value="Add a new data"/><br/><br/>
			<table>
				<tr>
					<th> Edit </th>
					<th> ID </th>
					<th> Trigger Formula </th>
					<th> Subcategory Description </th>
					<th> Belongs To Subcategory </th>
					<th> Category ID </th>
					<th> DELETE </th>
				</tr>
				
				<?php
				$i = 1;
				while ($subcatdata = odbc_fetch_array($subcatdataresult))
				{
					echo "
					 <tr>
						  <input type='hidden' id='hidden".$i."' name='hidden".$i."' value='".$subcatdata['Subcategory_ID']."'/>
						  <td><input type='submit' name='subcatEdit".$i."' value='Edit'/></td>
						  <td>".$subcatdata['Subcategory_ID']."</td>
						  <td>".utf8_encode($subcatdata['Trigger_Formula'])."</td>
						  <td>".utf8_encode($subcatdata['SubCategory_description'])."</td>
						  <td>".$subcatdata['Belongs_To_Subcategory']."</td>
						  <td>".$subcatdata['CategoryID']."</td>
						  <td><input type='submit' name='subcatDelete".$i."' value='Delete'/></td>
						</tr>
					";
					$i++;
				}
				?>
			</table>
		
		<!-- Create a new Subcategory data -->
		<?php } else if ($mode == 11){ ?>	
			<h2> Create a new Subcategory data </h2>
			<br/>
			<table>
				<tr>
					<td><strong>Trigger_Formula:</strong></td> 
					<td><input type="text" name="mode11_tf" size="75%" value='<?php echo $trigger; ?>'/></td>
				</tr>
				<tr>
					<td><strong>Subcategory Description:</strong></td> 
					<td><input type="text" name="mode11_sub" value='<?php echo $subcatdes; ?>' size="75%"/></td>
				</tr>
				<tr>
					<td><strong>Belongs To Subcategory: </strong></td>
					<td><input type="text" name="mode11_belong" value='<?php echo $belong; ?>' size="75%"/></td>
				</tr>
				<tr>
					<td><strong>Category ID: </strong></td>
					<td><input type="text" name="mode11_cat" value='<?php echo $cat; ?>' size="75%"/></td>
				</tr>
			</table>
			<br/>
			<p style="color: red">Please double-check all data before pressing Confirm button!</p>
			<input type="submit" name="subcatback" value="Back"/>
			<input type="submit" name="subcatadd" value="Confirm"/>
		
		<!-- Update a Subcategory data -->
		<?php } else if ($mode == 12){ ?>	
			<h2> Update a Subcategory data </h2>
			<h3> Subcategory ID <?php echo $_SESSION["subcatID"];?> </h3>
			<br/>
			<table>
				<input type="hidden" name="mode12_id" value='<?php echo $_SESSION["subcatID"];?>'/>
				<tr>
					<td><strong>Trigger Formula:</strong></td> 
					<td><input type="text" name="mode12_tf" size="75%" 
						value="<?php if ($mode12data["Trigger_Formula"] == "") echo $trigger; else echo $mode12data["Trigger_Formula"]; ?>"/></td>
				</tr>
				<tr>
					<td><strong>SubCategory Description: </strong></td>
					<td><input type="text" name="mode12_sub" 
						value='<?php if ($mode12data["SubCategory_description"] == "") echo $subcatdes; else echo $mode12data["SubCategory_description"]; ?>' size="75%" /></td>
				</tr>
				<tr>
					<td><strong>Belongs to Subcategory: </strong></td>
					<td><input type="text" name="mode12_belong" 
						value='<?php if ($mode12data["Belongs_To_Subcategory"] == "") echo $belong; else echo $mode12data["Belongs_To_Subcategory"]; ?>' size="75%"/></td>
				</tr>
				<tr>
					<td><strong>Category ID: </strong></td>
					<td><input type="text" name="mode12_cat" 
						value="<?php if ($mode12data["CategoryID"] == "") echo $cat; else echo $mode12data["CategoryID"]; ?>" size="75%"/></td>
				</tr>
			</table>
			<br/>
			<input type="submit" name="subcatback" value="Back"/>
			<input type="submit" name="subcatupdate" value="Update"/>
		
		<!-- Delete a Subcategory data -->
		<?php } else if ($mode == 13){ ?>	
			<h3> Are you really sure to permanently delete the subcategory #<?php echo $_SESSION["subcatID"];?> from the database? </h3>
			<br/>
			<input type="submit" name="subcatback" value="No"/>
			<input type="submit" name="subcatdelete" value="Yes, I want to delete this data"/>
			<input type="hidden" name="mode13_id" value='<?php echo $_SESSION["subcatID"];?>'/>
		
		
		<!-- Survey_Category table updates-->
		<?php } else if ($mode == "category"){  ?>
			<h2>
				Survey_Category Table
			</h2>
			<input type="submit" name="back" value="Back"/>
			<input type="submit" name="catAdd" value="Add a new data"/><br/><br/>
			<table>
				<tr>
					<th> Edit </th>
					<th> ID </th>
					<th> Category Description </th>
					<th> Name </th>
					<th> DELETE </th>
				</tr>
				
				<?php
				$i = 1;
				while ($catdata = odbc_fetch_array($catdataresult))
				{
					echo "
					 <tr>
						  <input type='hidden' id='hidden".$i."' name='hidden".$i."' value='".$catdata['Category_ID']."'/>
						  <td><input type='submit' name='catEdit".$i."' value='Edit'/></td>
						  <td>".$catdata['Category_ID']."</td>
						  <td>".utf8_encode($catdata['Category_Description'])."</td>
						  <td>".utf8_encode($catdata['Name'])."</td>
						  <td><input type='submit' name='catDelete".$i."' value='Delete'/></td>
						</tr>
					";
					$i++;
				}
				?>
			</table>
		
		<!-- Create a new category data -->
		<?php } else if ($mode == 21){ ?>	
			<h2> Create a new category data </h2>
			<br/>
			<table>
				<tr>
					<td><strong>Category Description:</strong></td> 
					<td><input type="text" name="mode21_catdes" size="75%" value='<?php echo $catdes; ?>'/></td>
				</tr>
				<tr>
					<td><strong>Name:</strong></td> 
					<td><input type="text" name="mode21_name" value='<?php echo $name; ?>' size="75%"/></td>
				</tr>
			</table>
			<br/>
			<p style="color: red">Please double-check all data before pressing Confirm button!</p>
			<input type="submit" name="catback" value="Back"/>
			<input type="submit" name="catadd" value="Confirm"/>
		
		<!-- Update a category data -->
		<?php } else if ($mode == 22){ ?>	
			<h2> Update a Category data </h2>
			<h3> Category ID <?php echo $_SESSION["catID"];?> </h3>
			<br/>
			<table>
				<input type="hidden" name="mode22_id" value='<?php echo $_SESSION["catID"];?>'/>
				<tr>
					<td><strong>Category Description:</strong></td> 
					<td><input type="text" name="mode22_catdes" size="75%" 
						value="<?php if ($mode22data["Category_Description"] == "") echo $catdes; else echo $mode22data["Category_Description"]; ?>"/></td>
				</tr>
				<tr>
					<td><strong>Name: </strong></td>
					<td><input type="text" name="mode22_name" 
						value='<?php if ($mode22data["Name"] == "") echo $name; else echo $mode22data["Name"]; ?>' size="75%" /></td>
				</tr>
			</table>
			<br/>
			<input type="submit" name="catback" value="Back"/>
			<input type="submit" name="catupdate" value="Update"/>
		
		<!-- Delete a category data -->
		<?php } else if ($mode == 23){ ?>	
			<h3> Are you really sure to permanently delete the category #<?php echo $_SESSION["catID"];?> from the database? </h3>
			<br/>
			<input type="submit" name="catback" value="No"/>
			<input type="submit" name="catdelete" value="Yes, I want to delete this data"/>
			<input type="hidden" name="mode23_id" value='<?php echo $_SESSION["catID"];?>'/>
		<?php }  ?>
	</p>
  <p>&nbsp;</p>
</div>
<p align="center"> </p>
<p align="center"> </p>

<?php if ($mode!="default") { ?>
	<p align="center">
	<input type="submit" name="back" value="Back to Admin Home"/>
	</p>
<?php } ?>

<div style="clear: both;">&nbsp;</div>
</div>
<div id="footer">
	<p>Copyright &copy; 2015</p>
</div>
</form>
</body>
</html>