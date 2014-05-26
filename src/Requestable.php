<?php namespace Brain\Cortex;

trait Requestable {

    public function getRequest() {
        return $this->request;
    }

    public function setRequest( \Brain\Request $request ) {
        $this->request = $request;
        return $this;
    }

}