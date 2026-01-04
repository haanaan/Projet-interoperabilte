<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" indent="yes"/>

<xsl:template match="/">
  <section class="meteo-resume">
    <h2>Météo - <xsl:value-of select="meteo/jour/ville"/></h2>
    <div class="meteo-jour">
      <xsl:apply-templates select="meteo/jour"/>
    </div>
  </section>
</xsl:template>

<xsl:template match="jour">
  <div class="periode">
    <h3>Date: <xsl:value-of select="@date"/></h3>
    
    <div class="moment">
      <strong>Matin:</strong> 
      <span class="temp"><xsl:value-of select="matin/temperature"/>°C</span> - 
      <span class="temps"><xsl:value-of select="matin/temps"/></span> - 
      <span class="vent"><xsl:value-of select="matin/vent/@force"/> vent</span>
    </div>
    
    <div class="moment">
      <strong>Midi:</strong> 
      <span class="temp"><xsl:value-of select="midi/temperature"/>°C</span> - 
      <span class="temps"><xsl:value-of select="midi/temps"/></span> - 
      <span class="vent"><xsl:value-of select="midi/vent/@force"/> vent</span>
    </div>
    
    <div class="moment">
      <strong>Soir:</strong> 
      <span class="temp"><xsl:value-of select="soir/temperature"/>°C</span> - 
      <span class="temps"><xsl:value-of select="soir/temps"/></span> - 
      <span class="vent"><xsl:value-of select="soir/vent/@force"/> vent</span>
    </div>
  </div>
</xsl:template>
</xsl:stylesheet>
