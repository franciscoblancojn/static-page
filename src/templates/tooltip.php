<?php

function STPA_Tooltip($title, $text)
{
    ob_start();
?>
    <div>
        <?= $title ?>
        <span class="stpa-tooltip">
            <span class="dashicons dashicons-info"></span>
            <span class="stpa-tooltip-text"><?= $text ?></span>
        </span>
    </div>
<?php
    return ob_get_clean();
}
