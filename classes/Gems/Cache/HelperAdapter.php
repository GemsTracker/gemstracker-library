<?php

declare(strict_types=1);


namespace Gems\Cache;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class HelperAdapter extends TagAwareAdapter
{
    private AdapterInterface $pool;

    public function __construct(
        AdapterInterface $itemsPool,
        AdapterInterface $tagsPool = null,
        float $knownTagVersionsTtl = 0.15
    ) {
        $this->pool = $itemsPool;
        parent::__construct($itemsPool, $tagsPool, $knownTagVersionsTtl);
    }

    public function getCacheItem($key)
    {
        if ($this->pool->hasItem($key)) {
            $item = $this->pool->getItem($key);
            return $item->get();
        }

        return null;
    }

    public function setCacheItem($key, $value, $tag=null)
    {
        $item = $this->pool->getItem($key);
        if ($tag !== null && $item instanceof ItemInterface) {
            $item->tag($tag);
        }
        $item->set($value);
        $this->pool->save($item);
    }
}
