<?php

class MageShop_StockMovementsCleaner_Model_Cron
{
    public function cleanOldRecords()
    {
        Mage::log("StockMovementsCleaner: Iniciando execução do cron", null, 'stockmovementscleaner.log');

        $enabled = Mage::getStoreConfig('cataloginventory/item_options/stockcleaner_enabled');
        $days = (int) Mage::getStoreConfig('cataloginventory/item_options/stockcleaner_days');

        Mage::log("StockMovementsCleaner: Configuração - Ativado: $enabled | Dias: $days", null, 'stockmovementscleaner.log');

        if (!$enabled || $days <= 0) {
            Mage::log("StockMovementsCleaner: Cron desativado ou número de dias inválido", null, 'stockmovementscleaner.log');
            return;
        }

        try {
            $resource = Mage::getSingleton('core/resource');
            $connection = $resource->getConnection('core_write');
            $table = $resource->getTableName('bubble_stock_movement');
            $dateLimit = date('Y-m-d H:i:s', strtotime("-{$days} days"));

            Mage::log("StockMovementsCleaner: Executando DELETE em registros com created_at < $dateLimit", null, 'stockmovementscleaner.log');

            // Logar um SELECT antes para ver se tem registros mesmo
            $selectSql = "SELECT COUNT(*) FROM $table WHERE created_at < :dateLimit";
            $count = $connection->fetchOne($selectSql, ['dateLimit' => $dateLimit]);
            Mage::log("StockMovementsCleaner: Registros antigos encontrados: $count", null, 'stockmovementscleaner.log');

            if ($count > 0) {
                $where = $connection->quoteInto('created_at < ?', $dateLimit);
                $deleted = $connection->delete($table, $where);
                Mage::log("StockMovementsCleaner: Registros deletados: $deleted", null, 'stockmovementscleaner.log');
            } else {
                Mage::log("StockMovementsCleaner: Nenhum registro a ser deletado", null, 'stockmovementscleaner.log');
            }

        } catch (Exception $e) {
            Mage::log("StockMovementsCleaner: Erro ao executar cron", null, 'stockmovementscleaner.log');
            Mage::logException($e);
        }
    }
}
