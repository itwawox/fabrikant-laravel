<?php

namespace App\Jobs\Menu;

use App\Services\Menu\MenuMarkup;

class CarryOverMenuMarkup extends MenuJob
{
    public function handle(MenuMarkup $markup): void
    {
        $this->menu->update(['progress_step' => 'carry_over']);

        if ($this->menu->carry_over) {
            $markup->carryOver($this->menu);
        }
    }
}
