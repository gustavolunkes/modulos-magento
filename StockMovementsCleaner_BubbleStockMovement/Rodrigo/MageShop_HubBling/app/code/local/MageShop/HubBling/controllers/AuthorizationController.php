<?php
class MageShop_HubBling_AuthorizationController extends Mage_Core_Controller_Front_Action
{
	public function indexAction()
	{
		try {
			$params = $this->getRequest()->getParams();

			if (!isset($params['code'])) {
				throw new \Exception("Code Not Found", 1);
			}

			$helper = Mage::helper('hubbling');
			$authorization_code = $params['code'];

			$helper->newToken('Api/v3/oauth/token', [
				"grant_type" => "authorization_code",
				"code" => $authorization_code
			]);

			// Adiciona mensagem de sucesso
            Mage::getSingleton('adminhtml/session')->addSuccess("Autorização concluída com sucesso!");
			$adminUrl = Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit", array('section' => 'hubbling', '_secure' => true));
			return Mage::app()->getResponse()->setRedirect($adminUrl);
		} catch (\Exception $e) {
			return $this->_forward('404');
		}
	}
}
