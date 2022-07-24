<?php

declare(strict_types=1);

namespace skh6075\lib\itemparser;

use InvalidArgumentException;
use pocketmine\data\bedrock\item\ItemTypeDeserializeException;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use function base64_encode;
use function base64_decode;

final class ItemParser{


	public static function toArray(Item $item): array{
		$savedItemData = GlobalItemDataHandlers::getSerializer()->serializeType($item);

		$data = [
			"stringId" => $savedItemData->getName()
		];

		if($item instanceof Durable && $item->getDamage() !== 0){
			$data["damage"] = $item->getDamage();
		}

		if($item->getCount() !== 1){
			$data["count"] = $item->getCount();
		}

		if($item->hasNamedTag()){
			$data["nbt_64"] = base64_encode((new LittleEndianNbtSerializer())->write(new TreeRoot($item->getNamedTag())));
		}

		return $data;
	}

	public static function fromArray(array $data): Item{
		if(!isset($data["stringId"])){
			throw new InvalidArgumentException("An array that does not conform to the ItemParser format cannot be processed into an item.");
		}

		$nbt = "";
		if(isset($data["nbt_64"])){
			$nbt = base64_decode($data["nbt_64"], true);
		}

		$itemStackData = GlobalItemDataHandlers::getUpgrader()->upgradeItemTypeDataString(
			rawNameId: $data["stringId"],
			meta: (int)($data["damage"] ?? 0),
			count: (int)($data["count"] ?? 1),
			nbt: $nbt !== "" ? (new LittleEndianNbtSerializer())->read($nbt)->mustGetCompoundTag() : null
		);

		try{
			return GlobalItemDataHandlers::getDeserializer()->deserializeStack($itemStackData);
		}catch(ItemTypeDeserializeException $e){
			throw new SavedDataLoadingException($e->getMessage(), 0, $e);
		}
	}
}