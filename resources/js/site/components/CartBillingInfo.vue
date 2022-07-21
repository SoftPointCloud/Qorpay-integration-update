<template>
  <div>
    <h6>Billing Information</h6>
    
    <div v-if="satellite">
      <div class="row mb-2">
        <div class="col-md-12">
          <strong>Satellite pickup:</strong><br />
          {{ satellite.name }} <br />
          {{ satellite.address }} <br />
          {{ satellite.city }},
          {{ satellite.state }}
          {{ satellite.zip }}
        </div>
      </div>
    </div>

    <!-- payment options -->
    <div v-if="onlinePaymentsOnly">
      <input
        type="hidden"
        name="payment_type"
        v-model="orderFormFields.paymentType"
        value="online" />
    </div>
    <div v-else-if="inStorePaymentsOnly">
      <div class="row mb-2">
        <div class="col-md-6">
          <label class="radio-inline">
            <input
              type="radio"
              name="payment_type"
              id="paymentOptionInStore"
              v-model="orderFormFields.paymentType"
              value="in-store"
              checked="selected" />
            Pay in store
          </label>
        </div>
      </div>
    </div>
    <div v-else>
      <div class="row mb-3">
        <div class="col-md-12">
          <div class="form-check form-check-inline">
            <input
              class="form-check-input"
              type="radio"
              name="payment_type"
              id="paymentOptionOnline"
              @change="paymentTypeChanged()"
              v-model="orderFormFields.paymentType"
              value="online"
              checked="selected" />
            <label class="radio-inline" for="paymentOptionOnline">Pay online</label>
            
          </div>

          <div class="form-check form-check-inline">
            <input
              class="form-check-input"
              type="radio"
              name="payment_type"
              @change="paymentTypeChanged()"
              id="paymentOptionInStore"
              v-model="orderFormFields.paymentType"
              value="in-store" />
            <label class="form-check-label" for="paymentOptionInStore">Pay in store</label>
            
              
          </div>
        </div>
      </div>
    </div>

    <!-- payment info -->
    <div id="credit-card-form" v-if="orderFormFields.paymentType === 'online'">
     <main>
    <div id="myForm" style="padding:5px; height: auto; width: 520px">
      <iframe id="pay-form" style="height: 323px;"></iframe>

      <!--button id="my-submit-button" type="submit" style="padding: 5px; width: <?=$payfields['width']?>;height: 40px; border-radius: 3px;">
        <span id="my-submit-button-text"><?=$payfields['button-text']?></span>
      </button-->

    </div>
    
  </main>
      <section v-if="hasStoredPaymentMethods()">

        <div class="row">
          <div class="form-group col-sm-12">
            <label>Saved Cards</label>
            <select @change="updateSelectedCard" v-model="selectedCard" class="form-control">
              <option
                v-for="(paymentMethod, index) in storedPaymentMethods"
                v-bind:value="paymentMethod.card_token">
                ******** {{ paymentMethod.card_last_4 }} &middot;
                {{ paymentMethod.card_exp_month }}/{{ paymentMethod.card_exp_year }} &middot;
                {{ paymentMethod.card_brand.toUpperCase() }}
              </option>
              <option value="new-card">Add New Card</option>
            </select>
          </div>
        </div>
      </section>
      <section id="payment-form" class="payment-form" v-if="showCCForm()">
        <div class="row">
          <div class="form-group col-sm-12 mb-2">
            <label for="cc-name" class="control-label">Name on Card  </label>
            <div class="form-control payment-fields disabled empty" id="cc-name" data-cc-name></div>
          </div>
        </div>

        <div class="row">
          <div class="form-group col-sm-12 mb-2">
            <label for="cc-card" class="control-label">Card Number </label>
            <div class="form-control payment-fields disabled empty" id="cc-card" data-cc-card></div>
          </div>
        </div>

        <div class="row">
          <div class="form-group col-sm-6 mb-2">
            <label for="cc-exp" class="control-label">Exp Date</label>
            <div class="form-control payment-fields disabled empty" id="cc-exp" data-cc-exp></div>
          </div>
          <div class="form-group col-sm-6 mb-2">
            <label for="cc-cvv" class="control-label">CVV</label>
            <div class="form-control payment-fields disabled empty" id="cc-cvv" data-cc-cvv></div>
          </div>
        </div>

        <div class="row" v-if="isLoggedIn">
          <div class="form-group col-sm-12">
            <input type="checkbox" name="save_card" id="save_card" checked="checked" />
            <label for="save_card">Save this card for future use</label>
          </div>
        </div>
      </section>
    </div>
    <div v-if="orderFormFields.paymentType === 'online'">
      <div class="col-md-12 mb-2">
        <hr class="hr-legacy" />
      </div>
      <div class="col-md-6 mb-3">
        <label>Would you like to add a tip?</label>
      </div>
      <div class="col-md-6">
        <div class="input-group">
          <div class="input-group-text">$</div>
          <input
            type="number"
            step="0.01"
            name="tip_amount"
            class="form-control"
            @change="updateTip"
            v-model="orderFormFields.tipAmount" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { orderForm } from '../order-form';

export default {
  props: {
    formErrors: [Object, Array],
    defaultPaymentType: String,
    storeLocationId: Number,
    satellite: Object,
    onlinePaymentsOnly: Boolean,
    inStorePaymentsOnly: Boolean,
    isLoggedIn: Boolean,
    storedPaymentMethods: Array,
    defaultName: String,
  },

  data() {
    return {
      orderFormFields: orderForm.fields,
      selectedCard: null,
    };
  },

  mounted() {
    this.orderFormFields.paymentType = this.defaultPaymentType;
    this.orderFormFields.name = this.defaultName;

    if (this.inStorePaymentsOnly) {
      this.orderFormFields.paymentType = 'in-store';
    }

    if (this.hasStoredPaymentMethods()) {
      this.selectedCard = this.storedPaymentMethods[0].card_token;
      this.updateSelectedCard();
    }

    if (this.showCCForm()) {
      this.initCCForm();
    }




    async function HMAC(key, message){
      const g = str => new Uint8Array([...unescape(encodeURIComponent(str))].map(c => c.charCodeAt(0))),
      k = g(key),
      m = g(message),
      c = await crypto.subtle.importKey('raw', k, { name: 'HMAC', hash: 'SHA-256' },true, ['sign']),
      s = await crypto.subtle.sign('HMAC', c, m);
      return [...new Uint8Array(s)].map(b => b.toString(16).padStart(2, '0')).join('');
    }

    /* Values that will be needed for generating the HMAC */
    let host_domain = "https://secure.qorcommerce.io";
    let qor_form_id = "frm_e67ba9b701de11ed87940aac2e024c3e"; // test form_id from a saved embedded form template
    let qor_app_key = "T6554252567241061980"; // test app-key
    let qor_client_key = "01dffeb784c64d098c8c691ea589eb82"; // test client-key
    let qor_mid = "887728203"; // test MID

    const payfields = {};

    payfields["width"] = '520'+'px'; // sets payment form DIV width and passed along to payment form -> 400px is single column, 520px (+) grid rendered

    document.getElementById("myForm").style.width = payfields["width"];

    const params = (new URL(location)).searchParams;
    
    switch(params.get('option')) {
      case '1':

        /** use this form to create a payment intent and tokenize the account to be used in your own payment attempt ***/

        payfields["timestamp"] = Math.floor(Date.now() / 1000); // Current Unix timestamp -> used for auto-reload
        payfields["payment-intent"] = 1; 
        payfields["domain"] = host_domain; // Domain of the website that will host this embeddedpayment form
        payfields["app-key"] = qor_app_key;
        payfields["client-key"] = qor_client_key;
        payfields["mid"] = qor_mid;
        payfields["include-cardholder"] = 1; // turn cardholder field on / off
        payfields["include-street"] = 0;  // turn steet field on / off
        payfields["include-zip"] = 1; // turn zip field on / off -> used for AVS
        payfields["button-submit"] = 1;
        payfields["button-text"] = "Place Order (intent)"; // set the submit button text
        //payfields["profile-id"] = '12345'; // tis is yor profile / customer id that we can assign to the token

        break;

      case '2':

        /** -- OR -- **/
        /** use this form to create a payment token to be used in your own payment attempt or simply save a payment method and store that token for your customer ***/
        
        payfields["timestamp"] = Math.floor(Date.now() / 1000); // Current Unix timestamp -> used for auto-reload
        payfields["payment-token"] = 1; 
        payfields["domain"] = host_domain; // Domain of the website that will host this embeddedpayment form
        payfields["app-key"] = qor_app_key;
        payfields["client-key"] = qor_client_key;
        payfields["mid"] = qor_mid;
        payfields["include-cardholder"] = 1; // turn cardholder field on / off
        payfields["include-street"] = 0;  // turn steet field on / off
        payfields["include-zip"] = 1; // turn zip field on / off -> used for AVS
        payfields["button-submit"] = 1;
        payfields["button-text"] = "Create Payment Token"; // set the submit button text
        //payfields["profile-id"] = '12345'; // tis is yor profile / customer id that we can assign to the token

        break;

      case '3':

        /** -- OR -- **/
        /*** send a form ID for a saved form template built from the assorted option of attributes ***/

        // JSON of attributes saved in the form template (DB)
        /*
        {
          "payment-intent": 0,
          "app-key": "T6554252567241061980",
          "client-key": "01dffeb784c64d098c8c691ea589eb82",
          "domain": "https://secure.qorcommerce.io",
          "mid": "887728203",
          "use3DS": 0,
          "css-url": "https://secure.qorcommerce.io/css/standard.css",
          "include-cardholder": 1,
          "include-street": 0,
          "include-zip": 1,
          "button-submit": 1,
          "button-text": "Place Order",
          "auto-reload": 0,
          "include-store-card": 1,
          "store-card-text": "Store for later use",
          "required-fields": "cardholdername,account,expdate,cv,zip"
        }
        */

        payfields["timestamp"] = Math.floor(Date.now() / 1000); // Current Unix timestamp -> used for auto-reload
        payfields["form_id"] = qor_form_id; 
        payfields["domain"] = host_domain; // id this is sent this overrides the saved host domain
        payfields["app-key"] = qor_app_key;
        payfields["client-key"] = qor_client_key;
        payfields["amount"] = "20.10"; // amount of order or shopping cart to be charged
        payfields["orderid"] = "oid-" + Math.floor(Math.random() * 100000) + 1000;; // unique order id - REPLACE with our oredr id
        //payfields["profile-id"] = '12345'; // tis is yor profile / customer id that we can assign to the order

        break;

      case '4':

        /** -- OR -- **/
        /*** send everything to build an embedded form ***/
      
        payfields["timestamp"] = time(); // Current Unix timestamp -> used for auto-reload
        payfields["domain"] = host_domain; // Domain of the website that will host this embeddedpayment form
        payfields["app-key"] = qor_app_key;
        payfields["client-key"] = qor_client_key;
        payfields["mid"] = qor_mid;
        //payfields["profile-id"] = '12345'; // tis is yor profile / customer id that we can assign to the order

        //$payfields["background-color"] = "#e7f0ff"; // a color eg. red -or- the hex eg. #e7f0ff

        payfields["amount"] = "20.10"; // amount of order or shopping cart to be charged
        payfields["orderid"] = "oid-" + Math.floor(Math.random() * 100000) + 1000;; // unique order id - REPLACE with our oredr id

        //$payfields["account"] = "131942$U60MLwx2"; // send a tokenized account to be charged (???? needs to be encrypted)

        payfields["use3DS"] = 0; // turn on 3D secure usage

        payfields["css-url"] = host_domain + "/css/standard.css";
        //$payfields["css-url"] = $host_domain . "/css/modern.css";
        //$payfields["css-url"] = $host_domain . "/css/floating.css"; // not done yet

        //$payfields["expdate-format"] = "separate"; // will also render exp month/year MM/YYYY - separate is default
        //$payfields["expdate-format"] = "merged"; // will also render exp month/year MM/YYYY - separate is default
        payfields["include-cardholder"] = 1; // turn cardholder field on / off
        //$payfields["include-street"] = 1;  // turn steet field on / off
        payfields["include-zip"] = 1; // turn zip field on / off -> used for AVS

        //$payfields["auto-reload"] = 1; // not done yet -> used for AVS

        //$payfields["include-store-card"] = 1; // show "Store for later use" checkbox
        //$payfields["store-card-text"] = "Store for later use"; // set the store card text on the form

        payfields["button-submit"] = 0;
        payfields["button-text"] = "Place Order"; // set the submit button text

        break;

      default:

        payfields["timestamp"] = Math.floor(Date.now() / 1000); // Current Unix timestamp -> used for auto-reload
        payfields["form_id"] = qor_form_id; 
        payfields["domain"] = host_domain; // id this is sent this overrides the saved host domain
        payfields["app-key"] = qor_app_key;
        payfields["client-key"] = qor_client_key;
        payfields["amount"] = "10.10"; // amount of order or shopping cart to be charged
        payfields["orderid"] = "oid-" + Math.floor(Math.random() * 100000) + 1000;; // unique order id - REPLACE with our oredr id

    }


    /* Response onSuccess INTENT:

    {
      "status":"approved",
      "code":"GW00",
      "message":"Get token was processed successfully (existing token).",
      "last_4":"1319",
      "token":"t:131942$U60MLwx2"  // temporary token
    }

    /* Response onSuccess GET TOKEN:

    {
      "status":"approved",
      "code":"GW00",
      "message":"Get token was processed successfully (existing token).",
      "last_4":"1319",
      "token":"131942$U60MLwx2"
    }

    /* Response onSuccess PAYMENT:

    {
      "status":"approved",
      "code":"GW00",
      "message":"Sale processed successfully.",
      "transaction_date":"2022-07-14 08:46:31",
      "transaction_id":"219445991395050",
      "order_id":"oid-2008028340",
      "last_4":"1319",
      "brand":"visa",
      "authcode":"222625",
      "amount_approved":"20.10",
      "isGC":"no"
    }

    /* Response onSuccess w/ token PAYMENT:

    {
      "status":"approved",
      "code":"GW00",
      "message":"Sale processed successfully.",
      "transaction_date":"2022-07-14 08:46:31",
      "transaction_id":"219445991395050",
      "order_id":"oid-2008028340",
      "last_4":"1319",
      "brand":"visa",
      "authcode":"222625",
      "amount_approved":"20.10",
      "token":"131942$U60MLwx2",
      "token_last4":false,
      "token_exp_m":null,
      "token_exp_y":2000,
      "token_brand":null,
      "isGC":"no"
    }

    /* Response onError PAYMENT:

    {
      "status":"declined",
      "code":"GW97",
      "message":"Sale was not processed. (97: CVV2\/CID error)",
      "order_id":"oid-153098432",
      "isGC":"no"
    }

    */

    let data_to_hash = Object.values(payfields).join(''); // implode values only

    // hash and build hmac
    HMAC(qor_client_key, data_to_hash)
       .then(function(hash) { 
         document.getElementById("pay-form").setAttribute("data-hmac-hmacsha256", hash); 

         for (const [key, value] of Object.entries(payfields)) {
           document.getElementById("pay-form").setAttribute("data-hmac-"+key, value); 
        }

         var paymentForm = new QorPaymentForm( "pay-form",host_domain);

        /***
          * define your own submit button 
          * When generating the iframe, send the attribute button-submit = 0.
            This will hide the payment forms default submit button.
          â€¢ to trigger a submit of the payment form, use the paymentForm object's
            submitPayment method
        ***/
        /*
        var mySubmitButton = document.getElementById("my-submit-button");
        mySubmitButton.addEventListener("click", function() {
            paymentForm.submitPayment();
          });
        */

        paymentForm.onSubmit(function(response) {
          if (response.code === 'approved') {
            alert( "Received an approval: " + response.onSuccess );
            // record the transaction, save the token if needed and finish order/sale
            console.log(response.onSuccess);
            window.location.assign("thank-you.html");
          } else {
            alert( "Received a decline: " + response.onError );
            console.log(response.onError);
            paymentForm.enableSubmitButton();
          }
        });

        paymentForm.request();
       } );


  },

  methods: {
    hasError(field) {
      return this.formErrors[field] !== undefined;
    },

    showCCForm() {
      if (this.hasStoredPaymentMethods() && this.selectedCard !== 'new-card') {
        return false;
      }

      return true;
    },

    hasStoredPaymentMethods() {
      return this.storedPaymentMethods.length > 0;
    },

    initCCForm() {
      this.$nextTick(function () {
        orderForm.initCCForm(this.storeLocationId);
      });
    },

    paymentTypeChanged() {
      this.initCCForm();

      const paymentType = this.orderFormFields.paymentType;
      this.$root.$emit('PAYMENT_TYPE_SET', { paymentType });

      if (paymentType !== 'online') {
        const tipAmount = 0;
        this.orderFormFields.tipAmount = tipAmount.toFixed(2);
        this.$root.$emit('TIP_SET', { tipAmount });
      }
    },

    updateSelectedCard() {
      if (this.selectedCard === 'new-card') {
        this.orderFormFields.storedCardToken = null;
        this.initCCForm();
      }

      this.orderFormFields.storedCardToken = this.selectedCard;
    },

    getError(field) {
      return this.formErrors[field][0];
    },

    updateTip(event) {
      let tipAmount = parseFloat(event.target.value.replace(/[^\d.-]/g, ''));

      if (isNaN(tipAmount) || tipAmount < 0) {
        tipAmount = 0;
      }

      this.orderFormFields.tipAmount = tipAmount.toFixed(2);
      this.$root.$emit('TIP_SET', { tipAmount });
    },
  },
};
</script>
