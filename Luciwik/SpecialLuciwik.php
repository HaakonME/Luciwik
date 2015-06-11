<?php

  /*
    Luciwik - OPDS catalog system for MediaWiki
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

if (!class_exists('Lucicat_Feed'))
    require_once 'lucicatAtom.php';

class SpecialLuciwik extends SpecialPage {
    const ALL_LINK = 1;
    const THIS_LINK = 2;
    const CATEGORY_ENTRY = 4;

    function __construct() {
	parent::__construct('Luciwik');
    }

    function execute($par) {
	global $wgOut;
	global $wgRequest;
	global $wgUser;

	/* This check is not really necessary because users without
	   'read' permission will not be able to read this page */
	if (method_exists($this, 'getUser'))	// MediaWiki versions >= 1.18
	    $user = $this->getUser();
	else
	    $user = $wgUser;
	if ((!$user->isAllowed('read')) or $user->isBlocked())
	    return;

	wfResetOutputBuffers();
	$wgOut->disable();

	if (method_exists($this, 'getRequest'))	// MediaWiki versions >= 1.18
	    $request = $this->getRequest();
	else
	    $request = $wgRequest;

	$first = NULL;
	$first = $request->getText('fi');
	if (!$first or !is_numeric($first))
	    $first = 1;

	$limit = NULL;
	$limit = $request->getText('ipp');
	if (!$limit or !is_numeric($limit))
	    $limit = 20;
	else if ($limit < 1)
	    $limit = 1;
	else if ($limit > 50)
	    $limit = 50;

	global $wgLuciwikMode;
	if ($wgLuciwikMode != LUCIWIK_MODE_FLAT and
	    $wgLuciwikMode != LUCIWIK_MODE_CATEGORIES and
	    $wgLuciwikMode != LUCIWIK_MODE_TREE) {
	    $wgLuciwikMode = LUCIWIK_MODE_FLAT;
	}

	// Spaces have already been translated into single underscores
	$par = rtrim($par, '/');
	$par = urldecode($par);

	$mode = NULL;
	$path = '';
	$pos = strpos($par, '/');
	if ($pos !== FALSE) {
	    if (strlen($par) > $pos + 1)
		$path = substr($par, $pos + 1);
	    $par = substr($par, 0, $pos);
	}
	if (strlen($par) == 2) {
	    $mode = $par[1];
	    $par = $par[0];
	}

	if ($par == 'a' or ($par == '' and $wgLuciwikMode == LUCIWIK_MODE_FLAT)) {
	    $opds = $this->browse_all($first, $limit);
	} else if ($par == 'b' or ($par == '' and $wgLuciwikMode == LUCIWIK_MODE_TREE)) {
	    if ($mode == ':')
		$opds = $this->browse_path_entries($path, $first, $limit);
	    else
		$opds = $this->browse_path($path, $first, $limit);
	} else if ($par == 'c' or ($par == '' and $wgLuciwikMode == LUCIWIK_MODE_CATEGORIES)) {
	    if ($mode == ':')
		$opds = $this->browse_category_entries($path, $first, $limit);
	    else
		$opds = $this->browse_category($path, $first, $limit);
	} else if ($par == 'e') {
	    $opds = $this->browse_entry($path, $first, $limit);
	} else if ($par == 'f') {
	    $opds = $this->browse_search($path, $first, $limit);
	} else if ($par == 'o') {
	    $this->generate_opensearch();
	    return;
	} else { // Invalid URL
	    $opds = $this->create_feed(0, 0, $limit, 0, 'a', '');
	}

	$opds->output();
    }

    function browse_all($first, $limit) {
	$dbr = wfGetDB(DB_SLAVE);
	$res = $dbr->select('page',
			    array('page_id',
				  'page_title',
				  'page_latest',
				  'page_touched'),
			    array('page_namespace = 0',	// NS_MAIN
				  'page_is_redirect = 0'),
			    'Database::select',
			    array(//'OFFSET' => $first - 1,
				  'ORDER BY' => 'page_title ASC'));

	$numrows = $res->numRows();
	$last = max(min($first + $limit - 1, $numrows), 0);
	if ($first > $last)
	    $first = $last;

	$opds = $this->create_feed($first, $last, $limit, $numrows, 'a', '');

	if ($last > 0) {
	    $res->seek($first - 1);
	    for ($i = $first - 1; $i < $last; $i++) {
		$row = $res->next();
		$this->create_entry($opds, $row);
	    }
	}
	$res->free();

	return $opds;
    }

    function browse_category($path, $first, $limit) {
	global $wgLuciwikAllLink;
	global $wgLuciwikThisLink;

	$dbr = wfGetDB(DB_SLAVE);

	$like = $dbr->buildLike('Luci/', $dbr->anyString());
	$res = NULL;
	$cats = array();

	if ($path) {
	    // Subcategories, always exists in 'page'
	    $res = $dbr->select(array('page', 'categorylinks'),
				array('page_title',
				      'page_latest'),
				array('page_namespace = 14',	// NS_CATEGORY
				      //'cat_title = page_title'
				      //'cat_pages > 0',
				      'page_title NOT ' . $like,
				      'cl_from = page_id',
				      'cl_to = ' . $dbr->addQuotes($path)),
				'Database::select');
	    foreach ($res as $row)
		$cats[$row->page_title] = $row->page_latest;
	} else {
	    // Root categories, always exists in 'category' (but not in 'page')
	    $res = $dbr->select(array('category'),
				array('cat_title'),
				array(// Check that category is not subcategory
				      'NOT EXISTS (SELECT * FROM ' .
				      $dbr->tableName('categorylinks') . ', ' .
				      $dbr->tableName('page') .
				      ' WHERE cl_from = page_id AND page_title = cat_title)',
				      // Check that category includes normal page or subcategory
				      'EXISTS (SELECT * FROM ' .
				      $dbr->tableName('categorylinks') . ', ' .
				      $dbr->tableName('page') .
				      ' WHERE cl_to = cat_title AND cl_from = page_id AND (page_namespace = 0 OR page_namespace = 14))', // NS_MAIN, NS_CATEGORY
				      'cat_title NOT ' . $like),
				'Database::select');
	    foreach ($res as $row)
		$cats[$row->cat_title] = NULL;
	}

	$res->free();
	unset($cats['Luci']);

	if (!$path and $wgLuciwikAllLink)
	    $cats[SpecialLuciwik::ALL_LINK] = NULL;

	$res = $this->get_category_entries($dbr, $path);

	if ($cats) {
	    if ($res->numRows() > 0 and $wgLuciwikThisLink)
		$cats[SpecialLuciwik::THIS_LINK] = NULL;
	    $res->free();

	    return $this->do_browse_nav('c', $cats, $path, $first, $limit);
	}

	return $this->do_browse_entries('c', $res, $path, $first, $limit, NULL);	// s/NULL/$path/
    }

    function browse_category_entries($path, $first, $limit) {
	$dbr = wfGetDB(DB_SLAVE);
	$res = $this->get_category_entries($dbr, $path);

	return $this->do_browse_entries('c:', $res, $path, $first, $limit, NULL);	// s/NULL/$path/
    }

    function get_category_entries($dbr, $path) {
	$res = NULL;

	// entries in category
	if ($path) {
	    $res = $dbr->select(array('page', 'categorylinks'),
				array('page_id',
				      'page_title',
				      'page_latest',
				      'page_touched'),
				array('cl_to = ' . $dbr->addQuotes($path),
				      'cl_from = page_id',
				      'page_is_redirect = 0',
				      'page_namespace = 0'),	// NS_MAIN
				'Database::select',
				array('ORDER BY' => 'page_title ASC'));
	} else {
	    $res = $dbr->select(array('page'),
				array('page_id',
				      'page_title',
				      'page_latest',
				      'page_touched'),
				array('NOT EXISTS (SELECT * FROM ' .
				      $dbr->tableName('categorylinks') .
				      ' WHERE cl_from = page_id)',
				      'page_is_redirect = 0',
				      'page_namespace = 0'),	// NS_MAIN
				'Database::select',
				array('ORDER BY' => 'page_title ASC'));
	}

	return $res;
    }

    function browse_path($path, $first, $limit) {
	global $wgLuciwikAllLink;
	global $wgLuciwikThisLink;

	$dbr = wfGetDB(DB_SLAVE);

	if ($path)
	    $like = $dbr->buildLike('Luci/' . $path . '/', $dbr->anyString());
	else
	    $like = $dbr->buildLike('Luci/', $dbr->anyString());

	$res = $dbr->select('category',
			    array('cat_title'),
			    array('cat_pages > 0',
				  'cat_title ' . $like),
			    'Database::select');

	$cats = array();
	foreach ($res as $row) {
	    if ($path)
		$cat = substr($row->cat_title, strlen($path) + 1 + 5);
	    else
		$cat = substr($row->cat_title, 5);	// remove 'Luci/'
	    $pos = strpos($cat, '/');
	    if ($pos !== false)
		$cat = substr($cat, 0, $pos);
	    $cats[$cat] = NULL;
	}

	$res->free();

	if (!$path and $wgLuciwikAllLink)
	    $cats[SpecialLuciwik::ALL_LINK] = NULL;

	$res = $this->get_path_entries($dbr, $path);

	if ($cats) {
	    if ($res->numRows() > 0 and $wgLuciwikThisLink)
		$cats[SpecialLuciwik::THIS_LINK] = NULL;
	    $res->free();

	    return $this->do_browse_nav('b', $cats, $path, $first, $limit);
	}

	return $this->do_browse_entries('b', $res, $path, $first, $limit, NULL);
    }

    function browse_path_entries($path, $first, $limit) {
	$dbr = wfGetDB(DB_SLAVE);
	$res = $this->get_path_entries($dbr, $path);

	return $this->do_browse_entries('b:', $res, $path, $first, $limit, NULL);
    }

    function get_path_entries($dbr, $path) {
	$luciPath = 'Luci';
	if ($path)
	    $luciPath .= '/' . $path;

	// entries in category
	return $dbr->select(array('page', 'categorylinks'),
			    array('page_id',
				  'page_title',
				  'page_latest',
				  'page_touched'),
			    array('cl_to = ' . $dbr->addQuotes($luciPath),
				  'cl_from = page_id',
				  'page_is_redirect = 0',
				  'page_namespace = 0'),	// NS_MAIN
			    'Database::select',
			    array('ORDER BY' => 'page_title ASC'));
    }

    function browse_entry($path, $first, $limit) {
	$dbr = wfGetDB(DB_SLAVE);

	$entry = NULL;
	$res = $dbr->select(array('page'),
			    array('page_id',
				  'page_title',
				  'page_latest',
				  'page_touched'),
			    array('page_title = ' . $dbr->addQuotes($path),
				  'page_namespace = 0'),	// NS_MAIN
			    'Database::select');
	if ($res) {
	    $row = $res->next();
	    $entry = $this->create_entry(NULL, $row);
	    $res->free();
	}

	return $entry;
    }

    function browse_search($search, $first, $limit) {
	$dbr = wfGetDB(DB_SLAVE);

	// $search already has underscores instead of spaces
	if ($search)
	    $like = $dbr->buildLike($dbr->anyString(), $search, $dbr->anyString());
	else
	    $like = $dbr->buildLike(' ');	// will not find anything

	$res = $dbr->select('page',
			    array('page_id',
				  'page_title',
				  'page_latest',
				  'page_touched'),
			    array('page_namespace = 0',	// NS_MAIN
				  'page_is_redirect = 0',
				  'CONVERT(page_title USING utf8)' . $like),
			    'Database::select',
			    array(//'OFFSET' => $first - 1,
				  'ORDER BY' => 'page_title ASC'));

	return $this->do_browse_entries('f', $res, $search, $first, $limit, NULL);
    }

    function do_browse_nav($type, $cats, $path, $first, $limit) {
	global $wgLuciwikAllLink;
	global $wgLuciwikThisLink;
	global $wgLuciwikEnableMetadata;

	$numrows = count($cats);
	$last = max(min($first + $limit - 1, $numrows), 0);
	/*
	if ($first > $last)
	    $first = $last;
	*/
	//error_log('first=' . $first . ' last=' . $last . ' numrows=' . $numrows);

	$opds = $this->create_feed($first, $last, $limit, $numrows, $type,
				   $path);

	if ($first < 1 or $first > $numrows or $first > $last)
	    return $opds;

	$opds_type = 'application/atom+xml;profile=opds-catalog';
	$keys = array_keys($cats);
	sort($keys);
	$i = 0;
	foreach ($keys as $key => $cat) {
	    $i++;
	    if ($i < $first)
		continue;
	    $entry = $opds->create_entry();
	    if ($cat == SpecialLuciwik::ALL_LINK) {
		if ($wgLuciwikAllLink === TRUE)
		    $entry->set_title(wfMsg('luciwik-all-link'));
		else
		    $entry->set_title($wgLuciwikAllLink);
		$uri = $this->get_url('a', '', 1, $limit);
	    } elseif ($cat == SpecialLuciwik::THIS_LINK) {
		if ($wgLuciwikThisLink === TRUE)
		    $entry->set_title(wfMsg('luciwik-this-link'));
		else
		    $entry->set_title($wgLuciwikThisLink);
		$uri = $this->get_url($type . ':', $path, 1, $limit);
	    } else {
		$entry->set_title(strtr($cat, '_', ' '));
		if ($type == 'b' and $path)
		    $uri = $this->get_url($type, $path . '/' . $cat, 1, $limit);
		else
		    $uri = $this->get_url($type, $cat, 1, $limit);
	    }
	    $entry->set_id();	// FIXME Should create id from uri instead
	    //$entry->set_id($uri . '&cat=entry');
	    $entry->set_updated();
	    $entry->add_link($uri, 'subsection', $opds_type, NULL);

	    $entry->set_content('');
	    if ($wgLuciwikEnableMetadata and $cat != SpecialLuciwik::ALL_LINK and $cat != SpecialLuciwik::THIS_LINK) {
		$page = $cats[$cat];
		if ($page)
		    $this->parse_luci($entry, $page);
		else
		    $this->parse_luci($entry, NULL, $cat);
	    }

	    if ($i == $last)
		break;
	}

	return $opds;
    }

    function do_browse_entries($type, $res, $path, $first, $limit, $category) {
	$numrows = $res->numRows();
	//if ($category)
	//    $numrows++;
	$last = max(min($first + $limit - 1, $numrows), 0);
	/*
	if ($first > $last)
	    $first = $last;
	*/
	//error_log('first=' . $first . ' last=' . $last . ' numrows=' . $numrows);

	$opds = $this->create_feed($first, $last, $limit, $numrows,
				   $type, $path);

	if ($first < 1 or $first > $numrows or $first > $last) {
	    $res->free();
	    return $opds;
	}

	/*
	if ($category) {
	    $row = { 'page_id' => NULL,
		     'page_title' => wfMsg('luciwik-this-link'),
		     'page_latest' => NULL,
		     'page_touched' => @gmdate('YmdHis') };
	    $this->create_entry($opds, $row);
	}
	*/

	$res->seek($first - 1);
	for ($i = $first - 1; $i < $last; $i++) {
	    $row = $res->next();
	    $this->create_entry($opds, $row);
	}
	$res->free();

	return $opds;
    }

    function create_feed($first, $last, $limit, $numrows, $type, $path) {
	global $wgArticlePath;
	global $wgSitename;
	global $wgServer;
	global $wgScriptPath;
	global $wgLuciwikMode;
	global $wgLuciwikStylesheet;

	$opds_type = 'application/atom+xml;profile=opds-catalog';

	$opds = new Lucicat_Feed();
	$opds->set_luciwik();
	//$opds->set_source_uri($config_source);
	$opds->add_dcterms_namespace();
	$opds->add_luci_namespace();
	$opds->add_opds_namespace();
	$opds->add_opensearch_namespace();
	$opds->add_xhtml_namespace();

	if ($path) {
	    $title = strtr($path, '_', ' ');
	    if ($type == 'b') {
		$pos = strrpos($title, '/');
		if ($pos !== FALSE)
		    $title = substr($title, $pos + 1);
	    }
	    $opds->set_title($title);
	} else
	    $opds->set_title($wgSitename);
	$opds->set_id();	// FIXME Should create id from uri instead
	$opds->set_updated();
	$opds->add_author('Luciwik', NULL, 'http://lucidor.org/luciwik/');

	$uri = $this->get_url($type, $path, $first, $limit);
	$opds->add_link($uri, 'self', $opds_type);

	$base = str_replace('$1', 'Special:Luciwik', $wgArticlePath);
	$opds->add_link($base . '/o', 'search',
			'application/opensearchdescription+xml');

	$is_start = (($type == $wgLuciwikMode) and ($path == '') and
		     ($first == 1));
	if ($is_start) {
	    $opds->add_link($this->get_url('a', '', 1, 50),
			    'http://opds-spec.org/crawlable',
			    $opds_type);
	} else {
	    $opds->add_link($base, 'start', $opds_type);
	}

	if ($first > 1) {
	    $uri = $this->get_url($type, $path, 1, $limit);
	    $opds->add_link($uri, 'first', $opds_type);

	    $pfirst = $first - $limit;
	    if ($pfirst < 1)
		$pfirst = 1;
	    $uri = $this->get_url($type, $path, $pfirst, $limit);
	    $opds->add_link($uri, 'previous', $opds_type);
	}

	if ($last < $numrows) {
	    $uri = $this->get_url($type, $path, $first + $limit, $limit);
	    $opds->add_link($uri, 'next', $opds_type);

	    $uri = $this->get_url($type, $path,
				  floor(($numrows - 1) / $limit) * $limit + 1,
				  $limit);
	    $opds->add_link($uri, 'last', $opds_type);
	}

	$opds->set_opensearch($limit, $first, 0, $numrows);

	if ($wgLuciwikStylesheet) {
	    if ($wgLuciwikStylesheet === TRUE)
		$opds->set_stylesheet($wgServer . $wgScriptPath .
				      '/extensions/Luciwik/luciwik.xsl');
	    else
		$opds->set_stylesheet($wgLuciwikStylesheet);
	}

	return $opds;
    }

    function create_entry($opds, $row) {
	global $wgArticlePath;
	global $wgServer;
	global $wgScriptPath;
	global $wgLuciwikEnableMetadata;
	global $wgLuciwikStylesheet;

	$entry = NULL;
	if ($opds)
	    $entry = $opds->create_entry();
	else {
	    $entry = new Lucicat_Entry();
	    $entry->set_luciwik();
	    //$entry->set_source_uri($config_source);
	    $entry->add_dcterms_namespace();
	    $entry->add_luci_namespace();
	    $entry->add_opds_namespace();
	    //$entry->add_opensearch_namespace();
	    $entry->add_xhtml_namespace();

	    if ($wgLuciwikStylesheet) {
		if ($wgLuciwikStylesheet === TRUE)
		    $entry->set_stylesheet($wgServer . $wgScriptPath .
					  '/extensions/Luciwik/luciwik.xsl');
		else
		    $entry->set_stylesheet($wgLuciwikStylesheet);
	    }
	}

	$entry->set_title(strtr($row->page_title, '_', ' '));
	//$entry->set_subtitle($row->page_id);
	$time = substr($row->page_touched, 0, 4) . '-' .
	    substr($row->page_touched, 4, 2) . '-' .
	    substr($row->page_touched, 6, 2) . 'T' .
	    substr($row->page_touched, 8, 2) . ':' .
	    substr($row->page_touched, 10, 2) . ':' .
	    substr($row->page_touched, 12, 2); // . '+00:00'; // FIXME
	$entry->set_updated($time);

	$href = NULL;
	if (function_exists('wfLuciwikEPUBToolbox'))
	    $href = str_replace('$1', 'Special:LuciwikEPUB/' . wfUrlencode($row->page_title), $wgArticlePath);
	else {	// ePubExport
	    $href = str_replace('$1', 'Special:EPubPrint', $wgArticlePath);
	    $href = wfAppendQuery($href, 'page=' . wfUrlencode($row->page_title));
	}
	$entry->add_link($href, 'http://opds-spec.org/acquisition',
			 'application/epub+zip');
	$entry->set_id();	// FIXME Should create id from uri instead
	//$entry->set_id($href . '&cat=entry');

	$entry->set_content('');
	if ($wgLuciwikEnableMetadata and $row->page_latest)
	    $this->parse_luci($entry, $row->page_latest);

	return $entry;
    }

    function parse_luci($entry, $revid, $title = NULL) {
	$rev = NULL;
	if ($revid)
	    $rev = Revision::newFromId($revid);
	else
	    $rev = Revision::newFromTitle(Title::makeTitleSafe(14, $title));
	if (!$rev)
	    return;

	$luci = $rev->getText(Revision::FOR_PUBLIC);
	$luci = Sanitizer::removeHTMLcomments($luci);
	if (!preg_match('~<luci>(.*?)</luci>~s', $luci, $matches))
	    return;

	$luci = $matches[1];
	if (FALSE and preg_match('~<html>(.*?)</html>~s', $luci, $matches)) {
	    $content = Sanitizer::removeHTMLtags($matches[1], null, array(), array(), array());
	    //$content = Sanitizer::stripAllTags($matches[1]);
	    $entry->set_content($content, 'html');
	} elseif (preg_match('~<text>(.*?)</text>~s', $luci, $matches))
	    $entry->set_content(htmlspecialchars($matches[1]));
	if (preg_match('~<summary>(.*?)</summary>~s', $luci, $matches))
	    $entry->set_summary(htmlspecialchars($matches[1]));
	if (preg_match_all('~<author>(.*?)</author>~s', $luci, $matches)) {
	    foreach ($matches[1] as $match)
		$entry->add_author(htmlspecialchars($match));
	}
	if (preg_match('~<issued>(.*?)</issued>~s', $luci, $matches))
	    $entry->set_issued(htmlspecialchars($matches[1]));
	if (preg_match('~<publisher>(.*?)</publisher>~s', $luci, $matches))
	    $entry->set_publisher(htmlspecialchars($matches[1]));
	if (preg_match('~<rights>(.*?)</rights>~s', $luci, $matches))
	    $entry->set_rights(htmlspecialchars($matches[1]));
	if (preg_match('~<image>(.*?)</image>~s', $luci, $matches)) {
	    $img = wfFindFile($matches[1]);
	    if ($img) {
		$type = $img->getMimeType();
		if ($type == 'image/jpeg' or $type == 'image/png' or
		    $type == 'image/gif')
		    $entry->add_link($img->getFullUrl(),
				     'http://opds-spec.org/image',
				     $type, NULL);
	    }
	}
	if (preg_match('~<thumbnail>(.*?)</thumbnail>~s', $luci, $matches)) {
	    $img = wfFindFile($matches[1]);
	    if ($img) {
		$type = $img->getMimeType();
		if ($type == 'image/jpeg' or $type == 'image/png' or
		    $type == 'image/gif')
		    $entry->add_link($img->getFullUrl(),
				     'http://opds-spec.org/image/thumbnail',
				     $type, NULL);
	    }
	}
    }

    function get_url($type, $path, $firstIndex = 1, $limit = 20) {
	global $wgArticlePath;

	$name = 'Special:Luciwik';
	if ($type)
	    $name .= '/' . $type;
	if ($path)
	    $name .= '/' . wfUrlencode($path);
	$url = str_replace('$1', $name, $wgArticlePath);
	if ($limit != 20)
	    $url = wfAppendQuery($url, 'ipp=' . $limit);
	if ($firstIndex != 1)
	    $url = wfAppendQuery($url, 'fi=' . $firstIndex);

	return $url;
    }

    function generate_opensearch() {
	global $wgSitename;
	global $wgArticlePath;

	header('Content-Type: application/xml');
	echo "<?xml version='1.0' encoding='UTF-8'?>\n";
	echo "<OpenSearchDescription xmlns='http://a9.com/-/spec/opensearch/1.1/' xmlns:luci='http://lucidor.org/-/x-opds/'>\n";
	echo "  <ShortName>" . $wgSitename . "</ShortName>\n";
	echo "  <InputEncoding>UTF-8</InputEncoding>\n";
	echo "  <OutputEncoding>UTF-8</OutputEncoding>\n";
	$url = str_replace('$1', 'Special:Luciwik/f/{searchTerms}', $wgArticlePath);
	$url = wfExpandUrl($url);
	echo "  <Url type='application/atom+xml' template='" . $url . "'/>\n";
	echo "</OpenSearchDescription>\n";
    }

}
