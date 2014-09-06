/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Varien
 * @package     js
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
if (typeof Product == 'undefined') {
    var Product = {};
}

/**************************** CONFIGURABLE PRODUCT **************************/

/**************************** CONFIGURABLE PRODUCT **************************/
Product.Config = Class.create();
Product.Config.prototype = {
    initialize: function(config){
        this.config     = config;
        this.taxConfig  = this.config.taxConfig;
        this.settings   = $$('.super-attribute-select');
        this.state      = new Hash();
        this.priceTemplate = new Template(this.config.template);
        this.prices     = config.prices;
        this.changeImageFlag = false;
        //console.log(this.settings)

        this.settings.each(function(element){
            Event.observe(element, 'change', this.configure.bind(this))
        }.bind(this));

        // fill state
        this.settings.each(function(element){
            var attributeId = element.id.replace(/[a-z]*/, '');
            if(attributeId && this.config.attributes[attributeId]) {
                element.config = this.config.attributes[attributeId];
                element.attributeId = attributeId;
                this.state[attributeId] = false;
            }
        }.bind(this))

        // Init settings dropdown
        var childSettings = [];
        for(var i=this.settings.length-1;i>=0;i--){
            var prevSetting = this.settings[i-1] ? this.settings[i-1] : false;
            var nextSetting = this.settings[i+1] ? this.settings[i+1] : false;
           /*
            if(i==0){
                this.fillSelect(this.settings[i])
            }
            else {
                this.settings[i].disabled=true;
            }
            */
            this.fillSelect(this.settings[i])
            $(this.settings[i]).childSettings = childSettings.clone();
            $(this.settings[i]).prevSetting   = prevSetting;
            $(this.settings[i]).nextSetting   = nextSetting;
            childSettings.push(this.settings[i]);
        }

        // try retireve options from url
        var separatorIndex = window.location.href.indexOf('#');
        if (separatorIndex!=-1) {
            var paramsStr = window.location.href.substr(separatorIndex+1);
            this.values = paramsStr.toQueryParams();
            this.settings.each(function(element){
                var attributeId = element.attributeId;
                element.value = this.values[attributeId];
                this.configureElement(element);
            }.bind(this));
        }
        $(document).observe('dom:loaded', function() {
            this.clickAllFirstRadios();
        }.bind(this));
    },

    configure: function(event){
        var element = Event.element(event);
        this.configureElement(element, false);
    },

    configureElement : function(element, recursive) {
        this.reloadOptionLabels(element);
        if(element.value){
           this.state[element.config.id] = element.value;
           //alert(element.config.options[element.value]);
           if (!recursive) {
        	   this.changeImageFlag = true;
               this.changeImage(element);
               //$('product-image').src = element.config.image;
           }
           if(element.parentNode.nextSetting){
               element.parentNode.nextSetting.disabled = false;
               this.fillSelect(element.parentNode.nextSetting);

               this.resetChildren(element.parentNode.nextSetting);
           }
        }
        else {
            this.resetChildren(element.parentNode);
        }
        this.reloadPrice();
       
//      Calculator.updatePrice();
    },
    
    changeImage: function(element) {
    	var key = '';
    	element =  $('attribute964');
    	for(var i=0;i<element.childNodes.length;i++){
    		if(element.childNodes[i].checked) {
    			key += element.childNodes[i].value;
    		} 
    	 }
    	
    	element2 =  $('attribute961');
    	for(var i=0;i<element2.childNodes.length;i++){
    		if(element2.childNodes[i].checked) {
    			key += '_' + element2.childNodes[i].value;
    		} 
    	 }
    	if (this.changeImageFlag) {
    		this.changeImageFlag = false;
    		$('product-image').src = this.config.images [key];
    	}
    	
    	
    },

    reloadOptionLabels: function(element){
        var selectedPrice;
        if(element.config){
            selectedPrice = parseFloat(element.config.price)
        }
        else{
            selectedPrice = 0;
        }
        for(var i=0;i<element.parentNode.childNodes.length;i++){
            if(element.parentNode.childNodes[i].config){
                element.parentNode.childNodes[i].text = this.getOptionLabel(element.parentNode.childNodes[i].config, element.parentNode.childNodes[i].config.price-selectedPrice);
            }
        }
    },

    resetChildren : function(element){
        /*
        if(element.childSettings) {
            for(var i=0;i<element.childSettings.length;i++){
                element.childSettings[i].selectedIndex = 0;


                //alert(element);
                element.childSettings[i].disabled = true;
                if(element.config){
                    this.state[element.config.id] = false;
                }
            }
        }
        */
    },

    fillSelect: function(element){

        var attributeId = element.id.replace(/[a-z]*/, '');

        var options = this.getAttributeOptions(attributeId);
        var clickMe = null;

        this.clearSelect(element);
        var prevConfig = false;

        if(element.prevSetting){

            for (c=0;c<element.prevSetting.childNodes.length-1;c++)
            {
                var selected = element.prevSetting.childNodes[c];
                //console.log(selected);
                if (selected.checked)
                {
                    var str = selected.id;
                    var str_array = str.split("_");
                    //console.log(str_array[2]);
                    if ((str_array[2] == 24) || (str_array[2] == 60) )
                    {
                        someName(str_array[2]);
                    }
                    prevConfig = selected;
                }
            }
        }

        if(options) {
            var index = 0;
            for(var i=0;i<options.length;i++){
                var allowedProducts = [];
                if(prevConfig) {
                    for(var j=0;j<options[i].products.length;j++){
                        if(prevConfig.config.allowedProducts
                            && prevConfig.config.allowedProducts.indexOf(options[i].products[j])>-1){
                            allowedProducts.push(options[i].products[j]);
                        }
                    }
                } else {
                    allowedProducts = options[i].products.clone();
                }

                if(allowedProducts.size()>0){
                    options[i].allowedProducts = allowedProducts;
                        var newElement = document.createElement("input");
                        var newElementId = element.getAttributeNode("name").value + "_" + options[i].id;
                        newElement.type = "radio";
                        newElement.value = options[i].id;
                        newElement.id = newElementId;
                        newElement.name = element.getAttributeNode("name").value;
                        newElement.config = options[i];
                        newElement.style.marginTop = "-4px";
                        newElement.className= "product_attribute";

                        Event.observe(newElement, 'click', this.configure.bind(this))

                        var labelElement = document.createElement("label");
                        labelElement.htmlFor = newElementId;

                        var labelText;
                        if(this.config.attributes[attributeId] && this.config.attributes[attributeId].code=='color' && options[i].code)
                        {
                            $colorCode = options[i].code;
                            alert(0);
                            $regExpr = /#[A-Za-z0-9]{6}/;
                            $hexCode = $regExpr.exec($colorCode);
                            if ($hexCode!=null)
                            {
                                var colorDivElement = document.createElement("div");
                                colorDivElement.className = "colorSelector" + " " + newElementId.toString();
                                colorDivElement.style.height = "30px";
                                colorDivElement.style.width = "30px";
                                labelElement.style.styleFloat = "left";
                                labelElement.style.cssFloat = "left";
                                labelElement.style.height = "30px";
                                labelElement.style.paddingLeft = "5px";
                                colorDivElement.style.backgroundColor = $hexCode.toString();
                                labelElement.appendChild(colorDivElement);
                                newElement.style.marginTop = "0";
                                newElement.style.styleFloat = "left";
                                newElement.style.cssFloat = "left";
                                newElement.style.height = "30px"

                                var heightElement = document.createElement("span");
                                heightElement.style.height = "30px";
                                heightElement.style.display = "inline-block";
                                element.appendChild(heightElement);
                            }
                            else
                            {
                                var colorLabel = document.createTextNode(" " + options[i].label);
                                labelElement.appendChild(colorLabel);
                            }

                            labelText = document.createTextNode(" " + this.getOptionLabelWithoutOptionName(options[i], options[i].price));
                        }
                        else
                        {
                            labelText = document.createTextNode(" " + this.getOptionLabel(options[i], options[i].price));
                        }

                        if (index==0)
                        {
                            clickMe = newElement;
                        }

                        labelElement.appendChild(labelText);

                        var breakElement = document.createElement("div");
                        breakElement.style.clear = "both";

                        element.appendChild(newElement);
                        element.appendChild(labelElement);
                        element.appendChild(breakElement);

                    index++;
                }
            }
        }
        if (clickMe) {
            //clickMe.click();
            // instead, do a recursive click
            clickMe.checked = true;
            this.configureElement(clickMe, true);
        }
    },

    clickAllFirstRadios: function() {
        for (i = 0; i < this.settings.length; i++) {
            if (this.settings[i].childNodes[0]) {
                //this.settings[i].childNodes[0].click()
                // instead, do a recursive click
                var el = this.settings[i].childNodes[0];
                el.checked = true;
                if (i == 0) {
                    this.configureElement(el, false);
                } else {
                    this.configureElement(el, true);
                }
            };
        }
    },

    getOptionLabel: function(option, price){
        var price = parseFloat(price);
        //var price = parseInt(price);
        if (this.taxConfig.includeTax) {
            var tax = price / (100 + this.taxConfig.defaultTax) * this.taxConfig.defaultTax;
            var excl = price - tax;
            var incl = excl*(1+(this.taxConfig.currentTax/100));
        } else {
            var tax = price * (this.taxConfig.currentTax / 100);
            var excl = price;
            var incl = excl + tax;
        }

        if (this.taxConfig.showIncludeTax || this.taxConfig.showBothPrices) {
            price = incl;
        } else {
            price = excl;
        }

        price = price.toFixed(0);

        var str = option.label;

        if(price != 0){
            if (this.taxConfig.showBothPrices) {
                str+= ' ' + this.formatPrice(excl, true) + ' (' + this.formatPrice(price, true) + ' ' + this.taxConfig.inclTaxTitle + ')';

            } else {
                //str+= ' ' + this.formatPrice(price, true);
                str+= ' ' + '(+ \u20ac '  + price + ')';
            }
        }
        return str;
    },

    getOptionLabelWithoutOptionName: function(option, price){
        var price = parseFloat(price);
        if (this.taxConfig.includeTax) {
            var tax = price / (100 + this.taxConfig.defaultTax) * this.taxConfig.defaultTax;
            var excl = price - tax;
            var incl = excl*(1+(this.taxConfig.currentTax/100));
        } else {
            var tax = price * (this.taxConfig.currentTax / 100);
            var excl = price;
            var incl = excl + tax;
        }

        if (this.taxConfig.showIncludeTax || this.taxConfig.showBothPrices) {
            price = incl;
        } else {
            price = excl;
        }

        var str = "";
        if(price){
            if (this.taxConfig.showBothPrices) {
                str+= ' ' + this.formatPrice(excl, true) + ' (' + this.formatPrice(price, true) + ' ' + this.taxConfig.inclTaxTitle + ')';
            } else {
                str+= ' ' + this.formatPrice(price, true);
            }
        }
        return str;
    },

    formatPrice: function(price, showSign){
        var str = '';
        price = parseFloat(price);
        if(showSign){
            if(price<0){
                str+= '-';
                price = -price;
            }
            else{
                str+= '+';
            }
        }

        var roundedPrice = (Math.round(price*100)/100).toString();

        if (this.prices && this.prices[roundedPrice]) {
            str+= this.prices[roundedPrice];
        }
        else {
            str+= this.priceTemplate.evaluate({price:price.toFixed(2)});
        }
        return str;
    },

    clearSelect: function(element){
        while ( element.childNodes.length >= 1 )
        {
            element.removeChild(element.firstChild);
        }
    },

    getAttributeOptions: function(attributeId){
        if(this.config.attributes[attributeId]){
            return this.config.attributes[attributeId].options;
        }
    },

    reloadPrice: function(){
        /*
        if (this.config.disablePriceReload) {
            return;
        }
        if ($('old-price-'+this.config.productId)) {

            var price = parseFloat(this.config.oldPrice);
            for(var i=this.settings.length-1;i>=0;i--){
                var selected = this.settings[i].options[this.settings[i].selectedIndex];
                if(selected.config){
                    price+= parseFloat(selected.config.price);
                }
            }
            if (price < 0)
                price = 0;
            price = this.formatPrice(price);

            if($('old-price-'+this.config.productId)){
                $('old-price-'+this.config.productId).innerHTML = price;
            }

        }
        */
    },

    reloadOldPrice: function(){
        /*
        if ($('old-price-'+this.config.productId)) {

            var price = parseFloat(this.config.oldPrice);
            for(var i=this.settings.length-1;i>=0;i--){
                var selected = this.settings[i].childNodes[this.settings[i].selectedIndex];
                if(selected.config){
                    price+= parseFloat(selected.config.price);
                }
            }
            if (price < 0)
                price = 0;
            price = this.formatPrice(price);

            if($('old-price-'+this.config.productId)){
                $('old-price-'+this.config.productId).innerHTML = price;
            }

        }
        */
    }
}
