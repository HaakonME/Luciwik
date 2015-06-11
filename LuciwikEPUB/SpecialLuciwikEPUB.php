<?php

  /*
    LuciwikEPUB - EPUB generator extension for MediaWiki
    Copyright © 2012-2014  Mikael Ylikoski

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
  */

if (!class_exists('LuciEPUB'))
    require_once 'LuciEPUB.php';

class SpecialLuciwikEPUB extends SpecialPage {
    function __construct() {
	parent::__construct('LuciwikEPUB');
    }

    function execute($par) {
	global $wgArticlePath;
	global $wgOut;
	global $wgRequest;
	global $wgUser;
	global $wgLuciwikEPUBLimit;

	/* This check is not really necessary because users without
	   'read' permission will not be able to read this page */
	if (method_exists($this, 'getUser'))	// MediaWiki versions >= 1.18
	    $user = $this->getUser();
	else
	    $user = $wgUser;
	if ((!$user->isAllowed('read')) or $user->isBlocked())
	    return;

	wfResetOutputBuffers();

	if (method_exists($this, 'getRequest'))	// MediaWiki versions >= 1.18
	    $request = $this->getRequest();
	else
	    $request = $wgRequest;

	if ($par) {
	    //$oldid = $request->getText('oldid');
	    return $this->generate(NULL, array($par));
	}

	session_name('LuciwikID');
	@session_start();

	$error = FALSE;
	$cmd = $request->getText('cmd');
	if ($cmd == 'add') {
	    if (array_key_exists('pages', $_SESSION) and
		count($_SESSION['pages']) >= $wgLuciwikEPUBLimit) {
		$error = 'add';
	    } else {
		$article = $request->getText('article');
		//$oldid = $request->getText('oldid');
		$_SESSION['pages'][] = $article;
	    }
	} elseif ($cmd == 'remove') {
	    $item = $request->getText('item');
	    unset($_SESSION['pages'][$item]);
	} elseif ($cmd == 'clear') {
	    unset($_SESSION['pages']);
	} elseif ($cmd == 'generate') {
	    if (array_key_exists('pages', $_SESSION)) {
		$name = $request->getText('name');
		$options = array();
		$options['discussion'] = $request->getCheck('discussion');
		return $this->generate($name, $_SESSION['pages'], $options);
	    } else
		$error = 'gen';
	}

	if (method_exists($this, 'getOutput'))
	    $output = $this->getOutput();
	else
	    $output = $wgOut;

	$this->setHeaders();

	$url = str_replace('$1', 'Special:LuciwikEPUB', $wgArticlePath);
	$purl = wfParseUrl(wfExpandUrl($url));
	$title = NULL;
	$text = '';

	if ($error == 'add')
	    $text .= "<p>" . wfMsgHtml('luciwikepub-error-add') . "</p>\n";

	if ($error == 'gen') {
	    $text .= "<p>" . wfMsgHtml('luciwikepub-error-cookies') . "</p>\n";
	} else if (empty($_SESSION['pages'])) {
	    $text .= "<p>" . wfMsgHtml('luciwikepub-no-selected') . "</p>\n";
	} else {
	    $text .= "<p>" . wfMsgHtml('luciwikepub-selected') . ":</p>\n";
	    $text .= "<ul>";
	    foreach ($_SESSION['pages'] as $key => $page) {
		$text .= "<li>";
		$text .= "<form action='" . $url . "'>";

		$text .= "<a href='" . str_replace('$1', urlencode($page),
						   $wgArticlePath) . "'>";
		$text .= str_replace('_', ' ', htmlentities($page));
		$text .= "</a>";
		$text .= " &#160;&#160;&#160; ";

		if (array_key_exists('query', $purl)) {
		    foreach (explode('&', $purl['query']) as $qs) {
			$pair = explode('=', $qs);
			if (array_key_exists(1, $pair))
			    $text .= "<input type='hidden' name='" .
				htmlentities($pair[0]) . "' value='" .
				htmlentities($pair[1]) . "'/>";
		    }
		}
		$text .= "<input type='hidden' name='cmd' value='remove'/>";
		$text .= "<input type='hidden' name='item' value='" .
		    htmlentities($key) . "'/>";
		$text .= "<input type='submit' value='" .
		    wfMsgHtml('luciwikepub-unselect') . "'/></form>";

		$text .= "</li>\n";
		if (!$title)
		    $title = str_replace('_', ' ', $page);
	    }
	    $text .= "</ul>\n";

	    // Unselect all button
	    if (count($_SESSION['pages']) > 1) {
		$text .= "<form action='" . $url . "'>";
		if (array_key_exists('query', $purl)) {
		    foreach (explode('&', $purl['query']) as $qs) {
			$pair = explode('=', $qs);
			if (array_key_exists(1, $pair))
			    $text .= "<input type='hidden' name='" .
				htmlentities($pair[0]) . "' value='" .
				htmlentities($pair[1]) . "'/>";
		    }
		}
		$text .= "<input type='hidden' name='cmd' value='clear'/>";
		$text .= "<p><input type='submit' value='" .
		    wfMsgHtml('luciwikepub-unselect-all') . "'/></p></form>\n";
	    }

	    $text .= "<br/>\n";

	    // Generate button
	    $text .= "<form action='" . $url . "'>";
	    if (array_key_exists('query', $purl)) {
		foreach (explode('&', $purl['query']) as $qs) {
		    $pair = explode('=', $qs);
		    if (array_key_exists(1, $pair))
			$text .= "<input type='hidden' name='" .
			    htmlentities($pair[0]) . "' value='" .
			    htmlentities($pair[1]) . "'/>";
		}
	    }
	    $text .= "<input type='hidden' name='cmd' value='generate'/>";
	    $text .= "<p><label>" . wfMsgHtml('luciwikepub-title') .
		": <input type='text' name='name' value='" .
		htmlentities($title) . "'/></label></p>";
	    $text .= "<p><label>" . wfMsgHtml('luciwikepub-enable-discussion') .
		": <input type='checkbox' name='discussion'";
	    global $wgLuciwikEPUBEnableDiscussion;
	    if ($wgLuciwikEPUBEnableDiscussion)
		$text .= " checked=''";
	    $text .= "/></label></p>";
	    $text .= "<p><input type='submit' value='" .
		wfMsgHtml('luciwikepub-download') . "'></p>";
	    $text .= "</form>\n";
	}

	$output->addHTML($text);
    }

    function generate($title, $arts, $args = array()) {
	global $wgOut;
	global $wgServer;
	global $wgArticlePath;
	global $wgLuciwikMode;
	global $wgLuciwikEPUBSplit;
	global $wgLuciwikEPUBEnableDiscussion;

	$opt_discussion = $wgLuciwikEPUBEnableDiscussion;
	if (array_key_exists('discussion', $args))
	    $opt_discussion = $args['discussion'];

	global $wgLuciwikEPUBLimit;
	$titles = array();
	foreach ($arts as $art) {
	    $tit = Title::newFromDBkey($art);
	    $titles[] = $tit;
	    if ($tit->getNamespace() == NS_CATEGORY) {
		$category = Category::newFromTitle($tit);
		//$category->getPageCount();
		foreach ($category->getMembers($wgLuciwikEPUBLimit) as $ctit)
		    $titles[] = $ctit;
	    }
	}

	$latest = NULL;
	$pages = array();
	$num = 0;
	foreach ($titles as $tit) {
	    $page = $this->get_page($tit);
	    if ($page) {
		$pages[] = $page;
		if (!$title)
		    $title = $page['name'];
		if (!$latest)
		    $latest = $page['latest'];
	    }

	    if (!$title and $tit->getNamespace() == NS_CATEGORY) {
		$title = $tit->getNsText();
		if ($title)
		    $title .= ': ';
		$title .= $tit->getText();
	    }
	    if (!$title)
		$title = 'Unknown';

	    if ($opt_discussion and !$tit->isTalkPage()) {
		$ttit = $tit->getTalkPage();
		$page = $this->get_page($ttit);
		if ($page) {
		    $pages[] = $this->get_discussion_header();
		    $pages[] = $page;
		}
	    }

	    if ($num++ >= $wgLuciwikEPUBLimit)
		break;
	}

	// Create book
	$epub = new LuciEPUB();

	// Set metadata
	$epub->set_title($title);
	$epub->set_uid();
	$epub->set_date();

	global $wgContLang;	// $wgLanguageCode, $wgLang;
	$epub->add_language($wgContLang->getCode());

	// Set additional metadata
	$cover = array(NULL, NULL);
	if ($latest)
	    $cover = $this->parse_luci($epub, $latest);

	// Add cover page
	$epub->add_spine_item($this->get_cover($title, $cover[1]));
	//$epub->set_item_toc(wfMsg('luciwikepub-cover'));

	// Add CSS stylesheet
	$epub->add_item_filepath(dirname(__FILE__) . DIRECTORY_SEPARATOR .
				 'luci.css', 'text/css', 'luci.css');

	$ts = NULL;
	$linkmap = array();

	// Add article pages
	foreach ($pages as $page) {
	    if (!$page)
		continue;

	    $html = $page['html'];
	    $images = $page['images'];
	    $imagenames = $page['imagenames'];
	    $name = $page['name'];
	    $timestamp = $page['timestamp'];
	    if (!$ts or intval($timestamp) > intval($ts))
		$ts = $timestamp;

	    if ($wgLuciwikEPUBSplit) {
		$splits = $epub->split($this->get_html_wrap($html, $name));
		$first = TRUE;
		foreach ($splits as $split) {
		    $epub->add_spine_item($split[0], $split[1]);
		    if ($page['toc']) {
			if ($first)
			    $epub->set_item_toc($name, TRUE, FALSE);
			else
			    $epub->set_item_toc(NULL, TRUE, TRUE);
			$first = FALSE;
		    }
		}
	    } else {
		$it = $epub->add_spine_item($this->get_html_wrap($html, $name));
		if ($page['toc'])
		    $epub->set_item_toc($name, TRUE);
		$nhref = str_replace('$1', $page['url'], $wgArticlePath);
		$nhref = wfExpandUrl($nhref);
		$linkmap[$nhref] = $it['href'];
		//error_log('linkmap: ' . $nhref . ':' . $it['href']);
	    }

	    // Add images
	    foreach ($imagenames as $key => $val) {
		$fil = wfFindFile($key);
		if (!$fil)
		    continue;

		$furl = $fil->getUrl();
		if (!array_key_exists($furl, $images))
		    continue;
		$fn = $images[$furl];

		if (method_exists($fil, 'getLocalRefPath'))
		    $data = $fil->getLocalRefPath();
		else	// MediaWiki 1.16
		    $data = $fil->getPath();
		if ($data) {
		    $epub->add_item_filepath($data, $fil->getMimeType(),
					     'images/' . $fn);
		    unset($images[$furl]);
		}
	    }

	    foreach ($images as $path => $nam) {
		$type = 'image/jpeg';
		$end = strtolower(substr($nam, strlen($nam) - 4));
		if ($end == '.png')
		    $type = 'image/png';
		elseif ($end == '.gif')
		    $type = 'image/gif';
		elseif ($end == '.svg')
		    $type = 'image/svg+xml';

		$data = $this->http_get($wgServer, $path);
		if ($data)
		    $epub->add_item($data, $type, 'images/' . $nam);
	    }
	    unset($images);
	}

	if ($linkmap) {
	    $epub->update_links($linkmap);
	    unset($linkmap);
	}

	/*
	$tz = @date('P');
	if ($tz == '+00:00')
	    $tz .= 'Z';
	*/
	$time = substr($ts, 0, 4) . '-' . substr($ts, 4, 2) . '-' .
	    substr($ts, 6, 2) . 'T' . substr($ts, 8, 2) . ':' .
	    substr($ts, 10, 2) . ':' . substr($ts, 12, 2) . 'Z';
	$epub->set_modified($time);

	// Add extra items
	global $wgLuciwikEPUBExtraItems;
	foreach ($wgLuciwikEPUBExtraItems as $item) {
	    if (!array_key_exists(0, $item))
		continue;
	    if (!array_key_exists(1, $item))
		continue;
	    $fn = $item[0];
	    // Check that file name does not contain path elements
	    if ((strpos($fn, '/') !== FALSE) or
		(strpos($fn, '\\') !== FALSE) or
		(strpos($fn, ':') !== FALSE) or
		(strpos($fn, DIRECTORY_SEPARATOR) !== FALSE))
		continue;
	    $fn = basename($fn);
	    $mime = $item[1];
	    $spine = FALSE;
	    if (array_key_exists(2, $item))
		$spine = $item[2];
	    $epub->add_item_filepath(dirname(__FILE__) . DIRECTORY_SEPARATOR .
				     $fn, $mime, $fn, $spine);
	    if ($spine and $spine !== TRUE)
		$epub->set_item_toc($spine);
	}

	// Generate navigation page
	$epub->generate_nav('luci.css', TRUE);

	$wgOut->disable();
	//wfResetOutputBuffers();

	$outname = preg_replace('~[/\\\'":?*& ]~', '_', $title);

	$out = $epub->generate();
	$out->sendZip(@utf8_decode($outname) . '.epub', 'application/epub+zip',
		      $outname . '.epub', TRUE);
    }

    function get_page($title) {
	// $title->exists() ->isKnown() ->getLatestRevID() ->getLength()
	if (!$title->exists())
	    return NULL;

	$newname = $title->getNsText();
	if ($newname)
	    $newname .= ': ';
	$newname .= $title->getText();

	if (method_exists('WikiPage', 'getParserOutput')) {	// MediaWiki 1.19 and later
	    $page = WikiPage::factory($title);
	    if (method_exists($page, 'getContentHandler')) {	// MediaWiki 1.21 and later
		$po = $page->getContentHandler()->makeParserOptions('canonical');
	    } else if (method_exists($page, 'getParserOptions')) {	// MediaWiki 1.18
		$po = $page->getParserOptions(TRUE);		
	    } else {	// MediaWiki 1.19-20
		$po = $page->makeParserOptions('canonical');
	    }
	    $po->setEditSection(FALSE);
	    $po->setTidy(TRUE);
	    $po->setIsPrintable(TRUE);
	    $po->enableLimitReport(FALSE);
	    //$po->setNumberHeadings(FALSE);
	    $pout = $page->getParserOutput($po);
	} else {	// MediaWiki 1.16-18
	    $page = new Article($title);
	    $user = new User();
	    $user->setOption('editsection', 0);
	    //$user->setOption('printable', 1);
	    //$user->setOption('numberheadings', 0);
	    $pout = $page->getParserOutput(null, $user);
	}

	$html = $pout->getText();
	if (preg_match('~^\s*<!--\s*NewPP limit~', $html))
	    return NULL;

	// Remove editsections, needed in MediaWiki 1.16-18
	$html = preg_replace('~<span class=[\'"]editsection[\'"]>.*?</span>~',
			     '', $html);

	// Handle local links
	//global $wgServer;
	//$html = preg_replace('~(<a [^>]*?href=[\'"])/~',
	//		     '$1' . $wgServer . '/', $html);
	$html = preg_replace_callback('~(<a [^>]*?)href=([\'"])(.+?)[\'"]~',
				      array($this, 'link_callback'), $html);

	// Handle <img> tags
	$this->images = array();
	$html = preg_replace_callback('~(<img [^>]*?)src=([\'"])(.+?)[\'"]~',
				      array($this, 'img_callback'), $html);
	$images = $this->images;
	unset($this->images);

	return array('name' => $newname,
		     'url' => $title->getPartialURL(),
		     'html' => $html,
		     'images' => $images,
		     'imagenames' => $pout->getImages(),
		     'timestamp' => $page->getTimestamp(),
		     'latest' => $page->getLatest(),
		     'toc' => TRUE);
    }

    function link_callback($matches) {
	return $matches[1] . 'href="' . wfExpandUrl($matches[3]) . '"';
    }

    function img_callback($matches) {
	$img = $matches[3];
	$pos = strrpos($img, '/');
	if ($pos === FALSE)
	    return $matches[0];

	$name = substr($img, $pos + 1);
	$this->images[$img] = $name;
	return $matches[1] . 'src="images/' . $name . '"';
    }

    function parse_luci($epub, $revid, $title = NULL) {
	$cover = NULL;
	$cover_name = NULL;

	$rev = NULL;
	if ($revid)
	    $rev = Revision::newFromId($revid);
	else
	    $rev = Revision::newFromTitle(Title::makeTitleSafe(14, $title));
	if (!$rev)
	    return array(NULL, NULL);

	$luci = $rev->getText(Revision::FOR_PUBLIC);
	$luci = Sanitizer::removeHTMLcomments($luci);
	if (!preg_match('~<luci>(.*?)</luci>~s', $luci, $matches))
	    return array(NULL, NULL);

	$luci = $matches[1];
	/*
	if (FALSE and preg_match('~<html>(.*?)</html>~s', $luci, $matches)) {
	} elseif (preg_match('~<text>(.*?)</text>~s', $luci, $matches))
	    $epub->set_description(htmlspecialchars($matches[1]));
	*/
	if (preg_match('~<summary>(.*?)</summary>~s', $luci, $matches))
	    $epub->set_description(htmlspecialchars($matches[1]));
	if (preg_match_all('~<author>(.*?)</author>~s', $luci, $matches)) {
	    foreach ($matches[1] as $match)
		$epub->add_author(htmlspecialchars($match));
	}
	/*
	if (preg_match('~<issued>(.*?)</issued>~s', $luci, $matches))
	    $epub->set_issued(htmlspecialchars($matches[1]));
	*/
	if (preg_match('~<publisher>(.*?)</publisher>~s', $luci, $matches))
	    $epub->set_publisher(htmlspecialchars($matches[1]));
	if (preg_match('~<rights>(.*?)</rights>~s', $luci, $matches))
	    $epub->set_rights(htmlspecialchars($matches[1]));
	if (preg_match('~<image>(.*?)</image>~s', $luci, $matches)) {
	    $fil = wfFindFile($matches[1]);
	    if ($fil) {
		$url = $fil->getUrl();
		$cover = $url;
		if (method_exists($fil, 'getLocalRefPath'))
		    $data = $fil->getLocalRefPath();
		else	// MediaWiki 1.16
		    $data = $fil->getPath();
		if ($data) {
		    $item = $epub->set_cover($data, $fil->getMimeType());
		    $cover_name = $item['href'];
		}
	    }
	}
	/*
	if (preg_match('~<thumbnail>(.*?)</thumbnail>~s', $luci, $matches)) {
	}
	*/

	return array($cover, $cover_name);
    }

    function get_cover($name, $image) {
	global $wgSitename;
	global $wgArticlePath;

	$doc = new DOMDocument();
	if (@$doc->load(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'luci.html')) {
	    $xpath = new DOMXPath($doc);
	    $entries = $xpath->query('//*[@id]', $doc);
	    foreach ($entries as $entry) {
		if ($entry->getAttribute('id') == 'title') {
		    $entry->appendChild($doc->createTextNode($name));
		}
		if ($image) {
		    if ($entry->getAttribute('id') == 'image') {
			$node = $doc->createElement('img');
			$node->setAttribute('src', $image);
			$entry->appendChild($node);
		    }
		}
		if ($entry->getAttribute('id') == 'date') {
		    //$entry->appendChild($doc->createTextNode(@strftime('%x')));
		    $entry->appendChild($doc->createTextNode(@strftime('%F')));
		    //$entry->appendChild($doc->createTextNode(@date('Y-m-d')));
		}
		if ($entry->getAttribute('id') == 'time') {
		    $entry->appendChild($doc->createTextNode(@strftime('%X')));
		    //$entry->appendChild($doc->createTextNode(@date('H:i')));
		}
	    }

	    return $doc->saveXML();
	}

	$html = "<br/><br/><br/><br/>\n";
	$html .= "<p class='covertitle'>" . $name . "</p>\n";
	if ($image)
	    $html .= "<p class='cover'><img src='" . $image . "'/></p>\n";
	else
	    $html .= "<br/>\n";
	$html .= "<br/><br/><br/>\n";
	$url = wfExpandUrl(str_replace('$1', '', $wgArticlePath));
	$html .= "<p class='cover'>";
	$html .= wfMsgHtml('luciwikepub-cover-footer',
			   "<a href='" . htmlentities($url) . "'>" .
			   htmlentities($wgSitename) . '</a>',
			   @strftime('%F'),
			   "<a href='http://lucidor.org/luciwik/'>Luciwik</a>");
	$html .= "</p>\n<br/><br/>\n";
	return $this->get_html_wrap($html, $name, TRUE);
    }

    function get_discussion_header() {
	global $wgContLang;

	$name = $wgContLang->getNsText(NS_TALK);
	$html = "<br/><br/><br/><br/>\n";
	$html .= "<p class='covertitle'>" . $name . "</p>\n";
	$html .= "<br/><br/>\n";
	//$html = $this->get_html_wrap($html, $name);
	return array('name' => $name,
		     'html' => $html,
		     'images' => array(),
		     'imagenames' => array(),
		     'timestamp' => NULL,
		     'latest' => NULL,
		     'toc' => FALSE);
    }

    function get_html_wrap($content, $title, $cover = FALSE) {
	$bodyclass = FALSE;
	if ($cover)
	    $bodyclass = 'cover';
	return LuciEPUB::get_html_wrap($content, $title, 'luci.css', $bodyclass);
    }

    function http_get($host, $path) {
	ini_set('default_socket_timeout', 5);

	// Method 1, handles http and https, not always enabled
	if (intval(ini_get('allow_url_fopen'))) {
	    $data = @file_get_contents($host . $path);
	    if ($data)
		return $data;
	}

	// Method 2, HTTP_Request, handles http, not always enabled
	/*
	if (class_exists('HTTP_Request') and substr($host, 0, 5) == 'http:') {
	    $req = new HTTP_Request($host . $path,
				    array('timeout' => 5));
	    if ($req->sendRequest() === TRUE)
		return $req->getResponseBody();
	}
	*/

	// Method 3, http_get, handles http, not always enabled
	/*
	if (function_exists('http_get') and substr($host, 0, 5) == 'http:') {
	    error_log('a');
	    $data = http_get($host . $path, array('timeout' => 5), $info);
	    if ($data) {
		//if (array_key_exists('content_type', $info))
		//    $ct = $info['content_type'];
		return $data;
	    }
	}
	*/

	// Method 4, curl, handles http and https, not always enabled
	/*
	if (function_exists('curl_init')) {
	    $ch = curl_init($host . $path);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	    curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);
	    curl_setopt($ch, CURLOPT_HEADER, FALSE);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	    $data = curl_exec($ch);
	    curl_close($ch);
	    if ($data)
		return $data;
	}
	*/

	// Method 5, handles http, should always work
	if (!preg_match('~^http://([^:/]+)(:([0-9]+))?$~', $host, $matches))
	    return NULL;
	$hostname = $matches[1];
	$port = 80;
	if (array_key_exists(3, $matches))
	    $port = intval($matches[3]);
	$fp = fsockopen($hostname, $port);
	if (!$fp) {
	    return NULL;
	} else {
	    $out = "GET " . $path . " HTTP/1.0\r\n";
	    $out .= "Host: " . $hostname . "\r\n";
	    $out .= "Connection: Close\r\n\r\n";
	    fwrite($fp, $out);
	    $data = '';
	    while (!feof($fp))
		$data .= fread($fp, 8192);
	    fclose($fp);
	    $pos = strpos(substr($data, 0, 500), "\r\n\r\n");
	    if ($pos !== FALSE) {
		//$headers = substr($data, 0, $pos);
		//Content-Type: image/jpeg
		$data = substr($data, $pos + 4);
		return $data;
	    }
	}

	return NULL;
    }
}
