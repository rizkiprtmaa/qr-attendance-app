<?php
// app/View/Components/Breadcrumbs.php
namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class Breadcrumbs extends Component
{
    public $items;

    public function __construct($items = [])
    {
        $this->items = $items;
    }

    public function render()
    {
        return view('components.breadcrumbs');
    }
}
