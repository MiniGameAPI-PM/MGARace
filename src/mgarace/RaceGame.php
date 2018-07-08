<?php
namespace mgarace;
use minigameapi\Game;
use minigameapi\Team;
use minigameapi\Time;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\level\Position;
use pocketmine\Player;

class RaceGame extends Game implements Listener {
    private $end;
    private $winner;
    public function __construct(
        MGARace $plugin,
        string $name,
        int $neededPlayers = 1,
        int $maxPlayers = 9999,
        Time $runningTime = null,
        Time $waitingTime = null,
        Position $waitingRoom = null,
        Position $endLocation
    ) {
        $this->end = $endLocation;
        parent::__construct($plugin, $name, $neededPlayers, $maxPlayers, $runningTime, $waitingTime, $waitingRoom);
    }

    public function onWaiting() {
        if($this->getRemainingWaitTime()->asSec() <= 10) $this->broadcastMessage($this->getRemainingWaitTime()->asSec() . ' second left!');
    }
    public function onRunning() {
        if ($this->getRemainingRunTime()->asSec() <= 10) $this->broadcastMessage($this->getRemainingWaitTime()->asSec() . ' second left!');
    }
    public function onStart(): bool {
        foreach ($this->getPlayers() as $player) {
            if(!$player instanceof Player) return true;
            $player->getInventory()->clearAll();
            $player->getInventory()->setItemInHand(json_decode($this->getPlugin()->getConfig()->get('games')[$this->getName()]['startitem']));
        }
        $this->broadcastMessage('game started!!');
        return true;
    }

    public function onPlayerMove(PlayerMoveEvent $event) {
        if (!$this->isInGame($event->getPlayer())) return;
        if ($this->isRunning()) {
            switch (false) {
                case $this->end->getFloorX() == $event->getPlayer()->getFloorX(): return;
                case $this->end->getFloorY() == $event->getPlayer()->getFloorY(): return;
                case $this->end->getFloorZ() == $event->getPlayer()->getFloorZ(): return;
                case $this->end->getLevel()->getFolderName() == $event->getPlayer()->getLevel()->getFolderName(): return;
                default:
                    $this->winner = $event->getPlayer();
                    $this->end();
                    return;
            }
        }
    }
    public function onEnd(int $endCode) {
        if($endCode !== Game::END_NORMAL) return;
        $this->broadcastMessage($this->winner->getName() . 'won the game!');
    }
    public function assignPlayers() {
        foreach ($this->getPlayers() as $player) {
            $team = new Team($player->getName(), 1,$this->getPlugin()->toPosition($this->getPlugin()->getConfig()->get('games')[$this->getName()]['spawn']));
            $team->addPlayer($player);
            $this->submitTeam($team);
        }
    }
}