<?php

class MageShop_StockOverrideEvento_Model_Stock_Observer
{

    // Flag para controlar os produtos já processados na mesma requisição
protected static $_processedProducts = array();

/**
 * Executes for individual events: product save or stock item save
 */
public function autoSetInStock(Varien_Event_Observer $observer)
{
    $threshold = (int) Mage::getStoreConfig('cataloginventory/item_options/auto_instock_threshold');
    if (!$threshold) {
        return;
    }

    // Pega o ID do produto associado ao evento
    $productId = null;
    $product = $observer->getEvent()->getProduct();
    if ($product && $product->getId()) {
        $productId = $product->getId();
    } else {
        $item = $observer->getEvent()->getItem();
        if ($item && $item instanceof Mage_CatalogInventory_Model_Stock_Item) {
            $productId = $item->getProductId();
        }
    }

    // Se não encontrou ID ou já processou este produto, aborta
    if (!$productId || in_array($productId, self::$_processedProducts)) {
        return;
    }
    self::$_processedProducts[] = $productId;

    // Agora obtém o stock item (caso ainda não tenha)
    $item = $observer->getEvent()->getItem();
    if (!$item || !$item instanceof Mage_CatalogInventory_Model_Stock_Item) {
        if (!$product) {
            $product = Mage::getModel('catalog/product')->load($productId);
        }
        if ($product && $product->getId()) {
            $item = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());
        }
    }

    // Se não encontrou o item de estoque, aborta
    if (!$item || !$item->getId()) {
        return;
    }

    $qty = (int) $item->getQty();

    if ($qty >= $threshold && !$item->getIsInStock()) {
        $item->setIsInStock(1);
        try {
            $item->getResource()->save($item);
            Mage::log('Product ID ' . $item->getProductId() . ' marked as In Stock (individual event)', null, 'stock_observer.log');
        } catch (Exception $e) {
            Mage::log('Error saving Stock Item: ' . $e->getMessage(), null, 'stock_observer.log');
        }
    } elseif ($qty < $threshold && $item->getIsInStock()) {
        $item->setIsInStock(0);
        try {
            $item->getResource()->save($item);
            Mage::log('Product ID ' . $item->getProductId() . ' marked as Out of Stock (individual event)', null, 'stock_observer.log');
        } catch (Exception $e) {
            Mage::log('Error saving Stock Item (out of stock): ' . $e->getMessage(), null, 'stock_observer.log');
        }
    }

    // Atualiza o produto configurável pai (se houver)
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
                $qty = (int) $item->getQty();

                if ($qty >= $threshold && !$item->getIsInStock()) {
                    $item->setIsInStock(1);
                    try {
                        $item->getResource()->save($item);
                        Mage::log('Product ID ' . $productId . ' marked as In Stock (mass update)', null, 'stock_observer.log');
                    } catch (Exception $e) {
                        Mage::log('Error saving Stock Item (mass update): ' . $e->getMessage(), null, 'stock_observer.log');
                    }
                } elseif ($qty < $threshold && $item->getIsInStock()) {
                    $item->setIsInStock(0);
                    try {
                        $item->getResource()->save($item);
                        Mage::log('Product ID ' . $productId . ' marked as Out of Stock (mass update)', null, 'stock_observer.log');
                    } catch (Exception $e) {
                        Mage::log('Error saving Stock Item (mass update - out of stock): ' . $e->getMessage(), null, 'stock_observer.log');
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
     * Otherwise, marks it as "Out of Stock"
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

            $parentStockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($parentId);
            if (!$parentStockItem || !$parentStockItem->getId()) {
                continue;
            }

            if ($hasInStockChild) {
                if (!$parentStockItem->getIsInStock()) {
                    $parentStockItem->setIsInStock(1);
                    try {
                        $parentStockItem->getResource()->save($parentStockItem);
                        Mage::log("Configurable Product ID {$parentId} marked as In Stock (at least one child in stock)", null, 'stock_observer.log');
                    } catch (Exception $e) {
                        Mage::log("Error saving Configurable Stock Item: " . $e->getMessage(), null, 'stock_observer.log');
                    }
                }
            } else {
                if ($parentStockItem->getIsInStock()) {
                    $parentStockItem->setIsInStock(0);
                    try {
                        $parentStockItem->getResource()->save($parentStockItem);
                        Mage::log("Configurable Product ID {$parentId} marked as Out of Stock (no child in stock)", null, 'stock_observer.log');
                    } catch (Exception $e) {
                        Mage::log("Error saving Configurable Stock Item (out of stock): " . $e->getMessage(), null, 'stock_observer.log');
                    }
                }
            }
        }
    }
}
