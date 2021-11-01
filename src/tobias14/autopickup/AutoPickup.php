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

    /** @var string $fullInvPopup */
    protected $fullInvPopup;

    /** @var string $mode */
    protected $mode;

    /** @var array $affectedWorlds */
    protected $affectedWorlds;

    public function onEnable() : void 
    {
        $this->reloadConfig();

        $this->fullInvPopup = $this->getConfig()->get('full-inventory', '');
        $this->mode = $this->getConfig()->get('mode', 'blacklist');
        $this->affectedWorlds = $this->getConfig()->get('worlds', []);

        $this->getServer()->getPluginManager()->registerEvent(BlockBreakEvent::class, $this, EventPriority::HIGHEST, new MethodEventExecutor("onBreak"), $this);
    }

    public function onBreak(BlockBreakEvent $event) : void 
    {
        if($event->isCancelled()) return;
        $player = $event->getPlayer();

        if(!$this->shouldPickup($player->getLevel()->getName()))
            return;

        // Send items to player
        $drops = $event->getDrops();
        foreach ($drops as $key => $drop) {
            if($player->getInventory()->canAddItem($drop)) {
                $player->getInventory()->addItem($drop);
                unset($drops[$key]);
            } else {
                if($this->fullInvPopup != '') {
                    $player->sendPopup(TextFormat::colorize($this->fullInvPopup));
                }
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
    private function shouldPickup(string $level): bool
    {
        if(strtolower($this->mode) == 'blacklist') {
            if(in_array($level, $this->affectedWorlds))
                return false;
        } elseif (strtolower($this->mode) == 'whitelist') {
            if(!in_array($level, $this->affectedWorlds))
                return false;
        }
        return true;
    }

}
