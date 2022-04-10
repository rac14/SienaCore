<?php

namespace DidntPot\player\item;

use DidntPot\PracticeCore;
use DidntPot\utils\Utils;

class ItemTextures
{
    /** @var array */
    private array $textures;

    /**
     * @param PracticeCore $core
     */
    public function __construct(PracticeCore $core)
    {
        $path = $core->getResourcesFolder();
        $contents = file($path . "items.txt");

        $this->textures = [];

        foreach ($contents as $content) {
            $content = trim($content);
            $index = Utils::str_indexOf(': ', $content);
            $itemName = substr($content, 0, $index);
            $itemTexture = trim(substr($content, $index + 2));
            $png = Utils::str_indexOf('.png', $itemTexture);
            $itemTexture = trim(substr($itemTexture, 0, $png));
            $this->textures[$itemName] = $itemTexture;
        }
    }

    /**
     * @param string $item
     * @return string
     */
    public function getTexture(string $item): string
    {
        $result = "apple";

        if (isset($this->textures[$item]))
            $result = $this->textures[$item];

        return 'textures/items/' . $result;
    }
}