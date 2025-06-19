<?php
class Box
{
    private $name;
    private $length;
    private $width;
    private $height;
    private $maxWeight;
    private $items;
    private $weight;
    
    public function __construct($name, $length, $width, $height, $maxWeight)
    {
        $this->name = $name;
        $this->length = $length;
        $this->width = $width;
        $this->height = $height;
        $this->maxWeight = $maxWeight;
        $this->items = new \SplObjectStorage();
        $this->weight = 0;
    }
    
    public function canHoldItem(Item $item)
    {
        if ($this->weight + $item->getWeight() > $this->maxWeight) {
            return false;
        }
        
        // Check if item fits in any orientation
        $dimensions = [
            [$item->getLength(), $item->getWidth(), $item->getHeight()],
            [$item->getLength(), $item->getHeight(), $item->getWidth()],
            [$item->getWidth(), $item->getLength(), $item->getHeight()],
            [$item->getWidth(), $item->getHeight(), $item->getLength()],
            [$item->getHeight(), $item->getLength(), $item->getWidth()],
            [$item->getHeight(), $item->getWidth(), $item->getLength()],
        ];
        
        foreach ($dimensions as $d) {
            if ($d[0] <= $this->length && $d[1] <= $this->width && $d[2] <= $this->height) {
                return true;
            }
        }
        
        return false;
    }
    
    public function insert(Item $item)
    {
        $this->items->attach($item);
        $this->weight += $item->getWeight();
    }
    
    public function getItems()
    {
        return $this->items;
    }
    
    public function getWeight()
    {
        return $this->weight;
    }
    
    public function getVolume()
    {
        return $this->length * $this->width * $this->height;
    }
    
    public function getUsedWidth()
    {
        $maxWidth = 0;
        foreach ($this->items as $item) {
            $maxWidth = max($maxWidth, $item->getWidth());
        }
        return min($maxWidth, $this->width);
    }
    
    public function getUsedLength()
    {
        $maxLength = 0;
        foreach ($this->items as $item) {
            $maxLength = max($maxLength, $item->getLength());
        }
        return min($maxLength, $this->length);
    }
    
    public function getUsedDepth()
    {
        $maxDepth = 0;
        foreach ($this->items as $item) {
            $maxDepth = max($maxDepth, $item->getHeight());
        }
        return min($maxDepth, $this->height);
    }
}