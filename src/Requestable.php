<?php namespace Brain\Cortex;

use Brain\Request;

trait Requestable {

    public function getRequest() {
        return $this->request;
    }

    public function setRequest( Request $request ) {
        $this->request = $request;
        return $this;
    }

}