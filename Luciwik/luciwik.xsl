<?xml version="1.0" encoding="UTF-8"?>

<!--
   Luciwik - OPDS catalog system for MediaWiki
   Copyright © 2012  Mikael Ylikoski

   Copying and distribution of this file, with or without modification,
   are permitted in any medium without royalty provided the copyright
   notice and this notice are preserved.  This file is offered as-is,
   without any warranty.
  -->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	    	xmlns="http://www.w3.org/1999/xhtml"
		xmlns:fn="http://www.w3.org/2005/xpath-functions"
		xmlns:atom="http://www.w3.org/2005/Atom"
		xmlns:dcterms="http://purl.org/dc/terms/"
		xmlns:luci="http://lucidor.org/-/x-opds/"
		xmlns:opensearch="http://a9.com/-/spec/opensearch/1.1/">
<xsl:output method="xml" encoding="UTF-8"
	    doctype-public="-//W3C//DTD XHTML 1.1//EN"
	    doctype-system="http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"/>
<xsl:param name="style"/>

<xsl:template match="/">
  <html>
    <head>
      <title><xsl:value-of select="*/atom:title"/></title>
      <link rel='stylesheet' type='text/css'>
	<xsl:attribute name='href'><xsl:value-of select="concat(substring(substring-before(substring-after(string(/processing-instruction('xml-stylesheet')[1]), 'href='), '.xsl'), 2), '.css')"/></xsl:attribute>
      </link>
    </head>
    <body>
      <div class='body'>
	<xsl:apply-templates select="*/atom:title" mode="root"/>
	<xsl:apply-templates select="*/atom:subtitle" mode="root"/>

	<xsl:apply-templates select="atom:feed/atom:link[@rel='search' and @type='application/opensearchdescription+xml']"/>

	<div class='toplinks'>
	  <xsl:apply-templates select="atom:feed/atom:link[@rel='start']"/>
	  <xsl:apply-templates select="atom:feed/atom:link[@rel='first']"/>
	  <xsl:apply-templates select="atom:feed/atom:link[@rel='last']"/>
	  <xsl:apply-templates select="atom:feed/atom:link[@rel='previous']"/>
	  <xsl:apply-templates select="atom:feed/atom:link[@rel='next']"/>
	  <xsl:apply-templates select="atom:feed/atom:link[@rel='http://opds-spec.org/sort/new' or @rel='http://opds-spec.org/sort/featured' or @rel='http://opds-spec.org/sort/popular' or @rel='related']"/>
	</div>

	<xsl:if test="atom:feed/opensearch:startIndex and atom:feed/atom:link[@rel='start']">
	  <div>
	    Showing results <xsl:value-of select="atom:feed/opensearch:startIndex"/> to
	    <xsl:choose>
	      <xsl:when test="atom:feed/opensearch:itemsPerPage and atom:feed/opensearch:totalResults">
		<xsl:choose>
		  <xsl:when test="(atom:feed/opensearch:startIndex + atom:feed/opensearch:itemsPerPage - 1) &lt; atom:feed/opensearch:totalResults">
		    <xsl:value-of select="atom:feed/opensearch:startIndex + atom:feed/opensearch:itemsPerPage - 1"/>
		  </xsl:when>
		  <xsl:otherwise>
		    <xsl:value-of select="atom:feed/opensearch:totalResults"/>
		  </xsl:otherwise>
		</xsl:choose>
	      </xsl:when>
	      <xsl:otherwise>
		...
	      </xsl:otherwise>
	    </xsl:choose>
	    <xsl:if test="atom:feed/opensearch:totalResults">
	      of <xsl:value-of select="atom:feed/opensearch:totalResults"/>
	    </xsl:if>
	  </div>
	</xsl:if>

	<xsl:if test="atom:feed/opensearch:totalResults=0">
	  <div class='noresults'>
	    No results
	  </div>
	</xsl:if>

	<xsl:apply-templates select="atom:entry"/>
	<xsl:apply-templates select="atom:feed/atom:entry"/>

	<div>
	  <xsl:apply-templates select="atom:feed/atom:link[@rel='start']"/>
	  <xsl:apply-templates select="atom:feed/atom:link[@rel='first']"/>
	  <xsl:apply-templates select="atom:feed/atom:link[@rel='last']"/>
	  <xsl:apply-templates select="atom:feed/atom:link[@rel='previous']"/>
	  <xsl:apply-templates select="atom:feed/atom:link[@rel='next']"/>
	</div>
      </div>
    </body>
  </html>
</xsl:template>

<xsl:template match="atom:feed"/>

<xsl:template match="atom:id"/>

<xsl:template match="atom:title" mode="root">
  <h1><xsl:value-of select="."/></h1>
</xsl:template>

<xsl:template match="atom:subtitle" mode="root">
  <h2><xsl:value-of select="."/></h2>
</xsl:template>

<xsl:template match="atom:feed/atom:link">
  <a>
    <xsl:attribute name='class'>toplink</xsl:attribute>
    <xsl:attribute name='href'><xsl:value-of select="@href"/></xsl:attribute>
    <xsl:choose>
      <xsl:when test="@title">
	<xsl:value-of select="@title"/>
      </xsl:when>
      <xsl:when test="@rel='http://opds-spec.org/sort/new'">
	<xsl:text>New</xsl:text>
      </xsl:when>
      <xsl:when test="@rel='http://opds-spec.org/sort/popular'">
	<xsl:text>Popular</xsl:text>
      </xsl:when>
      <xsl:when test="@rel='http://opds-spec.org/sort/featured'">
	<xsl:text>Featured</xsl:text>
      </xsl:when>
      <xsl:when test="@rel='related'">
	<xsl:text>Related</xsl:text>
      </xsl:when>
      <xsl:when test="@rel='http://opds-spec.org/crawlable'">
	<xsl:text>Crawlable</xsl:text>
      </xsl:when>
      <xsl:when test="@rel='search'"/>
    </xsl:choose>
  </a>
</xsl:template>

<xsl:template match="atom:feed/atom:link[@rel='start' or @rel='previous' or @rel='next' or @rel='first' or @rel='last']">
  <a class="toplink">
    <xsl:attribute name='href'><xsl:value-of select="@href"/></xsl:attribute>
    <xsl:choose>
      <xsl:when test="@title">
	<xsl:value-of select="@title"/>
      </xsl:when>
      <xsl:when test="@rel='start'">
	<xsl:text>Main</xsl:text>
      </xsl:when>
      <xsl:when test="@rel='previous'">
	<xsl:text>Previous page</xsl:text>
      </xsl:when>
      <xsl:when test="@rel='next'">
	<xsl:text>Next page</xsl:text>
      </xsl:when>
      <xsl:when test="@rel='first'">
	<xsl:text>First page</xsl:text>
      </xsl:when>
      <xsl:when test="@rel='last'">
	<xsl:text>Last page</xsl:text>
      </xsl:when>
    </xsl:choose>
  </a>
  <xsl:text> </xsl:text>
</xsl:template>

<!-- This template is specific for Luciwik -->
<!--
<xsl:template match="atom:feed/atom:link[@rel='search' and @type='application/opensearchdescription+xml']">
  <div class="search">
    <form action="?" method="get">
      <xsl:if test="$style='html'">
	<input type="text" name="style" value="html" style="display: none"/>
      </xsl:if>
      <input type="text" name="query"/>
      <input type="submit" value="Search"/>
    </form>
  </div>
</xsl:template>
-->

<xsl:template match="atom:entry">
  <div class="entry">
    <xsl:apply-templates select="atom:link[@rel='http://opds-spec.org/image/thumbnail']"/>
    <xsl:apply-templates select="atom:title"/>
    <xsl:apply-templates select="atom:author"/>
    <xsl:apply-templates select="atom:content"/>
    <xsl:apply-templates select="dcterms:language"/>
    <xsl:apply-templates select="dcterms:publisher"/>
    <xsl:if test="not(count(atom:link)=1 and atom:link[not(@rel) or @rel='subsection'])">
      <xsl:apply-templates select="atom:link[not(@rel) or @rel!='http://opds-spec.org/image/thumbnail']"/>
    </xsl:if>
    <div class="clear"/>
  </div>
</xsl:template>

<xsl:template match="atom:entry/atom:title">
  <xsl:choose>
    <xsl:when test="count(../atom:link)=1 and ../atom:link[not(@rel) or @rel='subsection']">
      <div class='link'>
	<a>
	  <xsl:attribute name='href'><xsl:value-of select="../atom:link/@href"/></xsl:attribute>
	  <xsl:value-of select="."/>
	</a>
      </div>
    </xsl:when>
    <xsl:otherwise>
      <div class='title'>
	<xsl:value-of select="."/>
      </div>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<xsl:template match="atom:entry/atom:author">
  <div class='author'>
    <xsl:value-of select="."/>
  </div>
</xsl:template>

<xsl:template match="atom:entry/atom:content">
  <div class='content'>
    <xsl:value-of select="."/>
  </div>
</xsl:template>

<xsl:template match="atom:entry/dcterms:language">
  <div>
    <span class='label'>Language:</span> <xsl:text> </xsl:text>
    <xsl:choose>
      <xsl:when test="@luci:label"><xsl:value-of select="@luci:label"/></xsl:when>
      <xsl:when test=".='de'">German</xsl:when>
      <xsl:when test=".='el'">Greek</xsl:when>
      <xsl:when test=".='en'">English</xsl:when>
      <xsl:when test=".='en-GB'">English (British)</xsl:when>
      <xsl:when test=".='en-US'">English (American)</xsl:when>
      <xsl:when test=".='es'">Spanish</xsl:when>
      <xsl:when test=".='fr'">French</xsl:when>
      <xsl:when test=".='it'">Italian</xsl:when>
      <xsl:when test=".='ja'">Japanese</xsl:when>
      <xsl:when test=".='nl'">Dutch</xsl:when>
      <xsl:when test=".='nb'">Norwegian (Bokmål)</xsl:when>
      <xsl:when test=".='nn'">Norwegian (Nynorsk)</xsl:when>
      <xsl:when test=".='no'">Norwegian</xsl:when>
      <xsl:when test=".='pl'">Polish</xsl:when>
      <xsl:when test=".='pt'">Portuguese</xsl:when>
      <xsl:when test=".='ru'">Russian</xsl:when>
      <xsl:when test=".='sv'">Swedish</xsl:when>
      <xsl:when test=".='zh'">Chinese</xsl:when>
      <xsl:otherwise>
	<xsl:value-of select="."/>
      </xsl:otherwise>
    </xsl:choose>
  </div>
</xsl:template>

<xsl:template match="atom:entry/dcterms:publisher">
  <div>
    <span class='label'>Publisher:</span> <xsl:text> </xsl:text><xsl:value-of select="."/>
  </div>
</xsl:template>

<xsl:template match="atom:entry/atom:link">
  <div class='link'>
    <a>
      <xsl:attribute name='href'><xsl:value-of select="@href"/></xsl:attribute>
      <xsl:choose>
	<xsl:when test="@title">
	  <xsl:value-of select="@title"/>
	</xsl:when>
	<xsl:when test="@rel='http://opds-spec.org/acquisition'">
	  <xsl:text>Get book</xsl:text>
	</xsl:when>
	<xsl:when test="@rel='http://opds-spec.org/image'">
	  <xsl:text>Cover image</xsl:text>
	</xsl:when>
	<xsl:when test="not(@rel) or @rel='alternate'">
	  <xsl:value-of select="../atom:title"/>
	</xsl:when>
	<xsl:otherwise>
	  <xsl:text>Link</xsl:text>
	</xsl:otherwise>
      </xsl:choose>
    </a>
    <!-- ↗ -->
    <xsl:choose>
      <xsl:when test="@type='text/html'">
	<xsl:text> </xsl:text><span class='suffix'>Web page</span>
      </xsl:when>
      <xsl:when test="@type='application/epub+zip'">
	<xsl:text> </xsl:text><span class='suffix'>EPUB format</span>
      </xsl:when>
      <xsl:when test="@type='application/pdf'">
	<xsl:text> </xsl:text><span class='suffix'>PDF format</span>
      </xsl:when>
      <xsl:when test="@type='application/x-mobipocket-ebook'">
	<xsl:text> </xsl:text><span class='suffix'>Mobipocket format</span>
      </xsl:when>
      <xsl:when test="@type='application/x-sony-bbeb'">
	<xsl:text> </xsl:text><span class='suffix'>Sony Reader format</span>
      </xsl:when>
      <xsl:when test="@type='application/x-ms-reader'">
	<xsl:text> </xsl:text><span class='suffix'>Microsoft Reader format</span>
      </xsl:when>
      <xsl:when test="@type='text/rtf'">
	<xsl:text> </xsl:text><span class='suffix'>RTF format</span>
      </xsl:when>
    </xsl:choose>
  </div>
</xsl:template>

<xsl:template match="atom:entry/atom:link[@rel='http://opds-spec.org/image/thumbnail']">
  <div class='thumb'>
    <object class='image'>
      <xsl:attribute name='data'><xsl:value-of select="@href"/></xsl:attribute>
      <xsl:attribute name='type'><xsl:value-of select="@type"/></xsl:attribute>
    </object>
  </div>
</xsl:template>

</xsl:stylesheet>
