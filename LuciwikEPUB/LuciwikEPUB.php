<?php

  /*
    LuciwikEPUB - EPUB generator extension for MediaWiki
  */

// Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if (!defined('MEDIAWIKI')) {
    echo("This is an extension to the MediaWiki package and cannot be run standalone.\n");
    die(-1);
}

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'LuciwikEPUB',
	'author' => 'Ordbrand',
	'url' => 'http://lucidor.org/luciwik/',
	'description' => 'Enables downloading of wiki pages in EPUB format',
	'version' => '0.5',
);

$wgAutoloadClasses['SpecialLuciwikEPUB'] = dirname(__FILE__) . '/' . 'SpecialLuciwikEPUB.php';
$wgExtensionMessagesFiles['LuciwikEPUB'] = dirname(__FILE__) . '/' . 'LuciwikEPUB.i18n.php';
$wgSpecialPages['LuciwikEPUB'] = 'SpecialLuciwikEPUB';
$wgSpecialPageGroups['LuciwikEPUB'] = 'pagetools';
$wgHooks['BaseTemplateToolbox'][] = 'wfLuciwikEPUBToolbox';
$wgHooks['SkinTemplateToolboxEnd'][] = 'wfLuciwikEPUBToolboxEnd';

// User configuration
$wgLuciwikEPUBEnableDiscussion = FALSE;
$wgLuciwikEPUBEnableMulti = TRUE;
$wgLuciwikEPUBEnableSingle = TRUE;
$wgLuciwikEPUBExtraItems = array();
$wgLuciwikEPUBLimit = 30;
$wgLuciwikEPUBSplit = FALSE;

function wfLuciwikEPUBToolbox(&$sk, &$toolbox) {
    global $wgTitle;
    global $wgArticlePath;
    global $wgLuciwikEPUBEnableSingle;
    global $wgLuciwikEPUBEnableMulti;

    if ($wgTitle) {
	$ns = $wgTitle->getNamespace();
	if ($wgLuciwikEPUBEnableSingle and
	    (($wgTitle->exists() and ($ns == NS_MAIN or $ns == NS_TALK))
	     or $ns == NS_CATEGORY)) {
	    $url = str_replace('$1', 'Special:LuciwikEPUB/' . $wgTitle->getPrefixedURL(), $wgArticlePath);
	    $toolbox['luciwikepub']['href'] = $url;
	    $toolbox['luciwikepub']['msg'] = 'luciwikepub-download';
	    $toolbox['luciwikepub']['rel'] = 'alternate';
	    $toolbox['luciwikepub']['type'] = 'application/epub+zip';
	    $toolbox['luciwikepub']['id'] = 't-luciwikepub';
	    $toolbox['luciwikepub']['tooltiponly'] = true;
	}

	if ($wgLuciwikEPUBEnableMulti and $wgTitle->exists()
	    and ($ns == NS_MAIN or $ns == NS_TALK)) {
	    $url = str_replace('$1', 'Special:LuciwikEPUB', $wgArticlePath);
	    $url = wfAppendQuery($url, 'cmd=add&article=' . $wgTitle->getPrefixedURL());
	    $toolbox['luciwikepubadd']['href'] = $url;
	    $toolbox['luciwikepubadd']['msg'] = 'luciwikepub-add';
	    $toolbox['luciwikepubadd']['id'] = 't-luciwikepub-add';
	    $toolbox['luciwikepubadd']['tooltiponly'] = true;
	}
    }

    return TRUE;
}

function wfLuciwikEPUBToolboxEnd(&$sk, $dummy = FALSE) {
    global $wgTitle;
    global $wgArticlePath;
    global $wgLuciwikEPUBEnableSingle;
    global $wgLuciwikEPUBEnableMulti;

    if (!$dummy and $wgTitle) {
	$ns = $wgTitle->getNamespace();
	if ($wgLuciwikEPUBEnableSingle and
	    (($wgTitle->exists() and ($ns == NS_MAIN or $ns == NS_TALK))
	     or $ns == NS_CATEGORY)) {
	    $url = str_replace('$1', 'Special:LuciwikEPUB/' . $wgTitle->getPrefixedURL(), $wgArticlePath);
	    echo "\n\t\t\t\t<li id='t-luciwikepub'><a href='" .
		htmlentities($url) . "' title='" .
		wfMsgHtml('tooltip-t-luciwikepub') .
		"' rel='alternate' type='application/epub+zip'>" .
		wfMsgHtml('luciwikepub-download') . "</a></li>";
	}

	if ($wgLuciwikEPUBEnableMulti and $wgTitle->exists()
	    and ($ns == NS_MAIN or $ns == NS_TALK)) {
	    $url = str_replace('$1', 'Special:LuciwikEPUB', $wgArticlePath);
	    $url = wfAppendQuery($url, 'cmd=add&article=' . $wgTitle->getPrefixedURL());
	    echo "\n\t\t\t\t<li id='t-luciwikepub-add'><a href='" .
		htmlentities($url) . "' title='" .
		wfMsgHtml('tooltip-t-luciwikepub-add') . "'>" .
		wfMsgHtml('luciwikepub-add') . "</a></li>";
	}
    }

    return TRUE;
}

?>