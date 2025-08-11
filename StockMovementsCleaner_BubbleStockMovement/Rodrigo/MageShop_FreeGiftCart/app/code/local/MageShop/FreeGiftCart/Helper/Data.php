<?php
class MageShop_FreeGiftCart_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Processa os brindes no carrinho
     */
    public function processarBrindes($quote)
    {
        $cart = Mage::getSingleton('checkout/cart');
        $checkoutSession = Mage::getSingleton('checkout/session');
        $coreSession = Mage::getSingleton('core/session');

        // Lista de itens do carrinho
        $items = $quote->getAllVisibleItems();

        // foreach ($quote->getAllVisibleItems() as $item) {
        //     Mage::log("Item no carrinho SKU: {$item->getSku()}, is_freegift: " . $item->getData('is_freegift'), null, 'freegiftcart.log', true);
        // }


        // Remove itens que eram brindes mas deixaram de ser
        foreach ($quote->getAllVisibleItems() as $item) {
            if ((int)$item->getIsFreegift() == 1) {
                $produto = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
                // Mage::log("Checando brinde no carrinho: SKU {$produto->getSku()} | produto_eh_brinde: " . $produto->getData('produto_eh_brinde'), null, 'freegiftcart_debug.log', true);

                if ($produto->getData('produto_eh_brinde') != 1) {
                    // Mage::log("Removendo {$produto->getSku()} do carrinho: deixou de ser brinde.", null, 'freegiftcart_debug.log', true);
                    try {
                        $cart->removeItem($item->getId());
                        $quote->setTotalsCollectedFlag(false);
                        $quote->collectTotals();
                        $cart->save();
                        $quote->save();
                    } catch (Exception $e) {
                        Mage::log("Erro ao remover item do carrinho: " . $e->getMessage(), null, 'freegiftcart_debug.log', true);
                    }
                }
            }
        }

        // Separa SKUs principais e SKUs de brinde
        $skusPrincipais = [];
        $skusBrindesNoCarrinho = [];
        $skusBrindes = [];        

        foreach ($items as $item) {
            $produto = $this->getProdutoSimples($item);

            // Garante que tem o atributo "produto_eh_brinde"
            if ($produto->getData('produto_eh_brinde') === null) {
                $produto = Mage::getModel('catalog/product')->load($produto->getId());
            }

            if ($this->isBrinde($produto)) {
                $skusBrindesNoCarrinho[$produto->getSku()] = $item;
                $skusBrindes[] = $produto->getSku();
            } else {
                $skusPrincipais[] = $produto->getSku();
            }
        }

        // Remove duplicados (produto que é brinde e principal ao mesmo tempo)
        $skusPrincipais = array_filter($skusPrincipais, function ($sku) use ($skusBrindes) {
            return !in_array($sku, $skusBrindes);
        });

        // Sessões de mensagens
        $mensagensBrindesAdicionados = (array)$checkoutSession->getBrindesMensagensAdicionados();
        $brindesRemovidosNotificados = (array)$coreSession->getBrindesRemovidosNotificados();

        // Busca todos os produtos configurados como brinde
        $produtosBrinde = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToFilter('produto_eh_brinde', ['eq' => 1])
            ->addAttributeToSelect(['sku', 'brindes_para_skus', 'name']);

        foreach ($produtosBrinde as $produtoBrindeOriginal) {
            $produtoBrinde = Mage::getModel('catalog/product')->load($produtoBrindeOriginal->getId());
            $skuBrinde = $produtoBrinde->getSku();

            // SKUs permitidos para receber este brinde
            $permitidos = array_map('trim', explode(',', $produtoBrinde->getData('brindes_para_skus')));

            // Verifica se algum produto do carrinho dá direito a esse brinde
            $temProdutoElegivel = $this->temProdutoElegivel($skusPrincipais, $permitidos);

            // Verifica se brinde está no carrinho
            $brindeNoCarrinho = ($quote->getItemByProduct($produtoBrinde) !== false);

            // Verifica estoque do brinde
            $temEstoque = $this->produtoTemEstoque($produtoBrinde);

            if ($temEstoque && $temProdutoElegivel && !$brindeNoCarrinho) {
                // Adicionar brinde
                $this->adicionarBrinde($cart, $quote, $produtoBrinde, $checkoutSession, $skuBrinde, $mensagensBrindesAdicionados, $brindesRemovidosNotificados);
            }

            if (!$temProdutoElegivel && $brindeNoCarrinho) {
                // Remover brinde
                $this->removerBrinde($cart, $quote, $produtoBrinde, $coreSession, $skuBrinde, $mensagensBrindesAdicionados, $brindesRemovidosNotificados);
            }
        }
        
        // Atualiza sessões
        $checkoutSession->setBrindesMensagensAdicionados($mensagensBrindesAdicionados);
        $coreSession->setBrindesRemovidosNotificados($brindesRemovidosNotificados);
        
        // Garante que brindes sempre tenham quantidade 1
        foreach ($quote->getAllItems() as $item) {
            $produto = $this->getProdutoSimples($item);
            if ($this->isBrinde($produto)) {
                if ($item->getQty() != 1) {
                    $item->setQty(1);
                    $item->save();
                }
            }
        }
    }

    /**
     * Retorna produto simples (se for configurável)
     */
    public function getProdutoSimples($item)
    {
        $produto = $item->getProduct();
        if ($produto->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $options = $produto->getTypeInstance(true)->getOrderOptions($produto);
            if (isset($options['simple_sku'])) {
                $simple = Mage::getModel('catalog/product')->loadByAttribute('sku', $options['simple_sku']);
                if ($simple) return $simple;
            }
        }
        return $produto;
    }

    /**
     * Verifica se é brinde
     */
    public function isBrinde($produto)
    {
        return ($produto->getData('produto_eh_brinde') == 1 || $produto->getData('produto_eh_brinde') === '1');
    }

    /**
     * Verifica se há produto elegível no carrinho
     */
    public function temProdutoElegivel($skusNoCarrinho, $permitidos)
    {
        return count(array_intersect($skusNoCarrinho, $permitidos)) > 0;
    }

    /**
     * Verifica se produto tem estoque
     */
    public function produtoTemEstoque($produto)
    {
        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($produto);
        return $stock && $stock->getIsInStock() && $stock->getQty() > 0;
    }

    /**
     * Adiciona brinde ao carrinho
     */
    public function adicionarBrinde($cart, $quote, $produto, $session, $sku, &$mensagensAdicionados, &$removidosNotificados)
    {
        try {
            $cart->addProduct($produto, ['qty' => 1]);
            $quote->setTotalsCollectedFlag(false);
            $cart->save();

            // Busca o item adicionado pelo SKU e marca como brinde
            $lastItem = $quote->getItemByProduct($produto);
            if ($lastItem) {
                $lastItem->setIsFreegift(1); // usa o setter da coluna real
                $lastItem->save(); // salva o item individualmente
                // Mage::log("Marcado {$produto->getSku()} como is_freegift=1 no banco", null, 'freegiftcart.log', true);
            }


            if (!in_array($sku, $mensagensAdicionados)) {
                $session->addSuccess("Brinde \"{$produto->getName()}\" adicionado ao carrinho!");
                if (($key = array_search($sku, $removidosNotificados)) !== false) unset($removidosNotificados[$key]);
                $mensagensAdicionados[] = $sku;
            }
        } catch (Exception $e) {
            Mage::log("Erro ao adicionar brinde {$sku}: " . $e->getMessage(), null, 'freegiftcart.log', true);
        }
    }

    /**
     * Remove brinde do carrinho
     */
    public function removerBrinde($cart, $quote, $produto, $session, $sku, &$mensagensAdicionados, &$removidosNotificados)
    {
        try {
            $cart->removeItem($quote->getItemByProduct($produto)->getId());
            $quote->setTotalsCollectedFlag(false);
            $cart->save();
            if (!in_array($sku, $removidosNotificados)) {
                $session->addNotice("Brinde \"{$produto->getName()}\" foi removido do carrinho porque não há produtos que dão direito a ele.");
                $brindesRemovidosNotificados[] = $sku;
            }
            if (($key = array_search($sku, $mensagensAdicionados)) !== false) unset($mensagensAdicionados[$key]);
        } catch (Exception $e) {
            Mage::log("Erro ao remover brinde {$sku}: " . $e->getMessage(), null, 'freegiftcart.log', true);
        }
    }
}
