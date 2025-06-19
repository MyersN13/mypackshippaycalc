<?php
require_once __DIR__.'/Box.php';
require_once __DIR__.'/Item.php';

class Packer
{
    private $boxes = [];
    private $items = [];
    
    public function addBox(Box $box)
    {
        $this->boxes[] = $box;
    }
    
    public function addItem(Item $item)
    {
        $this->items[] = $item;
    }
    
    public function pack()
    {
        // Sort boxes by volume (smallest first)
        usort($this->boxes, function ($a, $b) {
            return $a->getVolume() <=> $b->getVolume();
        });
        
        // Sort items by volume (largest first)
        usort($this->items, function ($a, $b) {
            return $b->getVolume() <=> $a->getVolume();
        });
        
        $packedBoxes = [];
        
        while (!empty($this->items)) {
            $boxFound = false;
            
            foreach ($this->boxes as $box) {
                $packedBox = $this->packIntoBox(clone $box, $this->items);
                
                if ($packedBox->getItems()->count() > 0) {
                    $packedBoxes[] = $packedBox;
                    $boxFound = true;
                    break;
                }
            }
            
            if (!$boxFound) {
                throw new RuntimeException('Item cannot be packed into any box');
            }
        }
        
        return $packedBoxes;
    }
    
    private function packIntoBox(Box $box, array &$items)
    {
        $remainingItems = [];
        
        foreach ($items as $item) {
            if ($box->canHoldItem($item)) {
                $box->insert($item);
            } else {
                $remainingItems[] = $item;
            }
        }
        
        $items = $remainingItems;
        return $box;
    }
}