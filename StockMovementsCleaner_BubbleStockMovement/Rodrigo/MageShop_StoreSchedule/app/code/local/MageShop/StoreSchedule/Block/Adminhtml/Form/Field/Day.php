<?php
class MageShop_StoreSchedule_Block_Adminhtml_Form_Field_Day extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    // Sobrescreve o método que gera o HTML do campo customizado no formulário de configuração
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        // Pega o valor atual armazenado no campo (JSON com horários)
        $value = $element->getValue();
        // Verifica se o valor não é um array (pode ser string JSON)
        if (!is_array($value)) {
            // Tenta decodificar o JSON para array associativo
            $value = json_decode($value, true);
            // Se mesmo assim não for array, inicializa com valores padrão vazios
            if (!is_array($value)) {
                $value = [
                    'abre1' => '',
                    'fecha1' => '',
                    'abre2' => '',
                    'fecha2' => '',
                ];
            }
        }

        // Obtém o nome HTML do campo, que será usado como prefixo para os inputs (ex: storeschedule[schedule][monday])
        $htmlName = $element->getName();

        $html  = '<div style="padding:10px; margin-bottom:10px; border-bottom:1px solid #ccc;">';

        $html .= '<label><strong>Loja:</strong></label> ';
        $html .= '<input type="time" style="width:70px" name="' . $htmlName . '[abre1]" value="' . htmlspecialchars($value['abre1']) . '" /> até ';
        $html .= '<input type="time" style="width:70px" name="' . $htmlName . '[fecha1]" value="' . htmlspecialchars($value['fecha1']) . '" /><br/><br/>';

        $html .= '<label><strong>Delivery:</strong></label> ';
        $html .= '<input type="time" style="width:70px" name="' . $htmlName . '[abre2]" value="' . htmlspecialchars($value['abre2']) . '" /> até ';
        $html .= '<input type="time" style="width:70px" name="' . $htmlName . '[fecha2]" value="' . htmlspecialchars($value['fecha2']) . '" />';

        $html .= '</div>';

        // Retorna o HTML completo para o Magento renderizar no formulário de configuração
        return $html;
    }
}
