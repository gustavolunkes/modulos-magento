<?php

class MageShop_StockOverrideEvento_Model_Stock_Observer
{
    // Flag to prevent multiple executions in the same request
    protected static $_alreadyExecuted = false;

    /**
     * Executes for individual events: product save or stock item save
     */
    public function autoSetInStock(Varien_Event_Observer $observer)
    {
        $threshold = (int) Mage::getStoreConfig('cataloginventory/item_options/auto_instock_threshold');

        if (!$threshold) {
            return;
        }

        if (self::$_alreadyExecuted) {
            return;
        }

        self::$_alreadyExecuted = true;

        // Try to get the stock item directly from the event
        $item = $observer->getEvent()->getItem();

        // If stock item is not provided, try to retrieve it using the product
        if (!$item || !$item instanceof Mage_CatalogInventory_Model_Stock_Item) {
            $product = $observer->getEvent()->getProduct();
            if ($product && $product->getId()) {
                $item = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());
            }
        }

        // Abort if stock item is still not found or invalid
        if (!$item || !$item->getId()) {
            return;
        }

        $qty = (int) $item->getQty();

        // If quantity >= threshold and product is still marked as out of stock
        if ($qty >= $threshold && !$item->getIsInStock()) {
            $item->setIsInStock(1);
            try {
                $item->getResource()->save($item);
                Mage::log('Product ID ' . $item->getProductId() . ' marked as In Stock (individual event)', null, 'stock_observer.log');
            } catch (Exception $e) {
                Mage::log('Error saving Stock Item: ' . $e->getMessage(), null, 'stock_observer.log');
            }
        }

        // Update the parent configurable product if necessary
        $this->updateParentConfigurableStock($item->getProductId(), $threshold);
    }

    /**
     * Executes for mass attribute updates (catalog_product_attribute_update_after)
     */
    public function autoSetInStockMassUpdate(Varien_Event_Observer $observer)
    {
        $threshold = (int) Mage::getStoreConfig('cataloginventory/item_options/auto_instock_threshold');

        if (!$threshold) {
            return;
        }

        $productIds = $observer->getEvent()->getProductIds();

        if (!is_array($productIds) || empty($productIds)) {
            return;
        }

        foreach ($productIds as $productId) {
            $item = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);

            if ($item && $item->getId()) {
                if ((int)$item->getQty() >= $threshold && !$item->getIsInStock()) {
                    $item->setIsInStock(1);
                    try {
                        $item->getResource()->save($item);
                        Mage::log('Product ID ' . $productId . ' marked as In Stock (mass update)', null, 'stock_observer.log');
                    } catch (Exception $e) {
                        Mage::log('Error saving Stock Item (mass update): ' . $e->getMessage(), null, 'stock_observer.log');
                    }
                }

                // Update the parent configurable product if necessary
                $this->updateParentConfigurableStock($productId, $threshold);
            }
        }
    }

    /**
    * Updates the status of the parent configurable product based on the inventory of its children
    * If at least one child has a quantity >= threshold, marks the parent as "In Stock"
    * Otherwise, does not change the parent's status
     */
    protected function updateParentConfigurableStock($childProductId, $threshold)
    {
        $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($childProductId);
        if (empty($parentIds)) {
            return;
        }

        foreach ($parentIds as $parentId) {
            $childIds = Mage::getModel('catalog/product_type_configurable')->getChildrenIds($parentId);
            $hasInStockChild = false;

            foreach ($childIds[0] as $id) {
                $childItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($id);
                if ($childItem && $childItem->getId() && (int)$childItem->getQty() >= $threshold) {
                    $hasInStockChild = true;
                    break;
                }
            }

            if ($hasInStockChild) {
                $parentStockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($parentId);
                if ($parentStockItem && !$parentStockItem->getIsInStock()) {
                    $parentStockItem->setIsInStock(1);
                    try {
                        $parentStockItem->getResource()->save($parentStockItem);
                        Mage::log("Configurable Product ID {$parentId} marked as In Stock (at least one child in stock)", null, 'stock_observer.log');
                    } catch (Exception $e) {
                        Mage::log("Error saving Configurable Stock Item: " . $e->getMessage(), null, 'stock_observer.log');
                    }
                }
            }
        }
    }
}