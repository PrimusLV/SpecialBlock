<?php

namespace Primus;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use Primus\Timer;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\Command;
use pocketmine\entity\Effect;
use pocketmine\utils\Config;
use pocketmine\Player;

class Main extends PluginBase implements Listener{
	
	private $cfg;
	private $blockCmds;
	private $killedByBlock;
	private $damageBlock;
	private $healingBlock;
	private $effectBlock;
	private $blockClass;
	private $list;
	private $interval;
	
	public function onEnable(){
		$this->interval = $this->getConfig()->get('interval');
		$this->cfg = $this->getConfig();
		$this->damageBlock = $this->getConfig()->get('damage-block');
		$this->healingBlock = $this->getConfig()->get('healing-block');
		$this->effectBlock = $this->getConfig()->get('effect-block');
		$this->killedByBlock = false;
		$default = array('100:100:100:world' => array(
		'me I love SpecialBlock'
		));
		// -------------------------------------------------------------
		if(!file_exists($this->getDataFolder()."blocks.yml")){
			$this->getLogger()->info('Created new config file for blocks');
			$this->blockCmds = new Config($this->getDataFolder()."blocks.yml", Config::YAML, $default);
		}else{
			$this->getLogger()->info('Loaded existing config file for blocks');
			$this->blockCmds = new Config($this->getDataFolder()."blocks.yml", Config::YAML);
		}
		// DEBUG - $this->getLogger()->info($this->blockCmds->get('100:100:100:world')[0]);
		$this->getLogger()->info("Activated");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->saveDefaultConfig();
		$this->reloadConfig();
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Timer($this), $this->interval);
	}
	public function onDisable(){
		$this->getLogger()->info("Deactivated");
		$this->saveDefaultConfig();
		
	}
	
	public function checkBlock($player, $x, $y, $z){
		$pos = new Vector3($x, $y - 1, $z);
		$blockId = $player->getLevel()->getBlock($pos);
		//$this->getLogger()->info($blockId." is on pos".$x."-".$y."-".$z."");
		$world = $player->getLevel()->getName();
		if($blockId instanceof Block){
			$this->getLogger($pos);
			if($blockId->getId() === Block::get($this->damageBlock)->getId()){
			//	$this->getLogger()->info($blockId." is on pos".$x."-".$y."-".$z."");
				$this->doDamage($player);
				return true;
			}elseif($blockId->getId() === Block::get($this->healingBlock)->getId()){
				$this->healPlayer($player);
				return true;
			}elseif($blockId->getId() === Block::get($this->effectBlock)->getId()){
				$this->giveEffect($player);
				return true;
			}elseif($this->isCommandBlock($x, $y, $z, $world)){
				$this->executeCmds($x, $y, $z, $world, $player);
				return true;
				}else{
				return true;
				}
		}else{
			//$this->getLogger()->info($blockId." is not a block on pos".$x."-".$y."-".$z."");
		}
	}
	
	public function doDamage($player){
		$damage = $this->getConfig()->get('damage');
		$currentHealth = $player->getHealth();
		$finalDmg = $currentHealth - $damage;
		if($currentHealth - $damage <= 0){
			$this->killedByBlock = true;
			$player->setHealth($finalDmg);
		}else{
			$player->setHealth($finalDmg);
		}
	}
	
	public function onDeath(PlayerDeathEvent $e){
		$msg = $this->getConfig()->get("death-message");
		$msg = str_replace("{PLAYER}", $e->getEntity()->getName(), $msg);
		$msg = str_replace("{BLOCK}", strtolower($this->getConfig()->get('damage-block-name')), $msg);
		if($this->killedByBlock){
			if($this->getConfig()->get("broadcast-on-chat") === false){
				foreach($this->getServer()->getOnlinePlayers() as $allP){
					$allP->sendPopup('/n');
					$allP->sendPopup($msg);
					unset($this->killedByBlock);
					$e->setDeathMessage(null);
				}
			}else{
			$e->setDeathMessage($msg);
			$this->killedByBlock = false;
			}

		}else{
			}
	}
	
	public function healPlayer($player){
		$currentHealth = $player->getHealth();
		$hpGain = $this->getConfig()->get('hp-gain');
		$msg = $this->getConfig()->get('healing-message');
		$player->sendPopup($msg);
		$player->setHealth($currentHealth + $hpGain);
	}
	
	public function giveEffect($player){
		$cfg = $this->getConfig();
        $id = $cfg->get('effect-id');
        $amplifier = $cfg->get('effect-amplifier');
        $visibility = $cfg->get('effect-visibility');
        $duration = $cfg->get('effect-duration');
        $effect = Effect::getEffect($id);
      
      if($effect != null){
        $effect->setVisible($visibility);
        $effect->setDuration($duration);
        $effect->setAmplifier($amplifier);
        $player->addEffect($effect);
        if($cfg->get('send-message-on-recieve')){
			$player->sendMessage($cfg->get('effect-message'));
		}elseif($cfg->get('send-popup-on-recieve')){
			$player->sendPopup($cfg->get('effect-popup'));
		}else{
			
			}
		}else{
			$this->getLogger()->info('ยง4Config is incorrectly setup');
			$this->getLogger()->info('effect-id: '.$this->getConfig()->get('effect-id').' - is not valid');
			}
	}
		
		public function isCommandBlock($x, $y, $z, $world){
			$needle = $x.":".$y.":".$z.":".$world;
			if($this->blockCmds == null) $this->getServer()->getPluginManager()->disablePlugin($this);
			$commandBlock = $this->blockCmds->get($needle);
			if($commandBlock){
				return true;
			}else{
				return false;
				}
		}
		
		public function executeCmds($x, $y, $z, $world, $player){
			$commands = $this->blockCmds->get($x.":".$y.":".$z.":".$world);
			foreach($commands as $command){
		 $type = $this->getCommandType($command);
			switch($type){
			////////// AS_OP //////////
				case 1:
				if($player->isOp() === true){
					$command = $this->filterCommand($command, $player);
					$this->getServer()->dispatchCommand($player, $command);
					return true;
				}else{
					$command = $this->filterCommand($command, $player);
					$player->setOp(true);
					$this->getServer()->dispatchCommand($player, $command);
					$player->setOp(false);
					return true;
				}
				break;
			////////// AS_CONSOLE //////////
				case 2:
				$command = $this->filterCommand($command, $player);
				$this->getServer()->dispatchCommand(new ConsoleCommandSender(), $command);
				break;
				case 3:
					$command = $this->filterCommand($command, $player);
					$command = explode(' ', $command);
					$issuer = $this->getServer()->getPlayer($command[0]);
					if(!($issuer instanceof Player)){
						$this->getLogger()->info('Player: '.$command[0].' was not found canceling command executing Error occured on block: '.$x.':'.$y.':'.$z.' world: '.$world);
						return;
					}
					unset($command[0]);
					$command = implode(' ', $command);
					$this->getServer()->dispatchCommand($issuer, $command);
				break;
				case 4:
					$command = $this->filterCommand($command, $player);
					$command = explode(' ', $command);
					$issuer = $this->getServer()->getPlayer($command[0]);
					if(!($issuer instanceof Player)){
						$this->getLogger()->info('Player: '.$command[0].' was not found canceling command executing Error occured on block: '.$x.':'.$y.':'.$z.' world: '.$world);
						return;
					}
					unset($command[0]);
					$command = implode(' ', $command);
					if($issuer->isOp()){
					$this->getServer()->dispatchCommand($issuer, $command);
				}else{
					$issuer->setOp(true);
					$this->getServer()->dispatchCommand($issuer, $command);
					$issuer->setOp(false);
				}
				break;
		default:
		echo 'Using default';
		 $command = $this->filterCommand($command, $player);
		 $this->getServer()->dispatchCommand($player, $command);
		 break;
			}
		}
	}
	
		public function getCommandType($command){
			if(strpos($command, '{OP}') !== false){
				echo strpos($command, '{OP}');
				return 1;
			}elseif(strpos($command, '{CON}') !== false){
				return 2;
			}elseif(strpos($command, '{AO}') !== false){
				return 3;
			}elseif(strpos($command, '{AOOP}') !== false){
				return 4;
				}else{
				return 5;
			}
		}
		
		public function filterCommand($command, $player){
			$command = str_replace('{OP}', '', $command);
			$command = str_replace('{CON}', '', $command);
			$command = str_replace('{AO}', '', $command);
			$command = str_replace('{AOOP}', '', $command);
			$command = str_replace('{PLAYER}', $player->getName(), $command);
			return $command;
		}
}
