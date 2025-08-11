<?php

class MageShop_UrlRedirect_Model_Observer
{
    public function redirectIfNeeded(Varien_Event_Observer $observer)
    {
        $isEnabled = Mage::getStoreConfig('urlredirect/general/enabled');
        if (!$isEnabled) {
            return;
        }

        $removePath = trim(Mage::getStoreConfig('urlredirect/general/remove_path'), '/');
        $redirectType = (int) Mage::getStoreConfig('urlredirect/general/redirect_type');

        if (!$removePath) {
            return;
        }

        $request = Mage::app()->getRequest();
        $currentPath = ltrim($request->getOriginalPathInfo(), '/');

        // Impede redirecionamento se for URL do admin
        $adminFrontName = (string) Mage::getConfig()->getNode('admin/routers/adminhtml/args/frontName');
        if (strpos($currentPath, $adminFrontName) === 0) {
            return;
        }

        // Remove todas as ocorrências da palavra configurada no path
        $pattern = '#(^|/)' . preg_quote($removePath, '#') . '(/|$)#i';
        $newPath = preg_replace($pattern, '/', $currentPath);
        $newPath = preg_replace('#/+#', '/', $newPath);
        $newPath = trim($newPath, '/');

        // Só redireciona se houver alteração no path
        if ($newPath !== $currentPath) {
            $url = Mage::getBaseUrl() . $newPath;

            if (!headers_sent()) {
                header("Location: " . $url, true, $redirectType == 301 ? 301 : 302);
                exit;
            }
        }
    }
}
