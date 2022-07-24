<?php

declare(strict_types=1);

namespace skh6075\itemsavedtest;

use ArrayIterator;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\PluginOwnedTrait;
use pocketmine\utils\TextFormat;
use skh6075\lib\itemparser\ItemParser;

final class Main extends PluginBase{

	/**
	 * Never use ArrayIterator on a production server.
	 *
	 * I used it for fun for testing.
	 */
	public ArrayIterator $iterator;

	public function onEnable() : void{
		$this->iterator = new ArrayIterator();

		$this->getServer()->getCommandMap()->register(strtolower($this->getName()), new class($this) extends Command implements PluginOwned{
			use PluginOwnedTrait;

			public function __construct(Main $plugin){
				parent::__construct('item', 'item saved test command.');
				$this->setPermission('item.saved.permission');
				$this->owningPlugin = $plugin;
			}

			public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
				if(!$sender instanceof Player || !$this->testPermission($sender)){
					return false;
				}
				switch(array_shift($args) ?? ''){
					case 'save':
						if(count($args) < 1){
							$sender->sendMessage(TextFormat::YELLOW . "/item save [name]");
							return false;
						}

						if(($item = $sender->getInventory()->getItemInHand())->isNull()){
							$sender->sendMessage(TextFormat::RED . "Try holding something other than air in your hand");
							return false;
						}

						$this->owningPlugin->iterator->offsetSet($name = array_shift($args), json_encode(ItemParser::toArray($item), JSON_THROW_ON_ERROR));
						$sender->sendMessage(TextFormat::AQUA . "Successfully save a hand-held item as \"$name\"");
						break;
					case 'load':
						if(count($args) < 1){
							$sender->sendMessage(TextFormat::YELLOW . "/item load [name]");
							return false;
						}

						$name = array_shift($args);
						if(!$this->owningPlugin->iterator->offsetExists($name)){
							$sender->sendMessage(TextFormat::RED . "The item $name could not be found");
							return false;
						}

						$item = ItemParser::fromArray(json_decode($this->owningPlugin->iterator->offsetGet($name), true, 512, JSON_THROW_ON_ERROR));
						$sender->getInventory()->setItemInHand($item);
						break;
					default:
						$sender->sendMessage(TextFormat::YELLOW . "/item [save/load] [name]");
						break;
				}
				return true;
			}
		});
	}

	public function getIterator(): ArrayIterator{
		return $this->iterator;
	}
}