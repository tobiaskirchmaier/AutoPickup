<?php
declare(strict_types=1);

namespace tobias14\autopickup;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

class AutoPickup extends PluginBase implements Listener
{

    public function onEnable()
    {
        $this->reloadConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onBreak(BlockBreakEvent $event)
    {
        if($event->isCancelled()) return;
        $player = $event->getPlayer();

        $affectedWorlds = $this->getConfig()->get("worlds", []);
        if(!in_array($player->getLevel()->getName(), $affectedWorlds)) return;

        // Send items to player
        $drops = $event->getDrops();
        foreach ($drops as $key => $drop) {
            if($player->getInventory()->canAddItem($drop)) {
                $player->getInventory()->addItem($drop);
                unset($drops[$key]);
            }
        }
        $event->setDrops($drops);

        // Send xp to player
        $xpDrops = $event->getXpDropAmount();
        $player->addXp($xpDrops);
        $event->setXpDropAmount(0);
    }

}
