<h1>WordPress OOP(Object Oriented Programming) Theme</h1>
<p>For those who prefer an object oriented aproach to WordPress development</p>

<h3>What this theme can be used for?</h3>
<p>This theme is just like any other theme in the sense that you have the usual theme structure (style.css, index.php, page.php, search.php etc), but with the difference that you can use code like <code>thisPage()->permalink</code>.</p>

<h3>What can I do with this theme</h3>
<p>Simple answer? Lots of easy stuff!</p>
<p>This theme uses global variables to store queried objects so not to perform multiple database queries unnecessarily. Calling <code>thisPage()->title</code> and <code>thisPage()->permalink</code> queries the page object only once and allows you to retreive many properties.</p>
<p>The theme allows you to easily get a page object by using <code>page($pageTitle)</code> or <code>page($str, $propToSearch='title')</code>
<p>You can easily query search results by <code>search()->hasPosts</code></p>

<h3>Features</h3>
<ul>
  <li>Access same queried object multiple times without duplicating unnecessary queries</li>
  <li>Compatible with Advanced Custom Fields using <code>thisPage()->gcf($fieldName, $fallback)</code></li>
</ul>
