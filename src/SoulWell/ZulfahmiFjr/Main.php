<?php

namespace SoulWell\ZulfahmiFjr;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\math\Vector3;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;

use skymin\InventoryLib\InvLibManager;
use SoulWell\ZulfahmiFjr\task\RollUpdater;

class Main extends PluginBase implements Listener{

    public $wellItems;
    public $souls;
    public $set = array();

    public function onEnable():void{
     if(!is_dir($this->getDataFolder())) @mkdir($this->getDataFolder());
     $this->getServer()->getPluginManager()->registerEvents($this, $this);
     InvLibManager::register($this);
     $this->saveResource("config.yml");
     $this->wellItems = $this->getConfig()->get("items");
     $this->souls = new Config($this->getDataFolder().'souls.yml', Config::YAML);
     $this->getLogger()->info("§l§d» §r§3This plugin was coded by:§e ZulfahmiFjr");
    }

    public function onPlayerJoin(PlayerJoinEvent $e){
     $p = $e->getPlayer();
     if($p instanceof Player){
      if(!$this->souls->exists(strtolower($p->getName()))){
       $this->souls->set(strtolower($p->getName()), 0);
       $this->souls->save();
      }
      $data = $this->getConfig();
      $x = $data->get("well.x");
      $y = $data->get("well.y");
      $z = $data->get("well.z");
      $text = "§2Kit§aPvP§b Soul Well\n§l§eRIGHT CLICK";
      $p->getWorld()->addParticle(new Vector3($x + 0.5, $y + 2, $z + 0.5), new FloatingTextParticle('', $text), array($p));
     }
    }

    public function onPlayerQuit(PlayerQuitEvent $e){
     $this->souls->save();
    }

    public function onPlayerInteract(PlayerInteractEvent $e){
     $p = $e->getPlayer();
     $b = $e->getBlock();
     if($p instanceof Player){
      $data = $this->getConfig();
      $x = $data->get("well.x");
      $y = $data->get("well.y");
      $z = $data->get("well.z");
      if($b->getPosition()->x === $x && $b->getPosition()->y === $y + 2 && $b->getPosition()->z === $z || $b->getPosition()->x === $x && $b->getPosition()->y === $y + 1 && $b->getPosition()->z === $z){
       $pk = new ModalFormRequestPacket();
       $pk->formId = 7382999;
       $message = "§f       _________________________\n           §5§lSoul Well by Blossom\n§r§f       -------------------------\n";
       if(!empty($this->getConfig()->get("message"))){
        if(is_array($this->getConfig()->get("message"))){
         foreach($this->getConfig()->get("message") as $text){
          $text = str_replace(["{KEY}", "{PLAYER}"], [$this->souls->get(strtolower($p->getName())), $p->getName()], $text);
          $message .= "{$text}§r\n\n";
         }
        }else{
         $text = str_replace(["{KEY}", "{PLAYER}"], [$this->souls->get(strtolower($p->getName())), $p->getName()], $this->getConfig()->get("message"));
         $message .= "{$text}§r\n\n";
        }
       }
       $encode = ["type" => "form", "title" => "§e§lSoul Well Confirm", "content" => "{$message}", "buttons" => [["text" => "§lOpen SoulWell"], ["text" => "§lCancel Opening"]]];
       $data = json_encode($encode);
       $pk->formData = $data;
       $p->getNetworkSession()->sendDataPacket($pk);
       $e->cancel();
      }
     }
    }

    public function onBlockBreak(BlockBreakEvent $e){
     $p = $e->getPlayer();
     $b = $e->getBlock();
     if(isset($this->set[$p->getName()])){
      $x = $b->getPosition()->x;
      $y = $b->getPosition()->y;
      $z = $b->getPosition()->z;
      $data = $this->getConfig();
      if(empty($data->get("well.x")) && empty($data->get("well.y")) && empty($data->get("well.z"))){
       $data->set("well.x", $x);
       $data->set("well.y", $y);
       $data->set("well.z", $z);
       $data->save();
       $text = "§2Kit§aPvP §bSoul Well\n§l§eRIGHT CLICK";
       $b->getPosition()->getWorld()->addParticle(new Vector3($x + 0.5, $y + 2, $z + 0.5), new FloatingTextParticle('', $text));
       $b->getPosition()->getWorld()->setBlockAt($x, $y + 1, $z, VanillaBlocks::END_PORTAL_FRAME());
       $p->sendMessage("§l§d» §r§aSoulWell successfully added§r§f.");
       unset($this->set[$p->getName()]);
      }else{
       $p->sendMessage("§l§d» §r§c A Soul Well has already been made please delete the existing one first§r§f.");
       unset($this->set[$p->getName()]);
      }
      $e->cancel();
      return;
     }
     $data = $this->getConfig();
     if(!empty($data->get("well.x")) && !empty($data->get("well.y")) && !empty($data->get("well.z"))){
      $x = $data->get("well.x");
      $y = $data->get("well.y");
      $z = $data->get("well.z");
      if($b->getPosition()->x === $x && $b->getPosition()->y === $y + 2 && $b->getPosition()->z === $z || $b->getPosition()->x === $x && $b->getPosition()->y === $y + 1  && $b->getPosition()->z === $z){
       if($this->getServer()->isOp($p->getName())){
        $data->remove("well.x");
        $data->remove("well.y");
        $data->remove("well.z");
        $data->save();
        $p->sendMessage("§§l§d» §r§aSoulWell successfully removed§r§f!");
       }else{
        $p->sendMessage("§l§d» §r§cYou do not have permission to break this SoulWell§r§f!");
        $e->cancel();
       }
      }
     }
    }

    public function onPacketReceive(DataPacketReceiveEvent $e){
     $pk = $e->getPacket();
     $p = $e->getOrigin()->getPlayer();
     if($pk instanceof ModalFormResponsePacket){
      $id = $pk->formId;
      $data = json_decode($pk->formData, true);
      if($id === 7382999){
       if(isset($data)){
        if($data === 0){
         if($this->souls->get(strtolower($p->getName())) < 10){
          $p->sendMessage("§l§d» §r§cYou do not own enough Soul Keys to open the SoulWell! Keep grinding!");
          return;
         }
         $this->souls->set(strtolower($p->getName()), $this->souls->get(strtolower($p->getName())) - 10);
         $this->souls->save();
         $this->getScheduler()->scheduleRepeatingTask(new RollUpdater($this, $p, 100), 3);
        }
       }
      }
     }
    }

    public function onCommand(CommandSender $p, Command $command, string $label, array $args):bool{
     switch($command->getName()){
      case "soulwell":{
       $p->sendMessage("§l§9» §r§e§oHello, this plugin was created by ZulfahmiFjr§r§f");
       break;
      }
      case "addwell":{
       if(!$p instanceof Player){
        $p->sendMessage("§l§d» §r§cPlease use this command in game§r§f!");
        return false;
       }
       if(!$this->getServer()->isOp($p->getName())){
        $p->sendMessage("§l§d» §r§cYou do not have permission to use this command§r§f!");
        return false;
       }
       $this->set[$p->getName()] = true;
       $p->sendMessage("§l§d» §r§bPlease destroy 1 block§r§f!");
       break;
      }
      case "addsouls":{
       if(!$this->getServer()->isOp($p->getName()) && $p instanceof Player){
        $p->sendMessage("§l§d» §r§cYou do not have permission to use this command§r§f!");
        return false;
       }
       if(!isset($args[0])){
        $p->sendMessage("§l§d» §r§cPlease use the command§r§f: /addsouls [player-name]");
        return false;
       }
       if(!isset($args[1])){
        $p->sendMessage("§l§d» §r§cPlease use the command§r§f: /addsouls [player-name] [count]");
        return false;
       }
       if(!is_numeric($args[1]) && $args[1] <= 0){
        $p->sendMessage("§l§d» §r§cPlease enter the number of soul keys correctly§r§f!");
        return false;
       }
       $t = $this->getServer()->getPlayerExact($args[0]);
       if($t instanceof Player){
        $t->sendMessage("§l§d» §r§a You have claimed§e ".$args[1]." §aSoul Keys§r§f!");
        $name = strtolower($t->getName());
       }else{
        $name = strtolower($args[0]);
       }
       $this->souls->set($name, $this->souls->get($name) + $args[1]);
       $this->souls->save();
       $p->sendMessage("§l§d» §r§aYou have successfully given §b".$name." ".$args[1]." §aSoul Keys§r§f!");
       break;
      }
     }
     return true;
    }

}
