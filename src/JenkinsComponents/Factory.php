<?php

namespace ooobii\Jenkins;

use ooobii\Jenkins;

class Factory
{

    /**
     * @param string $url
     *
     * @return Jenkins
     */
    public function build($url)
    {
        return new Jenkins($url);
    }
}
