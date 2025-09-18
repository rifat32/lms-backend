<?php

namespace App\View\Components;

use Illuminate\View\Component;

class LinkButton extends Component
{
    public $link;
    public $label;
    public $type;

    public function __construct($link, $label, $type = 'primary')
    {
        $this->link = $link;
        $this->label = $label;
        $this->type = $type;
    }
    public function render()
    {
        return view('components.link-button');
    }
}
