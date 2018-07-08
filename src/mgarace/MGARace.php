<?php
namespace mgarace;
use minigameapi\Game;
use minigameapi\MiniGameApi;
use minigameapi\Time;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\ItemIds;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class MGARace extends PluginBase {
    public function onEnable()/* : void /* TODO: uncomment this for next major version */ {
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        foreach ($this->getConfig()->get('games', []) as $key => $game){
            $this->initGame($key);
        }
    }
    public function initGame(string $gameName) {
        $game = $this->getConfig()->get('games')[$gameName];
        $game = new RaceGame($this, $gameName, $game['min'], $game['max'], new Time(0,0,1), new Time(0,30), json_decode($game['waitingroom']), json_decode($game['end']));
        MiniGameApi::getInstance()->getGameManager()->submitGame($game);
        $this->getServer()->getPluginManager()->registerEvents($game);
    }
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if(!isset($args[0])) return false;
        if (!isset($args[1])) return false;
        $games = $this->getConfig()->get('games', []);
        switch ($args[0]) {
            case 'create':
                if(isset($games[$args[1]])) {
                    $sender->sendMessage('this game is already existing');
                    break;
                }
                $games[$args[1]]['enabled'] = false;
                $sender->sendMessage('game created successfully');
                break;
            case 'remove':
                if(!isset($games[$args[1]])) {
                    $sender->sendMessage('this game is not existing');
                    break;
                }
                if($games[$args[1]]['enabled']) MiniGameApi::getInstance()->getGameManager()->removeGame($args[1]);
                unset($games[$args[1]]);
                break;
            case 'edit':
                if (!isset($args[2])) {
                    return false;
                }
                if(!isset($games[$args[1]])) {
                    $sender->sendMessage('this game is not existing');
                    break;
                }
                switch ($args[2]) {
                    case 'waitroom':
                        if (!$sender instanceof Player) {
                            $sender->sendMessage('only players can run this command');
                            break;
                        }
                        list($games[$args[1]]['waitingroom']['vec'], $games[$args[1]]['waitingroom']['level']) = [json_encode($sender->getPosition()->asVector3()), $sender->getPosition()->getLevel()->getFolderName()];
                        $sender->sendMessage('your location has set for the waiting room');
                        break;
                    case 'spawn':
                        if (!$sender instanceof Player) {
                            $sender->sendMessage('only players can run this command');
                            break;
                        }
                        list($games[$args[1]]['spawn']['vec'], $games[$args[1]]['spawn']['level']) = [json_encode($sender->getPosition()->asVector3()), $sender->getPosition()->getLevel()->getFolderName()];
                        $sender->sendMessage('your location has set for the spawn');
                        break;
                    case 'end':
                        if (!$sender instanceof Player) {
                            $sender->sendMessage('only players can run this command');
                                break;
                        }
                        list($games[$args[1]]['end']['vec'], $games[$args[1]]['end']['level']) = [json_encode($sender->getPosition()->asVector3()), $sender->getPosition()->getLevel()->getFolderName()];
                        $sender->sendMessage('your location has set for the end position');
                        break;
                    case 'min':
                        if (!isset($args[3]) or !is_numeric($args[3])) {
                            $sender->sendMessage('/mgarace ' . $args[1] . ' min <minPlayers(number)>');
                            break;
                        }
                        if($args[3] < 1) {
                            $sender->sendMessage('minPlayers must have to be bigger than 0');
                            break;
                        }
                        if (!isset($games[$args[1]]) and $games[$args[1]]['max'] < $args[3]) {
                            $sender->sendMessage('minPlayers must have to be smaller than maxPlayers');
                            break;
                        }
                        $games[$args[1]]['min'] = (int)$args[3];
                        $sender->sendMessage('minPlayers is now ' . (int)$args[3]);
                        break;
                    case 'max':
                        if (!isset($args[3]) or !is_numeric($args[3])) {
                            $sender->sendMessage('/mgarace ' . $args[1] . ' max <maxPlayers(number)>');
                            break;
                        }
                        if($args[3] < 1) {
                            $sender->sendMessage('maxPlayers must have to be bigger than 0');
                            break;
                        }
                        if (!isset($games[$args[1]]) and $games[$args[1]]['min'] > $args[3]) {
                            $sender->sendMessage('maxPlayers must have to be bigger than minPlayers');
                            break;
                        }
                        $games[$args[1]]['max'] = (int)$args[3];
                        $sender->sendMessage('maxPlayers is now ' . (int)$args[3]);
                        break;
                    case 'startitem':
                        if (!$sender instanceof Player) {
                            $sender->sendMessage('only players can run this command');
                            break;
                        }
                        if ($sender->getInventory()->getItemInHand()->getId() == ItemIds::AIR) {
                            $sender->sendMessage('air cannot be set to start item');
                        }
                        $games[$args[1]]['startitem'] = json_encode(clone $sender->getInventory()->getItemInHand());
                        $sender->sendMessage('item on your hand has set to start item');
                        break;
                }
                break;
            case 'enable':
                if(!isset($games[$args[1]])) {
                    $sender->sendMessage('this game is not existing');
                    break;
                }
                $game = $games[$args[1]];
                if ($game['enabled']) {
                    MiniGameApi::getInstance()->getGameManager()->getGame($args[1])->end(Game::END_KILLED_GAME);
                    $game['enabled'] = false;
                    $sender->sendMessage('game disabled');
                }
                switch (true) {
                    case !isset($game['waitingroom']):
                        $sender->sendMessage('waiting room not set');
                        break;
                    case !isset($game['spawn']):
                        $sender->sendMessage('spawn not set');
                        break;
                    case !isset($game['end']):
                        $sender->sendMessage('end location not set');
                        break;
                    case !isset($game['min']):
                        $sender->sendMessage('minPlayers not set');
                        break;
                    case !isset($game['max']):
                        $sender->sendMessage('maxPlayers not set');
                        break;
                    default:
                        $game['enabled'] = true;
                        $this->initGame($args[1]);
                        $sender->sendMessage('enabled game successfully');
                        break;
                }
                $games[$args[1]] = $game;
                break;
        }
        $this->getConfig()->set('games',$games);
        $this->getConfig()->save();
        return true;
    }
}