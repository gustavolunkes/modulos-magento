<?php
class MageShop_HubBling_Model_Cron
{
	/**
	 * Fn responsável por atualizar a tabela de sicronização
	 * @return bool
	 */
	public function getProductsBase()
	{
		$collectionSync = Mage::getModel('hubbling/syncProduct')->getCollection()
			->addFieldToSelect('sku');

		$skusSync = $collectionSync->count() === 0 ? [] : $collectionSync->getColumnValues('sku');
		// Buscar todos os produtos simples do Magento
		$collection = Mage::getResourceModel('catalog/product_collection')
			->addAttributeToSelect('sku')
			->addAttributeToFilter('type_id', array('eq' => 'simple'));

		if ($collection->count() === 0) {
			return false;
		}

		$skusBase = $collection->getColumnValues('sku');

		foreach ($skusBase as $sku) {
			if (in_array($sku, $skusSync)) {
				continue;
			}
			$newSku = Mage::getModel('hubbling/syncProduct');
			$newSku->setData([
				'sku' => $sku,
				'api_id'	=> '',
				'exist_bling' => 0,
				'sync' => 0,
				'qty_inventory' => 0,
				'created_at' => now(),
				'updated_at' => now()
			]);
			$newSku->save();
		}

		return true;

	}

	/**
	 * Fn Responsável por verifica se o produto da base existe na API
	 * @return bool
	 */
	public function getProductsApi()
	{
		if (!$this->getHelper()->getConfigData('active') || !$this->getHelper()->getConfigData('token')) {
			return false;
		}

		$collectionSync = Mage::getModel('hubbling/syncProduct')->getCollection()
			->addFieldToSelect('sku')
			->addFieldToFilter('exist_bling', 0)
			->setPageSize(100); // Limita a 100 itens

		$skusSync = $collectionSync->getColumnValues('sku');

		if (count($skusSync) == 0) {
			return false;
		}

		$webserver = $this->getHelper()->get('Api/v3/produtos', ['codigos' => $skusSync]);
		if ($webserver->success()) {
			$data = $webserver->toArray();
			if (isset($data['data']) && count($data['data']) > 0) {
				// Percorre os dados retornados pela API
				foreach ($data['data'] as $row) {
					// Verifica se o SKU retornado está presente na coleção local
					if (in_array($row['codigo'], $skusSync)) {
						// Atualiza a coluna exist_bling para true
						$productSync = Mage::getModel('hubbling/syncProduct')
							->load($row['codigo'], 'sku'); // Carrega o produto pela SKU

						if ($productSync->getId()) { // Verifica se o produto foi encontrado
							$productSync->setApiId($row['id']); // Id produto produto API
							$productSync->setExistBling(1); // Define exist_bling como 1 (true)
							$productSync->setUpdatedAt(now());
							$productSync->save(); // Salva a alteração
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * Fn responsável por pega o estoque na API
	 * @return bool|int
	 */
	public function getStockProducts()
	{
		if (!$this->getHelper()->getConfigData('active') || !$this->getHelper()->getConfigData('token')) {
			return false;
		}

		$collectionSync = Mage::getModel('hubbling/syncProduct')->getCollection()
			->addFieldToSelect('api_id')
			->addFieldToFilter('exist_bling', 1); // Limita a 100 itens

		$localSync = [];
		foreach ($collectionSync as $sync) {
			if ($sync->getApiId()) {
				$localSync[$sync->getApiId()] = $sync;
			}
		}

		if ($localSync == []) {
			return 0;
		}

		$productsIds = $collectionSync->getColumnValues('api_id');

		if (count($productsIds) == 0) {
			return false;
		}

		$webserver = $this->getHelper()->get('Api/v3/estoques/saldos', ['idsProdutos' => $productsIds]);
		if ($webserver->success()) {
			$data = $webserver->toArray();
			if (isset($data['data']) && count($data['data']) > 0) {
				// Percorre os dados retornados pela API
				foreach ($data['data'] as $row) {
					if (isset($localSync[$row['produto']['id']])) {
						$current = $localSync[$row['produto']['id']];
						// Certifique-se de que a SKU seja a chave primária ou que o modelo seja carregado corretamente
						$current->load($row['produto']['id'], 'api_id'); // Carregar pelo campo 'api_id'
						
						if (isset($row['depositos'])) {
							//if ($current->getId() && (int) $current->getQtyInventory() !== (int) $row['saldoVirtualTotal']) {
								$current->setDeposito(json_encode($row['depositos']));
								$current->setSync(1);
								$current->setUpdatedAt(now());
								$current->save();
							//}
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * Fn responsável por atualizar o estoque no magento
	 * @return bool
	 */
	public function updateStockProducts()
	{
		if (!$this->getHelper()->getConfigData('active') || !$this->getHelper()->getConfigData('token')) {
			return false;
		}

		// Buscar produtos que precisam ser sincronizados (sync = 1)
		$collectionSync = Mage::getModel('hubbling/syncProduct')->getCollection()
			->addFieldToSelect(['api_id', 'sku', 'qty_inventory'])
			->addFieldToFilter('sync', 1);

		if ($collectionSync->count() == 0) {
			return false; // Nenhum produto para sincronizar
		}

		foreach ($collectionSync as $syncItem) {
			$sku = $syncItem->getSku();
			//$qty = $syncItem->getQtyInventory();
			$depositos = json_decode($syncItem->getDeposito(), true);

			try {
				// Carregar o produto pelo SKU
				$product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);

				if ($product && $product->getId()) {
					
					foreach ($depositos as $deposito) {
						if ($deposito['id'] == '15412412454') {
							$product->setDeposito1($deposito['saldoVirtual']);
						}

						if ($deposito['id'] == '15412412454') {
							$product->setDeposito2($deposito['saldoVirtual']);
						}

						if ($deposito['id'] == '15412412454') {
							$product->setDeposito3($deposito['saldoVirtual']);
						}
					}

					$product->save();

					// // Atualizar estoque
					// $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
					// if ($stockItem->getId()) {
					// 	$stockItem->setData('qty', $qty);
					// 	$stockItem->setData('is_in_stock', ($qty > 0) ? 1 : 0); // Define disponibilidade
					// 	$stockItem->save();

					// 	// Marcar como sincronizado
					// 	$syncItem->setSync(0);
					// 	$syncItem->setUpdatedAt(now());
					// 	$syncItem->save();
					//}
				}
			} catch (Exception $e) {
				Mage::log("Erro ao atualizar estoque para SKU {$sku}: " . $e->getMessage(), null, 'sync_stock.log');
			}
		}

		return true;
	}

	private function getHelper()
	{
		return Mage::helper('hubbling');
	}
}