jQuery(document).ready(function ($) {

    var labels = document.getElementsByClassName('wpbdp-checkout-gateway-selection wpbdp-checkout-section');
    var arr_labels = document.getElementsByClassName('wpbdp-checkout-gateway-selection wpbdp-checkout-section')[0].getElementsByTagName("label");
    var element = document.createElement("div");
    element.id = 'description';
    element.innerHTML = '<br>Pay with Bitcoin, Litecoin, or other altcoins via<br><a href="https://alpha.coinpayments.net/" target="_blank" style="text-decoration: underline; font-weight: bold;" title="CoinPayments.net">CoinPayments.net</a></br>';

    Array.prototype.slice.call(labels).forEach(function (labelEl) {
        labelEl.addEventListener('click', function foo(event) {
            if (event.target.value === 'coinpayments')
                for (var i = 0; i < arr_labels.length; i++)
                    if (arr_labels[i].children[0].getAttribute("value") === "coinpayments")
                        arr_labels[i].appendChild(element);
            if (event.target.value !== 'coinpayments')
                for (var i = 0; i < arr_labels.length; i++)
                    if (arr_labels[i].children[0].getAttribute("value") === "coinpayments")
                        if(document.getElementById("description"))
                            arr_labels[i].removeChild(arr_labels[i].lastChild);
        });
    });
});
