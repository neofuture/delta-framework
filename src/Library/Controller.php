<?php

namespace NeoFuture\Library;

abstract class Controller {

    public function middleware($func){
        call_user_func($func);
    }
}