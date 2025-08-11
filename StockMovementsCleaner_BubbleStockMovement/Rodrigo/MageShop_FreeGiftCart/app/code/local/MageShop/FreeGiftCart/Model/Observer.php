<?php
class MageShop_FreeGiftCart_Model_Observer
{
    public function verificaBrindes(Varien_Event_Observer $observer)
    {
        // Verifica se módulo está ativo
        if (!Mage::getStoreConfig('catalog/freegiftcart/freegiftcart_active')) {
            return;
        }

        // Garante que só roda uma vez por request
        if (Mage::registry('verifica_brindes_executado')) {
            return;
        }
        Mage::register('verifica_brindes_executado', true);

        // Tenta obter o quote
        $quote = $observer->getEvent()->getQuote() ?: Mage::getSingleton('checkout/session')->getQuote();
        if (!$quote || !$quote->getId()) {
            Mage::log("Quote não disponível. Abortando observer.", null, 'freegiftcart.log', true);
            return;
        }

        // Chama o helper para processar
        Mage::helper('mageshop_freegiftcarthelper')->processarBrindes($quote);
    }

    public function fixBrindeQty(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Quote_Item $item */
        $item = $observer->getItem(); // Item que teve a QTD alterada
        $product = Mage::helper('mageshop_freegiftcarthelper')->getProdutoSimples($item);

        // Força carregar o produto completo para garantir que atributos customizados estão presentes
        if ($product && $product->getId()) {
            $product = Mage::getModel('catalog/product')->load($product->getId());
        }


        // Mage::log('Verificando SKU: ' . $item->getSku() . ' | atributo produto_eh_brinde: ' . $product->getData('produto_eh_brinde'), null, 'freegiftcart_debug.log', true);

        if ($product && $product->getData('produto_eh_brinde') == 1) {
            if ($item->getQty() != 1) {
                $item->setQty(1); 
                $item->save(); // <--- Garante que o item seja salvo
                $item->getQuote()->setTotalsCollectedFlag(false)->collectTotals()->save(); // <--- Recalcula o carrinho
            }
        }
    }


}
