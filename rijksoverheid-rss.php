<?php 

if(isset($_GET["q"])) $query=$_GET["q"]; else $query=NULL;

if (trim($query)=="")
{
?>
       <meta name="viewport" content="width=device-width, initial-scale=1">
       <form action="rijksoverheid-rss.php">
       <input name="q" placeholder="rotterdam OR amsterdam OR schiedam"><br>
	<input type="submit"></form>
	<?php
	die();
}

header('Content-Type: application/rss+xml; charset=utf-8');
// header('Content-Type: text/plain; charset=utf-8');

 
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
	<link>rijksoverheid-zoek-rss.php?<?php print $query; ?></link>
<?php

libxml_use_internal_errors(true);

$body=file_get_contents("https://www.rijksoverheid.nl/zoeken?trefwoord=".urlencode($query)."&sorteren%2Dop=datum");

$dom_body = new DOMDocument();

$dom_body->validateOnParse = true;

@$dom_body->loadHTML($body);

$classname = 'common results';
$finder = new DomXPath($dom_body);
//$nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");

$h3s = $finder->query("//div[@class='$classname']//h3");
$links = $finder->query("//div[@class='$classname']//@href");
$ps = $finder->query("//div[@class='$classname']//p");


$pubDate_old=date("r"); // failsafe

$a=0;
while ($a<10)
{
	//titel
	$title=trim($h3s->item($a)->textContent);

	//link
	$link="https://www.rijksoverheid.nl".trim($links->item($a)->textContent);

	//beschrijving
	$description=clean_up(trim($ps->item($a)->textContent));
	
	//datum
	$datum_class=$finder->query("/html/body/div[1]/main/div/div[1]/div[5]/a[$a]/p[2]");

	unset($pubDate);

	if($datum_class->item(0)!==null)
	{
		$pubDate= substr($datum_class->item(0)->textContent,strpos($datum_class->item(0)->textContent,"|")+2);
	}
	else
	{
		preg_match("/\/\d{4}\/\d{2}\/\d{2}\/.*$/", $link, $datum );
		if (isset($datum[0])) $pubDate= substr($datum[0],1,11);
	}

	if (!isset($pubDate))
		$pubDate=$pubDate_old;
	else
		$pubDate_old=$pubDate;

	print "\t<item>\n";
	print "\t\t<title>".clean_up($title)."</title>\n";
	print "\t\t<link>$link</link>\n";
	print "\t\t<guid>$link</guid>\n";
	print "\t\t<description>$description</description>\n";
	print "\t\t<pubDate>".date("r",strtotime($pubDate))."</pubDate>\n";
	print "\t</item>\n";
		
	$a++;
}

function clean_up($subject) 
{
	$subject=str_replace("&","&#x26;",$subject); //was &amp;
	$subject=str_replace(">","&gt;",$subject);
	$subject=str_replace("<","&#x3C;",$subject); // was &lt;
	$subject=str_replace(chr(8),"",$subject); // edge case
	return $subject;
}


?>	</channel>
</rss>
