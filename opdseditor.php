<?PHP
//M:OPDS Editor
/*
 | This is an OPDS xml file editor.
 | It can either be used standalone or as part of Helicon Technologies CMS.
 | Helicon technologies CMS defines a global named $id so we test this variable to know if we are stand alone or not.
 | It is assumed that you have write permission to the folder containing the OPDS XML file.
 |
 | Written by: Ori Idan <ori@heliconbooks.com>
 */
global $id;

$imgprefix = "opds";
$fileprefix = "opds";
// $outfile = "opds/opds1.xml";

$xmlfile = isset($_GET['opds']) ? $_GET['opds'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';

function GetMimeType($t) {
	$ext = strrchr($t, '.');
	if($ext == ".jpg")
		return "image/jpeg";
	if($ent == '.png')
		return "image/png";
}

function PrintAcquisitionTypeSelect($acq) {
	$acqtypearr = array('http://opds-spec.org/acquisition' => 'General acquisition',
						'http://opds-spec.org/acquisition/open-access' => 'Open access',
						'http://opds-spec.org/acquisition/buy' => 'Sale',
						'http://opds-spec.org/acquisition/borrow' => 'Landing',
						'http://opds-spec.org/acquisition/subscribe' => 'Subscription',
						'http://opds-spec.org/acquisition/sample' => 'Sampling');

	print "<select name=\"acqtype[]\" class=\"input-medium\">\n";
	foreach($acqtypearr as $k => $v) {
		$s = ($k == $acq) ? "selected" : '';
		print "<option value=\"$k\" $s>$v</option>\n";
	}
	print "</select>\n";
}

function PrintFooter() {
	print "</div></div>\n</body>\n</html>\n";
}

if(!$id) {	/* We are standalone so emit header.html */
	$fd = fopen("head.html", "r");
	while(($line = fgets($fd)) !== false) {
		print $line;
	}
	fclose($fd);
}

if($xmlfile == '') {
	print "<form method=\"get\">\n";
	if($id)	// If not standalone include $id
		print "<input type=\"hidden\" name=\"id\" value=\"$id\">\n";
	print "<table align=\"center\"><tr><td colspan=\"2\">\n";
	print "<h1>OPDS file/stream</h1>\n";
	print "</td></tr>\n";
	print "<tr><td>File/URL: </td>\n";
	print "<td><input type=\"text\" name=\"opds\" value=\"$xmlfile\"></td>\n";
	print "</tr><tr>\n";
	print "<td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Submit\"></td>\n";
	print "</tr>\n";
	print "</table>\n";
	print "</form>\n";
	if(!$id)
		PrintFooter();
	return;
}

if($action == 'opdssubmit') {
	$outfile = htmlspecialchars($_POST['outfile'], ENT_QUOTES);
	$fname = ($outfile) ? $outfile : $xmlfile;
	$out = fopen($fname, "wt");
	if(!$out) {
		print "<h3>Error opening $fname for writing</h3>\n";
	}
	else {
		fwrite($out, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
		fwrite($out, "<feed xmlns=\"http://www.w3.org/2005/Atom\" xmlns:dc=\"http://purl.org/dc/terms/\" xmlns:opds=\"http://opds-spec.org/2010/catalog\">\n");
		$id = htmlspecialchars($_POST['id'], ENT_QUOTES);
		fwrite($out, "<id>$id</id>\n");
		$self = htmlspecialchars($_POST['self'], ENT_QUOTES);
		fwrite($out, "<link rel=\"self\" href=\"$self\" type=\"application/atom+xml;profile=opds-catalog;kind=acquisition\"/>\n");
		$root = htmlspecialchars($_POST['root'], ENT_QUOTES);
		fwrite($out, "<link rel=\"root\" href=\"$root\" type=\"application/atom+xml;profile=opds-catalog;kind=acquisition\"/>\n");
		$opdstitle = htmlspecialchars($_POST['opdstitle'], ENT_QUOTES);
		fwrite($out, "<title>$opdstitle</title>\n");
		$updated = date("Y-m-d\Th:m:s\Z");
		fwrite($out, "<updated>$updated</updated>\n");
		$author = htmlspecialchars($_POST['author'], ENT_QUOTES);
		fwrite($out, "<author><name>$author</name></author>\n");
		$entrytitle = $_POST['entrytitle'];
		$entryid = $_POST['entryid'];
		$author = $_POST['entryauthor'];
		$lang = $_POST['entrylang'];
		$summary = $_POST['summary'];
		$entryimg = $_POST['entryimg'];
		$acquisition = $_POST['acquisition'];
		$acqtype = $_POST['acqtype'];
		$price = $_POST['price'];
		$curcode = $_POST['curcode'];
		$i = 0;
		foreach($entryid as $id) {
			fwrite($out, "<entry>\n");
			$t = htmlspecialchars($entrytitle[$i], ENT_QUOTES);
			fwrite($out, "<title>$t</title>\n");
			$t = htmlspecialchars($id, ENT_QUOTES);
			fwrite($out, "<id>$t</id>\n");
			fwrite($out, "<updated>$updated</updated>\n");
			$t = htmlspecialchars($author[$i], ENT_QUOTES);
			fwrite($out, "<author><name>$t</name></author>\n");
			$t = htmlspecialchars($lang[$i], ENT_QUOTES);
			fwrite($out, "<dc:language>$t</dc:language>\n");
			$t = htmlspecialchars($summary[$i], ENT_QUOTES);
			fwrite($out, "<summary>\n$t</summary>\n");
			$t = htmlspecialchars($entryimg[$i], ENT_QUOTES);
			$mimetype = GetMimeType($t);
			fwrite($out, "<link rel=\"http://opds-spec.org/image\" href=\"$t\" type=\"$mimetype\" />\n");
			$t = htmlspecialchars($acquisition[$i], ENT_QUOTES);
			$atype = htmlspecialchars($acqtype[$i], ENT_QUOTES);
			fwrite($out, "<link rel=\"$atype\" href=\"$t\" type=\"application/epub+zip\" />\n");
			$p = htmlspecialchars($price[$i], ENT_QUOTES);
			$c = htmlspecialchars($curcode[$i], ENT_QUOTES);
			if($p)
				fwrite($out, "<opds:price currencycode=\"$c\">$p</opds:price>\n");
			fwrite($out, "</entry>\n");
			$i++;
		}
		fwrite($out, "</feed>\n");
		fclose($out);
		print "<h2>OPDS file written</h2>\n";
	}
}
/* Now the real part of the editor. Read the given XML file and parse it */
libxml_use_internal_errors(true);
$xmlstring = file_get_contents($xmlfile);
$xmlstring = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $xmlstring); 
$xml = simplexml_load_string($xmlstring);
//$xml = simplexml_load_file($xmlfile);
if(!$xml) {
	print "<h2>Failed loading: $xmlfile</h2>\n";
	foreach(libxml_get_errors() as $error) {
		echo $error->message;
		print "<br />\n";
	}
	if(!$id)
		PrintFooter();
	return;
}
if($xml->getName() != "feed") {
	print "<h2>Error $xmlfile is not a feed file</h2>\n";
	if(!$id)
		PrintFooter();
	return;
}

/* If we got here we got a valid OPDS stream in $xml object so print editing form */
if($id)
	print "<form action=\"?id=$id&amp;action=opdssubmit&amp;opds=$xmlfile\" method=\"post\">\n";
else
	print "<form action=\"opdseditor.php?action=opdssubmit&amp;opds=$xmlfile\" method=\"post\">\n";
print "<h2 style=\"margin-bottom:5px\">General parameters</h2>\n";
print "<table class=\"formtbl\" border=\"0\">\n<tr>\n";
$fname = strrchr($xmlfile, '/');
$outfile = "${fileprefix}$fname";
print "<td>Output file: &nbsp;</td><td><input type=\"text\" name=\"outfile\" value=\"$outfile\"></td></tr><tr>\n";

print "<td>OPDS id: &nbsp;</td>\n";
$id = $xml->id;
print "<td><input type=\"text\" name=\"id\" value=\"$id\"></td>\n";
print "</tr><tr>\n";

/* I am sure there is a simpler more efficient way but I am not familiar enough with PHP objects */
foreach($xml->link as $link) {
	foreach($link->attributes() as $k => $a) {
		$at[$k] = $a;
	}
	if($at['rel'] == "self")
		$self = $at['href'];
	if($at['rel'] == "root")
		$root = $at['href'];
}
print "<td>Self link: </td>\n";
print "<td><input type=\"text\" name=\"self\" value=\"$self\"></td>\n";
print "</tr><tr>\n";
print "<td>Root link: </td>\n";
print "<td><input type=\"text\" name=\"root\" value=\"$root\"></td>\n";
print "</tr><tr>\n";

$opdstitle = $xml->title;
print "<td>Catalog title: &nbsp;</td>\n";
print "<td><input type=\"text\" name=\"opdstitle\" value=\"$opdstitle\"></td>\n";
print "</tr><tr>\n";

$author = $xml->author->name;
print "<td>Author name: &nbsp;</td>\n";
print "<td><input type=\"text\" name=\"author\" value=\"$author\"></td>\n";
print "</tr>\n";
print "</table>\n";

/* Now the actual entries */
print "<h2>Entries data</h2>\n";
print "<table class=\"formtbl\" border=\"1\" width=\"100%\">\n";
$c = 0;
foreach($xml->entry as $entry) {
	if($c == 0)
		print "<tr><td valign=\"top\">\n";
	else
		print "<td valign=\"top\">\n";
//	print_r($entry);
	print "<table >\n";
	$entrytitle = $entry->title;
	$entryid = $entry->id;
	print "<tr>\n";
	print "<td>Title: &nbsp;</td>\n";
	print "<td><input type=\"text\" class=\"input-medium\" name=\"entrytitle[]\" value=\"$entrytitle\"></td>\n";
	print "</tr><tr>\n";
	print "<td>Id: </td>\n";
	print "<td><input type=\"text\" class=\"input-medium\" name=\"entryid[]\" value=\"$entryid\"></td>\n";
	print "</tr><tr>\n";
	$entryauthor = $entry->author->name;
	print "<td>Author: </td>\n";
	print "<td><input type=\"text\" class=\"input-medium\" name=\"entryauthor[]\" value=\"$entryauthor\"></td>\n";
	print "</tr><tr>\n";
	$entrylang = $entry->{'dclanguage'};
/*	if($entrylang == '')
		$entrylang = $deflang; */
	print "<td>language: &nbsp;</td>\n";
	print "<td><input type=\"text\" class=\"input-small\" name=\"entrylang[]\" value=\"$entrylang\"></td>\n";
	print "</tr><tr>\n";

	/* I am sure there is a simpler more efficient way but I am not familiar enough with PHP objects */
	foreach($entry->link as $link) {
		foreach($link->attributes() as $k => $a) {
			$at[$k] = $a;
		}
		if(strpos($at['rel'], "image"))
			$entryimg = $at['href'];
		else {
			$acquisition = $at['href'];
			$acqtype = $at['rel'];
		}
	}
	print "<td colspan=\"2\"><img src=\"$imgprefix/$entryimg\" alt=\"Entry image\" width=\"200px\" /><br />\n";
	print "<input type=\"text\" class=\"input-medium\" name=\"entryimg[]\" value=\"$entryimg\"></td>\n";
	print "</tr><tr>\n";
	print "<td colspan=\"2\">\n";
	print "Acquisition link: <br />\n";
	print "<input type=\"text\" class=\"input-medium\" name=\"acquisition[]\" value=\"$acquisition\"></td>\n";
	print "</tr><tr>\n";
	print "<td>Type: </td><td>\n";
	PrintAcquisitionTypeSelect($acqtype);
	print "</td></tr><tr>\n";
	
	$price = $entry->{'opdsprice'};
	$curcode = $price->attributes();
//	print_r($curcode);
	print "<td>Price: </td><td><input type=\"text\" class=\"input-small\" name=\"price[]\" value=\"$price\"></td>\n";
	print "</tr><tr>\n";
	print "<td>Curr. code: </td><td><input type=\"text\" class=\"input-small\" name=\"curcode[]\" value=\"$curcode\"></td>\n";
	print "</tr><tr>\n";

	$summary = $entry->summary;
	print "<td colspan=\"2\">Summary:<br />\n";
	print "<textarea rows=\"3\" name=\"summary[]\">$summary</textarea>\n";
	print "</td></tr>\n";
	print "</table>\n";
	print "</td>\n";
	$c++;
	if($c == 4)
		$c = 0;
}
print "<table width=\"100%\"><tr><td align=\"center\">\n";
print "<input type=\"submit\" value=\"Submit\" class=\"btn btn-primary\"></td></tr>\n";
print "</table>\n";
print "</form>\n";
if(!$id)
	PrintFooter();

?>

