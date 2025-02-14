<ul><?php foreach ($page->children() as $cat) { ?>
    <li><a href="<?= $cat->url() ?>"><?= $cat->title() ?></a></li>
<?php } ?>
</ul>
