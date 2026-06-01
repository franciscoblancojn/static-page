<?php

class STPA_USE_DATA_CONFIG
{
    protected $KEY = STPA_CONFIG;
    protected $DATA = [];

    public function __construct()
    {
        $this->DATA = get_option($this->KEY, []);
    }

    public function get()
    {
        return $this->DATA;
    }

    public function set($DATA)
    {
        $this->DATA = $DATA;
        update_option($this->KEY, $this->DATA);
    }

    public function setField($key, $value)
    {
        $this->DATA[$key] = $value;
        update_option($this->KEY, $this->DATA);
    }
}
