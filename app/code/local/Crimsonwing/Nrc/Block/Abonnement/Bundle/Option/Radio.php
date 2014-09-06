<?php

class Crimsonwing_Nrc_Block_Abonnement_Bundle_Option_Radio extends Mage_Bundle_Block_Catalog_Product_View_Type_Bundle_Option_Radio
{
    /**
     * Set template
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setTemplate('catalog/product/abonnement/view/type/bundle/option/radio.phtml');
    }
}