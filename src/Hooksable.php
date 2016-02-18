<?php namespace Brain\Cortex;

use Brain\Hooks;

trait Hooksable {

    public function getHooks() {
        return $this->hooks;
    }

    public function setHooks( Hooks $hooks ) {
        $this->hooks = $hooks;
        return $this;
    }

}