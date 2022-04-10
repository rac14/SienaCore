<?php

namespace DidntPot\forms\types\properties;

use DidntPot\misc\ISaved;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

class ButtonTexture implements ISaved
{
    const TYPE_NONE = -1;
    const TYPE_PATH = 0;
    const TYPE_URL = 1;

    /** @var int */
    private int $imageType;

    /** @var string */
    private string $path;

    public function __construct(int $imageType, string $path)
    {
        $this->imageType = $imageType;
        $this->path = $path;
    }

    /**
     * @param $data
     * @return ButtonTexture|null
     *
     * Decodes the data and creates a new button texture object.
     */
    #[Pure] public static function decode($data): ?ButtonTexture
    {
        if (is_array($data) && isset($data["type"], $data["path"])) {
            return new ButtonTexture(
                $data["type"],
                $data["path"]
            );
        }
        return null;
    }

    /**
     * @return string
     *
     * Gets the texture path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * Sets the path of the button.
     */
    public function setPath(string $path): void
    {
        if ($path === $this->path) {
            return;
        }

        $this->path = $path;
    }

    /**
     * @return int
     *
     * Gets the texture path type (URL or PATH).
     */
    public function getImageType(): int
    {
        return $this->imageType;
    }

    /**
     * @param $imageType
     *
     * Sets the image type of the button texture.
     */
    public function setImageType($imageType): void
    {
        if ($this->imageType === (int)$imageType) {
            return;
        }

        $this->imageType = (int)$imageType;
    }

    /**
     * @param array $array - The array we are importing the information.
     *
     * Imports the button texture information.
     */
    public function import(array &$array): void
    {
        if (!$this->validate()) {
            return;
        }

        $array["image"]["type"] = $this->imageType === 0 ? "path" : "url";
        $array["image"]["data"] = $this->path;
    }

    /**
     * @return bool
     *
     * Determines if the button texture information is valid.
     */
    public function validate(): bool
    {
        return ($this->imageType === 0 || $this->imageType === 1) && $this->imageType !== "";
    }

    /**
     * @return array
     *
     * Exports the button texture.
     */
    #[ArrayShape(["type" => "int", "path" => "string"])] public function export(): array
    {
        return [
            "type" => $this->imageType,
            "path" => $this->path
        ];
    }
}