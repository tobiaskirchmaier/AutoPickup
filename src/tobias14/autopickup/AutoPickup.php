<?php
declare(strict_types=1);

namespace tobias14\autopickup;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\plugin\MethodEventExecutor;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class AutoPickup extends PluginBase implements Listener
{

    public function onEnable() : void 
    {
        $this->reloadConfig();
        $this->getServer()->getPluginManager()->registerEvent(BlockBreakEvent::class, $this, EventPriority::HIGHEST, new MethodEventExecutor("onBreak"), $this);
    }

    public function onBreak(BlockBreakEvent $event) : void 
    {
        if($event->isCancelled()) return;
        $player = $event->getPlayer();

        if(!$this->checkWorld($player->getLevel()->getName()))
            return;

        // Send items to player
        $drops = $event->getDrops();
        foreach ($drops as $key => $drop) {
            if($player->getInventory()->canAddItem($drop)) {
                $player->getInventory()->addItem($drop);
                unset($drops[$key]);
            } else {
                $popup = $this->getConfig()->get('full-inventory', '');
                if($popup != '')
                    $player->sendPopup(TextFormat::colorize($popup));
            }
        }
        $event->setDrops($drops);

        // Send xp to player
        $xpDrops = $event->getXpDropAmount();
        $player->addXp($xpDrops);
        $event->setXpDropAmount(0);
    }

    /**
     * @param string $level
     * @return bool
     */
    private function checkWorld(string $level): bool
    {
        $mode = $this->getConfig()->get('mode', 'blacklist');
        $affectedWorlds = $this->getConfig()->get('worlds', []);
        if(strtolower($mode) == 'blacklist') {
            if(in_array($level, $affectedWorlds))
                return false;
        } elseif (strtolower($mode) == 'whitelist') {
            if(!in_array($level, $affectedWorlds))
                return false;
        }
        return true;
    }

}
