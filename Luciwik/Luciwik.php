<?php

  /*
    Luciwik - OPDS catalog system for MediaWiki
  */


// Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if (!defined('MEDIAWIKI')) {
    echo("This is an extension to the MediaWiki package and cannot be run standalone.\n");
    die(-1);
}

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'Luciwik',
	'author' => 'Ordbrand',
	'url' => 'http://lucidor.org/luciwik/',
	'description' => 'Enables access to wiki pages as an OPDS catalog',
	'version' => '0.5',
);

$wgAutoloadClasses['SpecialLuciwik'] = dirname(__FILE__) . '/' . 'SpecialLuciwik.php';
$wgExtensionMessagesFiles['Luciwik'] = dirname(__FILE__) . '/' . 'Luciwik.i18n.php';
$wgSpecialPages['Luciwik'] = 'SpecialLuciwik';
$wgSpecialPageGroups['Luciwik'] = 'other';

// User configuration
define('LUCIWIK_MODE_FLAT', 'a');
define('LUCIWIK_MODE_TREE', 'b');
define('LUCIWIK_MODE_CATEGORIES', 'c');

$wgLuciwikMode = LUCIWIK_MODE_FLAT;
$wgLuciwikAllLink = TRUE;
$wgLuciwikThisLink = TRUE;
$wgLuciwikEnableMetadata = FALSE;
$wgLuciwikStylesheet = FALSE;

$wgHooks['ParserFirstCallInit'][] = 'wfLuciwikParserInit';

function wfLuciwikParserInit(Parser $parser) {
    $parser->setHook('luci', 'wfLuciwikRender');
    return TRUE;
}

function wfLuciwikRender($input, array $args, Parser $parser, PPFrame $frame) {
    return '';
}

?>