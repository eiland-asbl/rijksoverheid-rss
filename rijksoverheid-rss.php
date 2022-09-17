<?php 

if(isset($_GET["q"])) $query=$_GET["q"]; else $query=NULL;

if (trim($query)=="")
{
?>
       <meta name="viewport" content="width=device-width, initial-scale=1">
       <form action="rijksoverheid-rss.php">
       <input name="q" placeholder="rotterdam OR amsterdam OR Schiedam"><br>
	<input type="submit"></form>
	<?php
	die();
}

//header('Content-Type: application/rss+xml; charset=utf-8');
header('Content-Type: text/plain; charset=utf-8');

 
 ?><rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	>
<channel>
	<title>Rijksoverheid - <?php print $query; ?></title>
	<lastBuildDate><?php print date("r"); ?></lastBuildDate>
	<language>nl</language>
	<sy:updatePeriod>hourly</sy:updatePeriod>
	<sy:updateFrequency>1</sy:updateFrequency>
	<generator>custom</generator>
	<description>custom</description>
	<link>rijksoverheid-rss.php?<?php print $query; ?></link>
<?php

libxml_use_internal_errors(true);

$body=file_get_contents("https://www.rijksoverheid.nl/zoeken?trefwoord=".urlencode($query)."&sorteren-op=datum");

$dom_body = new DOMDocument();

$dom_body->validateOnParse = true;

@$dom_body->loadHTML($body);

$ul=$dom_body->getElementsByTagName('ol')->item(0);

$loop=0;

$pubDate_old=date("r"); // failsafe

$check=TRUE;

while($loop <= 10 && $check)
{ 
 	$item=$ul->getElementsByTagName('li')->item($loop);

//	print $item->nodeValue;

	$title= $item->getElementsByTagName('h3')->item(0)->textContent;

	$description= $item->getElementsByTagName('p')->item(0)->nodeValue;
	
	$link= $item->getElementsByTagName('a')->item(0)->getAttribute('href');
	
	$datum_class = $item->getElementsByTagName('p');
	
	$loop++;
	
	$check=$ul->getElementsByTagName('li')->item($loop+1);


// process

//	if($datum_class->item(1)!==null)
//	{
//		$pubDate= substr($datum_class->item(1)->textContent, strpos($datum_class->item(1)->textContent,"|")+2, 10);
//	}
//	else
//	{
		preg_match("/\/\d{4}\/\d{2}\/\d{2}\/.*$/", $link, $datum );
		if (isset($datum[0])) $pubDate= substr($datum[0],1,11);
//	}

	if (!isset($pubDate))
		$pubDate=$pubDate_old;
	else
		$pubDate_old=$pubDate;

	print "\t<item>\n";
	print "\t\t<title>".clean_up($title)."</title>\n";
	print "\t\t<link>https://www.rijksoverheid.nl$link</link>\n";
	print "\t\t<guid>https://www.rijksoverheid.nl$link</guid>\n";
	print "\t\t<description>".clean_up($description)."</description>\n";
//	print "\t\t<pubDate>".date("r",strtotime($pubDate))." - ". $datum_class->item(1)->textContent."</pubDate>\n";
	print "\t\t<pubDate>".date("r",strtotime($pubDate))."</pubDate>\n";
	print "\t</item>\n";


}

function clean_up($subject) 
{
	$subject=str_replace("&","&#x26;",$subject); //was &amp;
	$subject=str_replace(">","&gt;",$subject);
	$subject=str_replace("<","&#x3C;",$subject); // was &lt;
	$subject=str_replace(chr(8),"",$subject); // edge case, added dec 8th 2015 (backspace)
	return trim($subject);
}

?>	</channel>
</rss>
