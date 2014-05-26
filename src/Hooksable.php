<?php namespace Brain\Cortex;

trait Hooksable {

    public function getHooks() {
        return $this->hooks;
    }

    public function setHooks( \Brain\Hooks $hooks ) {
        $this->hooks = $hooks;
        return $this;
    }

}