<?php

namespace discord;

/**
 * Class Embed
 * @package discord
 */
class Embed
{

    /** @var array */
    protected $data = [];

    /**
     * @return array
     */
    public function asArray(): array
    {
        return $this->data;
    }

    /**
     * @param string $name
     * @param string|null $url
     * @param string|null $iconURL
     */
    public function setAuthor(string $name, string $url = null, string $iconURL = null): void
    {
        if (!isset($this->data["author"])) {
            $this->data["author"] = [];
        }
        $this->data["author"]["name"] = $name;

        if ($url !== null) {
            $this->data["author"]["url"] = $url;
        }

        if ($iconURL !== null) {
            $this->data["author"]["icon_url"] = $iconURL;
        }
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->data["title"] = $title;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->data["description"] = $description;
    }

    /**
     * @param int $color
     */
    public function setColor(int $color): void
    {
        $this->data["color"] = $color;
    }

    /**
     * @param string $name
     * @param string $value
     * @param bool $inline
     */
    public function addField(string $name, string $value, bool $inline = false): void
    {
        if (!isset($this->data["fields"])) {
            $this->data["fields"] = [];
        }
        $this->data["fields"][] = [
            "name" => $name,
            "value" => $value,
            "inline" => $inline,
        ];
    }

    /**
     * @param string $url
     */
    public function setThumbnail(string $url): void
    {
        if (!isset($this->data["thumbnail"])) {
            $this->data["thumbnail"] = [];
        }
        $this->data["thumbnail"]["url"] = $url;
    }

    /**
     * @param string $url
     */
    public function setImage(string $url): void
    {
        if (!isset($this->data["image"])) {
            $this->data["image"] = [];
        }
        $this->data["image"]["url"] = $url;
    }

    /**
     * @param string $text
     * @param string|null $iconURL
     */
    public function setFooter(string $text, string $iconURL = null): void
    {
        if (!isset($this->data["footer"])) {
            $this->data["footer"] = [];
        }
        $this->data["footer"]["text"] = $text;

        if ($iconURL !== null) {
            $this->data["footer"]["icon_url"] = $iconURL;
        }
    }

    /**
     * @param \DateTime $timestamp
     */
    public function setTimestamp(\DateTime $timestamp): void
    {
        $timestamp->setTimezone(new \DateTimeZone("UTC"));
        $this->data["timestamp"] = $timestamp->format("Y-m-d\TH:i:s.v\Z");
    }
}
