<?php

namespace discord;

use JsonSerializable;

/**
 * Class Message
 * @package discord
 */
class Message implements JsonSerializable
{
    /** @var array */
    protected $data = [];

    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->data["content"] = $content;
    }

    /**
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->data["content"];
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->data["username"];
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username): void
    {
        $this->data["username"] = $username;
    }

    /**
     * @return string|null
     */
    public function getAvatarURL(): ?string
    {
        return $this->data["avatar_url"];
    }

    /**
     * @param string $avatarURL
     */
    public function setAvatarURL(string $avatarURL): void
    {
        $this->data["avatar_url"] = $avatarURL;
    }

    /**
     * @param Embed $embed
     */
    public function addEmbed(Embed $embed): void
    {
        if (!empty(($arr = $embed->asArray()))) {
            $this->data["embeds"][] = $arr;
        }
    }

    /**
     * @param bool $ttsEnabled
     */
    public function setTextToSpeech(bool $ttsEnabled): void
    {
        $this->data["tts"] = $ttsEnabled;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return $this->data;
    }
}
