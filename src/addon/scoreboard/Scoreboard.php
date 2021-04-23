<?php

declare(strict_types=1);

namespace addon\scoreboard;

use addon\player\AddonPlayer;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\utils\TextFormat;

/**
 * Class Scoreboard
 * @package addon\scoreboard
 */
class Scoreboard
{

    /** @var int */
    private const SORT_ASCENDING = 0;
    /** @var string */
    private const SLOT_SIDEBAR = 'sidebar';

    /** @var AddonPlayer */
    private $player;
    /** @var string */
    private $title;
    /** @var ScorePacketEntry[] */
    private $lines = [];

    /**
     * Scoreboard constructor.
     * @param AddonPlayer $player
     * @param string $title
     */
    public function __construct(AddonPlayer $player, string $title = TextFormat::BOLD . TextFormat::LIGHT_PURPLE . 'UHC')
    {
        $this->player = $player;
        $this->title = $title;
        $this->initScoreboard();
    }

    private function initScoreboard() : void
    {
        $pkt = new SetDisplayObjectivePacket();
        $pkt->objectiveName = $this->player->getName();
        $pkt->displayName = $this->title;
        $pkt->sortOrder = self::SORT_ASCENDING;
        $pkt->displaySlot = self::SLOT_SIDEBAR;
        $pkt->criteriaName = 'dummy';
        $this->player->dataPacket($pkt);
    }

    public function clearScoreboard(): void
    {
        $pkt = new SetScorePacket();
        $pkt->entries = $this->lines;
        $pkt->type = SetScorePacket::TYPE_REMOVE;
        $this->player->dataPacket($pkt);
        $this->lines = [];
    }

    public function addLine(int $id, string $line): void
    {
        $entry = new ScorePacketEntry();
        $entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;

        if (isset($this->lines[$id])) {
            $pkt = new SetScorePacket();
            $pkt->entries[] = $this->lines[$id];
            $pkt->type = SetScorePacket::TYPE_REMOVE;
            $this->player->dataPacket($pkt);
            unset($this->lines[$id]);
        }
        $entry->score = $id;
        $entry->scoreboardId = $id;
        $entry->entityUniqueId = $this->player->getId();
        $entry->objectiveName = $this->player->getName();
        $entry->customName = $line;
        $this->lines[$id] = $entry;

        $pkt = new SetScorePacket();
        $pkt->entries[] = $entry;
        $pkt->type = SetScorePacket::TYPE_CHANGE;
        $this->player->dataPacket($pkt);
    }

    public function removeLine(int $id): void
    {
        if (isset($this->lines[$id])) {
            $line = $this->lines[$id];
            $packet = new SetScorePacket();
            $packet->entries[] = $line;
            $packet->type = SetScorePacket::TYPE_REMOVE;
            $this->player->dataPacket($packet);
            unset($this->lines[$id]);
        }
    }

    public function removeScoreboard(): void
    {
        $packet = new RemoveObjectivePacket();
        $packet->objectiveName = $this->player->getName();
        $this->player->dataPacket($packet);
    }
}