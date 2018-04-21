<!-- Google Tag Manager Data Layer -->
<script data-keepinline="true">
window.dataLayer = window.dataLayer || [];
{if $transactionId}
dataLayer.push({
    'ecommerce' : {
        'purchase' : {
            'actionField' : {
                'id': '{$transactionId}',
                'revenue' : '{$transactionTotal}',
                'shipping': '{$transactionShipping}',
                },
                'products' : [
                             {foreach from=$transactionProducts item=product}
                                {literal}
                                    {
                                {/literal}
                                'sku' : '{$product['id_product']}',
                                'name' : '{$product['name']}',
                                'category' : '{$product['category']}',
                                'price' : '{$product['price_wt']}',
                                'quantity' : '{$product['quantity']}'
                                {literal}
                                    }
                                {/literal}{if not $product@last}, {/if}
                                {/foreach}
                            ]
                }
        }
    }
);
{*Criteo One Tag Transaction ID data Layer var*}
dataLayer.push({
    'TransactionID' : '{$transactionId}'
});
{/if}
{if $pageType}
dataLayer.push({
    'PageType': '{$pageType}'{if !empty($productId)},
    'ProductID': '{$productId}'
{/if}
});
{/if}
{if $hashedEmail}
dataLayer.push({
    'hashedEmail': '{$hashedEmail}'
});
{/if}
{if !empty($three_products)}
dataLayer.push({
   'ProductIDList': [{foreach from=$three_products item=product}'{$product['id_product']}'{if not $product@last}, {/if}{/foreach}]
});
{/if}
{if !empty($transactionProducts)}
dataLayer.push({
    'ProductIDList': [{foreach from=$transactionProducts item=product}{literal}{{/literal}'id' : '{$product['id_product']}', 'price' : '{$product['price_wt']}', 'quantity':'{$product['quantity']}'{literal}}{/literal} {if not $product@last}, {/if}{/foreach}]
});
{/if}
{if !empty($type_of_customer)}
dataLayer.push({
    'typeof_c' : '{$type_of_customer}'
});
{/if}
{if !empty($dataLayer)}
dataLayer.push({$dataLayer|json_encode});
{/if}
</script>
<!-- End Google Tag Manager Data Layer -->