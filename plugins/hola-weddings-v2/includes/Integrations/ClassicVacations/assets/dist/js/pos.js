"use strict";

/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/
(function () {
  // webpackBootstrap
  /******/
  "use strict";

  /******/
  var __webpack_modules__ = {
    /***/"./assets/src/js/pos.js": (
    /*!******************************!*\
      !*** ./assets/src/js/pos.js ***!
      \******************************/
    /***/
    function assetsSrcJsPosJs(__unused_webpack_module, __webpack_exports__, __webpack_require__) {
      eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _pos_pos_class__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./pos/pos.class */ \"./assets/src/js/pos/pos.class.js\");\n\r\n\r\njQuery.validator.setDefaults({\r\n    debug: true,\r\n    errorElement: 'span',\r\n    errorPlacement: function (error, element) {\r\n        error.addClass('invalid-feedback');\r\n        element.closest('.form-group').append(error);\r\n    },\r\n    highlight: function (element, errorClass, validClass) {\r\n        jQuery(element).addClass('is-invalid');\r\n    },\r\n    unhighlight: function (element, errorClass, validClass) {\r\n        jQuery(element).removeClass('is-invalid');\r\n    }\r\n});\r\n\r\nNoty.overrideDefaults({\r\n    timeout: 3000\r\n});\r\n\r\njQuery(function(){\r\n\r\n    var pos = new _pos_pos_class__WEBPACK_IMPORTED_MODULE_0__[\"default\"]();\r\n\r\n    jQuery.validator.addMethod(\"custom_amount\", function(value, element) {\r\n        \r\n            return this.optional(element) || ( value > 0 && value <= pos.getInvoice().due_amount);\r\n        }, \r\n        \"Please specify a valid amount.\"\r\n    );\r\n\r\n    pos.validator = jQuery(\"#cv-pos-form\").validate({\r\n        ignore: '*:not([name])',\r\n        rules: {\r\n            \"invoice_id\": \"required\",\r\n            \"custom_amount\": {\r\n                required: {\r\n                    depends: function() {\r\n                        return jQuery('#pos-amount-custom').is(':checked')\r\n                    }\r\n                },\r\n                custom_amount : true\r\n            },\r\n            \"billing[first_name]\": \"required\",\r\n            \"billing[last_name]\": \"required\",\r\n            \"billing[email]\" : {\r\n                required: true,\r\n                email: true\r\n            },\r\n            \"billing[address1]\": \"required\",\r\n            \"billing[administrative_area]\": \"required\",\r\n            \"billing[country]\": \"required\",\r\n            \"billing[locality]\": \"required\",\r\n            \"billing[postal_code]\": \"required\",\r\n            \"card[type]\": {\r\n                required: {\r\n                    depends: function() {\r\n                        return pos.useSavedCard();\r\n                    }\r\n                }\r\n            },\r\n            \"card[holder]\": {\r\n                required: {\r\n                    depends: function() {\r\n                        return pos.useSavedCard();\r\n                    }\r\n                }\r\n            },\r\n            \"card[number]\": {\r\n                required: {\r\n                    depends: function() {\r\n                        return pos.useSavedCard();\r\n                    }\r\n                },\r\n                minlength: 16\r\n            },\r\n            \"card[month]\": {\r\n                required: {\r\n                    depends: function() {\r\n                        return pos.useSavedCard();\r\n                    }\r\n                },\r\n                minlength: 2,\r\n                maxlength: 2,\r\n                max: 12,\r\n                digits: true\r\n            },\r\n            \"card[year]\": {\r\n                required: {\r\n                    depends: function() {\r\n                        return pos.useSavedCard();\r\n                    }\r\n                },\r\n                minlength: 4,\r\n                maxlength: 4,\r\n                digits: true,\r\n                min: 2024\r\n            },\r\n            \"card[ccv]\": {\r\n                required: {\r\n                    depends: function() {\r\n                        return pos.useSavedCard();\r\n                    }\r\n                },\r\n                minlength: 3,\r\n                maxlength: 4,\r\n                digits: true\r\n            }\r\n        },\r\n        /*messages: {\r\n            \"room_id\": messages.room_id,\r\n            \"customer[first_name]\": messages.customer_first_name,\r\n            \"customer[last_name]\": messages.customer_last_name,\r\n            \"customer[phone_number]\": messages.customer_phone_number,\r\n            \"customer[email]\" : {\r\n                required: messages.customer_email.required,\r\n                email: messages.customer_email.email\r\n            },\r\n            \"customer[state]\": messages.customer_state,\r\n            \"customer[country]\": messages.customer_country,\r\n            \"terms_and_conditions\" : messages.terms_and_conditions,\r\n            \"payment[card-type]\" : messages.payment_card_type,\r\n            \"payment[card-owner]\" : messages.payment_card_owner,\r\n            \"payment[card-number]\": {\r\n                required: messages.payment_card_number.required,\r\n                minlength: messages.payment_card_number.minlenght,\r\n            },\r\n            \"payment[card-month]\": {\r\n                required: messages.payment_card_month.required,\r\n                minlength: messages.payment_card_month.minlength,\r\n                maxlength: messages.payment_card_month.maxlength,\r\n                digits: messages.payment_card_month.digits\r\n            },\r\n            \"payment[card-year]\": {\r\n                required: messages.payment_card_year.required,\r\n                minlength: messages.payment_card_year.minlength,\r\n                maxlength: messages.payment_card_year.maxlength,\r\n                digits: messages.payment_card_year.digits\r\n            },\r\n            \"payment[card-ccv]\": {\r\n                required: messages.payment_card_ccv.required,\r\n                minlength: messages.payment_card_ccv.minlength,\r\n                maxlength: messages.payment_card_ccv.maxlength,\r\n                digits: messages.payment_card_ccv.digits\r\n            }\r\n        }*/\r\n    });\r\n})\n\n//# sourceURL=webpack://hola-weddings-classic-vacations/./assets/src/js/pos.js?");

      /***/
    }),
    /***/"./assets/src/js/pos/pos.class.js": (
    /*!****************************************!*\
      !*** ./assets/src/js/pos/pos.class.js ***!
      \****************************************/
    /***/
    function assetsSrcJsPosPosClassJs(__unused_webpack_module, __webpack_exports__, __webpack_require__) {
      eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\nclass POS{\r\n    constructor(){\r\n\r\n        this._invoice = null;\r\n        this.el = jQuery('#cv-pos');\r\n        this.form = this.el.find('form');\r\n        this.quote_field = this.el.find('#pos-quote-field');\r\n        this.invoice_field = this.el.find('#pos-invoice-field');\r\n        this.billing_form = this.el.find('#billing-form');\r\n        this.card_form = this.el.find('#card-form');\r\n        this.submitBtn = this.el.find('#submit');\r\n        this.billing_information = null;\r\n\r\n        this.bindEvents();\r\n    }\r\n\r\n    bindEvents(){\r\n\r\n        var _self = this;\r\n\r\n        this.quote_field.on('change', () => { \r\n            _self.loadInvoices() \r\n        } );\r\n\r\n        this.invoice_field.on('change', () => { \r\n            _self.loadInvoice() \r\n        });\r\n\r\n        jQuery(document).on('change', 'input[name=\"amount\"]', function(){\r\n        \r\n            if(this.value === 'custom'){\r\n                jQuery('#pos-payment-custom-amount').show();\r\n            }else{\r\n                jQuery('#pos-payment-custom-amount').hide();\r\n            }\r\n        });\r\n\r\n        jQuery(document).on( 'click', '#clear-card-fields', () => _self.clearCardForm());\r\n    \r\n        jQuery(document).on( 'click', '#clear-billing-fields', () => _self.clearBillingForm());\r\n    \r\n        jQuery(document).on( 'change', 'input[name=\"use_saved_card\"]', function(){\r\n\r\n            if( parseInt(jQuery(this).val()) !== 1 ){\r\n\r\n                _self.card_form.children('.fields').show();\r\n            }else{\r\n\r\n                _self.card_form.children('.fields').hide();\r\n            }\r\n        });\r\n\r\n        jQuery(document).on( 'click', '#pos-load-billing-data', () => _self.loadBillingData() );\r\n\r\n        // submit\r\n        this.submitBtn.on( 'click', () => _self.processPayment() );\r\n    }\r\n\r\n    /**\r\n     * process the payment \r\n     * \r\n     * @returns void\r\n     */\r\n    processPayment(){\r\n\r\n        var _self = this;\r\n\r\n        if(!this.validator.form()){\r\n            return;\r\n        }\r\n\r\n        jQuery.ajax({\r\n            url: ajaxurl,\r\n            dataType : 'json',\r\n            method: 'POST',\r\n            beforeSend: function(){\r\n                _self.block_form();\r\n            },\r\n            data: _self.form.serialize()\r\n        }).done(function(response){\r\n\r\n            if(response.success){\r\n                \r\n                _self.loadInvoice();\r\n            }else{\r\n\r\n                new Noty({\r\n                    text: response.message,\r\n                    type: 'error'\r\n                }).show();\r\n            }\r\n        }).fail(function(){\r\n\r\n            new Noty({\r\n                text: 'An error occurred while processing the payment',\r\n                type: 'error'\r\n            }).show();\r\n\r\n        }).always(function(){\r\n\r\n            _self.unblock_form();\r\n        });\r\n    }\r\n\r\n    /**\r\n     * load invoice data\r\n     * \r\n     * @returns void\r\n     */\r\n    loadInvoice(){\r\n\r\n        var _self = this,\r\n            value = this.invoice_field.val();\r\n \r\n        if(value === undefined){\r\n            alert('Select an invoice');\r\n            return;\r\n        }\r\n\r\n        this.billing_form.hide();\r\n        this.card_form.hide();\r\n\r\n        jQuery.ajax({\r\n            url: ajaxurl,\r\n            dataType : 'json',\r\n            method: 'GET',\r\n            beforeSend: function(){\r\n                _self.block_form();\r\n            },\r\n            data: {\r\n                action: 'hw_cv_pos_get_invoice_checkout',\r\n                invoice_id: value\r\n            }\r\n        }).done(function(response){\r\n\r\n            if(response.success){\r\n                \r\n                _self.processInvoiceResponse(response.data);\r\n            }else{\r\n\r\n                new Noty({\r\n                    text: response.message,\r\n                    type: 'error'\r\n                }).show();\r\n            }\r\n        }).fail(function(){\r\n\r\n            new Noty({\r\n                text: 'An error occurred with the request',\r\n                type: 'error'\r\n            }).show();\r\n\r\n        }).always(function(){\r\n\r\n            _self.unblock_form();\r\n        });\r\n    }\r\n\r\n    /**\r\n     * load invoice list after choosing the quote\r\n     * \r\n     * @returns void\r\n     */\r\n    loadInvoices(){\r\n\r\n        var _self = this,\r\n            value = this.quote_field.val();\r\n\r\n        if(value === undefined){\r\n            alert('Select a quote');\r\n            return;\r\n        }\r\n\r\n        this.invoice_field.html('');\r\n\r\n        jQuery.ajax({\r\n\t\t\turl: ajaxurl,\r\n\t\t\tdataType : 'json',\r\n\t\t\tmethod: 'GET',\r\n\t\t\tbeforeSend: function(){\r\n\t\t\t\t_self.invoice_field.prop('disabled', true);\r\n                jQuery('#invoice-checkout').find('.ajax-container').html('');\r\n\t\t\t},\r\n\t\t\tdata: {\r\n\t\t\t\taction: 'hw_cv_pos_get_invoices_by_quote',\r\n                quote_id: value\r\n\t\t\t}\r\n\t\t}).done(function(response){\r\n\r\n\t\t\tif(response.success){\r\n\t\t\t\t\r\n                _self.processQuoteResponse(response.data);\r\n\t\t\t}else{\r\n\r\n\t\t\t\tnew Noty({\r\n                    text: response.message,\r\n                    type: 'error'\r\n                }).show();\r\n\t\t\t}\r\n\t\t}).fail(function(){\r\n\r\n\t\t\tnew Noty({\r\n                text: 'An error occurred with the request',\r\n                type: 'error'\r\n            }).show();\r\n\r\n\t\t});\r\n    }\r\n\r\n    /**\r\n     * Process quote response\r\n     * \r\n     * @param {array} response \r\n     */\r\n    processQuoteResponse(response){\r\n\r\n        if(response.hasOwnProperty('invoice_options')){\r\n\r\n            if(response.invoice_options !== ''){\r\n\r\n                this.invoice_field.html(response.invoice_options).prop('disabled', false);\r\n            }else{\r\n\r\n                this.invoice_field.html('<option>No invoices found</option>');\r\n                \r\n                new Noty({\r\n                    text: 'No invoices found.',\r\n                    type: 'error'\r\n                }).show();\r\n            }\r\n\r\n            if(response.card_digits !== ''){\r\n                jQuery('#card-digits').text(response.card_digits);\r\n            }\r\n        }\r\n\r\n        if(response.hasOwnProperty('billing_information')){\r\n\r\n            this.billing_information = response.billing_information;\r\n        }else{\r\n\r\n            this.billing_information = null;\r\n        }\r\n\r\n        this.loadBillingData();\r\n    }\r\n\r\n    /**\r\n     * process invoice data response\r\n     * \r\n     * @param {Array} response \r\n     */\r\n    processInvoiceResponse(response){\r\n\r\n        var checkoutAjaxContainer = jQuery('#invoice-checkout').find('.ajax-container');\r\n\r\n        checkoutAjaxContainer.html(response.html)\r\n\r\n        this._invoice = response.invoice;\r\n        \r\n        this.update();\r\n    }\r\n\r\n    /**\r\n     * update the POS\r\n     */\r\n    update(){\r\n\r\n        if(this._invoice === null){\r\n\r\n            this.card_form.hide();\r\n            this.billing_form.hide();\r\n            this.disableSubmitBtn();\r\n            return;\r\n        }\r\n\r\n        if(this._invoice.due_amount <= 0){\r\n\r\n            this.disableSubmitBtn();\r\n        }else{\r\n\r\n            this.card_form.show();\r\n            this.billing_form.show();\r\n            this.enableSubmitBtn();\r\n        }\r\n    }\r\n\r\n    /**\r\n     * return the invoice\r\n     * \r\n     * @returns Object\r\n     */\r\n    getInvoice(){\r\n        return this._invoice;\r\n    }\r\n\r\n    /**\r\n     * return whether the card is \r\n     * \r\n     * @returns bool\r\n     */\r\n    useSavedCard(){\r\n        return !this.el.find('#use_saved_card').is(':checked');\r\n    }\r\n\r\n    /**\r\n     * block the form\r\n     * \r\n     * @param {string} message \r\n     */\r\n    block_form(message){\r\n        blockUI(this.form, message);\r\n    }\r\n\r\n    /**\r\n     * unblock the form\r\n     * \r\n     * @param {Object} el \r\n     */\r\n    unblock_form(){\r\n        unblockUI(this.form);\r\n    }\r\n\r\n    disableSubmitBtn(){\r\n\r\n        this.submitBtn.prop('disabled', true);\r\n    }\r\n\r\n    enableSubmitBtn(){\r\n\r\n        this.submitBtn.prop('disabled', false);\r\n    }\r\n\r\n    clearCardForm(){\r\n\r\n        clearInputs(this.card_form.find(':input'));\r\n    }\r\n\r\n    clearBillingForm(){\r\n\r\n        clearInputs(this.billing_form.find(':input'));\r\n    }\r\n\r\n    loadBillingData(){\r\n\r\n        var _self = this;\r\n\r\n        if(this.billing_information === null){\r\n            return;\r\n        }\r\n\r\n        jQuery.each(this.billing_information, function( index, value){\r\n            \r\n            switch(index){\r\n                case 'zip':\r\n                    index = 'postal_code';\r\n                    break;\r\n            }\r\n\r\n            _self.billing_form.find('#'+index).val(value);\r\n        });\r\n    }\r\n};\r\n\r\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (POS);\n\n//# sourceURL=webpack://hola-weddings-classic-vacations/./assets/src/js/pos/pos.class.js?");

      /***/
    })

    /******/
  };
  /************************************************************************/
  /******/ // The module cache
  /******/
  var __webpack_module_cache__ = {};
  /******/
  /******/ // The require function
  /******/
  function __webpack_require__(moduleId) {
    /******/ // Check if module is in cache
    /******/var cachedModule = __webpack_module_cache__[moduleId];
    /******/
    if (cachedModule !== undefined) {
      /******/return cachedModule.exports;
      /******/
    }
    /******/ // Create a new module (and put it into the cache)
    /******/
    var module = __webpack_module_cache__[moduleId] = {
      /******/ // no module.id needed
      /******/ // no module.loaded needed
      /******/exports: {}
      /******/
    };
    /******/
    /******/ // Execute the module function
    /******/
    __webpack_modules__[moduleId](module, module.exports, __webpack_require__);
    /******/
    /******/ // Return the exports of the module
    /******/
    return module.exports;
    /******/
  }
  /******/
  /************************************************************************/
  /******/ /* webpack/runtime/define property getters */
  /******/
  (function () {
    /******/ // define getter functions for harmony exports
    /******/__webpack_require__.d = function (exports, definition) {
      /******/for (var key in definition) {
        /******/if (__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
          /******/Object.defineProperty(exports, key, {
            enumerable: true,
            get: definition[key]
          });
          /******/
        }
        /******/
      }
      /******/
    };
    /******/
  })();
  /******/
  /******/ /* webpack/runtime/hasOwnProperty shorthand */
  /******/
  (function () {
    /******/__webpack_require__.o = function (obj, prop) {
      return Object.prototype.hasOwnProperty.call(obj, prop);
    };
    /******/
  })();
  /******/
  /******/ /* webpack/runtime/make namespace object */
  /******/
  (function () {
    /******/ // define __esModule on exports
    /******/__webpack_require__.r = function (exports) {
      /******/if (typeof Symbol !== 'undefined' && Symbol.toStringTag) {
        /******/Object.defineProperty(exports, Symbol.toStringTag, {
          value: 'Module'
        });
        /******/
      }
      /******/
      Object.defineProperty(exports, '__esModule', {
        value: true
      });
      /******/
    };
    /******/
  })();
  /******/
  /************************************************************************/
  /******/
  /******/ // startup
  /******/ // Load entry module and return exports
  /******/ // This entry module can't be inlined because the eval devtool is used.
  /******/
  var __webpack_exports__ = __webpack_require__("./assets/src/js/pos.js");
  /******/
  /******/
})();