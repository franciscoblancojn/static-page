<?php

class STPA_GLOBAL_SECTIONS_DATA
{
    const OPTION_KEY = STPA_KEY . '_GLOBAL_SECTIONS';

    public static function getAll()
    {
        $data = get_option(self::OPTION_KEY, []);
        return is_array($data) ? $data : [];
    }

    public static function get($slug)
    {
        $all = self::getAll();
        return $all[$slug] ?? null;
    }

    public static function save($slug, $entry)
    {
        $all = self::getAll();
        $all[$slug] = $entry;
        update_option(self::OPTION_KEY, $all);
    }

    public static function delete($slug)
    {
        $all = self::getAll();
        unset($all[$slug]);
        update_option(self::OPTION_KEY, $all);
    }
}
