<?php

  /*
    Lucicat - OPDS catalog system
    Copyright © 2009-2012  Mikael Ylikoski

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

class Lucicat_Container {
    protected $namespaces = array();
    protected $luciwik = FALSE;
    protected $stylesheet = NULL;
    protected $source_uri = 'http://lucidor.org/lucicat/';
    protected $id = NULL;
    protected $title = NULL;
    protected $subtitle = NULL;
    protected $updated = NULL;
    protected $rights = NULL;
    protected $icon = NULL;
    protected $authors = array();
    protected $links = array();
    protected $languages = array();
    protected $custom = array();

    public function add_namespace($prefix, $uri) {
	$this->namespaces[] = array("prefix" => $prefix,
				    "uri" => $uri);
    }

    public function add_dcterms_namespace() {
	$this->namespaces[] = array("prefix" => "dcterms",
				    "uri" => "http://purl.org/dc/terms/");
    }

    public function add_luci_namespace() {
	$this->namespaces[] = array("prefix" => "luci",
				    "uri" => "http://lucidor.org/-/x-opds/");
    }

    public function add_opds_namespace() {
	$this->namespaces[] = array("prefix" => "opds",
				    "uri" => "http://opds-spec.org/2010/catalog");
    }

    public function add_opensearch_namespace() {
	$this->namespaces[] = array("prefix" => "opensearch",
				    "uri" => "http://a9.com/-/spec/opensearch/1.1/");
    }

    public function add_xhtml_namespace() {
	$this->namespaces[] = array("prefix" => "xhtml",
				    "uri" => "http://www.w3.org/1999/xhtml");
    }

    public function add_xsi_namespace() {
	$this->namespaces[] = array("prefix" => "xsi",
				    "uri" => "http://www.w3.org/2001/XMLSchema-instance");
    }

    public function set_luciwik() {
	$this->luciwik = TRUE;
	$this->source_uri = 'http://lucidor.org/luciwik/';
    }

    public function set_stylesheet($stylesheet) {
	$this->stylesheet = $stylesheet;
    }

    public function set_source_uri($uri) {
	$this->source_uri = $uri;
    }

    public function set_id($id = NULL) {
	if ($id)
	    $this->id = $id;
	else
	    $this->id = "urn:uuid:" . $this->uuid4_gen();
    }

    public function set_title($title) {
        $this->title = $title;
    }

    public function set_subtitle($subtitle) {
        $this->subtitle = $subtitle;
    }

    public function set_updated($date = NULL) {
	if ($date)
	    $this->updated = $date;
	else
	    $this->updated = @date(DATE_ATOM);
    }

    public function set_rights($rights) {
        $this->rights = $rights;
    }

    public function set_icon($uri) {
        $this->icon = $uri;
    }

    public function add_author($name, $email = NULL, $uri = NULL) {
	$this->authors[] = array("name" => $name,
				 "email" => $email,
				 "uri" => $uri);
    }

    public function add_link($href, $rel = NULL, $type, $title = NULL) {
	$this->links[] = array("href" => $href,
			       "rel" => $rel,
			       "type" => $type,
			       "title" => $title);
    }

    public function add_dcterms_language($lang, $label = NULL) {
	$this->languages[] = array("lang" => $lang,
				   "label" => $label);
    }

    public function add_custom($custom) {
	$this->custom[] = $custom;
    }

    public function get_generator() {
	if ($this->luciwik)
	    return "<generator uri='http://lucidor.org/luciwik/'>Luciwik</generator>";
	return "<generator uri='http://lucidor.org/lucicat/'>Lucicat</generator>";
    }

    protected function output_header($pp, &$var) {
	if ($var !== NULL)
	    return;

	if ($this->stylesheet)
	    // Needed to prevent web browsers from ignoring the stylesheet
	    header("Content-Type: application/xml");
	else
	    header("Content-Type: application/atom+xml");

	echo "<?xml version='1.0' encoding='UTF-8'?>\n";
	if ($this->stylesheet) {
	    echo "<?xml-stylesheet type='text/xsl' href='" .
		$this->escape($this->stylesheet) . "'?>\n";
	}

	$text = "";
	if ($this->source_uri) {
	    if ($this->luciwik) {
		$text = "     This feed was created with Luciwik (http://lucidor.org/luciwik/).\n" .
		    "     Luciwik is released under the GNU Affero General Public License.\n";
	    } else {
		$text = "     This feed was created with Lucicat (http://lucidor.org/lucicat/).\n" .
		    "     Lucicat is released under the GNU Affero General Public License.\n";
	    }
	    $text .= "     The source code for this application is available from\n" .
		"     " . $this->escape($this->source_uri) . "\n";
	}

	if ($this->stylesheet || $text) {
	    // Needed to prevent web browsers from ignoring the stylesheet
	    echo "<!-- ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n";
	    $n = 0;
	    $st = "";
	    if ($this->stylesheet) {
		$st = "     ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n";
		$n = ceil((512 - strlen($text) - strlen($st) * 2) / strlen($st) / 2);
		echo str_repeat($st, $n);
	    }
	    echo $text;
	    echo str_repeat($st, $n);
	    echo "     ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ -->\n";
	}
    }

    protected function output_container($pp, $level, &$var) {
	if ($this->id)
	    $this->pretty("<id>" . $this->escape($this->id) . "</id>",
			  $pp, $level, $var);
	if ($this->title)
	    $this->pretty("<title>" . $this->escape($this->title) . "</title>",
			  $pp, $level, $var);
	if ($this->subtitle)
	    $this->pretty("<subtitle>" . $this->escape($this->subtitle) .
			  "</subtitle>", $pp, $level, $var);

	foreach ($this->authors as &$author) {
	    $this->pretty("<author>", $pp, $level, $var);
	    $this->pretty("<name>" . $this->escape($author["name"]) . "</name>",
			  $pp, $level + 1, $var);
	    if ($author["email"])
		$this->pretty("<email>" . $this->escape($author["email"]) .
			      "</email>", $pp, $level + 1, $var);
	    if ($author["uri"])
		$this->pretty("<uri>" . $this->escape($author["uri"]) . "</uri>",
			      $pp, $level + 1, $var);
	    $this->pretty("</author>", $pp, $level, $var);
	}
	unset($author);

	if ($this->updated)
	    $this->pretty("<updated>" . $this->escape($this->updated) . "</updated>",
			  $pp, $level, $var);

	if ($this->rights)
	    $this->pretty("<rights>" . $this->escape($this->rights) . "</rights>",
			  $pp, $level, $var);

	if ($this->icon)
	    $this->pretty("<icon>" . $this->escape($this->icon) . "</icon>",
			  $pp, $level, $var);

	foreach ($this->links as &$link) {
	    $str = "<link href='" . $this->escape($link["href"]) . "'";
	    if ($link["rel"])
		$str .= " rel='" . $this->escape($link["rel"]) . "'";
	    if ($link["type"])
		$str .= " type='" . $this->escape($link["type"]) . "'";
	    if ($link["title"])
		$str .= " title='" . $this->escape($link["title"]) . "'";
	    $str .= "/>";
	    $this->pretty($str, $pp, $level, $var);
	}
	unset($link);

	foreach ($this->languages as &$lang) {
	    $str = "<dcterms:language";
	    if ($lang["label"])
		$str .= " luci:label='" . $this->escape($lang["label"]) . "'";
	    $str .= ">" . $this->escape($lang["lang"]) . "</dcterms:language>";
	    $this->pretty($str, $pp, $level, $var);
	}
	unset($lang);

	foreach ($this->custom as &$custom)
	    $this->pretty($custom, $pp, $level, $var);
	unset($custom);
    }

    /*
     * Generate uuid version 4
     * From http://www.ajaxray.com/blog/2008/02/06/php-uuid-generator-function/comment-page-1/#comment-2667
     * Can be initialized with: mt_srand(intval(microtime(TRUE) * 1000));
     * An alternative would be uuid_create() from the PECL uuid extension
     */
    protected static function uuid4_gen() {
	$b = md5(uniqid(mt_rand(), TRUE), TRUE);
	$b[6] = chr((ord($b[6]) & 0x0F) | 0x40);
	$b[8] = chr((ord($b[8]) & 0x3F) | 0x80);
	return implode("-", unpack("H8a/H4b/H4c/H4d/H12e", $b));
    }

    /*
     * Escape special html characters
     */
    protected static function escape($data) {
	return htmlspecialchars($data, ENT_QUOTES, "UTF-8");
    }

    /*
     * Output string with indentation
     */
    protected static function pretty($str, $pp, $level, &$var = NULL) {
	if ($var === NULL) {
	    if ($pp)
		echo str_repeat("  ", $level) . $str . "\n";
	    else
		echo $str;
	} else {
	    if ($pp)
		$var .= str_repeat("  ", $level) . $str . "\n";
	    else
		$var .= $str;
	}
    }

}

class Lucicat_Feed extends Lucicat_Container {
    protected $entries = array();
    protected $opensearch = NULL;

    public function create_entry() {
	$entry = new Lucicat_Entry();
	$this->entries[] = $entry;
	return $entry;
    }

    public function set_opensearch($ipp, $si, $sp, $total) {
	$this->opensearch = array("itemsPerPage" => $ipp,
				  "startIndex" => $si,
				  "startPage" => $sp,
				  "totalResults" => $total);
    }

    public function output($pp = TRUE, &$var = NULL) {
	parent::output_header($pp, $var);

	$str = "<feed xmlns='http://www.w3.org/2005/Atom'";
	foreach ($this->namespaces as &$ns)
	    $str .= " xmlns:" . $ns["prefix"] . "='" . $ns["uri"] . "'";
	unset($ns);
	$str .= ">";
	$this->pretty($str, $pp, 0, $var);

	$this->pretty($this->get_generator(), $pp, 1, $var);

	parent::output_container($pp, 1, $var);

	if ($this->opensearch) {
	    if ($this->opensearch["itemsPerPage"])
		$this->pretty("<opensearch:itemsPerPage>" .
			      $this->opensearch["itemsPerPage"] .
			      "</opensearch:itemsPerPage>", $pp, 1, $var);
	    if ($this->opensearch["startIndex"])
		$this->pretty("<opensearch:startIndex>" .
			      $this->opensearch["startIndex"] .
			      "</opensearch:startIndex>", $pp, 1, $var);
	    if ($this->opensearch["startPage"])
		$this->pretty("<opensearch:startPage>" .
			      $this->opensearch["startPage"] .
			      "</opensearch:startPage>", $pp, 1, $var);
	    $this->pretty("<opensearch:totalResults>" .
			  $this->opensearch["totalResults"] .
			  "</opensearch:totalResults>", $pp, 1, $var);
	}

	foreach ($this->entries as &$ent)
	    $ent->output_entry($pp, 1, $var);
	unset($ent);

	$this->pretty("</feed>", $pp, 0, $var);
    }

}

class Lucicat_Entry extends Lucicat_Container {
    protected $issued = NULL;
    protected $publisher = NULL;
    protected $categories = array();
    protected $subjects = array();
    protected $summary = NULL;
    protected $content = NULL;

    public function set_issued($date) {
	$this->issued = $date;
    }

    public function set_publisher($publisher) {
        $this->publisher = $publisher;
    }

    public function add_category($term, $scheme = NULL, $label = NULL) {
	$this->categories[] = array("term" => $term,
				    "scheme" => $scheme,
				    "label" => $label);
    }

    public function add_subject($subject, $type = NULL) {
	$this->subjects[] = array("subject" => $subject,
				  "type" => $type);
    }

    public function set_summary($summary, $type = "text") {
	$this->summary = array("summary" => $summary,
			       "type" => $type);
    }

    public function set_content($content, $type = "text") {
	$this->content = array("content" => $content,
			       "type" => $type);
    }

    public function output($pp = TRUE, &$var = NULL) {
	parent::output_header($pp, $var);

	$str = "<entry xmlns='http://www.w3.org/2005/Atom'";
	foreach ($this->namespaces as &$ns)
	    $str .= " xmlns:" . $ns["prefix"] . "='" . $ns["uri"] . "'";
	unset($ns);
	$str .= ">";
	$this->pretty($str, $pp, 0, $var);

	$this->pretty($this->get_generator(), $pp, 1, $var);

	$this->output_container($pp, 0, $var);

	$this->pretty("</entry>", $pp, 0, $var);
    }

    public function output_entry($pp, $level, &$var) {
	$this->pretty("<entry>", $pp, $level, $var);
	$this->output_container($pp, $level, $var);
	$this->pretty("</entry>", $pp, $level, $var);
    }

    protected function output_container($pp, $level, &$var) {
	parent::output_container($pp, $level + 1, $var);
	if ($this->issued)
	    $this->pretty("<dcterms:issued>" . $this->issued .
			  "</dcterms:issued>", $pp, $level + 1, $var);
	if ($this->publisher)
	    $this->pretty("<dcterms:publisher>" . $this->escape($this->publisher) .
			  "</dcterms:publisher>", $pp, $level + 1, $var);
	foreach ($this->categories as &$category) {
	    $str = "<category term='" . $this->escape($category["term"]) . "'";
	    if ($category["scheme"])
		$str .= " scheme='" . $this->escape($category["scheme"]) . "'";
	    if ($category["label"])
		$str .= " label='" . $this->escape($category["label"]) . "'";
	    $str .= "/>";
	    $this->pretty($str, $pp, $level + 1, $var);
	}
	unset($category);
	foreach ($this->subjects as &$subject) {
	    if ($subject["type"])
		$this->pretty("<dcterms:subject xsi:type='" . $subject["type"] .
			      "'>" . $this->escape($subject["subject"]) .
			      "</dcterms:subject>",
			      $pp, $level + 1, $var);
	    else
		$this->pretty("<dcterms:subject>" .
			      $this->escape($subject["subject"]) .
			      "</dcterms:subject>", $pp, $level + 1, $var);
	}
	unset($subject);
	if ($this->summary) {
	    $str = "<summary type='" . $this->summary["type"] . "'>";
	    if ($this->summary["type"] == "text")
		$str .= $this->escape($this->summary["summary"]);
	    else
		$str .= $this->summary["summary"];
	    $str .= "</summary>";
	    $this->pretty($str, $pp, $level + 1, $var);
	}
	if ($this->content) {
	    $str = "<content type='" . $this->content["type"] . "'>";
	    if ($this->content["type"] == "text")
		$str .= $this->escape($this->content["content"]);
	    else
		$str .= $this->content["content"];
	    $str .= "</content>";
	    $this->pretty($str, $pp, $level + 1, $var);
	}
    }

}

?>