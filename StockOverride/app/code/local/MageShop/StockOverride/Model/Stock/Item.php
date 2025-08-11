<?php

// Define a classe personalizada que estende a classe original de controle de estoque do Magento
class MageShop_StockOverride_Model_Stock_Item extends Mage_CatalogInventory_Model_Stock_Item
{
    // Sobrescreve o método _beforeSave(), que é chamado automaticamente antes de salvar o item de estoque
    protected function _beforeSave()
    {
        // Chama o método _beforeSave() da classe pai para garantir que o comportamento padrão seja mantido
        parent::_beforeSave();

        // Obtém o valor da configuração personalizada definida no painel do Magento em:
        // Sistema > Configuração > Catálogo > Inventário > Opções do item > auto_instock_threshold
        $threshold = Mage::getStoreConfig('cataloginventory/item_options/auto_instock_threshold');

        if (is_numeric($threshold) && $this->getQty() >= $threshold) {         // Verifica se o valor da configuração é numérico e se a quantidade do produto é maior ou igual ao limite
            $this->setIsInStock(1);                                            // Define o produto como "em estoque" (is_in_stock = 1)
        }

        return $this;                                                          // Retorna o objeto atual para encadeamento de métodos, se necessário
    }
}
