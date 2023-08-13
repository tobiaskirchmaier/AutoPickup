<?php
declare(strict_types=1);

namespace tobiaskirchmaier\autopickup;

use Closure;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\plugin\DisablePluginException;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use ReflectionException;
use tobiaskirchmaier\autopickup\utils\Configuration;

class AutoPickup extends PluginBase implements Listener
{

    /** @var Configuration $configuration */
    private Configuration $configuration;

    public function onEnable() : void
    {
        $this->reloadConfig();
        $this->initConfiguration();

        $pluginMgr = $this->getServer()->getPluginManager();
        try {
            $onBreak = Closure::fromCallable([$this, 'onBreak']);
            $pluginMgr->registerEvent(BlockBreakEvent::class, $onBreak, EventPriority::HIGHEST, $this);
            $onEntityDeath = Closure::fromCallable([$this, 'onEntityDeath']);
            $pluginMgr->registerEvent(EntityDeathEvent::class, $onEntityDeath, EventPriority::HIGHEST, $this);
        } catch (ReflectionException $e) {
            throw new DisablePluginException($e->getMessage());
        }
    }

    public function onBreak(BlockBreakEvent $event) : void 
    {
        $player = $event->getPlayer();
        if(!$this->shouldPickup($player->getWorld()->getFolderName())) {
            return;
        }

        // Send items to player.
        $drops = $event->getDrops();
        foreach ($drops as $key => $drop) {
            if($player->getInventory()->canAddItem($drop)) {
                $player->getInventory()->addItem($drop);
                unset($drops[$key]);
                continue;
            }
            if($this->configuration->fullInvPopup != '') {
                $player->sendPopup(TextFormat::colorize($this->configuration->fullInvPopup));
            }
        }
        $event->setDrops($drops);

        // Send xp to player.
        $xpDrops = $event->getXpDropAmount();
        $player->getXpManager()->addXp($xpDrops);
        $event->setXpDropAmount(0);
    }

    public function onEntityDeath(EntityDeathEvent $event): void
    {
        $entity = $event->getEntity();
        if(!$this->shouldPickup($entity->getWorld()->getFolderName())) {
            return;
        }
        $lastDamageEvent = $entity->getLastDamageCause();
        if(!($lastDamageEvent instanceof EntityDamageByEntityEvent)) {
            return;
        }
        $player = $lastDamageEvent->getDamager();
        if(!($player instanceof Player)) {
            return;
        }

        // Send items to player.
        $drops = $event->getDrops();
        foreach ($drops as $key => $drop) {
            if($player->getInventory()->canAddItem($drop)) {
                $player->getInventory()->addItem($drop);
                unset($drops[$key]);
                continue;
            }
            if($this->configuration->fullInvPopup != '') {
                $player->sendPopup(TextFormat::colorize($this->configuration->fullInvPopup));
            }
        }
        $event->setDrops($drops);

        // Send xp to player.
        $xpDrops = $event->getXpDropAmount();
        $player->getXpManager()->addXp($xpDrops);
        $event->setXpDropAmount(0);
    }

    /**
     * @param string $world
     * @return bool
     */
    private function shouldPickup(string $world): bool
    {
        $mode = strtolower($this->configuration->mode);
        $affectedWorlds = $this->configuration->affectedWorlds;

        return ($mode === 'blacklist' && !in_array($world, $affectedWorlds)) or
            ($mode === 'whitelist' && in_array($world, $affectedWorlds));
    }

    private function initConfiguration(): void
    {
        $config = $this->getConfig();
        $this->configuration = new Configuration();
        $this->configuration->fullInvPopup = $config->get('full-inventory-popup', '');
        $this->configuration->mode = $config->get('mode', 'blacklist');
        $this->configuration->affectedWorlds = $config->get('worlds', []);
    }

}
