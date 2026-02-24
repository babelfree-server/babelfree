<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="2.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
  xmlns:xhtml="http://www.w3.org/1999/xhtml">
<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
<xsl:template match="/">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
  <title>XML Sitemap — babelfree.com</title>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #1a1208; color: #e8dcc8; padding: 2rem; }
    h1 { color: #c8a96e; margin-bottom: 0.5rem; font-size: 1.5rem; }
    p.info { color: #a89070; margin-bottom: 1.5rem; font-size: 0.9rem; }
    table { width: 100%; border-collapse: collapse; background: #2a2218; border-radius: 8px; overflow: hidden; }
    th { background: #3a3020; color: #c8a96e; text-align: left; padding: 0.75rem 1rem; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; }
    td { padding: 0.5rem 1rem; border-top: 1px solid #3a3020; font-size: 0.85rem; }
    tr:hover td { background: #332a1a; }
    a { color: #c8a96e; text-decoration: none; }
    a:hover { text-decoration: underline; }
    .priority { text-align: center; }
    .freq { text-align: center; }
    .count { color: #a89070; font-size: 0.85rem; margin-bottom: 1rem; }
  </style>
</head>
<body>
  <h1>XML Sitemap</h1>
  <p class="count">
    <xsl:value-of select="count(sitemap:urlset/sitemap:url)"/> URLs
  </p>
  <table>
    <tr>
      <th>URL</th>
      <th class="priority">Priority</th>
      <th class="freq">Frequency</th>
      <th>Last Modified</th>
    </tr>
    <xsl:for-each select="sitemap:urlset/sitemap:url">
      <tr>
        <td><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a></td>
        <td class="priority"><xsl:value-of select="sitemap:priority"/></td>
        <td class="freq"><xsl:value-of select="sitemap:changefreq"/></td>
        <td><xsl:value-of select="sitemap:lastmod"/></td>
      </tr>
    </xsl:for-each>
  </table>
</body>
</html>
</xsl:template>
</xsl:stylesheet>
