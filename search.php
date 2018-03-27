<?php
/*
Template Name: Search Page
*/
?>
<?php getTheHeader(); ?>
<div class="page-container small-width center-block">
	<h1><?= thisPage()->title; ?></h1>
	<div class="page-intro page-container-inner left">
		<p class="center normal size-2 black"><?= "{$numberResults} result(s) for search term &quot;<span class=\"bold\">{$keywords}</span>&quot;"; ?></p>
		<p class="center normal size-1 black italic"><?= "Showing page {$currentPage} of {$totalPages} pages"; ?></p>
		<?php if (search()->hasResults) { ?>
		<div id="search-results">
			<ul id="the-results" class="plain-list list-vertical">
			<?php foreach (search()->results as $result) { ?>
			    <li class="table full-width result">
			    	<a href="<?= $result->permalink; ?>"><?= $result->title; ?></a>
			    </li>
			<?php } ?>
			</ul>
			<?php if (search()->pages > 1) { ?>
			<ul id="result-pagination" class="plain-list list-horizontal center">
			<?php for ($p = 1; $p <= search()->pages; $p++) { ?>
			    <li>
			    <?php if ($p == search()->currentPage) { ?>
			    	<span><?= $p; ?></span>
			    <?php } else { ?>
			    	<a href="<?= search()->pageLink($p); ?>"><?= $p; ?></a>
			    <?php } ?>
			    </li>
			<?php } ?>
			</ul>
			<?php } ?>
		</div>
		<?php } ?>
	</div>
</div>
<?php getTheFooter(); ?>