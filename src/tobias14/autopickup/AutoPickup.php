<?php
declare(strict_types=1);

namespace tobias14\autopickup;

use Closure;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use ReflectionException;

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

        $handlerClosure = Closure::fromCallable([$this, 'onBreak']);
        try {
            $this->getServer()->getPluginManager()->registerEvent(BlockBreakEvent::class, $handlerClosure, EventPriority::HIGHEST, $this);
        } catch (ReflectionException $e) {
            $this->getLogger()->critical($e->getMessage());
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
    }

    public function onBreak(BlockBreakEvent $event) : void 
    {
        $player = $event->getPlayer();

        if(!$this->shouldPickup($player->getWorld()->getFolderName()))
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
        $player->getXpManager()->addXp($xpDrops);
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
