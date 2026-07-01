<?php

use franciscoblancojn\wordpress_utils\FWURespond;

function STPA_Respond($respond)
{
    echo FWURespond::css();
    return FWURespond::html($respond);
}
