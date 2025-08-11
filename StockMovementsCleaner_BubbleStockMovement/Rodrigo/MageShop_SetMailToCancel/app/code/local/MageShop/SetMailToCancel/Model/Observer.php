<?php
class MageShop_SetMailToCancel_Model_Observer
{
    public function setMailToCancel(Varien_Event_Observer $observer)
    {
        // $eventName = $observer->getEvent()->getName();
        // Mage::log("Observer SetMailToCancel chamado pelo evento: " . $eventName, null, 'order_cancel.log', true);

        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getEvent()->getOrder();

        // Verifica se o pedido existe
        if (!$order || !$order->getId()) {
            Mage::log('Pedido não encontrado.', null, 'order_cancel.log', true);
            return;
        }

        // Verifica se o pedido está cancelado
        if ($order->getState() != Mage_Sales_Model_Order::STATE_CANCELED) {
            Mage::log('Pedido não está com estado "canceled". Ignorando...', null, 'order_cancel.log', true);
            return;
        }

        // Verifica se o envio de e-mail de cancelamento está habilitado
        if (!Mage::getStoreConfigFlag('sales_email/order_cancel/enabled', $order->getStoreId())) {
            Mage::log('Envio de e-mail de cancelamento desabilitado.', null, 'order_cancel.log', true);
            return;
        }

        // Verifica se o cliente já foi notificado com um e-mail de cancelamento
        $notified = false;
        foreach ($order->getAllStatusHistory() as $history) {
            if (
                $history->getIsCustomerNotified() &&
                $history->getStatus() == 'canceled' &&
                (
                    strpos($history->getComment(), 'E-mail de cancelamento enviado ao cliente') !== false ||
                    strpos($history->getComment(), 'Pedido cancelado automaticamente. E-mail de cancelamento enviado ao cliente') !== false ||
                    strpos($history->getComment(), 'Pedido cancelado de forma manual.E-mail de cancelamento enviado ao cliente') !== false
                )
            ) {
                $notified = true;
                Mage::log('Cliente já foi notificado anteriormente. Ignorando novo envio.', null, 'order_cancel.log', true);
                break;
            }
        }


        if ($notified) {
            Mage::log('Cliente já foi notificado anteriormente. Ignorando novo envio.', null, 'order_cancel.log', true);
            return;
        }

        try {
            $storeId = $order->getStoreId();

            // Template
            $templateId = Mage::getStoreConfig('sales_email/order_cancel/email_template', $storeId);
            if (!$templateId) {
                $templateId = 'sales_email_order_cancel_template';
            }

            // Remetente
            $sender = Mage::getStoreConfig('sales_email/order_cancel/email_identity', $storeId);

            // Área de design
            Mage::app()->getLayout()->setArea('frontend');

            // Cópias
            $copyTo = Mage::getStoreConfig('sales_email/order_cancel/copy_to', $storeId);
            $copyMethod = Mage::getStoreConfig('sales_email/order_cancel/copy_method', $storeId);
            $emailsCopia = array_filter(array_map('trim', explode(',', $copyTo)));

            // Template instance
            $emailTemplate = Mage::getModel('core/email_template');

            if ($copyMethod == 'bcc') {
                $emailTemplate->addBcc($emailsCopia);
            }

            // Envia o e-mail principal
            $emailTemplate->sendTransactional(
                $templateId,
                $sender,
                $order->getCustomerEmail(),
                $order->getCustomerName(),
                array('order' => $order),
                $storeId
            );

            // Envia e-mails separados, se for 'copy'
            if ($copyMethod == 'copy') {
                foreach ($emailsCopia as $email) {
                    $emailTemplate->sendTransactional(
                        $templateId,
                        $sender,
                        $email,
                        null, // nome opcional
                        array('order' => $order),
                        $storeId
                    );
                }
            }

            if ($order->getData('cancelado_por_autocancel')) {
                $mensagem = 'Pedido cancelado automaticamente. E-mail de cancelamento enviado ao cliente.';
            } else {
                $mensagem = 'Pedido cancelado de forma manual. E-mail de cancelamento enviado ao cliente.';
            }

            $order->addStatusHistoryComment($mensagem)
                ->setIsCustomerNotified(true);
            $order->setCustomerNoteNotify(true);
            $order->save();

            Mage::log('E-mail de cancelamento enviado para ' . $order->getCustomerEmail(), null, 'order_cancel.log', true);
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}
