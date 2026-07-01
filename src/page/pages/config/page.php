<?php
require_once STPA_DIR . 'src/css/global.php';
require_once STPA_DIR . 'src/js/procesing-html.php';

$STPA_USE_DATA_CONFIG = new STPA_USE_DATA_CONFIG();
$CONFIG = $STPA_USE_DATA_CONFIG->get();

$TAGS = [
    [
        'key' => 'list',
        'title' => 'Páginas Estáticas',
    ],
    [
        'key' => 'global-sections',
        'title' => 'Secciones Globales',
    ],
    [
        'key' => 'config',
        'title' => 'Configuración',
    ],
];
$defaultTag = $TAGS[0]['key'];

?>
<div id="page-<?= STPA_KEY ?>" class="wrap">
    <h1>Static Page</h1>
    <div class="nav-tab-wrapper woo-nav-tab-wrapper">
        <?php foreach ($TAGS as $tag): ?>
            <a class="nav-tab <?= $tag['key'] === $defaultTag ? 'nav-tab-active' : '' ?>"
               data-tab="<?= $tag['key'] ?>"
               href="#tag-<?= $tag['key'] ?>">
                <?= $tag['title'] ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php foreach ($TAGS as $tag): ?>
        <div class="tab-content <?= $tag['key'] === $defaultTag ? 'nav-tab-active' : '' ?>" id="<?= $tag['key'] ?>">
            <?php require_once STPA_DIR . 'src/page/sections/' . $tag['key'] . '.php'; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php
require_once STPA_DIR . 'src/js/global.php';
