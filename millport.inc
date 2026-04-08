<?php
	// error reporting
	ini_set('display_errors', 1);
	error_reporting(E_ALL);

	// setup variables
	define( "DBHOST", "localhost" );
	define( "DBUSER", "millport" );
	define( "DBPASS", "PIvAdEsTleyetyInGulE3" );
	define( "DBNAME", "millport" );

	include( 'GoogChart.class.php' );

		require '/var/www/lib/GoogleChart.php';
		require '/var/www/lib/markers/GoogleChartShapeMarker.php';
		

	$THEME = "millport";
	
	$current_year = 2026;
	$earliest_year = 2008;
	$earliest_year_ade = 2016;

	$phylum_weight = 5;
	$class_weight = 5;
	$genus_weight = 5;
	$species_weight = 5;
	$id_weight = 1;
	
	$max_location = 100;

	$dbconn;
	$settings;
	
	$marine_only = true;
	
	function GetStr($index) {
		return isset($_GET[$index]) ? $_GET[$index] : NULL;
	}
		
	function GetInt($index) {
		return isset($_GET[$index]) ? intval($_GET[$index]) : NULL;
	}

	function Get($index, $defaultValue) {
		return isset($_GET[$index]) ? $_GET[$index] : $defaultValue;
	}
		
	function PostStr($index) {
		return isset($_POST[$index]) ? $_POST[$index] : NULL;
	}
		
	function PostInt($index) {
		return isset($_POST[$index]) ? intval($_POST[$index]) : NULL;
	}
		
	function Post($index, $defaultValue) {
		return isset($_POST[$index]) ? $_POST[$index] : $defaultValue;
	}
		
	function connectToDatabase($database) {
		global $dbconn;
		global $settings;

		try { $dbconn = new PDO("mysql:host=" . DBHOST . ";dbname=$database", DBUSER, DBPASS); }
		catch( PDOException $e ) { echo $e->getMessage; }
		$dbconn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
		
		/*
		$db = mysqli_connect("localhost", "millport", "PIvAdEsTleyetyInGulE3");
		mysqli_select_db($db,$database);
		
		$result = mysqli_query($db,"SELECT * FROM Settings");
		if ($result && $setting = mysqli_fetch_array($result)) {
			do {
				$key = $setting["setting"];
				$value = $setting["value"];
				$settings[$key] = $value;
			} while ($setting = mysqli_fetch_array($result));
		}
		*/
	}

	function getSetting($setting) {
		global $settings;
		return $settings[$setting];
	}
	
	function startPage() {
		echo('
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
		');
	}

	function writeHeader($title, $keywords="", $description="", $feed_url="", $meta="") {
		global $THEME;

		echo('
	<head>	
		<title>'.$title.'</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta name="robots" content="none" />
		<meta name="keywords" content="'.$keywords.'" />
		<meta name="description" content="'.$description.'" />
		'.$meta.'
		<link rel="stylesheet"
			type="text/css"
			media="screen"
			href="themes/'.$THEME.'/styles.css" />
		<!--[if lte IE 6]>
					<style type="text/css" media="screen">  #pageHeader, #sidebarContainer, #footer {behavior: url(%pathto(iepngfix.htc)%);}</style>
		<![endif]-->
		<link rel="shortcut icon" href="http://tree.bio.ed.ac.uk/images/darwin.png" type="image/png" />
		<link rel="icon" href="http://tree.bio.ed.ac.uk/images/darwin.png" type="image/png" />
		');
		
		if ($feed_url) {
			echo('
	   <link rel="alternate"
	        type="application/rss+xml"
    	    title="'.$title.'"
        	href="'.$feed_url.'" />
      	  ');
        }

		echo('
	</head>
		');
	}

	function startBody($attributes = "") {
		echo('
	<body '.$attributes.'>
			');
	}
	
	function writeTitle($title, $subtitle, $image, $titleColour="#000000", $subtitleColour="#222222") {
		echo('
      <div id="pageHeader" style="background-image: url(/images/'.$image.'.png)">
        <!-- Start page header -->

        <h1 style="color: '.$titleColour.'"><a href="/millport/">'.$title.'</a></h1>

        <h2 style="color: '.$subtitleColour.'">'.$subtitle.'</h2>
      </div><!-- End page header -->
      ');
	}
	
	function writeClearer() {
	 	echo('
          <div class="clearer">&nbsp;</div>
          ');
    }
    
	function writeFooter($crumbs="", $root="millport") {
		writeClearer();
	 	echo('
	     <div id="footer">
          <!-- Start Footer -->

		  <div id="breadcrumbcontainer">
            <!-- Start the breadcrumb wrapper -->

            <ul>
            ');
            
        $path = "/".$root."/";
   	 	echo('
           <li><a href= "'.$path.'">'.$root.'</a></li>
               ');
               
			if ($crumbs) {
			   foreach ($crumbs as $crumb) { 
			   		$path = $path.$crumb."/";
					echo('
          <li>&nbsp;&gt;&nbsp;<a href= "'.$path.'">'.$crumb.'</a></li>
             	');
           		}
            }
	 	echo('
           </ul>
          </div><!-- End breadcrumb -->
        </div><!-- End container -->
      </div><!-- End Footer -->
    </div><!-- End wrapper -->
  </div><!-- End border -->
 			');
	}
		
	function finishPage() {
//		echo('
//		<script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
//		</script>
//		<script type="text/javascript">
//			_uacct = "UA-277246-2";
//			urchinTracker();
//		</script>
		echo('
	</body>
</html> 
		');
	}
	
	function writePublicationAuthors($publication, $db) {
		$authors1 = explode("&", $publication["authors"]);
		$authors = explode(",", $authors1[0]);
		if (sizeof($authors1) > 1) {
			array_push($authors, $authors1[1]);
		}
					
		$n = sizeof($authors);
		for ($i=0; $i < $n; $i++) {
			$author = trim($authors[$i]);
			list($name, $initials) = explode(" ", $author);
			$result = mysqli_query($db,"SELECT * FROM People WHERE lastName=\"$name\" AND initials=\"$initials\"");
			$person = mysqli_fetch_array($result);
			if ($person) {
				echo('<b><a href="/people.html?id='.$person[id].'">'.$authors[$i].'</a></b>');				
			} else {
				echo($author);
			}
			
			if ($i == ($n - 2)) {
				echo(' &amp; ');
			} else if ($i < ($n - 2)) {
				echo(', ');
			} 
		}
	}

	function writePublication($publication, $db) {
		$title = rtrim($publication["title"], ".");
		if ($publication["pdfLink"]) {
			echo('            
            <div style="float: left; margin: 4px"><a href="/downloadPaper.php?id='.$publication["id"].'"><img src="/images/pdf.png" width="16px"/></a></div>
			');
		}
		echo('<dl>
				<dt><a href="/publications/'.$publication["id"].'">'.$title.'.</a></dt>
				<dd>');
		writePublicationAuthors($publication, $db);
		if ($publication["pages"]) {
			printf(" (%s) <i>%s</i> <b>%s</b>, %s.\n", 
					$publication["year"], $publication["journal"], $publication["volume"], 
					$publication["pages"]);
		} else {
			printf(" (%s) <i>%s</i> <b>%s</b>\n", 
					$publication["year"], $publication["journal"], $publication["volume"]);
		}
		echo('
				</dd></dl>
			');
	}

	## #####################################################
	##  emailAddress()
	##  Modifies an email address to make it less spam-scraper-friendly
	##  usage: emailAddress($email[, $linkText])
	##  returns a complete mailto link
	## #####################################################
	function emailAddress($email) {
		if ($email == NULL) {
			return "Unknown email address";
		}
		$partA = substr($email,0, strpos($email,'@'));
		$partB = substr($email,strpos($email,'@'));
		$linkText = (func_num_args() == 2) ? func_get_arg(1) : $email;
		$linkText = str_replace('@', '<span >&#64;</span>', $linkText);
		return '<a href="#" onClick=\'a="'.$partA.'";this.href="mail"+"to:"+a+"'.$partB.'";\'>'.$linkText.'</a>';
	}		

	function summarize($paragraph, $limit,$link)
	{
		$tok = strtok($paragraph, " ");
		while($tok)
		{
			$text .= " $tok";
			$words++;
			if(($words >= $limit) && ((substr($tok, -1) == "!")||(substr($tok, -1) == ".")))
				break;
			$tok = strtok(" ");
		}
		$text .= ' '.$link;
		return ltrim($text);
	}
	
	function standardPage($title, $tab, $content, $keywords="", $description="", $meta = "") 
	{
		startPage();
		writeHeader($title, $keywords, $description, "", $meta);
	
		startBody();
		
		writeNavigation($tab);
		
		writeTitle($title, "canyon2", "#000000", "#111111");
	
		echo('
		  <div id="container">
			<div id="sidebarContainer">
			  <!-- Start Sidebar wrapper -->
	
			  <div id="navcontainer2">
				<!-- Start Navigation -->
			  </div><!-- End Navigation -->
			  ');
	
		echo('
			  <div class="sideHeader">'.$title.'</div><!-- Sidebar header -->
	
			  <div id="sidebar">
				<!-- Start sidebar content -->
	
				<p><a href="research/">Research themes and
				grants</a></p>
	
				<p><a href="people/">Researchers and
				students</a></p>
	
				<p><a href="software/">Evolution and phylogenetics
				Software</a></p>
	
				<p><a href="publications/">Publications from the
				group</a></p>
				<br />
				<div style="float:top">
				  <img class="imageStyle" width="180" src="images/strangelove.png" />
				</div>
			  </div><!-- End sidebar content -->
	
			  <div id="sideFooter">
				  <hr style="color: #666; background-color: #666; width: 80%; height: 1px; border: 0px"/>
				  <div style="text-align:center"><a href="/map.html">How to find us</a></div>
			  </div>
			</div><!-- End sidebar wrapper -->
			');
	
		echo('
			<div id="contentContainer">
			  <!-- Start main content wrapper -->
	
			  <div id="content">
			  ');
		echo($content);
		echo('</div><!-- End content -->
			</div><!-- End main content wrapper -->
			');
		writeFooter();
		finishPage();

	}
	
	function getPhylumSpeciesCounts($year = null, $site = null) {
		global $dbconn;
				
		//$result = mysqli_query($db,"SELECT * FROM Phyla");
		//if ($phylum = mysqli_fetch_array($result)) {
		$query = $dbconn->prepare("SELECT * FROM phyla");
		$query->execute();
		if ($phylum = $query->fetch(PDO::FETCH_ASSOC)) {
			do {
				if ($phylum["id"] != 18  && $phylum["id"] != 17) {
					$array[$phylum["name"]] = 0;
				}
			} while ($phylum = $query->fetch(PDO::FETCH_ASSOC));	
		}
	
		$query = "SELECT * FROM identifications";
		$first = true;
		if ($site != null) {
			$query = $query . ($first ? " WHERE" : " AND"). " site=$site";
			$first = false;
		}
		if ($year != null) {
			$query = $query . ($first ? " WHERE" : " AND"). " year=$year";
		}

		//$result = mysqli_query($db,$query);
		$result = $dbconn->prepare($query);
		$result->execute();
		if ($ident = $result->fetch(PDO::FETCH_ASSOC)) {
			do {
				//$result2 = mysqli_query($db,"SELECT * FROM species WHERE id=\"".$ident["species_id"]."\"");
				//if ($species = mysqli_fetch_array($result2)) {
				$query2 = $dbconn->prepare("SELECT * FROM species WHERE id=:id");
				$query2->bindParam(':id', $ident["species_id"], PDO::PARAM_INT);
				$query2->execute();
				if ($species = $query2->fetch(PDO::FETCH_ASSOC)) {
					//$result3 = mysqli_query($db,"SELECT * FROM phyla WHERE id=\"".$species["phylum_id"]."\"");
					//$phylum = mysqli_fetch_array($result3);
					$query3 = $dbconn->prepare("SELECT * FROM phyla WHERE id=:id");
					$query3->bindParam(':id', $species["phylum_id"], PDO::PARAM_INT);
					$query3->execute();
					$phylum = $query3->fetch(PDO::FETCH_ASSOC);
					if ($phylum  && $phylum["id"] != 18  && $phylum["id"] != 17) {
						
						if (!array_key_exists($phylum["name"], $array)) {
							$array[$phylum["name"]] = 1;
						} else {
							$array[$phylum["name"]] = $array[$phylum["name"]] + 1;						
						}
					}
				}
			} while ($ident = $result->fetch(PDO::FETCH_ASSOC));	
		}
		
		return $array;
	}
	
	function getDiversityIndex($year = null, $site = null, $phylum_id = null) {
		if ($phylum_id) {
			$species_counts = getClassSpeciesCounts($phylum_id, $year, $site);
		} else {
			$species_counts = getPhylumSpeciesCounts($year, $site);
		}
		
		$sum = 0;
		foreach ($species_counts as $count) {
			$sum += $count;
		}
		
		if ($sum == 0) {
			return 0.0;
		}

		$index = 0.0;
		foreach ($species_counts as $count) {
			if ($count > 0) {
				$p = $count / $sum;
		    	$index += $p * log($p);
		    }
		}
		
		$index = exp(-$index);
		
		return $index;
	}
	
	function getClassSpeciesCounts($phylum_id, $year = null, $site = null) {
		global $dbconn;
				
		$array = [];

		$query = "SELECT * FROM identifications";
		$first = true;
		if ($site != null) {
			$query = $query . ($first ? " WHERE" : " AND"). " site=$site";
			$first = false;
		}
		if ($year != null) {
			$query = $query . ($first ? " WHERE" : " AND"). " year=$year";
		}

		//$result = mysqli_query($db, $query );
		$result = $dbconn->prepare($query);
		$result->execute();
		if ($ident = $result->fetch(PDO::FETCH_ASSOC)) {
			do {
				//$result2 = mysqli_query($db,"SELECT * FROM species WHERE id=\"".$ident["species_id"]."\"");
				//if ($species = mysqli_fetch_array($result2)) {
				$query2 = $dbconn->prepare("SELECT * FROM species WHERE id=:id");
				$query2->bindParam(':id', $ident["species_id"], PDO::PARAM_INT);
				$query2->execute();
				if ($species = $query2->fetch(PDO::FETCH_ASSOC)) {
					if ($phylum_id == $species["phylum_id"]) {
						$array[$species['class']] = array();
					}
				}
			} while ($ident = $result->fetch(PDO::FETCH_ASSOC));	
		}	
		
		//$result = mysqli_query($db, $query );
		$result = $dbconn->prepare($query);
		$result->execute();
		if ($ident = $result->fetch(PDO::FETCH_ASSOC)) {
			do {
				//$result2 = mysqli_query($db,"SELECT * FROM species WHERE id=\"".$ident["species_id"]."\"");
				//if ($species = mysqli_fetch_array($result2)) {
				$query2 = $dbconn->prepare("SELECT * FROM species WHERE id=:id");
				$query2->bindParam(':id', $ident["species_id"], PDO::PARAM_INT);
				$query2->execute();
				if ($species = $query2->fetch(PDO::FETCH_ASSOC)) {
					if ($phylum_id == $species["phylum_id"]) {
						$class = $species['class'];
						$species_array = $array[$class];
						$species_array[$species['id']] = true;		
						$array[$class] = $species_array;
					}
				}
			} while ($ident = $result->fetch(PDO::FETCH_ASSOC));	
		}		
	
		$counts = [];
		
		foreach ($array as $class => $species_array) {
			$counts[$class] = count($species_array);
		}

		return $counts;
	}
	
	function getDiversity($year = null, $site = null) {
		global $dbconn;
				
		global $phylum_weight;
		global $class_weight;
		global $genus_weight;
		global $species_weight;
		global $max_location;
				
		$phylum_found = [];
		$class_found = [];
		$genus_found = [];
		$species_found = [];
		
		$query = "SELECT * FROM identifications";
		$first = true;
		if ($site != null) {
			$query = $query . ($first ? " WHERE" : " AND"). " site=$site";
			$first = false;
		} else {
			$query = $query . ($first ? " WHERE" : " AND"). " site<$max_location";
			$first = false;
		}
		if ($year != null) {
			$query = $query . ($first ? " WHERE" : " AND"). " year=$year";
		}

		$id_count = 0;
		
		//$result = mysqli_query($db, $query );
		$result = $dbconn->prepare($query);
		$result->execute();
		if ($ident = $result->fetch(PDO::FETCH_ASSOC)) {
			do {
				//$result2 = mysqli_query($db,"SELECT * FROM species WHERE id=\"".$ident["species_id"]."\"");
				//if ($species = mysqli_fetch_array($result2)) {
				$query2 = $dbconn->prepare("SELECT * FROM species WHERE id=:id");
				$query2->bindParam(':id', $ident["species_id"], PDO::PARAM_INT);
				$query2->execute();
				if ($species = $query2->fetch(PDO::FETCH_ASSOC)) {

					$genus = $species['genus'];

					if (strtoupper($genus) != "UNKNOWN" && $genus != "?") {
						if (!isset($genus_found[$genus])) {
							$genus_found[$genus] = true;
						}

						if ($species['species'] && 
							strtoupper($species['species']) != "SP" &&
							strtoupper($species['species']) != "UNKNOWN" &&
										$species['species'] != "?" ) {
							if (!isset($species_found[$species['species']])) {
								$species_found[$species['species']] = true;
							}
						}
					}

					if (!isset($class_found[$species['class']]) && 
							strtoupper($species['class']) != "UNKNOWN" &&
										$species['class'] != "?" ) {
						$class_found[$species['class']] = true;
					}
					
					if (!isset($phylum_found[$species['phylum_id']]) && $species['phylum_id'] != 18  && $species['phylum_id'] != 17) {
						$phylum_found[$species['phylum_id']] = true;
					}
				}
				
				$id_count ++;
			} while ($ident = $result->fetch(PDO::FETCH_ASSOC));	
		}
		
		$score = count($phylum_found) * $phylum_weight +
					count($class_found) * $class_weight +
					count($genus_found) * $genus_weight +
					count($species_found) * $species_weight;
		
		$diversity = getDiversityIndex($year, $site);
		return array("phyla" => count($phylum_found),
						"classes" => count($class_found),
						"genera" => count($genus_found),
						"species" => count($species_found),
						"ids" => $id_count,
						"score" => $score,
						"diversity" => $diversity
						);
	}
	
	function getUniqueDiversity($year = null) {
		global $dbconn;
		global $current_year;
		global $max_location;
	
		if ($year == null) {
			$year = $current_year;
		}

		$phylum_found = [];
		$class_found = [];
		$genus_found = [];
		$species_found = [];

		//$result = mysqli_query($db,"SELECT * FROM identifications WHERE year=$year AND site<$max_location");
		//if ($ident = mysqli_fetch_array($result)) {
		$query = $dbconn->prepare("SELECT * FROM identifications WHERE year=:y AND site<:max");
		$query->bindParam(':y', $id, PDO::PARAM_INT);
		$query->bindParam(':max', $max_location, PDO::PARAM_INT);
		$query->execute();
		if ($ident = $query->fetch(PDO::FETCH_ASSOC)) {								
			do {
				//$result2 = mysqli_query($db,"SELECT * FROM species WHERE id=\"".$ident["species_id"]."\"");
				//if ($species = mysqli_fetch_array($result2)) {
				$query2 = $dbconn->prepare("SELECT * FROM species WHERE id=:id");
				$query2->bindParam(':id', $ident["species_id"], PDO::PARAM_INT);
				$query2->execute();
				if ($species = $query2->fetch(PDO::FETCH_ASSOC)) {
					if ($species['phylum_id'] != 18  && $species['phylum_id'] != 17) {
					
						$class = $species['class'];
						if (strtoupper($class) != "UNKNOWN" && $class != "?" && $class != "" && $class != null) {
							$class_found[$class] = true;
						}

						$genus = $species['genus'];

						if (strtoupper($genus) != "UNKNOWN" && $genus != "?" && $genus != "" && $genus != null) {
							$genus_found[$genus] = true;
			
							if ($species['species'] && 
								strtoupper($species['species']) != "SP" &&
								strtoupper($species['species']) != "UNKNOWN" &&
											$species['species'] != "?" &&
											$species['species'] != "" &&
											$species['species'] != null) {
								$species_found[getBinomial($species)] = true;
							}
						}

				
						$phylum_found[$species['phylum_id']] = true;
					}
				}
				
			} while ($ident = $query->fetch(PDO::FETCH_ASSOC));	
		}
		
		//$result = mysqli_query($db,"SELECT * FROM identifications WHERE year=$year AND site<$max_location");
		//if ($ident = mysqli_fetch_array($result)) {
		$query = $dbconn->prepare("SELECT * FROM identifications WHERE year=:y AND site<:max");
		$query->bindParam(':y', $id, PDO::PARAM_INT);
		$query->bindParam(':max', $max_location, PDO::PARAM_INT);
		$query->execute();
		if ($ident = $query->fetch(PDO::FETCH_ASSOC)) {
			do {
				//$result2 = mysqli_query($db,"SELECT * FROM species WHERE id=\"".$ident["species_id"]."\"");
				//if ($species = mysqli_fetch_array($result2)) {
				$query2 = $dbconn->prepare("SELECT * FROM species WHERE id=:id");
				$query2->bindParam(':id', $ident["species_id"], PDO::PARAM_INT);
				$query2->execute();
				if ($species = $query2->fetch(PDO::FETCH_ASSOC)) {
					unset($genus_found[$species['genus']]);
					unset($species_found[getBinomial($species)]);
					unset($class_found[$species['class']]);					
					unset($phylum_found[$species['phylum_id']]);
				}
				
			} while ($ident = $query->fetch(PDO::FETCH_ASSOC));	
		}
		
		//print_r($species_found);
		//print_r($genus_found);
		//print_r($class_found);
		//print_r($phylum_found);
		
		return array("phyla" => count($phylum_found),
						"classes" => count($class_found),
						"genera" => count($genus_found),
						"species" => count($species_found));
	}
	

	function getCumulativeSpeciesCounts($year = null) {
		global $dbconn;
		global $current_year;
				
		if ($year == null) {
			$year = $current_year;
		}

		$count = 0;
		$array = [];
		
		//$result = mysqli_query($dbconn,"SELECT * FROM identifications WHERE year=$year ORDER BY time ASC");
		//if ($ident = mysqli_fetch_array($result)) {
		
		$query = $dbconn->prepare("SELECT * FROM identifications WHERE year=:y ORDER BY time ASC");
		$query->bindParam(':y', $year, PDO::PARAM_INT);
		$query->execute();
		if ($ident = $query->fetch(PDO::FETCH_ASSOC)) {
			$startTime = date_create($ident['time'])->getTimestamp(); 

			$species_found = [];
			do {
				$time = date_create($ident['time'])->getTimestamp(); 
				$diff = round(($time - $startTime) / 60);
				//$result2 = mysqli_query($db,"SELECT * FROM species WHERE id=\"".$ident["species_id"]."\"");
				//if ($species = mysqli_fetch_array($result2)) {
				$query2 = $dbconn->prepare("SELECT * FROM species WHERE id=:id");
				$query2->bindParam(':id', $ident["species_id"], PDO::PARAM_INT);
				$query2->execute();
				if ($species = $query2->fetch(PDO::FETCH_ASSOC)) {
					if (!isset($species_found[$species['id']])) {
						$count ++;
						$array[$diff] = $count;
						$species_found[$species['id']] = true;
					}
				}
			} while ($ident = $query->fetch(PDO::FETCH_ASSOC));	
		}
		
		return $array;
	}
	
	function getCumulativeIdentCounts($year = null) {
		global $dbconn;
		global $current_year;
				
		if ($year == null) {
			$year = $current_year;
		}

		$count = 0;
		//$result = mysqli_query($db,"SELECT * FROM identifications WHERE year=$year ORDER BY time ASC");
		$query = $dbconn->prepare("SELECT * FROM identifications WHERE year=:y ORDER BY time ASC");
		$query->bindParam(':y', $year, PDO::PARAM_INT);
		$query->execute();
		if ($ident = $query->fetch(PDO::FETCH_ASSOC)) {
			$startTime = date_create($ident['time'])->getTimestamp(); 

			do {
				$time = date_create($ident['time'])->getTimestamp(); 
				$diff = round(($time - $startTime) / 60);
				$count ++;
				$array[$diff] = $count;
			} while ($ident = $query->fetch(PDO::FETCH_ASSOC));	
		}
		
		return $array;
	}
	
	
	function drawBarChart($data, $width, $height) {		
		
		foreach ($data as $key => $count) {
			$values[] = $count;
		}

		foreach ($data as $key => $count) {
			$labels[] = $key;
		}

		$chart = new GoogleChart('bhs', $width, $height);
//		$chart->setAutoscale(GoogleChart::AUTOSCALE_VALUES);
		$chart->setFill('00000000');
		$chart->setFill('00000000', GoogleChart::CHART_AREA);
		$chart->setMargin(5);
			
//		$bars = new GoogleChartData($values);
//		$bars->setColor(array('54C7C588'));
//		$chart->addData($bars);
		$chart->addData(new GoogleChartData($values));

		$y_axis = new GoogleChartAxis('y');
		$y_axis->setLabels($labels);
		$y_axis->setDrawLine(false);
		$y_axis->setDrawTickMarks(false);
		$y_axis->setFontSize(10);
//		$y_axis->setTickMarks(5);
		$y_axis->setTickColor('444444');
		$y_axis->setLabelColor('444444');
		$y_axis->setRange(0, 20);
		$chart->addAxis($y_axis);

		$x_axis = new GoogleChartAxis('x');
		$x_axis->setFontSize(10);
		$x_axis->setLabelColor('444444');
		$chart->addAxis($x_axis);


		echo '<img src="'.$chart->getUrl().'" />';
	}


	function drawPieChart($data, $width, $height) {		
		
		foreach ($data as $key => $count) {
			if ($count == 0) {
				$labels[$key] = null;
			} else {
				$labels[$key] = $key;
			}
		}

		$chart = new GoogleChart('pc', $width, $height);
		$chart->setAutoscale(GoogleChart::AUTOSCALE_VALUES);
		$chart->setFill('00000000');
		$chart->setFill('00000000', GoogleChart::CHART_AREA);
		$chart->setMargin(5);
			
		
		$pie = new GoogleChartData($data);
		$pie->setThickness(0);
		$pie->setColor(array('88000088', 'FF000088', 'FFFF0088', '00FF0088', '00FFFF88', '0000FF88', '00008888'));
		$chart->addData($pie);


		$x_axis = new GoogleChartAxis('x');
		$x_axis->setLabels($labels);
		$x_axis->setFontSize(10);
		$x_axis->setLabelColor('444444');
		$chart->addAxis($x_axis);

		echo '<img src="'.$chart->getUrl().'" />';
	}

	function drawTimeChart($data, $data2, $width, $height, $x_range, $y_range) {		
		
		$chart = new GoogleChart('lc', $width, $height);
		$chart->setAutoscale(GoogleChart::AUTOSCALE_VALUES);
		$chart->setGridLines(0,50, 3,2);
		$chart->setFill('00000000');
		$chart->setFill('00000000', GoogleChart::CHART_AREA);
		$chart->setMargin(5);
		
		$line2 = new GoogleChartData($data);
		$line2->setThickness(1);
		$line2->setColor('226633');
		$line2->setFill('33885588');
		$chart->addData($line2);

		$line = new GoogleChartData($data2);
		$line->setThickness(2);
		$line->setColor('662233');
//		$line->setFill('88335588');
		$chart->addData($line);
				
		$y_axis = new GoogleChartAxis('y');
		$y_axis->setDrawLine(false);
		$y_axis->setDrawTickMarks(false);
		$y_axis->setLabels(array(null,35,70));
		$y_axis->setFontSize(10);
		$y_axis->setTickMarks(5);
		$y_axis->setTickColor('444444');
		$y_axis->setLabelColor('444444');
		$y_axis->setRange(0, $y_range);
		$chart->addAxis($y_axis);
		
		$x_axis = new GoogleChartAxis('x');
		$x_axis->setDrawLine(false);
//		$x_axis->setLabels(array('27 apr','04 may','11 may','18 may'));
//		$x_axis->setLabelPositions(0,25.8,51.8,77.6);
		$x_axis->setTickMarks(5);
		$x_axis->setFontSize(10);
		$x_axis->setTickColor('444444');
		$x_axis->setLabelColor('444444');
		$x_axis->setRange(0, $x_range);
		$chart->addAxis($x_axis);
		
		echo '<img src="'.$chart->getUrl().'" />';
	}
	
	function writeSpecies($species) {
		$class = ($species["class"] ? " (class ".$species["class"].")" : "");
		$name = ($species["name"] ? " - ".$species["name"] : "");
		printf("<a href=\"species.html?id=%s\" rel=\"self\"><i>%s</i> %s %s</a>\n", 
					$species["id"], 
					getBinomial($species), 
					$class, 
					$name
					);

	}
	
	function getBinomial($species) {
		if ($species["species"]) {
 			return $species["genus"] ." ". $species["species"];
 		} else {
 			return $species["genus"] ." sp.";
 		}
	}

?>
