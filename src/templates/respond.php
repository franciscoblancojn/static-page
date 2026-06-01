<?php

function STPA_parseRespondMessage($text)
{
    return preg_replace(
        '/(https?:\/\/[^\s]+)/',
        '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
        $text
    );
}

function STPA_Respond($respond)
{
    if (!isset($respond)) {
        return "";
    }

    ob_start();
?>
    <p class="stpa-message <?= $respond['status'] ?>" data="<?= htmlspecialchars(json_encode($respond['data'] ?? [])) ?>">
        <?= STPA_parseRespondMessage($respond['message']) ?>
    </p>
<?php
    return ob_get_clean();
}
