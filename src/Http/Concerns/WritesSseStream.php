<?php

namespace LimenAi\Http\Concerns;

trait WritesSseStream
{
    /** @param  list<string>  $lines */
    protected function writeSseLines(array $lines): void
    {
        foreach ($lines as $line) {
            echo $line."\n\n";
        }

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}
