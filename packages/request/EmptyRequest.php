<?php

namespace Request;

class EmptyRequest extends Request implements \IRequest
{
    public function send() { /*_*/ }
}