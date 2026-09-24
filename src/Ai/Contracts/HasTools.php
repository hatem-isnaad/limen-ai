<?php

namespace LimenAi\Ai\Contracts;

use LimenAi\Contracts\Tools\Tool;

interface HasTools
{
    /**
     * @return iterable<int, Tool|class-string<Tool>>
     */
    public function tools(): iterable;
}
