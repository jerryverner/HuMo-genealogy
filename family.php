<?php
// *************************************************************
// *** Family                                                ***
// *************************************************************
//error_reporting(E_ALL);
$screen_mode='';
if (isset($_POST["screen_mode"]) AND $_POST["screen_mode"]=='PDF'){ $screen_mode='PDF'; }
if (isset($_POST["screen_mode"]) AND $_POST["screen_mode"]=='RTF'){ $screen_mode='RTF'; }
if (isset($_GET["screen_mode"]) AND $_GET["screen_mode"]=='STAR'){ $screen_mode='STAR'; }
if (isset($_GET["screen_mode"]) AND $_GET["screen_mode"]=='STARSIZE'){ $screen_mode='STARSIZE'; }
$hourglass=false;
if (isset($_GET["screen_mode"]) AND $_GET["screen_mode"]=='HOUR'){ $screen_mode='STAR'; $hourglass=true; }
if (isset($_GET["screen_mode"]) AND $_GET["screen_mode"]=='HOURSTARSIZE'){ $screen_mode='STARSIZE'; $hourglass=true; }

$pdf_source= array();  // is set in show_sources.php with sourcenr as key to be used in source appendix
// see end of this code
global $chosengen, $genarray, $size, $keepfamily_id, $keepmain_person, $direction;
global $pdf_footnotes;

//global $temp,$templ_person;
//global $templ_relation;
global $templ_name;

include_once("header.php"); // returns CMS_ROOTPATH constant

// *** "Last visited" id is used for contact form ***
$last_visited=$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
$_SESSION['save_last_visitid']=$last_visited;

@set_time_limit(300);

$menu=0;
if(isset($_GET['menu']) AND $_GET['menu']=="1") { $menu = 1; }  // called from fanchart iframe with &menu=1-> no menu!

if($screen_mode!='PDF' AND $menu!=1) {  //we can't have a menu in pdf... and don't want it when called in an iframe
	include_once(CMS_ROOTPATH."menu.php");
}

if($screen_mode=='PDF') {  // if PDF: necessary parts from menu.php
	include_once(CMS_ROOTPATH."include/db_functions_cls.php");
	$db_functions = New db_functions;

	if (isset($_SESSION['tree_prefix'])){
		$dataqry = "SELECT * FROM humo_trees LEFT JOIN humo_tree_texts
		ON humo_trees.tree_id=humo_tree_texts.treetext_tree_id
		AND humo_tree_texts.treetext_language='".$selected_language."'
		WHERE tree_prefix='".$tree_prefix_quoted."'";
		@$datasql = $dbh->query($dataqry);
		@$dataDb = $datasql->fetch(PDO::FETCH_OBJ);
	}

	$tree_id=$dataDb->tree_id;
	$db_functions->set_tree_id($tree_id);
}

include_once(CMS_ROOTPATH."include/language_date.php");
include_once(CMS_ROOTPATH."include/language_event.php");
include_once(CMS_ROOTPATH."include/date_place.php");
include_once(CMS_ROOTPATH."include/process_text.php");
include_once(CMS_ROOTPATH."include/calculate_age_cls.php");
include_once(CMS_ROOTPATH."include/person_cls.php");
include_once(CMS_ROOTPATH."include/marriage_cls.php");
include_once(CMS_ROOTPATH."include/show_sources.php");
include_once(CMS_ROOTPATH."include/witness.php");
include_once(CMS_ROOTPATH."include/show_addresses.php");
include_once(CMS_ROOTPATH."include/show_picture.php");
include_once(CMS_ROOTPATH."include/show_quality.php");

// *** Show person/ family topline: family top text, pop-up settings, PDF export, favourite ***
function topline(){
	global $dataDb, $bot_visit, $descendant_loop, $parent1_marr, $rtlmarker, $family_id, $main_person;
	global $alignmarker, $language, $uri_path, $descendant_report, $family_expanded;
	global $user, $source_presentation, $swap_parent1_parent2, $maps_presentation, $picture_presentation, $text_presentation;
	global $database, $parent1_cls, $parent1Db, $parent2_cls, $parent2Db, $selected_language;
	global $tree_id,$humo_option;

	//$text='<tr class="table_headline"><td class="table_header" width="65%">';
	$text='<tr class="table_headline"><td class="table_header">';

	// *** Text above family ***
	$treetext=show_tree_text($dataDb->tree_id, $selected_language);
	$text.='<div class="family_page_toptext fonts">'.$treetext['family_top'].'<br></div>';

	//$text.='</td><td class="table_header fonts" width="12%" style="text-align:center";>';
	$text.='</td><td class="table_header fonts" width="130" style="text-align:right";>';

	// *** Hide selections for bots, and second family screen (descendant report etc.) ***
	if (!$bot_visit AND $descendant_loop==0 AND $parent1_marr==0){

		// *** Settings in pop-up screen ***
		//$text.= '<div class="'.$rtlmarker.'sddm" style="left:10px;top:10px;display:inline;">';
		$text.= '<div class="'.$rtlmarker.'sddm" style="left:10px; top:10px; display:inline-block; vertical-align:middle;">';

			// *** Use url_rewrite ***
			if ($humo_option["url_rewrite"]=="j"){
				// *** $uri_path made in header.php ***
				$settings_url=$uri_path.'family/'.$tree_id.'/'.$family_id; $url_add='?';
				if ($main_person){
					$settings_url.='?main_person='.$main_person; $url_add='&amp;';
				}
			}
			else{
				$settings_url=CMS_ROOTPATH.'family.php?tree_id='.$tree_id.'&amp;id='.$family_id;
				if ($main_person){
					$settings_url.='&amp;main_person='.$main_person;
				}
				$url_add='&amp;';
			}

			$text.= '<a href="'.$settings_url.'" style="display:inline" ';
			$text.= 'onmouseover="mopen(event,\'help_menu\',0,0)"';
			$text.= 'onmouseout="mclosetime()">';
			$text.= '<img src="images/settings.png" alt="'.__('Settings').'">';
			$text.= '</a> ';

			//$text='<div style="z-index:40; padding:4px; direction:'.$rtlmarker.'" id="help_menu" onmouseover="mcancelclosetime()" onmouseout="mclosetime()">';
			$text.='<div class="sddm_fixed" style="z-index:10; padding:4px; text-align:'.$alignmarker.';  direction:'.$rtlmarker.';" id="help_menu" onmouseover="mcancelclosetime()" onmouseout="mclosetime()">';

			$text.='<span style="color:blue">=====</span>&nbsp;<b>'.__('Settings family screen').'</b> <span style="color:blue">=====</span><br><br>';

			$text.='<table><tr><td>';

				// *** Extended view button ***
				$text.='<b>'.__('Family Page').'</b><br>';

				$desc_rep = ''; if($descendant_report==true) { $desc_rep = '&amp;descendant_report=1'; }

				$selected=' CHECKED'; $selected2=''; if ($family_expanded==true) { $selected=''; $selected2=' CHECKED'; }
				$text.='<input type="radio" name="keuze0" value="" onclick="javascript: document.location.href=\''.$settings_url.$url_add.'family_expanded=0'.$desc_rep.'&xx=\'+this.value"'.$selected.'>'.__('Compact view')."<br>\n";

				$text.='<input type="radio" name="keuze0" value="" onclick="javascript: document.location.href=\''.$settings_url.$url_add.'family_expanded=1'.$desc_rep.'&xx=\'+this.value"'.$selected2.'>'.__('Expanded view')."<br>\n";

				// *** Select source presentation (as title/ footnote or hide sources) ***
				if($user['group_sources']!='n') {
					$text.='<hr>';
					$text.='<b>'.__('Sources').'</b><br>';
					$desc_rep = ''; if($descendant_report==true) { $desc_rep = '&amp;descendant_report=1'; }

					$selected=''; if ($source_presentation=='title') { $selected=' CHECKED'; }
					$text.='<input type="radio" name="keuze1" value="" onclick="javascript: document.location.href=\''.$settings_url.$url_add.'source_presentation=title'.$desc_rep.'&xx=\'+this.value"'.$selected.'>'.__('Show source')."<br>\n";

					$selected=''; if ($source_presentation=='footnote') { $selected=' CHECKED'; }
					$text.='<input type="radio" name="keuze1" value="" onclick="javascript: document.location.href=\''.$settings_url.$url_add.'source_presentation=footnote'.$desc_rep.'&xx=\'+this.value"'.$selected.'>'.__('Show source as footnote')."<br>\n";

					$selected=''; if ($source_presentation=='hide') { $selected=' CHECKED'; }
					$text.='<input type="radio" name="keuze1" value="" onclick="javascript: document.location.href=\''.$settings_url.$url_add.'source_presentation=hide'.$desc_rep.'&xx=\'+this.value"'.$selected.'>'.__('Hide sources')."<br>\n";
				}

				// *** Show/ hide Google maps ***
				if($descendant_report==false) {
					// *** Only show selection if there is a Google maps database ***
					global $dbh;
					$temp = $dbh->query("SHOW TABLES LIKE 'humo_location'");
					if($temp->rowCount()) {
						$text.='<hr><b>'.__('Google maps').'</b><br>';
						$selected=''; $selected2=''; if ($maps_presentation=='hide') $selected2=' CHECKED'; else $selected=' CHECKED';

						$text.='<input type="radio" name="keuze2" value="" onclick="javascript: document.location.href=\''.$settings_url.$url_add.'maps_presentation=show&xx=\'+this.value"'.$selected.'>'.__('Show Google maps')."<br>\n";

						$text.='<input type="radio" name="keuze2" value="" onclick="javascript: document.location.href=\''.$settings_url.$url_add.'maps_presentation=hide&xx=\'+this.value"'.$selected2.'>'.__('Hide Google maps')."<br>\n";
					}
				}

			$text.='</td><td valign="top">';

				if ($user['group_pictures']=='j'){
					$text.='<b>'.__('Pictures').'</b><br>';
					$selected=''; $selected2=''; if ($picture_presentation=='hide') $selected2=' CHECKED'; else $selected=' CHECKED';

					$text.='<input type="radio" name="keuze3" value="" onclick="javascript: document.location.href=\''.$settings_url.$url_add.'picture_presentation=show'.$desc_rep.'&xx=\'+this.value"'.$selected.'>'.__('Show pictures')."<br>\n";

					$text.='<input type="radio" name="keuze3" value="" onclick="javascript: document.location.href=\''.$settings_url.$url_add.'picture_presentation=hide'.$desc_rep.'&xx=\'+this.value"'.$selected2.'>'.__('Hide pictures')."<br>\n";

					$text.='<hr>';
				}

				$text.='<b>'.__('Texts').'</b><br>';
				$selected=''; if ($text_presentation=='show') $selected=' CHECKED';
				$text.='<input type="radio" name="keuze4" value="" onclick="javascript: document.location.href=\''.$settings_url.$url_add.'text_presentation=show'.$desc_rep.'&xx=\'+this.value"'.$selected.'>'.__('Show texts')."<br>\n";

				$selected=''; if ($text_presentation=='popup') $selected=' CHECKED';
				$text.='<input type="radio" name="keuze4" value="" onclick="javascript: document.location.href=\''.$settings_url.$url_add.'text_presentation=popup'.$desc_rep.'&xx=\'+this.value"'.$selected.'>'.__('Show texts in popup screen')."<br>\n";

				$selected=''; if ($text_presentation=='hide') $selected=' CHECKED';
				$text.='<input type="radio" name="keuze4" value="" onclick="javascript: document.location.href=\''.$settings_url.$url_add.'text_presentation=hide'.$desc_rep.'&xx=\'+this.value"'.$selected.'>'.__('Hide texts')."<br>\n";

			$text.='</td></tr></table>';

			$text.='</div>';
		$text.='</div>';

	//$text.='</td><td class="table_header fonts" width="20%" style="text-align:center";>';

		// *** PDF button ***
		if($user["group_pdf_button"]=='y' AND $language["dir"]!="rtl" AND $language["name"]!="简体中文") {
			$text.=' <form method="POST" action="'.$uri_path.'family.php?show_sources=1" style="display:inline-block; vertical-align:middle;">';
				$text.='<input type="hidden" name="id" value="'.$family_id.'">';
				$text.='<input type="hidden" name="main_person" value="'.$main_person.'">';
				$text.='<input type="hidden" name="database" value="'.$database.'">';
				$text.='<input type="hidden" name="screen_mode" value="PDF">';
				if($descendant_report==true) {
					$text.='<input type="hidden" name="descendant_report" value="'.$descendant_report.'">';
				}
				//$text.='<input class="fonts" type="Submit" name="submit" value="'.__('PDF Report').'">';
				//$text.='<input type="image" src="images/pdf.jpeg" width="20" border="0" alt="PDF Report">';
				$text.='<input class="fonts" style="background-color:#FF0000; color:white; font-weight:bold;" type="Submit" name="submit" value="'.__('PDF').'">';
			$text.='</form> ';
		}

		// *** RTF button ***
		if($user["group_rtf_button"]=='y' AND $language["dir"]!="rtl") {
			$text.='<form method="POST" action="'.$uri_path.'family.php?show_sources=1" style="display:inline-block; vertical-align:middle;">';
				$text.='<input type="hidden" name="id" value="'.$family_id.'">';
				$text.='<input type="hidden" name="main_person" value="'.$main_person.'">';
				$text.='<input type="hidden" name="database" value="'.$database.'">';
				$text.='<input type="hidden" name="screen_mode" value="RTF">';
				if($descendant_report==true) {
					$text.='<input type="hidden" name="descendant_report" value="'.$descendant_report.'">';
				}
				//$text.='<input class="fonts" type="Submit" name="submit" value="'.__('RTF Report').'">';
				$text.='<input class="fonts" style="background-color:#0040FF; color:white; font-weight:bold;" type="Submit" name="submit" value="'.__('RTF').'">';
			$text.='</form> ';
		}

	//$text.='</td><td class="table_header fonts" width="5%" style="text-align:center";>';

		// *** Add family to favourite list ***
		// If there is a N.N. father, then use mother in favourite icon.
		//if ($swap_parent1_parent2==true OR !isset($parent1Db->pers_gedcomnumber)){
		if (!isset($parent1Db->pers_gedcomnumber)){
			$name=$parent2_cls->person_name($parent2Db);
			$favorite_gedcomnumber=' ['.$parent2Db->pers_gedcomnumber.']';
		}
		else{
			$name=$parent1_cls->person_name($parent1Db);
			$favorite_gedcomnumber=' ['.$parent1Db->pers_gedcomnumber.']';
		}

		if ($name){
			$favorite_values=$name['name'].$favorite_gedcomnumber.'|'.$family_id.'|'.$_SESSION['tree_prefix'].'|'.$main_person;
			$check=false;
			if (isset($_SESSION['save_favorites'])){
				foreach($_SESSION['save_favorites'] as $key=>$value){
					if ($value==$favorite_values){ $check=true; }
				}
			}
			$text.='<form method="POST" action="'.$uri_path.'family.php" style="display:inline-block; vertical-align:middle;">';
			$text.='<input type="hidden" name="id" value="'.$family_id.'">';
			$text.='<input type="hidden" name="main_person" value="'.$main_person.'">';

			if ($descendant_report==true){ echo '<input type="hidden" name="descendant_report" value="1">'; }
			if ($check==false){
				$text.='<input type="hidden" name="favorite" value="'.$favorite_values.'">';
				$text.=' <input type="image" src="images/favorite.png" name="favorite_button" alt="'.__('Add to favourite list').'" />';
			}
			else{
				$text.='<input type="hidden" name="favorite_remove" value="'.$favorite_values.'">';
				$text.=' <input type="image" src="images/favorite_blue.png" name="favorite_button" alt="'.__('Add to favourite list').'" />';
			}
			$text.='</form>';
		}

	} // End of bot visit

	$text.='</td></tr>';

	return $text;
}


$family_nr=1;  // *** process multiple families ***

$family_id='F1'; // *** standard: show first family ***
//if (isset($urlpart[1])){ $family_id=$urlpart[1]; }
if (isset($_GET["id"])){ $family_id=$_GET["id"]; }
if (isset($_POST["id"])){ $family_id=$_POST["id"]; }

$main_person=''; // *** Mainperson of a family ***
//if (isset($urlpart[2])){ $main_person=$urlpart[2]; }
if (isset($_GET["main_person"])){ $main_person=$_GET["main_person"]; }
if (isset($_POST["main_person"])){ $main_person=$_POST["main_person"]; }

// *** A favourite ID is used ***
if (isset($_POST["humo_favorite_id"])){
	$favorite_array_id=explode("|",$_POST["humo_favorite_id"]);
	$family_id=$favorite_array_id[0];
	$main_person=$favorite_array_id[1];
}

// *** Check if family gedcomnumber is valid ***
$db_functions->check_family($family_id);

// *** Check if person gedcomnumber is valid ***
$db_functions->check_person($main_person);


if($screen_mode=='STAR' OR $screen_mode=='STARSIZE') {
	$dna = "none"; // DNA setting
	if (isset($_GET["dnachart"])){ $dna=$_GET["dnachart"]; }
	if (isset($_POST["dnachart"])){ $dna=$_POST["dnachart"]; }
	$chosengen=4; if($dna!="none") $chosengen="All"; // in DNA chart by default show all generations
	if (isset($_GET["chosengen"])){ $chosengen=$_GET["chosengen"]; }
	if (isset($_POST["chosengen"])){ $chosengen=$_POST["chosengen"]; }
	$chosengenanc=4;  // for hourglass -- no. of generations of ancestors
	if (isset($_GET["chosengenanc"])){ $chosengenanc=$_GET["chosengenanc"]; }
	if (isset($_POST["chosengenanc"])){ $chosengenanc=$_POST["chosengenanc"]; }
	if(isset($_SESSION['chartsize'])) { $size=$_SESSION['chartsize']; } 
	else { $size=50; if($dna!="none") $size=25;} // in DNA chart by default zoom position 4
	if (isset($_GET["chosensize"])){ $size=$_GET["chosensize"]; }
	if (isset($_POST["chosensize"])){ $size=$_POST["chosensize"]; }
	$_SESSION['chartsize']=$size;
	$keepfamily_id=$family_id;
	$keepmain_person=$main_person;
	$direction=0; // vertical
	if (isset($_GET["direction"])){ $direction=$_GET["direction"]; }
	if (isset($_POST["direction"])){ $direction=$_POST["direction"]; }

	if($dna!="none") {
		if (isset($_GET["bf"])){ $base_person_famc=$_GET["bf"]; }
		if (isset($_POST["bf"])){ $base_person_famc=$_POST["bf"]; }
		if (isset($_GET["bs"])){ $base_person_sexe=$_GET["bs"]; }
		if (isset($_POST["bs"])){ $base_person_sexe=$_POST["bs"]; }
		if (isset($_GET["bn"])){ $base_person_name=$_GET["bn"]; }
		if (isset($_POST["bn"])){ $base_person_name=$_POST["bn"]; }
		if (isset($_GET["bg"])){ $base_person_gednr=$_GET["bg"]; }
		if (isset($_POST["bg"])){ $base_person_gednr=$_POST["bg"]; }
	}
}

if($screen_mode=='STARSIZE') {
	if (isset($_SESSION['genarray'])){ $genarray=$_SESSION['genarray']; }
}

if($screen_mode!='STAR' AND $screen_mode!='STARSIZE') {
	// ***************************************************************
	// *** Descendant report                                       ***
	// ***************************************************************
	// == define numbers (max. 60 generations)
	$number_roman = array( 1=>'I', 2=>'II', 3=>'III', 4=>'IV', 5=>'V', 6=>'VI', 7=>'VII', 8=>'VIII', 9=>'IX', 10=>'X',
	11=>'XI', 12=>'XII', 13=>'XIII', 14=>'XIV', 15=>'XV', 16=>'XVI', 17=>'XVII', 18=>'XVIII', 19=>'XIX', 20=>'XX',
	21=>'XXI', 22=>'XXII', 23=>'XXIII', 24=>'XXIV', 25=>'XXV', 26=>'XXVII', 27=>'XXVII', 28=>'XXVIII', 29=>'XXIX', 30=>'XXX',
	31=>'XXXI',32=>'XXXII',33=>'XXXIII',34=>'XXXIV',35=>'XXXV',36=>'XXXVII',37=>'XXXVII',38=>'XXXVIII',39=>'XXXIX',40=>'XL',
	41=>'XLI', 42=>'XLII', 43=>'XLIII', 44=>'XLIV', 45=>'XLV', 46=>'XLVII', 47=>'XLVII', 48=>'XLVIII', 49=>'XLIX', 50=>'L',
	51=>'LI',  52=>'LII',  53=>'LIII',  54=>'LIV',  55=>'LV',  56=>'LVII',  57=>'LVII',  58=>'LVIII',  59=>'LIX',  60=>'LX',);

	//a-z
	$number_generation[]=''; //(1st number_generation is not used)
	for ($i=1; $i<=26; $i++) {
		$number_generation[]=chr($i+96); //chr(97)=a
	}
	//aa-zz
	for ($i=1; $i<=676; $i++) {
		for ($j=1; $j<=26; $j++) {
			$number_generation[]=chr($i+96).chr($j+96); //chr(97)=a
		}
	}

	// **********************************************************
	// *** Maximum number of generations in descendant report ***
	// **********************************************************
	$max_generation=($humo_option["descendant_generations"]-1);

	$descendant_report=false;
	if (isset($_GET['descendant_report'])){ $descendant_report=true; }
	if (isset($_POST['descendant_report'])){ $descendant_report=true; }

	// *** Compact or expanded view ***
	if (isset($_GET['family_expanded'])){
		if ($_GET['family_expanded']=='0') $_SESSION['save_family_expanded']='0';
			else $_SESSION['save_family_expanded']='1';
	}
	// *** Default setting is selected by administrator ***
	if ($user['group_family_presentation']=='expanded')
		$family_expanded=true;
	else
		$family_expanded=false;
	if (isset($_SESSION['save_family_expanded'])) $family_expanded=$_SESSION['save_family_expanded'];

	// *** Source presentation selected by user (title/ footnote/ hide) ***
	if (isset($_GET['source_presentation'])){
		$_SESSION['save_source_presentation']=safe_text_db($_GET["source_presentation"]);
	}
	// *** Default setting is selected by administrator ***
	$source_presentation=$user['group_source_presentation'];
	if (isset($_SESSION['save_source_presentation'])) $source_presentation=$_SESSION['save_source_presentation'];
	else{
		// *** Extra saving of setting in session (if no choice is made, this is admin default setting, needed for show_sources.php!!!) ***
		$_SESSION['save_source_presentation']=safe_text_db($source_presentation);
	}

	// *** Show/ hide Google maps ***
	if (isset($_GET['maps_presentation'])) $_SESSION['save_maps_presentation']=safe_text_db($_GET["maps_presentation"]);
	// *** Default setting is selected by administrator ***
	$maps_presentation=$user['group_maps_presentation'];
	//if ($user['group_maps_presentation']=='sources') $maps_presentation='hide'; // *** sources = backwards compatible!! *** 
	if (isset($_SESSION['save_maps_presentation'])) $maps_presentation=$_SESSION['save_maps_presentation'];
	// *** Only show selection if there is a Google maps database ***
	global $dbh;
	$temp = $dbh->query("SHOW TABLES LIKE 'humo_location'");
	if(!$temp->rowCount()) {
		$maps_presentation='hide';
	}

	// *** Show/ hide pictures ***
	if (isset($_GET['picture_presentation'])) $_SESSION['save_picture_presentation']=safe_text_db($_GET["picture_presentation"]);
	// *** Default setting is selected by administrator ***
	//$picture_presentation=$user['group_picture_presentation'];
	if (isset($_SESSION['save_picture_presentation'])) $picture_presentation=$_SESSION['save_picture_presentation'];

	// *** Show/ hide texts ***
	if (isset($_GET['text_presentation'])) $_SESSION['save_text_presentation']=safe_text_db($_GET["text_presentation"]);
	// *** Default setting is selected by administrator ***
	$text_presentation=$user['group_text_presentation'];
	if (isset($_SESSION['save_text_presentation'])) $text_presentation=$_SESSION['save_text_presentation'];
}
if($screen_mode=='STAR') {
	$descendant_report=true;
	$genarray = array();
	$family_expanded=false;
}

if($screen_mode=='PDF') {  //initialize pdf generation
	$family_expanded=false;
	$pdfdetails=array();
	$pdf_marriage=array();
	$pdf=new PDF();

	// *** Generate title of PDF file ***
	@$persDb = $db_functions->get_person($main_person);
	// *** Use class to process person ***
	$pers_cls = New person_cls;
	$pers_cls->construct($persDb);
	$name=$pers_cls->person_name($persDb);
	if(!$descendant_report==false) {
		$title=pdf_convert(__('Descendant report').__(' of ').$name["standard_name"]);
	}
	else {
		$title=pdf_convert(__('Family group sheet').__(' of ').$name["standard_name"]);
	}
	$pdf->SetTitle($title);

	$pdf->SetAuthor('Huub Mons (pdf: Yossi Beck)');
	$pdf->AddPage();
	$pdf->SetFont('Arial','',12);
} // end if pdfmode

if($screen_mode=='RTF') {  // initialize rtf generation
	require_once 'include/phprtflite/lib/PHPRtfLite.php';
	$family_expanded=false;

	// *** registers PHPRtfLite autoloader (spl) ***
	PHPRtfLite::registerAutoloader();
	// *** rtf document instance ***
	$rtf = new PHPRtfLite();

	// *** Add section ***
	$sect = $rtf->addSection();

	// *** RTF Settings ***
	$arial12 = new PHPRtfLite_Font(12, 'Arial');
	$arial14 = new PHPRtfLite_Font(14, 'Arial', '#000066');
	//Fonts
	$fontHead = new PHPRtfLite_Font(12, 'Arial');
	$fontSmall = new PHPRtfLite_Font(3);
	$fontAnimated = new PHPRtfLite_Font(10);
	$fontLink = new PHPRtfLite_Font(10, 'Helvetica', '#0000cc');

	$parBlack = new PHPRtfLite_ParFormat();
	$parBlack->setIndentRight(12.5);
	//$parBlack->setBackgroundColor('#000000');
	$parBlack->setSpaceBefore(12);

	$parHead = new PHPRtfLite_ParFormat();
	$parHead->setSpaceBefore(3);
	$parHead->setSpaceAfter(8);
	$parHead->setBackgroundColor('#baf4c1');

	$parSimple = new PHPRtfLite_ParFormat();
	$parSimple->setIndentLeft(1);
	$parSimple->setIndentRight(0.5);

	$par_child_text = new PHPRtfLite_ParFormat();
	$par_child_text->setIndentLeft(0.5);
	$par_child_text->setIndentRight(0.5);

	//$rtf->setMargins(3, 1, 1 ,2);

	// *** Generate title of RTF file ***
	@$persDb = $db_functions->get_person($main_person);
	// *** Use class to process person ***
	$pers_cls = New person_cls;
	$pers_cls->construct($persDb);
	$name=$pers_cls->person_name($persDb);
	if(!$descendant_report==false) {
		$title=__('Descendant report').__(' of ').$name["standard_name"];
	}
	else {
		$title=__('Family group sheet').__(' of ').$name["standard_name"];
	}
	//$sect->writeText($title, $arial14, new PHPRtfLite_ParFormat());
	$sect->writeText($title, $arial14, $parHead);

	$file_name=date("Y_m_d_H_i_s").'.rtf';
	// *** FOR TESTING PURPOSES ONLY ***
	if (@file_exists("../gedcom-bestanden")) $file_name='../gedcom-bestanden/'.$file_name;
		else $file_name='tmp_files/'.$file_name;

	// *** Automatically remove old RTF files ***
	$dh  = opendir('tmp_files');
	while (false !== ($filename = readdir($dh))) {
		if (substr($filename, -3) == "rtf"){
			//echo 'tmp_files/'.$filename.'<br>';
			// *** Remove files older then today ***
			if (substr($filename,0,10)!=date("Y_m_d")) unlink('tmp_files/'.$filename);
		}
	}

	//echo $file_name;
}

if($screen_mode=='STAR') {
// DNA chart -> change base person to earliest father-line (Y-DNA) or mother-line (Mt-DNA) ancestor
	$max_generation=100;
	@$dnaDb = $db_functions->get_person($main_person);

	$dnapers_cls = New person_cls;
	$dnaname=$dnapers_cls->person_name($dnaDb);
	$base_person_name =  $dnaname["standard_name"];	// need these 4 in report_descendant.php
	$base_person_sexe = $dnaDb->pers_sexe;
	$base_person_famc = $dnaDb->pers_famc;
	$base_person_gednr = $dnaDb->pers_gedcomnumber;

	if($dna=="ydna" OR $dna=="ydnamark") {
		while(isset($dnaDb->pers_famc) AND $dnaDb->pers_famc!="") {
			@$dnaparDb = $db_functions->get_family($dnaDb->pers_famc);
			if($dnaparDb->fam_man=="") break;
			else {
				$main_person = $dnaparDb->fam_man;
				$family_id  = $dnaDb->pers_famc;
				@$dnaDb = $db_functions->get_person($dnaparDb->fam_man);
			}
		}
	}
	if($dna=="mtdna" OR $dna=="mtdnamark") {
		while(isset($dnaDb->pers_famc) AND $dnaDb->pers_famc!="") {
			@$dnaparDb = $db_functions->get_family($dnaDb->pers_famc);
			if($dnaparDb->fam_woman=="") break;
			else {
				$main_person = $dnaparDb->fam_woman;
				$family_id  = $dnaDb->pers_famc;
				@$dnaDb = $db_functions->get_person($dnaparDb->fam_woman);
			}
		}
	}
}

// **************************
// *** Show single person ***
// **************************
if (!$family_id){
	// starfieldchart is never called when there is no own fam so no need to mark this out
	// *** Privacy filter ***
	@$parent1Db = $db_functions->get_person($main_person);
	// *** Use class to show person ***
	$parent1_cls = New person_cls;
	$parent1_cls->construct($parent1Db);

	if($screen_mode=='PDF') {
		// *** Show familysheet name: user's choice or default ***
		$pdf->Cell(0,2," ",0,1);
		$pdf->SetFont('Arial','BI',12);
		$pdf->SetFillColor(196,242,107);

		$treetext=show_tree_text($dataDb->tree_id, $selected_language);
		$family_top=$treetext['family_top'];
		if($family_top!='') {
			$pdf->Cell(0,6,pdf_convert($family_top),0,1,'L',true);
		}
		else {
			$pdf->Cell(0,6,pdf_convert(__('Family group sheet')),0,1,'L',true);
		}

		$pdf->SetFont('Arial','B',12);
		$pdf->Write(8,$parent1_cls->name_extended("parent1"));
		$pdf->SetFont('Arial','',12);
		$pdf->Write(8,"\n");
		$id='';
		//$pdfdetails= pdf_convert($parent1_cls->person_data("parent1", $id));
		$pdfdetails= $parent1_cls->person_data("parent1", $id);
		if($pdfdetails) $pdf->pdfdisplay($pdfdetails,"parent");
	}

	elseif($screen_mode=='RTF') {
		$rtf_text=strip_tags($parent1_cls->name_extended("parent1"),"<b><i>");
		$sect->writeText($rtf_text, $arial12, new PHPRtfLite_ParFormat());
		$id='';
		$rtf_text=strip_tags($parent1_cls->person_data("parent1", $id),"<b><i>");
		$sect->writeText($rtf_text, $arial12, $parSimple);
	}

	else {
		// *** Add tip in person screen ***
		if (!$bot_visit){
			echo '<div class="print_version"><b>';
			printf(__('TIP: use %s for other (ancestor and descendant) reports.'), '<img src="images/reports.gif">');
			echo '</b><br><br></div>';
		}

		echo '<table class="humo standard">';

			// *** Show person topline (top text, settings, favourite) ***
			echo topline();

		echo '<tr><td colspan="4">';
			//*** Show person data ***
			echo '<span class="parent1 fonts">'.$parent1_cls->name_extended("parent1");
			$id='';
			echo $parent1_cls->person_data("parent1", $id).'</span>';
		echo '</td></tr>';
		echo '</table>';
	} // end if not pdf
}

// *******************
// *** Show family ***
// *******************
else{
	if($screen_mode=='PDF') {
		$pdf->SetFont('Arial','B',15);
		$pdf->Ln(4);
		$name=$pers_cls->person_name($persDb);
		if(!$descendant_report==false) {
			$pdf->MultiCell(0,10,__('Descendant report').__(' of ').$name["standard_name"],0,'C');
		}
		else {
			$pdf->MultiCell(0,10,__('Family group sheet').__(' of ').$name["standard_name"],0,'C');
		}
		$pdf->Ln(4);
		$pdf->SetFont('Arial','',12);
	}
	if($screen_mode!='STARSIZE') {
		$descendant_family_id2[]=$family_id;
		$descendant_main_person2[]=$main_person;
	}

	if($screen_mode=='STAR') {
		$arraynr=0;
	}

	// *** Nr. of generations ***
	if($screen_mode=='STAR') {
		if($chosengen != "All") { $max_generation=$chosengen-2; }
		else { $max_generation=100; } // any impossibly high number, will anyway stop at last generation
	}
	if($screen_mode!='STARSIZE') {

	try { // only prepare location statement if table exists otherwise PDO throws exception!
		$result = $dbh->query("SELECT 1 FROM humo_location LIMIT 1"); 
	} catch (Exception $e) {
		// We got an exception == table not found
		$result = FALSE;
	}
	if($result !== FALSE) {
		$location_prep=$dbh->prepare("SELECT * FROM humo_location where location_location =?");
		$location_prep->bindParam(1,$location_var);
	}

	$old_stat_prep=$dbh->prepare("UPDATE humo_families SET fam_counter=? WHERE fam_tree_id='".$tree_id."' AND fam_gedcomnumber=?");
	$old_stat_prep->bindParam(1,$fam_counter_var);
	$old_stat_prep->bindParam(2,$fam_gednr_var);

	for ($descendant_loop=0; $descendant_loop<=$max_generation; $descendant_loop++){
		// ORG
		$descendant_family_id2[]=0;
		$descendant_main_person2[]=0;
		if (!isset($descendant_family_id2[1])){ break; }

		// TEST code (only works with family, will give error in descendant report and DNA reports:
		// if (!isset($descendant_family_id2[0])){ break; }

		// *** Copy array ***
		unset ($descendant_family_id);
		$descendant_family_id=$descendant_family_id2;
		unset ($descendant_family_id2);

		unset ($descendant_main_person);
		$descendant_main_person=$descendant_main_person2;
		unset ($descendant_main_person2);

		if($screen_mode=='STAR') {
			if($descendant_loop!=0) {
				if(isset($genarray[$arraynr])) {
					$temppar=$genarray[$arraynr]["par"];
				}
				while(isset($genarray[$temppar]["gen"]) AND $genarray[$temppar]["gen"]==$descendant_loop-1) {
					$lst_in_array += $genarray[$temppar]["nrc"];
					$temppar++;
				}
			}
			$nrchldingen=0;
		}
		else {
			if ($descendant_report==true){
				if($screen_mode=='PDF') {
					$pdf->SetLeftMargin(10);
					$pdf->Cell(0,2,"",0,1);
					$pdf->SetFont('Arial','BI',14);
					$pdf->SetFillColor(200,220,255);
					if($pdf->GetY() > 250) { $pdf->AddPage(); $pdf->SetY(20); }
					$pdf->Cell(0,8,pdf_convert(__('generation ')).$number_roman[$descendant_loop+1],0,1,'C',true);
					$pdf->SetFont('Arial','',12);

					// *** Added mar. 2021 ***
					unset($templ_name);
				}
				elseif($screen_mode=='RTF') {
					$rtf_text=__('generation ').$number_roman[$descendant_loop+1];
					$sect->writeText($rtf_text, $arial14, $parHead);
				}
				else {
					echo '<div class="standard_header fonts">'.__('generation ').$number_roman[$descendant_loop+1].'</div>';
				}
			}
		}

		// *** Nr of families in one generation ***
		$nr_families=count($descendant_family_id);
		for ($descendant_loop2=0; $descendant_loop2<$nr_families; $descendant_loop2++){

			if($screen_mode=='STAR') {
				while (isset($genarray[$arraynr]["non"]) AND $genarray[$arraynr]["non"]==1
				AND isset($genarray[$arraynr]["gen"]) AND $genarray[$arraynr]["gen"]==$descendant_loop) {
					//$genarray[$arraynr]["nrc"]==0;
					$genarray[$arraynr]["nrc"]=0;
					$arraynr++;
				}
			}

			// Original code:
			//if ($descendant_family_id[$descendant_loop2]==''){ break; }
			if ($descendant_family_id[$descendant_loop2]=='0') { break; }

			$family_id_loop=$descendant_family_id[$descendant_loop2];
			$main_person=$descendant_main_person[$descendant_loop2];
			$family_nr=1;

			// *** Count marriages of man ***
			$familyDb = $db_functions->get_family($family_id_loop);
			$parent1=''; $parent2=''; $swap_parent1_parent2=false;
			// *** Standard main person is the father ***
			if ($familyDb->fam_man){
				$parent1=$familyDb->fam_man;
			}
			// *** After clicking the mother, the mother is main person ***
			if ($familyDb->fam_woman==$main_person){
				$parent1=$familyDb->fam_woman;
				$swap_parent1_parent2=true;
			}

			// *** Check for parent1: N.N. ***
			if ($parent1){
				// *** Save parent1 families in array ***
				$personDb = $db_functions->get_person($parent1);
				$marriage_array=explode(";",$personDb->pers_fams);
				$count_marr=substr_count($personDb->pers_fams, ";");
			}
			else{
				$marriage_array[0]=$family_id_loop;
				$count_marr="0";
			}

			// *** Loop multiple marriages of main_person ***
			for ($parent1_marr=0; $parent1_marr<=$count_marr; $parent1_marr++){
				$id=$marriage_array[$parent1_marr];
				@$familyDb = $db_functions->get_family($id);

				// *** Don't count search bots, crawlers etc. ***
				if (!$bot_visit){
					// *** Update (old) statistics counter ***
					$fam_counter=$familyDb->fam_counter+1;
					$fam_counter_var = $fam_counter;
					$fam_gednr_var = $id;
					$old_stat_prep->execute();

					// *** Extended statistics ***
					if ($descendant_report==false AND $user['group_statistics']=='j'){
						$stat_easy_id=$familyDb->fam_tree_id.'-'.$familyDb->fam_gedcomnumber.'-'.$familyDb->fam_man.'-'.$familyDb->fam_woman;
						$update_sql="INSERT INTO humo_stat_date SET
							stat_easy_id='".$stat_easy_id."',
							stat_ip_address='".$_SERVER['REMOTE_ADDR']."',
							stat_user_agent='".$_SERVER['HTTP_USER_AGENT']."',
							stat_tree_id='".$familyDb->fam_tree_id."',
							stat_gedcom_fam='".$familyDb->fam_gedcomnumber."',
							stat_gedcom_man='".$familyDb->fam_man."',
							stat_gedcom_woman='".$familyDb->fam_woman."',
							stat_date_stat='".date("Y-m-d H:i")."',
							stat_date_linux='".time()."'";
						$result = $dbh->query($update_sql);
					}
				}

				// Oct. 2021 New method:
				if ($swap_parent1_parent2==true){
					$parent1=$familyDb->fam_woman;
					$parent2=$familyDb->fam_man;
				}
				else{
					$parent1=$familyDb->fam_man;
					$parent2=$familyDb->fam_woman;
				}
				@$parent1Db = $db_functions->get_person($parent1);
				// *** Proces parent1 using a class ***
				$parent1_cls = New person_cls;
				$parent1_cls->construct($parent1Db);

				@$parent2Db = $db_functions->get_person($parent2);
				// *** Proces parent2 using a class ***
				$parent2_cls = New person_cls;
				$parent2_cls->construct($parent2Db);

				// *** Proces marriage using a class ***
				$marriage_cls = New marriage_cls;
				$marriage_cls->construct($familyDb, $parent1_cls->privacy, $parent2_cls->privacy);
				$family_privacy=$marriage_cls->privacy;


				// *******************************************************************
				// *** Show family                                                 ***
				// *******************************************************************
				if($screen_mode!='STAR') {

					// *** Internal link for descendant_report ***
					if ($descendant_report==true){
						// *** Internal link (Roman number_generation) ***
						if($screen_mode=='PDF') {
							// put internal PDF link to family
							$pdf->Cell(0,1," ",0,1);
							$romannr=$number_roman[$descendant_loop+1].'-'.$number_generation[$descendant_loop2+1];
							if(isset($link[$romannr])) {
								$pdf->SetLink($link[$romannr],-1); //link to this family from child with "volgt"
							}
							$parlink[$id]=$pdf->Addlink();
							$pdf->SetLink($parlink[$id],-1);   // link to this family from parents
						}
						elseif($screen_mode=='RTF') {
							//$rtf_text=$number_roman[$descendant_loop+1].'-'.$number_generation[$descendant_loop2+1].' ';
							//$sect->writeText($rtf_text, $arial12, new PHPRtfLite_ParFormat());
						}
						else {
							echo '<a name="'.$number_roman[$descendant_loop+1].'-'.$number_generation[$descendant_loop2+1].'">';
							echo '&nbsp;</a>';
						}
					}

					if($screen_mode=='PDF') {
						// Show "Family Page", user's choice or default
						$pdf->SetLeftMargin(10);
						$pdf->Cell(0,2," ",0,1);
						if($pdf->GetY() > 260 AND $descendant_loop2!=0) {
							// move to next page so family sheet banner won't be last on page
							// but if we are in first family in generation, the gen banner
							// is already checked so no need here
							$pdf->AddPage(); $pdf->SetY(20);
						}
						$pdf->SetFont('Arial','BI',12);
						$pdf->SetFillColor(186,244,193);

						$treetext=show_tree_text($dataDb->tree_id, $selected_language);
						$family_top=$treetext['family_top'];
						if($family_top!='') {
							$pdf->SetLeftMargin(10);
							$pdf->Cell(0,6,pdf_convert($family_top),0,1,'L',true);
						}
						else {
							$pdf->SetLeftMargin(10);
							$pdf->Cell(0,6,pdf_convert(__('Family group sheet')),0,1,'L',true);
						}
						$pdf->SetFont('Arial','',12);
					}
					elseif($screen_mode=='RTF') {
						$sect->addEmptyParagraph($fontSmall, $parBlack);

						$treetext=show_tree_text($dataDb->tree_id, $selected_language);
						$rtf_text=$treetext['family_top'];
						if($rtf_text!='')
							$sect->writeText($rtf_text, $arial14, $parHead);
						else
							$sect->writeText(__('Family group sheet'), $arial14, $parHead);
					}
					else {
						// *** Add tip in family screen ***
						if (!$bot_visit AND $descendant_loop==0 AND $parent1_marr==0){
							echo '<div class="print_version"><b>';
							printf(__('TIP: use %s for other (ancestor and descendant) reports.'), '<img src="images/reports.gif">');
							echo '</b><br><br></div>';
						}

						echo '<table class="humo standard">';

							// *** Show family top line (family top text, settings, favourite) ***
							echo topline();

						echo '<tr><td colspan="4">';
					} //end  "if not pdf"

				}  // end if not STAR

				// *************************************************************
				// *** Parent1 (normally the father)                         ***
				// *************************************************************
				if ($familyDb->fam_kind!='PRO-GEN'){  //onecht kind, woman without man
					if ($family_nr==1){
						//*** Show data of parent1 ***
						if($screen_mode=='') {
							echo '<div class="parent1 fonts">';
								// *** Show roman number in descendant_report ***
								if ($descendant_report==true){
									echo '<b>'.$number_roman[$descendant_loop+1].'-'.$number_generation[$descendant_loop2+1].'</b> ';
								}

								$show_name_texts=true;
								echo $parent1_cls->name_extended("parent1",$show_name_texts);
								echo $parent1_cls->person_data("parent1", $id);

								// *** Change page title ***
								if ($descendant_loop==0 AND $descendant_loop2==0){
									echo '<script type="text/javascript">';
										$name = $parent1_cls->person_name($parent1Db);
										echo 'document.title = "'.__('Family Page').': '.$name["index_name"].'";';
									echo '</script>';
								}
							echo '</div>';
						}
						elseif($screen_mode=='PDF') {
							if ($descendant_report==true) {
								$pdf->Write(8,$number_roman[$descendant_loop+1].'-'.$number_generation[$descendant_loop2+1]." ");
							}

							//  PDF rendering of name + details
							unset ($templ_person);
							unset ($templ_name);

							// *** Name ***
							$pdfdetails=$parent1_cls->name_extended("parent1");
							if($pdfdetails) {
								//$pdf->write_name($pdfdetails,$pdf->GetX()+5,"long");
								$pdf->write_name($templ_name,$pdf->GetX()+5,"long");

								// *** Resets line ***
								$pdf->MultiCell(0,8,'',0,"L");
							}
							$indent=$pdf->GetX();

							// *** Person data ***
							$pdf->SetLeftMargin($indent);
							$pdfdetails= $parent1_cls->person_data("parent1", $id);
							if($pdfdetails) {
								$pdf->pdfdisplay($pdfdetails,"parent1");
							}
							$pdf->SetLeftMargin($indent-5);

						}
						elseif($screen_mode=='RTF') {
							$rtf_text=' <b>'.$number_roman[$descendant_loop+1].'-'.$number_generation[$descendant_loop2+1].'</b> ';
							//$sect->writeText($rtf_text, $arial12, new PHPRtfLite_ParFormat());
							$sect->writeText($rtf_text, $arial12);

							// *** Start new line ***
							$sect->writeText('', $arial12, new PHPRtfLite_ParFormat());

							$rtf_text=strip_tags($parent1_cls->name_extended("parent1"),"<b><i>");
							//$sect->writeText($rtf_text, $arial12, new PHPRtfLite_ParFormat());
							$sect->writeText($rtf_text, $arial12);
							$id='';
							$rtf_text=strip_tags($parent1_cls->person_data("parent1", $id),"<b><i>");
							$sect->writeText($rtf_text, $arial12, $parSimple);

							// *** Show RTF media ***
							if (!$parent1_cls->privacy){
								show_rtf_media('person',$parent1Db->pers_gedcomnumber);
							}
						}
						elseif($screen_mode=='STAR') {
							if($descendant_loop==0) {
								$name=$parent1_cls->person_name($parent1Db);
								$genarray[$arraynr]["nam"]=$name["standard_name"];
								if (isset ($name["colour_mark"]))
									$genarray[$arraynr]["nam"].=$name["colour_mark"];
								$genarray[$arraynr]["init"]=$name["initials"];
								$genarray[$arraynr]["short"]=$name["short_firstname"];
								$genarray[$arraynr]["fams"]=$id;
								if (isset($parent1Db->pers_gedcomnumber))
									$genarray[$arraynr]["gednr"]=$parent1Db->pers_gedcomnumber;
								$genarray[$arraynr]["2nd"]=0;

								if ($swap_parent1_parent2==true){
									$genarray[$arraynr]["sex"]="v";
									if($dna=="mtdnamark" OR $dna=="mtdna") { $genarray[$arraynr]["dna"]=1; }
										else $genarray[$arraynr]["dna"]="no";
								}
								else{
									$genarray[$arraynr]["sex"]="m";
									if($dna=="ydnamark" OR $dna=="ydna" OR $dna=="mtdnamark" OR $dna=="mtdna") { $genarray[$arraynr]["dna"]=1; }
										else $genarray[$arraynr]["dna"]="no";
								}
							}
						}
						//$family_nr++;
					}
					else{
						// *** Show standard marriage text and name in 2nd, 3rd, etc. marriage ***
						if($screen_mode=='') {
							echo $marriage_cls->marriage_data($familyDb,$family_nr,'shorter').' ';

							echo '<br>'.$parent1_cls->name_extended("parent1").'<br>';
						}
						elseif($screen_mode=='PDF') {
							$pdf->SetLeftMargin($indent);
							$pdf_marriage=$marriage_cls->marriage_data($familyDb,$family_nr,'shorter');
							$pdf->Write(8,$pdf_marriage["relnr_rel"].__(' of ')."\n");

							unset ($templ_person);
							unset ($templ_name);

							// *** PDF rendering of name ***
							$pdfdetails=$parent1_cls->name_extended("parent1");
							if($pdfdetails) {
								//$pdf->write_name($pdfdetails,$pdf->GetX()+5,"kort");
								$pdf->write_name($templ_name,$pdf->GetX()+5,"kort");

								// *** Resets line ***
								$pdf->MultiCell(0,8,'',0,"L");
							}
							$indent=$pdf->GetX();

						}
						elseif($screen_mode=='RTF') {
							$rtf_text=strip_tags($marriage_cls->marriage_data($familyDb,$family_nr,'shorter'),"<b><i>");
							$sect->writeText($rtf_text, $arial12, new PHPRtfLite_ParFormat());

							$rtf_text=strip_tags($parent1_cls->name_extended("parent1"),"<b><i>");
							$sect->writeText($rtf_text, $arial12, new PHPRtfLite_ParFormat());
						}
						elseif($screen_mode=='STAR') {
							if($descendant_loop==0) {
								$genarray[$arraynr]=$genarray[$arraynr-1];
								$genarray[$arraynr]["2nd"]=1;
								//$genarray[$arraynr]["fams"]=$id;
							}
							$genarray[$arraynr]["huw"]=$marriage_cls->marriage_data($familyDb,$family_nr,'shorter');
							$genarray[$arraynr]["fams"]=$id;
						}

					}
					$family_nr++;
				} // *** End check of PRO-GEN ***


				// *************************************************************
				// *** Marriage                                              ***
				// *************************************************************
				if ($familyDb->fam_kind!='PRO-GEN'){  // onecht kind, wife without man
					if($screen_mode=='') {
						echo '<br><div class="marriage fonts">';
						// *** $family_privacy='1' = filter ***
						if ($family_privacy){
							// *** Show standard marriage data ***
							echo $marriage_cls->marriage_data($familyDb,'','short');
						}
						else{
							echo $marriage_cls->marriage_data();
						}
						echo '</div><br>';
					}
					if($screen_mode=='PDF') {
						//unset ($templ_person);
						//unset ($templ_relation);

						if($family_privacy) {
							$pdf_marriage=$marriage_cls->marriage_data($familyDb,'','short');
							$pdf->SetLeftMargin($indent);
							if($pdf_marriage) {
								$pdf->displayrel($pdf_marriage,"dummy");
							}
						}
						else {
							$pdf_marriage=$marriage_cls->marriage_data();
							$pdf->SetLeftMargin($indent);
							if($pdf_marriage) {
								$pdf->displayrel($pdf_marriage,"dummy");
							}
						}
					}

					if($screen_mode=='RTF') {
						if ($family_privacy){
							// *** Show standard marriage data ***
							$rtf_text=strip_tags($marriage_cls->marriage_data($familyDb,'','short'),"<b><i>");
							$sect->writeText($rtf_text, $arial12, new PHPRtfLite_ParFormat());
						}
						else{
							$rtf_text=strip_tags($marriage_cls->marriage_data(),"<b><i>");
							$sect->writeText($rtf_text, $arial12, new PHPRtfLite_ParFormat());

							// *** Show RTF media ***
							show_rtf_media('family',$familyDb->fam_gedcomnumber);
						}
					}

					if($screen_mode=='STAR') {
						if($family_privacy) {
							$genarray[$arraynr]["htx"]=$marriage_cls->marriage_data($familyDb,'','short');
						}
						else {
							$genarray[$arraynr]["htx"]=$marriage_cls->marriage_data();
						}
					}
				}

				// *************************************************************
				// *** Parent2 (normally the mother)                         ***
				// *************************************************************
				if($screen_mode=='') {
					echo '<div class="parent2 fonts">';
						$show_name_texts=true;
						echo $parent2_cls->name_extended("parent2",$show_name_texts);
						echo $parent2_cls->person_data("parent2", $id);
					echo '</div>';
				}

				elseif($screen_mode=='PDF') {
					unset ($templ_person);
					unset ($templ_name);
					// PDF rendering of name + details
					$pdf->Write(8," "); // IMPORTANT - otherwise at bottom of page man/woman.gif image will print, but name may move to following page!
					$pdfdetails=$parent2_cls->name_extended("parent2");
					if($pdfdetails) {
						//$pdf->write_name($pdfdetails,$pdf->GetX()+5,"long");
						$pdf->write_name($templ_name,$pdf->GetX()+5,"long");

						// *** Resets line ***
						$pdf->MultiCell(0,8,'',0,"L");
					}
					$indent=$pdf->GetX();

					$pdfdetails= $parent2_cls->person_data("parent2", $id);
					$pdf->SetLeftMargin($indent);
					if($pdfdetails) {
						$pdf->pdfdisplay($pdfdetails,"parent2");
					}
				}

				elseif($screen_mode=='RTF') {
					$sect->addEmptyParagraph($fontSmall, $parBlack);

					// *** Start new line ***
					$sect->writeText('', $arial12, new PHPRtfLite_ParFormat());

					$rtf_text=strip_tags($parent2_cls->name_extended("parent2"),"<b><i>");
					//$sect->writeText($rtf_text, $arial12, new PHPRtfLite_ParFormat());
					$sect->writeText($rtf_text, $arial12);
					$rtf_text=strip_tags($parent2_cls->person_data("parent2",$id),"<b><i>");
					$sect->writeText($rtf_text, $arial12, $parSimple);

					// *** Show RTF media ***
					if (!$parent2_cls->privacy){
						show_rtf_media('person',$parent2Db->pers_gedcomnumber);
					}
				}

				elseif($screen_mode=='STAR') {
					if($parent2Db) {
						$name=$parent2_cls->person_name($parent2Db);
						$genarray[$arraynr]["sps"]=$name["standard_name"];
						$genarray[$arraynr]["spgednr"]=$parent2Db->pers_gedcomnumber;
					}
					else {
						$genarray[$arraynr]["sps"]= __('Unknown');
						$genarray[$arraynr]["spgednr"]=''; // this is a non existing NN spouse!
					}
					$genarray[$arraynr]["spfams"]=$id;
				}


				// *************************************************************
				// *** Marriagetext                                          ***
				// *************************************************************
				$temp='';

				if($screen_mode!='STAR') {
					if ($family_privacy){
						// No marriage data
					}
					else{
						if ($user["group_texts_fam"]=='j' AND process_text($familyDb->fam_text)){
							if($screen_mode=='PDF') {
								// PDF rendering of marriage notes
								//$pdf->SetFont('Arial','I',11);
								//$pdf->Write(6,process_text($familyDb->fam_text)."\n");
								//$pdf->Write(6,show_sources2("family","fam_text_source",$familyDb->fam_gedcomnumber)."\n");
								//$pdf->SetFont('Arial','',12);

								$templ_relation["fam_text"]=$familyDb->fam_text;
								$temp="fam_text";

								$source=show_sources2("family","fam_text_source",$familyDb->fam_gedcomnumber);
								$templ_relation["fam_text_source"]=$source;
								$temp="fam_text_source";

							}
							elseif($screen_mode=='RTF') {
								$sect->addEmptyParagraph($fontSmall, $parBlack);

								$rtf_text=strip_tags(process_text($familyDb->fam_text),"<b><i>");
								$sect->writeText($rtf_text, $arial12, new PHPRtfLite_ParFormat());
								$rtf_text=strip_tags(show_sources2("family","fam_text_source",$familyDb->fam_gedcomnumber),"<b><i>");
								$sect->writeText($rtf_text, $arial12, new PHPRtfLite_ParFormat());
							}
							else {
								echo '<br>'.process_text($familyDb->fam_text, 'family');
								// *** BK: source by family text ***
								echo show_sources2("family","fam_text_source",$familyDb->fam_gedcomnumber);
							}
						}
					}

					// *** Show addresses by family ***
					if ($user['group_living_place']=='j'){
						if($screen_mode=='PDF') {
							//show_addresses('family','family_address',$familyDb->fam_gedcomnumber);
							$fam_address=show_addresses('family','family_address',$familyDb->fam_gedcomnumber);
						}
//						elseif($screen_mode=='RTF') {
//							//
//						}
						else{
							$fam_address=show_addresses('family','family_address',$familyDb->fam_gedcomnumber);
							if ($fam_address){
								echo '<br>'.$fam_address;
							}
						}
					}

					// *** Family source ***
					if($screen_mode=='PDF') {
						$source=show_sources2("family","family_source",$familyDb->fam_gedcomnumber);
						if ($source){
							if ($temp) $templ_relation[$temp].='. ';

							$templ_relation["fam_source"]=$source;
							$temp="fam_source";
							$pdf->displayrel($templ_relation,"dummy");
						}
					}
					elseif($screen_mode=='RTF') {
						$rtf_text=strip_tags(show_sources2("family","family_source",$familyDb->fam_gedcomnumber),"<b><i>");
						$sect->writeText($rtf_text, $arial12, new PHPRtfLite_ParFormat());
					}
					else {
						echo show_sources2("family","family_source",$familyDb->fam_gedcomnumber);
					}

				} //end "if not STAR"

				if($screen_mode=='STAR') {
						if($descendant_loop==0) {
						$lst_in_array=$count_marr;
						$genarray[$arraynr]["gen"]=0;
						$genarray[$arraynr]["par"]=-1;
						$genarray[$arraynr]["chd"]=$arraynr + 1;
						$genarray[$arraynr]["non"]=0;
					}
				}

				// *************************************************************
				// *** Children                                              ***
				// *************************************************************

				if($screen_mode=='STAR') {
					if (!$familyDb->fam_children){
						$genarray[$arraynr]["nrc"]=0;
					}
				}

				if ($familyDb->fam_children){
					$childnr=1;
					$child_array=explode(";",$familyDb->fam_children);

					if($screen_mode=='') {
						// *** Show "Child(ren):" ***
						if (count($child_array)=='1'){
							echo '<p><b>'.__('Child').':</b></p>';
						}
						else{
							echo '<p><b>'.__('Children').':</b></p>';
						}
						//echo "</td></tr>\n";
					}
					if($screen_mode=='PDF') {
						unset ($templ_person);
						unset ($templ_name);

						$pdf->SetLeftMargin(10);
						$pdf->SetDrawColor(200);  // grey line
						$pdf->Cell(0,2," ",'B',1);
					}
					if($screen_mode=='RTF') {
						// *** Show "Child(ren):" ***
						if (count($child_array)=='1'){
							$rtf_text='<b>'.__('Child').':</b>';
							$sect->writeText($rtf_text, $arial12, new PHPRtfLite_ParFormat());
						}
						else{
							$rtf_text='<b>'.__('Children').':</b>';
							$sect->writeText($rtf_text, $arial12, new PHPRtfLite_ParFormat());
						}
					}
					if($screen_mode=='STAR') {
						$genarray[$arraynr]["nrc"]=count($child_array);
						// dna -> count only man or women
						if($dna=="ydna" OR $dna=="mtdna") {
							$countdna = 0;
							for($i=0; $i<=substr_count($familyDb->fam_children, ";"); $i++){
								@$childDb = $db_functions->get_person($child_array[$i]);
								if($dna=="ydna" AND $childDb->pers_sexe == "M" AND $genarray[$arraynr]["sex"]=="m" AND $genarray[$arraynr]["dna"]==1) $countdna++;
								elseif($dna=="mtdna" AND $genarray[$arraynr]["sex"]=="v" AND $genarray[$arraynr]["dna"]==1) $countdna++;
							} 
							$genarray[$arraynr]["nrc"]=$countdna;
						}
					}
					for ($i=0; $i<=substr_count($familyDb->fam_children, ";"); $i++){
						@$childDb = $db_functions->get_person($child_array[$i]);
						// *** Use person class ***
						$child_cls = New person_cls;
						$child_cls->construct($childDb);

						if($screen_mode=='') {
							echo '<div class="children">';
							echo '<div class="child_nr">'.$childnr.'.</div> ';
							echo $child_cls->name_extended("child");
						}
						if($screen_mode=='PDF') {
							// *** PDF rendering of name and details ***
							$pdf->SetFont('Arial','B',11);
							$pdf->SetLeftMargin($indent);
							$pdf->Write(6,$childnr.'. ');

							unset ($templ_person);
							unset ($templ_name);
							$pdfdetails=$child_cls->name_extended("child");
							if($pdfdetails) {
								//$pdf->write_name($pdfdetails,$pdf->GetX()+5,"long");
								$pdf->write_name($templ_name,$pdf->GetX()+5,"child");

								// *** Resets line ***
								//$pdf->MultiCell(0,8,'',0,"L");   // NOT IN USE WITH CHILD
							}
							//$indent=$pdf->GetX();
						}
						if($screen_mode=='RTF') {
							$rtf_text=$childnr.'. ';
							$sect->writeText($rtf_text, $arial12, new PHPRtfLite_ParFormat());

							$rtf_text=strip_tags($child_cls->name_extended("child"),'<b><i>');
							$sect->writeText($rtf_text, $arial12);
						}
						if($screen_mode=='STAR') {
							$chdn_in_gen=$nrchldingen + $childnr;
							$place=$lst_in_array+$chdn_in_gen;

//if (isset($genarray[$arraynr]["sex"]) AND isset($genarray[$arraynr]["dna"] )){
							if(($dna=="ydnamark" OR $dna=="ydna") AND $childDb->pers_sexe=="M" 
									AND $genarray[$arraynr]["sex"]=="m" AND $genarray[$arraynr]["dna"]==1) {
								$genarray[$place]["dna"]=1;
							}
							elseif(($dna=="mtdnamark" OR $dna=="mtdna") AND $genarray[$arraynr]["sex"]=="v" AND $genarray[$arraynr]["dna"]==1) {
								$genarray[$place]["dna"]=1;
							}
							elseif($dna=="ydna" OR $dna=="mtdna") {
								continue;
							}
							else {
								$genarray[$place]["dna"]="no";
							}
//}

							$genarray[$place]["gen"]=$descendant_loop+1;
							$genarray[$place]["par"]=$arraynr;
							$genarray[$place]["chd"]=$childnr;
							$genarray[$place]["non"]=0;
							$genarray[$place]["nrc"]=0;
							$genarray[$place]["2nd"]=0;
							$name=$child_cls->person_name($childDb);
							$genarray[$place]["nam"]=$name["standard_name"].$name["colour_mark"];
							$genarray[$place]["init"]=$name["initials"];
							$genarray[$place]["short"]=$name["short_firstname"];
							$genarray[$place]["gednr"]=$childDb->pers_gedcomnumber;
							if($childDb->pers_fams) {
								$childfam=explode(";",$childDb->pers_fams);
								$genarray[$place]["fams"]=$childfam[0];
							}
							else {
								$genarray[$place]["fams"]=$childDb->pers_famc;
							}
							if($childDb->pers_sexe == "F") { $genarray[$place]["sex"]="v"; }
							else { $genarray[$place]["sex"]="m"; }
						}

						// *** Build descendant_report ***
						if ($descendant_report==true AND $childDb->pers_fams AND $descendant_loop<$max_generation){

							// *** 1st family of child ***
							$child_family=explode(";",$childDb->pers_fams);

							// *** Check for double families in descendant report (if a person relates or marries another person in the same family) ***
							if (isset($check_double) AND in_array($child_family[0], $check_double)){
								// *** Don't show this family, double... ***
							}
							else
								$descendant_family_id2[]=$child_family[0];

							if($screen_mode!='STAR') {
								// *** Save all marriages of person in check array ***
								for ($k=0; $k<count($child_family) ; $k++) {
									$check_double[]=$child_family[$k];
									// *** Save "Follows: " text in array, also needed for doubles... ***
									$follows_array[]=$number_roman[$descendant_loop+2].'-'.$number_generation[count($descendant_family_id2)];
								}
							}

							if ($screen_mode=='STAR') {
								if (count($child_family)>1) {
									for ($k=1; $k<count($child_family) ; $k++) {
										$childnr++;
										$thisplace=$place+$k;
										$genarray[$thisplace]=$genarray[$place];
										$genarray[$thisplace]["chd"]=$childnr;
										$genarray[$thisplace]["2nd"]=1;
										$genarray[$arraynr]["nrc"]+=1;
									}
								}
							}

							// *** YB: show children first in descendant_report ***
							$descendant_main_person2[]=$childDb->pers_gedcomnumber;
							if($screen_mode=='') {
								$search_nr=array_search($child_family[0], $check_double);
								echo '<b><i>, '.__('follows').': </i></b>';
								echo '<a href="'.str_replace("&","&amp;",$_SERVER['REQUEST_URI']).'#'.$follows_array[$search_nr].'">'.$follows_array[$search_nr].'</a>';
							}

							if($screen_mode=='PDF') {
								// PDF rendering of link to own family
								$pdf->Write(6,', '.__('follows').': ');
								$search_nr=array_search($child_family[0], $check_double);
								$romnr=$follows_array[$search_nr];
								$link[$romnr]=$pdf->AddLink();
								$pdf->SetFont('Arial','U',11);  $pdf->SetTextColor(28,28,255);
								$pdf->Write(6,$romnr."\n",$link[$romnr]);
								$pdf->SetFont('Arial','',12); $pdf->SetTextColor(0);
								$parentchild[$romnr]=$id;
							}

							if($screen_mode=='RTF') {
								$search_nr=array_search($child_family[0], $check_double);
								$rtf_text='<b><i>, '.__('follows').': </i></b>'.$follows_array[$search_nr];
								$sect->writeText($rtf_text, $arial12);
							}

						}
						else{
							if($screen_mode=='') {
								echo $child_cls->person_data("child", $id);
							}
							if($screen_mode=='PDF') {
								// *** PDF rendering of child details ***
								$pdf->Write(6,"\n");
								unset ($templ_person);
								unset ($templ_name);

								$pdf_child=$child_cls->person_data("child", $id);
								if($pdf_child) {
									$pdf->SetLeftMargin($child_indent);
									$pdf->pdfdisplay($pdf_child,"child");
									$pdf->SetLeftMargin($indent);
								}
							}
							if($screen_mode=='RTF' AND $child_cls->person_data("child", $id)) {
								$rtf_text=strip_tags($child_cls->person_data("child", $id),'<b><i>');
								$sect->writeText($rtf_text, $arial12, $par_child_text);

								// *** Show RTF media ***
								if (!$child_cls->privacy){
									show_rtf_media('person',$childDb->pers_gedcomnumber);
								}
							}
							if($screen_mode=='STAR') {
								$genarray[$place]["non"]=1;
							}
						}

						if($screen_mode=='') {
							echo "</div><br>\n";	// *** Added an empty line between children ***
							//echo '</td></tr>'."\n";
						}
						$childnr++;
					}
					if($screen_mode=='STAR') {
						$nrchldingen += ($childnr-1);
					}
					if($screen_mode=='PDF') {
						$pdf->SetFont('Arial','',12);
					}
				}

				if($screen_mode=='') {
					// *********************************************************************************************
					// *** Check for adoptive parents (just for sure: made it for multiple adoptive parents...) ***
					// *********************************************************************************************
					$famc_adoptive_qry_prep = $db_functions->get_events_kind($familyDb->fam_gedcomnumber,'adoption');
					foreach($famc_adoptive_qry_prep as $famc_adoptiveDb){
						echo '<tr><td colspan="4"><div class="children">';
						@$childDb = $db_functions->get_person($famc_adoptiveDb->event_connect_id);
						// *** Use person class ***
						$child_cls = New person_cls;
						$child_cls->construct($childDb);
						echo '<b>'.__('Adopted child:').'</b> '.$child_cls->name_extended("child");
						echo '</div></td></tr>'."\n";
					}

					// *************************************************************
					// *** Check for adoptive parent ESPECIALLY MADE FOR ALDFAER ***
					// *************************************************************
					$famc_adoptive_by_person_qry_prep = $db_functions->get_events_kind($familyDb->fam_man,'adoption_by_person');
					foreach($famc_adoptive_by_person_qry_prep as $famc_adoptiveDb){
						echo '<tr><td colspan="4"><div class="children">';
						@$childDb = $db_functions->get_person($famc_adoptiveDb->event_connect_id);
						// *** Use person class ***
						$child_cls = New person_cls;
						$child_cls->construct($childDb);

						if ($famc_adoptiveDb->event_gedcom=='steph') echo '<b>'.__('Stepchild').':</b>';
						elseif ($famc_adoptiveDb->event_gedcom=='legal') echo '<b>'.__('Legal child').':</b>';
						elseif ($famc_adoptiveDb->event_gedcom=='foster') echo '<b>'.__('Foster child').':</b>';
						else echo '<b>'.__('Adopted child:').'</b>';

						echo ' '.$child_cls->name_extended("child");
						echo '</div></td></tr>'."\n";
					}
					// *************************************************************
					// *** Check for adoptive parent ESPECIALLY MADE FOR ALDFAER ***
					// *************************************************************
					$famc_adoptive_by_person_qry_prep = $db_functions->get_events_kind($familyDb->fam_woman,'adoption_by_person');
					foreach($famc_adoptive_by_person_qry_prep as $famc_adoptiveDb){
						echo '<tr><td colspan="4"><div class="children">';
						@$childDb = $db_functions->get_person($famc_adoptiveDb->event_connect_id);
						// *** Use person class ***
						$child_cls = New person_cls;
						$child_cls->construct($childDb);

						if ($famc_adoptiveDb->event_gedcom=='steph') echo '<b>'.__('Stepchild').':</b>';
						elseif ($famc_adoptiveDb->event_gedcom=='legal') echo '<b>'.__('Legal child').':</b>';
						elseif ($famc_adoptiveDb->event_gedcom=='foster') echo '<b>'.__('Foster child').':</b>';
						else echo '<b>'.__('Adopted child:').'</b>';

						echo ' '.$child_cls->name_extended("child");
						echo '</div></td></tr>'."\n";
					}


				//if($screen_mode=='') {
					echo "</table><br>\n";

					// *** Show Google map ***
					if ($descendant_report==false AND $maps_presentation=='show') {
						$show_google_map=false;
						// *** Only show main javascript once ***
						if ($family_nr==2){

							$api_key = '';
							if(isset($humo_option['google_api_key']) AND $humo_option['google_api_key']!='') {
								$api_key = "&key=".$humo_option['google_api_key'];
							}

							if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') { 
								echo '<script src="https://maps.google.com/maps/api/js?v=3'.$api_key.'" type="text/javascript"></script>';
							}
							else {
								echo '<script src="http://maps.google.com/maps/api/js?v=3'.$api_key.'" type="text/javascript"></script>';
							}

							echo '<script type="text/javascript">
								var center = null;
								var map=new Array();
								var currentPopup;
								var bounds = new google.maps.LatLngBounds();
							</script>';

							echo '<script type="text/javascript">
								function addMarker(family_nr, lat, lng, info, icon) {
									var pt = new google.maps.LatLng(lat, lng);
									var fam_nr=family_nr;
									bounds.extend(pt);
									//bounds(fam_nr).extend(pt);
									var marker = new google.maps.Marker({
										position: pt,
										icon: icon,
										title: info,
										map: map[fam_nr]
									});
								}
							</script>';
						}

						$maptype = "ROADMAP";
						if(isset($humo_option['google_map_type'])) {
							$maptype = $humo_option['google_map_type']; 
						}
						echo '<script type="text/javascript">

							function initMap'.$family_nr.'(family_nr) {
								var fam_nr=family_nr;
								map[fam_nr] = new google.maps.Map(document.getElementById(fam_nr), {
									center: new google.maps.LatLng(50.917293, 5.974782),
									maxZoom: 16,
									mapTypeId: google.maps.MapTypeId.'.$maptype.',
									mapTypeControl: true,
									mapTypeControlOptions: {
										style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR
									}
								});
								';

								unset($location_array); unset($lat_array); unset($lon_array);
								unset($text_array);

								$location_array[]=''; $lat_array[]=''; $lon_array[]='';
								$text_array[]='';

								// BIRTH man
								if (!$parent1_cls->privacy){
									$location_var = $parent1Db->pers_birth_place; 
									if($location_var !='') {
										$short=__('BORN_SHORT');
										if($location_var=='') {
											$location_var = $parent1Db->pers_bapt_place; 
											$short=__('BAPTISED_SHORT');
										}
										$location_prep->execute();
										$man_birth_result = $location_prep->rowCount();
										if($man_birth_result >0) {
											$info = $location_prep->fetch();
											$name=$parent1_cls->person_name($parent1Db);
											$google_name=$name["standard_name"];

											$location_array[]=$location_var;
											$lat_array[]=$info['location_lat'];
											$lon_array[]=$info['location_lng'];
											$text_array[]=addslashes($google_name.", ".$short.' '.$location_var );
										}
									}
								}

								// BIRTH woman
								if (!$parent2_cls->privacy){
									$location_var = $parent2Db->pers_birth_place;
									if($location_var !='') {
										$short=__('BORN_SHORT');
										if($location_var=='') {
											$location_var = $parent2Db->pers_bapt_place;
											$short=__('BAPTISED_SHORT');
										}
										$location_prep->execute();
										$woman_birth_result = $location_prep->rowCount();
										if($woman_birth_result >0) {
											$info = $location_prep->fetch();
											$name=$parent2_cls->person_name($parent2Db);
											$google_name=$name["standard_name"];
											$key = array_search($location_var , $location_array);
											if (isset($key) AND $key>0){
												$text_array[$key].="\\n".addslashes($google_name.", ".$short.' '.$location_var );
											}
											else{
												$location_array[]=$location_var ;
												$lat_array[]=$info['location_lat'];
												$lon_array[]=$info['location_lng'];
												$text_array[]=addslashes($google_name.", ".$short.' '.$location_var );
											}
										}
									}
								}

								// DEATH man
								if (!$parent1_cls->privacy){
									$location_var = $parent1Db->pers_death_place;
									$short = __('DIED_SHORT');
									if($location_var=='') {
										$location_var = $parent1Db->pers_buried_place;
										$short = __('BURIED_SHORT');
									}
									if($location_var !='') {
										$location_prep->execute();
										$man_death_result = $location_prep->rowCount();

										if($man_death_result >0) {
											$info = $location_prep->fetch();
	
											$name=$parent1_cls->person_name($parent1Db);
											$google_name=$name["standard_name"];
											$key = array_search($location_var, $location_array);
											if (isset($key) AND $key>0){
												$text_array[$key].="\\n".addslashes($google_name.", ".$short.' '.$location_var);
											}
											else{
												$location_array[]=$location_var;
												$lat_array[]=$info['location_lat'];
												$lon_array[]=$info['location_lng'];
												$text_array[]=addslashes($google_name.", ".$short.' '.$location_var);
											}
										}
									}
								}

								// DEATH woman
								if (!$parent2_cls->privacy){
									$location_var = $parent2Db->pers_death_place;
									$short = __('DIED_SHORT');
									if($location_var=='') {
										$location_var = $parent2Db->pers_buried_place;
										$short = __('BURIED_SHORT');
									}
									if($location_var !='') {
										$location_prep->execute();
										$woman_death_result = $location_prep->rowCount();
										if($woman_death_result >0) {
											$info = $location_prep->fetch();

											$name=$parent2_cls->person_name($parent2Db);
											$google_name=$name["standard_name"];
											$key = array_search($location_var , $location_array);
											if (isset($key) AND $key>0){
												$text_array[$key].="\\n".addslashes($google_name.", ".$short.' '.$location_var );
											}
											else{
												$location_array[]=$location_var ;
												$lat_array[]=$info['location_lat'];
												$lon_array[]=$info['location_lng'];
												$text_array[]=addslashes($google_name.", ".$short.' '.$location_var );
											}
										}
									}
								}

								// MARRIED
								$location_var = $familyDb->fam_marr_place;
								if($location_var !='') {
									$location_prep->execute();
									$marriage_result = $location_prep->rowCount();
									
									if($marriage_result >0) {
										$info = $location_prep->fetch();

										$name=$parent1_cls->person_name($parent1Db);
										$google_name=$name["standard_name"];

										$name=$parent2_cls->person_name($parent2Db);
										$google_name.=' & '.$name["standard_name"];

										if (!$parent1_cls->privacy AND !$parent2_cls->privacy){
											$key = array_search($familyDb->fam_marr_place, $location_array);
											if (isset($key) AND $key>0){
												$text_array[$key].="\\n".addslashes($google_name.", ".__('married').' '.$familyDb->fam_marr_place);
											}
											else{
												$location_array[]=$familyDb->fam_marr_place;
												$lat_array[]=$info['location_lat'];
												$lon_array[]=$info['location_lng'];
												$text_array[]=addslashes($google_name.", ".__('married').' '.$familyDb->fam_marr_place);
											}
										}
									}
								}


								$child_array=explode(";",$familyDb->fam_children);
								for ($i=0; $i<=substr_count($familyDb->fam_children, ";"); $i++){
									@$childDb = $db_functions->get_person($child_array[$i]);
									if ($childDb !== false) {  // no error in query
										// *** Use person class ***
										$person_cls = New person_cls;
										$person_cls->construct($childDb);
										if (!$person_cls->privacy){

											// *** Child birth ***
											$location_var = $childDb->pers_birth_place;
											if($location_var !='') {
												$location_prep->execute();
												$child_result = $location_prep->rowCount();

												if($child_result >0) {
													$info = $location_prep->fetch();

													$name=$person_cls->person_name($childDb);
													$google_name=$name["standard_name"];
													$key = array_search($childDb->pers_birth_place, $location_array);
													if (isset($key) AND $key>0){
														$text_array[$key].="\\n".addslashes($google_name.", ".__('BORN_SHORT').' '.$childDb->pers_birth_place);
													}
													else{
														$location_array[]=$childDb->pers_birth_place;
														$lat_array[]=$info['location_lat'];
														$lon_array[]=$info['location_lng'];
														$text_array[]=addslashes($google_name.", ".__('BORN_SHORT').' '.$childDb->pers_birth_place);
													}
												}
											}
											// *** Child death ***
											$location_var = $childDb->pers_death_place;
											if($location_var !='') {
												$location_prep->execute();
												$child_result = $location_prep->rowCount();
												
												if($child_result >0) {
													$info = $location_prep->fetch();

													$name=$person_cls->person_name($childDb);
													$google_name=$name["standard_name"];
													$key = array_search($childDb->pers_death_place, $location_array);
													if (isset($key) AND $key>0){
														$text_array[$key].="\\n".addslashes($google_name.", ".__('DIED_SHORT').' '.$childDb->pers_death_place);
													}
													else{
														$location_array[]=$childDb->pers_death_place;
														$lat_array[]=$info['location_lat'];
														$lon_array[]=$info['location_lng'];
														$text_array[]=addslashes($google_name.", ".__('DIED_SHORT').' '.$childDb->pers_death_place);
													}
												}
											}
										}
									}
								}


								// *** Add all markers from array ***
								for ($i=1; $i<count($location_array); $i++){
									$show_google_map=true;

									$api_key = '';
									if(isset($humo_option['google_api_key']) AND $humo_option['google_api_key']!='') {
										$api_key = "&key=".$humo_option['google_api_key'];
									}

									if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') { 
										echo ("addMarker($family_nr,$lat_array[$i], $lon_array[$i], '".$text_array[$i]."', 'https://chart.googleapis.com/chart?chst=d_map_spin&chld=0.5|0|f7fe2e|10|_|".$api_key."');\n");
									}
									else {
										echo ("addMarker($family_nr,$lat_array[$i], $lon_array[$i], '".$text_array[$i]."', 'http://chart.googleapis.com/chart?chst=d_map_spin&chld=0.5|0|f7fe2e|10|_|".$api_key."');\n");
									}
								}

								echo 'center = bounds.getCenter();';
								echo 'map[fam_nr].fitBounds(bounds);';
							echo '}
						</script>';

						if ($show_google_map==true){
							echo __('Family events').'<br>';

							echo '<div style="width: 600px; height: 300px; border: 0px; padding: 0px;" id="'.$family_nr.'"></div>';
							
							echo '<script type="text/javascript">
								initMap'.$family_nr.'('.$family_nr.');
							</script>
							';
						}

					}


				}
				if($screen_mode=='STAR') {
					$arraynr++;
				}

			} // Show multiple marriages

		} // Multiple families in 1 generation

	} // nr. of generations
	} // end if not STARSIZE
} // End of single person

// *** If source footnotes are selected, show them here ***
if (isset($_SESSION['save_source_presentation']) AND $_SESSION['save_source_presentation']=='footnote' AND $screen_mode!="PDF"){
	if ($screen_mode=="RTF"){
		//$rtf_text=strip_tags(show_sources_footnotes(),'<b>');
		//$sect->writeText($rtf_text, $arial12);
		$rtf_text=strip_tags(show_sources_footnotes());
// *** BUG: add Endnote doesn't show text in rtf file! ***
		//$sect->addEndnote($rtf_text);
		$sect->writeText('<br>');
		$sect->writeText($rtf_text, $arial12);
	}
	else{
		echo show_sources_footnotes();
	}
}

// *** Generate citations, that can be used as a source for this person/ page ***
/* EXAMPLE:
"Family Page: Bethel, Catherine Ann Charles." database, Dolly Mae Alpha Index - Wyannie Malone Historical Museum (http://subscriber.bahamasgenealogyrecor ... son=I52982 : accessed 17 April 2016, Catherine Anne Charles Bethel, born 19 feb 1809 at New Providence, Bahamas; citing Christ Church Cathedral - Baptismal Register. Book 2, Whites -Page 99, item 21. for period Feb. 7, 1802 to Dec. 22, 1840.
*/
if ($screen_mode=='' AND $user['group_citation_generation']=='y'){
	$name1=$parent1_cls->person_name($parent1Db);
	if (isset($parent2Db)) $name2=$parent2_cls->person_name($parent2Db);

	echo '<br><b>'.__('Citation for:').' '.__('Family Page').'</b><br>';

	echo '<span class="citation">';
		// *** Name of citation ***
		echo '"'.__('Family Page').': '.$name1['name'];
		if (isset($name2['name']) AND $name2['name']) echo ' &amp; '.$name2['name'].'."';

		// *** Link to family page ***
		echo ' HuMo-genealogy - '.$humo_option["database_name"].' (';

		// *** url_rewrite ***
		if ($humo_option["url_rewrite"]=="j"){
			// *** $uri_path is made header.php ***
			echo $uri_path.'family/'.$tree_id.'/'.$family_id.'/'.$main_person;
		}
		else{
			echo 'http://'.$_SERVER['SERVER_NAME'].'/family.php?tree_id='.$tree_id.'&amp;id='.$family_id.'&amp;main_person='.$main_person;
		}

		echo ' : '.__('accessed').' '.date("d F Y");
		echo ')';

		// *** Name and GEDCOM number of main person ***
		if ($parent1Db){
			echo ' '.$name1['name'].' #'.$parent1Db->pers_gedcomnumber;

			// *** Birth or baptise date ***
			if (isset($family_privacy) AND !$family_privacy){
				if ($parent1Db->pers_birth_date OR $parent1Db->pers_birth_place){
					echo ', '.__('born').' '.date_place($parent1Db->pers_birth_date,$parent1Db->pers_birth_place);
				}
				elseif ($parent1Db->pers_bapt_date OR $parent1Db->pers_bapt_place){
					echo ', '.__('baptised').' '.date_place($parent1Db->pers_bapt_date,$parent1Db->pers_bapt_place);
				}
			}
		}

	echo '</span><br><br>';
}

// *** Extra footer text in family screen ***
if($screen_mode=='') {
	if ($descendant_report==false) {
		// *** Show extra footer text in family screen ***
		$treetext=show_tree_text($dataDb->tree_id, $selected_language);
		echo $treetext['family_footer'];

		if ($user['group_user_notes_show']=='y'){
			$note_qry= "SELECT * FROM humo_user_notes WHERE note_tree_prefix='".$tree_prefix_quoted."'
			AND note_fam_gedcomnumber='".$family_id."' AND note_pers_gedcomnumber='".$main_person."' AND note_status = 'approved'";
			$note_result = $dbh->query($note_qry);
			$num_rows = $note_result->rowCount();

			echo '<table align="center" class="humo">';
			echo '<tr class="humo_user_notes"><th>';
				if ($num_rows)
					echo '<a href="#humo_user_notes"></a> ';
				echo __('User notes').'</th><th colspan="2">';
				if ($num_rows)
					printf(__('There are %d user added notes.'), $num_rows);
				else
					printf(__('There are %d user added notes.'), 0);
			echo '</th></tr>';

			while($noteDb=$note_result->fetch(PDO::FETCH_OBJ)){
				$user_qry = "SELECT * FROM humo_users
					WHERE user_id='".$noteDb->note_user_id."'";
				$user_result = $dbh->query($user_qry);
				$userDb=$user_result->fetch(PDO::FETCH_OBJ);

				echo '<tr class="humo_color"><td valign="top">';
					echo $noteDb->note_date.' '.$noteDb->note_time.' '.$userDb->user_name.'<br>';
					//echo $noteDb->note_names;
				echo '</td><td>';
					echo nl2br($noteDb->note_note);
				echo '</td></tr>';
			}
			echo '</table><br>';
		}


		// *** User is allowed to add a note to a person in the family tree ***
		if ($user['group_user_notes']=='y' AND is_numeric($_SESSION['user_id'])){
			// *** Find user that adds a note ***
			$usersql='SELECT * FROM humo_users WHERE user_id="'.$_SESSION['user_id'].'"';
			$user_note=$dbh->query($usersql);
			$userDb=$user_note->fetch(PDO::FETCH_OBJ);

			// *** Name of selected person in family tree ***
			$name = $parent1_cls->person_name($parent1Db);
			// *** Person url example (optional: "main_person=I23"): http://localhost/humo-genealogy/family/2/F10?main_person=I23/ ***
			$start_url='';
			if (isset($parent1Db->pers_tree_id))
				$start_url=$parent1_cls->person_url2($parent1Db->pers_tree_id,$parent1Db->pers_famc,$parent1Db->pers_fams,$parent1Db->pers_gedcomnumber);

			if (isset($_POST['send_mail'])){
				$gedcom_date=strtoupper(date("d M Y")); $gedcom_time=date("H:i:s");

				// *** note_status show/ hide/ moderate options ***
				$user_register_date=date("Y-m-d H:i");
				$sql="INSERT INTO humo_user_notes SET
				note_date='".$gedcom_date."',
				note_time='".$gedcom_time."',
				note_user_id='".safe_text_db($_SESSION['user_id'])."',
				note_note='".safe_text_db($_POST["user_note"])."',
				note_fam_gedcomnumber='".safe_text_db($family_id)."',
				note_pers_gedcomnumber='".safe_text_db($main_person)."',
				note_tree_id='".$tree_id."',
				note_tree_prefix='".$tree_prefix_quoted."',
				note_names='".safe_text_db($name["standard_name"])."'
				;";
				$result=$dbh->query($sql);

				// *** Mail new user note to the administrator ***
				$register_address=$dataDb->tree_email;
				$register_subject="HuMo-genealogy. ".__('New user note').": ".$userDb->user_name."\n";

				// *** It's better to use plain text in the subject ***
				$register_subject=strip_tags($register_subject,ENT_QUOTES);

				$register_message = sprintf(__('Message sent through %s from the website.'),'HuMo-genealogy');
				$register_message .="<br><br>\n";
				$register_message .=__('New user note')."<br>\n";
				$register_message .=__('Name').':'.$userDb->user_name."<br>\n";
				//$register_message .=__('E-mail').": <a href='mailto:".$_POST['register_mail']."'>".$_POST['register_mail']."</a><br>\n";
				$register_message .=$_POST['user_note']."<br>\n";

				$register_message.=__('User note by family').': <a href="'.$_SERVER['SERVER_NAME'].'/family.php?database='.$tree_prefix_quoted.
				'&amp;id='.$family_id.'&amp;main_person='.$main_person.'">'.safe_text_db($name["standard_name"]).'</a>';

				include_once ('include/mail.php');

				// *** Set who the message is to be sent from ***
				$mail->setFrom($userDb->user_mail, $userDb->user_name);
				// *** Set who the message is to be sent to ***
				$mail->addAddress($register_address, $register_address);
				// *** Set the subject line ***
				$mail->Subject = $register_subject;
				$mail->msgHTML($register_message);
				// *** Replace the plain text body with one created manually ***
				//$mail->AltBody = 'This is a plain-text message body';
				if (!$mail->send()) {
				//	echo '<br><b>'.__('Sending e-mail failed!').' '. $mail->ErrorInfo.'</b>';
				//} else {
				//	echo '<br><b>'.__('E-mail sent!').'</b><br>';
				}

				echo '<table align="center" class="humo">';
				echo '<tr><th><a name="add_info"></a>'.__('Your information is saved and will be reviewed by the webmaster.').'</th></tr>';
				echo '</table>';
			}
			else{

				// *** Script voor expand and collapse of items ***
				echo '
				<script type="text/javascript">
				function hideShow(el_id){
					// *** Hide or show item ***
					var arr = document.getElementsByName(\'row\'+el_id);
					for (i=0; i<arr.length; i++){
						if(arr[i].style.display!="none"){
							arr[i].style.display="none";
						}else{
							arr[i].style.display="";
						}
					}
					// *** Change [+] into [-] or reverse ***
					if (document.getElementById(\'hideshowlink\'+el_id).innerHTML == "[+]")
						document.getElementById(\'hideshowlink\'+el_id).innerHTML = "[-]";
					else
						document.getElementById(\'hideshowlink\'+el_id).innerHTML = "[+]";
				}
				</script>';

				//echo '<form method="POST" action="'.$uri_path.'family.php#add_info" style="display : inline;">';
				echo '<form method="POST" action="'.$start_url.'#add_info" style="display : inline;">';
				echo '<input type="hidden" name="id" value="'.$family_id.'">';
				echo '<input type="hidden" name="main_person" value="'.$main_person.'">';

				echo '<table align="center" class="humo" width="40%">';
				echo '<tr><th class="fonts" colspan="2">';
					echo '<a name="add_info"></a>';

					echo '<a href="'.$start_url.'#add_info" onclick="hideShow(1);"><span id="hideshowlink1">'.__('[+]').'</span></a>';

					echo ' '.__('Add information or remarks').'</th></tr>';

				echo '<tr style="display:none;" id="row1" name="row1"><td>'.__('Person').'</td><td>'.$name["standard_name"].'</td></tr>';

				echo '<tr style="display:none;" id="row1" name="row1"><td>'.__('Name').'</td><td>'.$userDb->user_name.'</td></tr>';

				if ($userDb->user_mail==''){
					print '<tr style="background-color:#FF6600; display:none;" id="row1" name="row1"><td>'.__('E-mail address').'</td><td>'.__('Your e-mail address is missing. Please add you\'re mail address here: ').' <a href="user_settings.php">'.__('Settings').'</a></td></tr>';
				}

				$register_text=''; if (isset($_POST['register_text'])){ $register_text=$_POST['register_text']; }
				print '<tr style="display:none;" id="row1" name="row1"><td>'.__('Text').'</td><td><textarea name="user_note" ROWS="5" COLS="40" class="fonts">'.$register_text.'</textarea></td></tr>';

				print '<tr style="display:none;" id="row1" name="row1"><td></td><td><input class="fonts" type="submit" name="send_mail" value="'.__('Send').'"></td></tr>';
				print '</table>';
				print '</form>';
			}

		}

	}
}


// list appendix of sources
if($screen_mode=="PDF" AND !empty($pdf_source) AND ($source_presentation=='footnote' OR $user['group_sources']=='j') ) {
	include_once(CMS_ROOTPATH."source.php");
	$pdf->AddPage(); // appendix on new page
	$pdf->SetFont('Arial',"B",14);
	$pdf->Write(8,__('Sources')."\n\n");
	$pdf->SetFont('Arial','',10);
	// the $pdf_source array is set in show_sources.php with sourcenr as key and value if a linked source is given
	$count=0;

	foreach($pdf_source as $key => $value) {
		$count++;
		if(isset($pdf_source[$key])) {
			$pdf->SetLink($pdf_footnotes[$count-1],-1);
			$pdf->SetFont('Arial','B',10);
			$pdf->Write(6,$count.". ");
			if($user['group_sources']=='j') {
				source_display($pdf_source[$key]);  // function source_display from source.php, called with source nr.
			}
			elseif ($user['group_sources']=='t') {
				$sourceDb = $db_functions->get_source($pdf_source[$key]);
				if ($sourceDb->source_title OR $sourceDb->source_text){
					//$pdf->SetFont('Arial','B',10);
					//$pdf->Write(6,__('Title').": ");
					$pdf->SetFont('Arial','',10);

					if (trim($sourceDb->source_title))
						$txt = ' '.trim($sourceDb->source_title);
					else $txt =' '.trim($sourceDb->source_text);

					if ($sourceDb->source_date or $sourceDb->source_place){ $txt.=" ".date_place($sourceDb->source_date, $sourceDb->source_place); }
					$pdf->Write(6,$txt."\n");
				}
			}
			$pdf->Write(2,"\n");
			$pdf->SetDrawColor(200);  // grey line
			$pdf->Cell(0,2," ",'B',1);
			$pdf->Write(4,"\n");
		}
	}
	unset($value);
}

if($hourglass===false) { // in hourglass there's more code after family.php is included
	if($screen_mode=='STAR' OR $screen_mode=='STARSIZE') {
		include_once(CMS_ROOTPATH."report_descendant.php");
		generate();
		printchart();
	}

	if($screen_mode=='RTF') {  // initialize rtf generation
		// *** Save rtf document to file ***
		$rtf->save($file_name);

		echo '<br><br><a href="'.$file_name.'">'.__('Download RTF report.').'</a>';
		echo '<br><br>'.__('TIP: Don\'t use Wordpad to open this file (the lay-out will be wrong!). It\'s better to use a text processor like Word or OpenOffice Writer.');

		$text='<br><br><form method="POST" action="'.$uri_path.'family.php?show_sources=1" style="display : inline;">';
		$text.='<input type="hidden" name="id" value="'.$family_id.'">';
		$text.='<input type="hidden" name="main_person" value="'.$main_person.'">';
		$text.='<input type="hidden" name="database" value="'.$database.'">';
		$text.='<input type="hidden" name="screen_mode" value="">';
		if($descendant_report==true) {
			$text.='<input type="hidden" name="descendant_report" value="'.$descendant_report.'">';
		}
		$text.='<input class="fonts" type="Submit" name="submit" value="'.__('Back').'">';
		$text.='</form> ';
		echo $text;
	}
	elseif($screen_mode!='PDF') {
		include_once(CMS_ROOTPATH."footer.php");
	}
	else {
		$pdf->Output($title.".pdf","I");
	}
}

function show_rtf_media($media_kind,$gedcomnumber){
	// *** Show RTF media ***
	global $sect;

	$result = show_media($media_kind,$gedcomnumber);
	if(isset($result[1]) AND count($result[1])>0) {
		$break=0; $textarr = Array(); $goodpics=FALSE;
		foreach($result[1] as $key => $value) {
			if (strpos($key,"path")!==FALSE) {
				$type = substr($result[1][$key],-3);
				if($type=="jpg" OR $type=="png") {
					if($goodpics==FALSE) { //found 1st pic - make table
						$table = $sect->addTable();
						$table->addRow(0.1);
						$table->addColumnsList(array(5,5,5));
						$goodpics=TRUE;
					}
					$break++;
					$cell = $table->getCell(1,$break);
					$imageFile = $value;
					$image = $cell->addImage($imageFile);
					$txtkey = str_replace("pic_path","pic_text",$key); 
					if(isset($result[1][$txtkey])) {
						$textarr[]=$result[1][$txtkey];
					}
					else { $textarr[]="&nbsp;"; }
				}
			}

			//if($break==3) break; // max 3 pics
			// *** Process multiple pictures ***
			if($break==3){
				$break1=0;
				if(count($textarr)>0) {
					$table->addRow(0.1); //add row only if there is photo text
					foreach($textarr as $value) {
						$break1++;
						$cell = $table->getCell(2, $break1);
						$cell->writeText($value);
					}
				}
				unset($textarr);
				$goodpics=FALSE;
				$break=0;
			}

		}
		$break1=0;

		if(isset($textarr) AND count($textarr)>0) {
			$table->addRow(0.1); //add row only if there is photo text
			foreach($textarr as $value) {
				$break1++;
				$cell = $table->getCell(2, $break1);
				$cell->writeText($value);
			}
		}

	}
}

?>