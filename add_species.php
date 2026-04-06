<?php
	include("../millport.inc");
	
	connectToDatabase("millport");

	$title = "Add species...";

	$mobile = true;
	
	startPage();
	writeHeader($title, null, null,  null, 
		"<meta name=\"viewport\" content=\"width=320, initial-scale=1.0, user-scalable=yes\" />".
		"<meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black\" />".
		"<meta name=\"apple-mobile-web-app-capable\" content=\"yes\" />"
		);
	startBody(($mobile?"id=\"mobile\"":null));
	
	echo('<h1>'.$title.'</h1>');
    echo('<h2>Biodiversity exercise</h2>');

	$binomial = PostStr('binomial');
	$species_id = PostInt('sid');
	$mode = PostStr('mode');

	$error = false;
	if ($binomial && $species_id) {
		$errorMessage = "Both a species id and a binomial have been provided.";
		$error = true;
	}
	
	$synonyms = [];
	
	if (!$error && $binomial) {
		$class = trim(PostStr('class', NULL));		
		$phylum_id = trim(PostInt('pid', NULL));
		$name = trim(PostStr('name', NULL));
		$notes = trim(PostStr('notes', NULL));
		
		$syn = trim(PostStr('synonyms'));
		if ($syn) {
			$synonyms = explode(",", $syn);
		}
		$synonym_notes = trim(PostStr('synonym_notes'));

		$binomial = preg_split("/\s+/", $binomial);
		$genus = $binomial[0];
		$species = "";
		if (count($binomial) > 1) {
			$species = $binomial[1];
		}
		
		if (!$mode && (!$class || strlen($class) < 3)) {
			$errorMessage = "Class field is missing or short. Please go back and provide a valid <b>Class</b> for this species";
			$error = true;
		}
	}
	
	if (!$error) {
		$year = trim(PostInt('year'));		
		$site = trim(PostInt('site'));
		
		if ($site != null) {
			$sites[] = $site;
		} else {	
			if (PostInt('site1')) $sites[] = 1;
			if (PostInt('site2')) $sites[] = 2;
			if (PostInt('site3')) $sites[] = 3;
			if (PostInt('site4')) $sites[] = 4;
		}
		if (count($sites) == 0) {
			$sites[] = 0;
		}
		
		$location = trim(PostStr('location'));
		$id_notes = trim(PostStr('id_notes'));
		$lab = trim(PostInt('lab'));
		$identified_by = trim(PostStr('identified_by'));
		$corroborated_by = trim(PostStr('corroborated_by'));

		if (!$mode && (!$identified_by || strlen($identified_by) < 2)) {
			$errorMessage = "The 'Identified By' field is missing. Please go back and provide your initials (at least 2)";
			$error = true;
		}
	}
	
	if (!$error && $binomial) {
		//$query="INSERT INTO species set ".
		//	"genus= \"".$genus."\",".
		//	"species= \"".$species."\",".
		//	"class= \"".$class."\",".
		//	"phylum_id= \"".$phylum_id."\",".
		//	"name= \"".$name."\",".
		//	"notes= \"".$notes."\";";

		$sql = "INSERT INTO species (genus, species, class, phylum_id, name, notes) VALUES (?,?,?,?,?,?)";
		$query = $dbconn->prepare($sql);
		$query->execute([$genus, $species, $class, $phylum_id, $name, $notes]);		
		$species_id = $dbconn->lastInsertId();
//			$species_id = mysqli_insert_id();

		echo('<div class="subtitle">Added species record:</div>');

		echo('<table id="record">');
		echo('<tr><th style="text-align:right; width:33%">Species ID:</th><th style="text-align:left"><i>'.$species_id.'</i></th></tr>');
		echo('<tr><td style="text-align:right">Species:</td><td><i>'.$genus." ".$species.'</i></td></tr>');
		echo('<tr><td style="text-align:right">Phylum:</th><td>'.$phylum_id.'</td></tr>');
		echo('<tr><td style="text-align:right">Class:</td><td>'.$class.'</td></tr>');
		echo('<tr><td style="text-align:right">Common name:</td><td>'.$name.'</td></tr>');
		echo('<tr><td style="text-align:right">Notes:</td><td>'.$notes.'</td></tr>');
		echo('</table>');
	}
	
	if (!$error && count($synonyms) > 0) {
		foreach( $synonyms as $synonym ) {
			//$query="INSERT INTO synonyms set ".
			//	"species_id= \"".$species_id."\",".
			//	"synonym= \"".$synonym."\",".
			//	"notes= \"".$synonym_notes."\";";
			//
			//if (!mysqli_query($query)) {
			//	echo(mysqli_error());
			//} else {
			//	$synonym_id = mysqli_insert_id();
	
			$sql = "INSERT INTO synonyms (species_id, synonym, notes) VALUES (?,?,?)";
			$query = $dbconn->prepare($sql);
			$query->execute([$species_id, $synonym, $synonym_notes]);		
			$synonym_id = $dbconn->lastInsertId();
			echo('<div /><div class="subtitle">Added synonym record:</div>');
	
			echo('<table id="record">');
			echo('<tr><th style="text-align:right; width:33%">Synonym ID</th><th style="text-align:left"><i>'.$synonym_id.'</i></th></tr>');
			echo('<tr><td style="text-align:right">Species ID:</th><td>'.$species_id.'</td></tr>');
			echo('<tr><td style="text-align:right">Synonym:</td><td>'.$synonym.'</td></tr>');
			echo('<tr><td style="text-align:right">Notes:</td><td>'.$synonym_notes.'</td></tr>');
			echo('</table>');
		}
	}

	if (!$error && $species_id) {
							
		$new_species = true;
		$new_genus = true;
		$new_class = true;
		$new_phylum = true;
		
		//$result = mysqli_query("SELECT * FROM species WHERE id=".$species_id."",$db);
		//$species = mysqli_fetch_array($result);
		$query = $dbconn->prepare("SELECT * FROM species WHERE id=:s");
		$query->bindParam(':s', $species_id, PDO::PARAM_INT);
		$query->execute();
		$species = $query->fetch(PDO::FETCH_ASSOC);

		//$result = mysqli_query("SELECT * FROM phyla WHERE id=".$species['phylum_id']."",$db);
		//$phylum = mysqli_fetch_array($result);
		$query = $dbconn->prepare("SELECT * FROM phyla WHERE id=:p");
		$query->bindParam(':p', $species['phylum_id'], PDO::PARAM_INT);
		$query->execute();
		$phylum = $query->fetch(PDO::FETCH_ASSOC);

		//$result = mysqli_query("SELECT * FROM identifications WHERE year=$year", $db);
		//if ($ident = mysqli_fetch_array($result)) {
		$query = $dbconn->prepare("SELECT * FROM identifications WHERE year=:y");
		$query->bindParam(':y', $year, PDO::PARAM_INT);
		$query->execute();
		if ($ident = $query->fetch(PDO::FETCH_ASSOC)) {
			do {
				if ($ident['species_id'] == $species_id) {
					$new_species = false;
					$new_genus = false;
					$new_class = false;
					$new_phylum = false;
					break;
				} else {
					//$db_species = mysqli_fetch_array(mysqli_query("SELECT * FROM species WHERE id=\"".$ident["species_id"]."\"",$db));
					$query2 = $dbconn->prepare("SELECT * FROM species WHERE id=:s");
					$query2->bindParam(':s', $ident["species_id"], PDO::PARAM_INT);
					$query2->execute();
					$db_species = $query2->fetch(PDO::FETCH_ASSOC);
					
					if ($species['genus'] == $db_species['genus']) {
						$new_genus = false;
						$new_class = false;
						$new_phylum = false;						
					} else if ($species['class'] == $db_species['class']) {
						$new_class = false;
						$new_phylum = false;						
					} else if ($species['phylum_id'] == $db_species['phylum_id']) {
						$new_phylum = false;						
					}  
				}
			} while ($ident = $query->fetch(PDO::FETCH_ASSOC));	
		}

		//if ($ident = $query->fetch(PDO::FETCH_ASSOC)) {
		//	$species_found = [];
		//	$count = 0;
		//
		//	do {
		//		//$result2 = mysqli_query("SELECT * FROM species WHERE id=\"".$ident["species_id"]."\"",$db);
		//		//if ($species = mysqli_fetch_array($result2)) {
		//		$query2 = $dbconn->prepare("SELECT * FROM species WHERE id=:s");
		//		$query2->bindParam(':s', $ident["species_id"], PDO::PARAM_INT);
		//		$query2->execute();
		//		if ($species = $query2->fetch(PDO::FETCH_ASSOC)) {
		//			if (!isset($species_found[$species['id']])) {
		//				$count ++;
		//				$species_found[$species['id']] = true;
		//			}
		//		}
		//		$time = date_create($ident['time'])->getTimestamp(); 
		//		$diff = round(($time - $startTime) / 60);
		//		$array[$diff] = $count;
		//	} while ($ident = $query->fetch(PDO::FETCH_ASSOC));	
		//}
//
	
		foreach( $sites as $site ) {
			//$query="INSERT INTO identifications set ".
			//	"species_id= \"".$species_id."\",".
			//	"year= \"".$year."\",".
			//	"site= \"".$site."\",".
			//	"location= \"".$location."\",".
			//	"notes= \"".$id_notes."\",".
			//	"laboratory= \"".$lab."\",".
			//	"identified_by= \"".$identified_by."\",".
			//	"corroborated_by= \"".$corroborated_by."\";";
			//
			//if (!mysqli_query($query)) {
			//	echo(mysqli_error());
			//} else {
			//	$ident_id = mysqli_insert_id();
	
			$sql = "INSERT INTO identifications (species_id, year, site, location, notes, laboratory, identified_by, corroborated_by) VALUES (?,?,?,?,?,?,?,?)";
			$query = $dbconn->prepare($sql);
			$query->execute([$species_id, $year, $site, $location, $id_notes, $lab, $identified_by, $corroborated_by]);		
			$ident_id = $dbconn->lastInsertId();
			
			echo('<div /><div class="subtitle">Added identification record:</div>');
	
			echo('<table id="record">');
			echo('<tr><th style="text-align:right; width:33%">Identification ID</th><th style="text-align:left"><i>'.$ident_id.'</i></th></tr>');
			echo('<tr><td style="text-align:right">Species ID:</th><td>'.$species_id.'</td></tr>');
			echo('<tr><td style="text-align:right">Year:</th><td>'.$year.'</td></tr>');
			echo('<tr><td style="text-align:right">Site:</td><td>'.$site.'</td></tr>');
			echo('<tr><td style="text-align:right">Location:</td><td>'.$location.'</td></tr>');
			echo('<tr><td style="text-align:right">Notes:</td><td>'.$id_notes.'</td></tr>');
			echo('<tr><td style="text-align:right">Laboratory:</td><td>'.$lab.'</td></tr>');
			echo('<tr><td style="text-align:right">Identified by:</td><td>'.$identified_by.'</td></tr>');
			echo('<tr><td style="text-align:right">Corroborated by:</td><td>'.$corroborated_by.'</td></tr>');
			echo('</table>');
			
			echo('<div /><div class="title">The number for this identification is: '.$ident_id.' (record this on your card)</div>');
	
			$level = null;
			if ($new_phylum) {
				echo('<div /><div class="subtitle"><b>First identification of a member of phylum, '.$phylum['name'].', this year.</b></div>');
				//$sql="INSERT INTO latest set ".
				//	"species_id= \"".$species_id."\",".
				//	"level= \"phylum\",".
				//	"name= \"".$phylum['name']."\",".
				//	"year= \"".$year."\",".
				//	"laboratory= \"".$lab."\",".
				//	"ident_id= \"".$ident_id."\";";
				$level = "phylum";
				$value =  $phylum['name'];
			} else if ($new_class) {
				echo('<div /><div class="subtitle"><b>First identification of a member of class, '.$species['class'].' ('.$phylum['name'].'), this year.</b></div>');
				//sql="INSERT INTO latest set ".
				//	"species_id= \"".$species_id."\",".
				//	"level= \"class\",".
				//	"name= \"".$species['class']."\",".
				//	"year= \"".$year."\",".
				//	"laboratory= \"".$lab."\",".
				//	"ident_id= \"".$ident_id."\";";
				$level = "class";
				$value = $species['class'];
			} else if ($new_genus) {
				echo('<div /><div class="subtitle"><b>First identification of species of genus, <i>'.$species['genus'].'</i> ('.$species['class'].', '.$phylum['name'].'), this year.</b></div>');
				//$sql="INSERT INTO latest set ".
				//	"species_id= \"".$species_id."\",".
				//	"level= \"genus\",".
				//	"name= \"".$species['genus']."\",".
				//	"year= \"".$year."\",".
				//	"laboratory= \"".$lab."\",".
				//	"ident_id= \"".$ident_id."\";";
				$level = "genus";
				$value = $species['genus'];
			} else if ($new_species) {
				echo('<div /><div class="subtitle"><b>First identification of species, <i>'.getBinomial($species).'</i> ('.$species['class'].', '.$phylum['name'].'), this year.</b></div>');
				//$sql="INSERT INTO latest set ".
				//	"species_id= \"".$species_id."\",".
				//	"level= \"species\",".
				//	"name= \"".getBinomial($species)."\",".
				//	"year= \"".$year."\",".
				//	"laboratory= \"".$lab."\",".
				//	"ident_id= \"".$ident_id."\";";
				$level = "species";
				$value = getBinomial($species);
			}
			if ($level) {
				$sql = "INSERT INTO latest (species_id, level, name, year, laboratory, ident_id) VALUES (?,?,?,?,?,?)";
				$query = $dbconn->prepare($sql);
				$query->execute([$species_id, $level, $value, $year, $lab, $ident_id]);		
				$ident_id = $dbconn->lastInsertId();
			}
		}
	}

	if ($error) {
		echo('<div class="subtitle">Error: '.$errorMessage.'</div>');
		echo('<form action="add.html" method="get" enctype="multipart/form-data" id="add-form">');
		echo('<div class="subtitle"><input type="button" value="Back" onClick="history.go(-1);return true;" /></div>');
		echo('</form>');			
	} else {	
		echo('<form action="add.html" method="get">');
		echo('<input type="hidden" name="lab" id="lab" value="'.$lab.'" />');
		if ($year!=$current_year) echo('<input type="hidden" name="year" id="year" value="'.$year.'" />');
		if ($mode) echo('<input type="hidden" name="mode" id="mode" value="'.$mode.'" />');
		echo('<div class="subtitle"><input type="submit" value="Add another..." /></div>');
		echo('</form>');
	}
	
	//mysqli_close($db);

	writeFooter();
	finishPage();

?>

