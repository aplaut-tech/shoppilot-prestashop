<script>
  var _shoppilot = _shoppilot || [];
  _shoppilot.push(['token', "{$token}"]);
  _shoppilot.push(['transaction', {
    "signature":  "{$signature}",
    "number":    "{$order->id}",
    "email":     "{$customer->email}",
    "full_name": "{$customer->firstname} {$customer->lastname}",
    "details":    {
      "total_price": {$order->total_paid}
    },
    order_lines: [
      {foreach from=$products item=product name=products}
        {
          "id":    "{$product.product_id}",
          "title": "{$product.product_name}",
          "price":  {$product.product_price},
          "image": "{$images[$smarty.foreach.products.index]}",
          "count":  {$product.product_quantity}
        }
        {if $smarty.foreach.products.last} {else},{/if}
      {/foreach}
    ]
  }]);
  (function() {
      var script = document.createElement('script');
      script.type = 'text/javascript';
      script.async = true;
      script.src = "//shoppilot.ru/scripts/loader.js";
      script.setAttribute('data-main', "//shoppilot.ru/scripts/main.js");
      var s = document.getElementsByTagName('script')[0];
      s.parentNode.insertBefore(script, s);
  })();
</script>
