<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
  version="2.0">

<xsl:output method="xml"/> 

<xsl:template match="/">
<xsl:for-each select="//testsuite[@file]">
    <xsl:variable name="filename" select="concat(@name,'.xml')" />
    <xsl:result-document href="{$filename}" method="xml">
        <xsl:copy-of select="." />
    </xsl:result-document>
</xsl:for-each>
</xsl:template>

</xsl:stylesheet>
