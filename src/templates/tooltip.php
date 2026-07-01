<?php

use franciscoblancojn\wordpress_utils\FWUTooltip;

function STPA_Tooltip($title, $text)
{
    echo FWUTooltip::css();
    return FWUTooltip::html($title, $text);
}
