
$(document).ready(function(){

		$(document).on('click', '.ajax_add_to_cart_button', function(e){
			var idProduct =  $(this).data('id-product');
      dataLayer.push({
        event: 'addToCart',
        ecommerce: {
          add: {
            products: [{
              id: idProduct
            }]
          }
        }
      });
		});
		//for product page 'add' button...
		$(document).on('click', '#add_to_cart button', function(e){
        dataLayer.push({
          event: 'addToCart',
          ecommerce: {
            add: {
              products: [{
                id: $('#product_page_product_id').val()
              }]
            }
          }
        });
			ajaxCart.add($('#product_page_product_id').val(), $('#idCombination').val(), true, null, $('#quantity_wanted').val(), null);
		});

		//for 'delete' buttons in the cart block...
		$(document).on('click', '.cart_block_list .ajax_cart_block_remove_link', function(e){
			e.preventDefault();
			// Customized product management
			var customizationId = 0;
			var productId = 0;
			var productAttributeId = 0;
			var customizableProductDiv = $($(this).parent().parent()).find("div[data-id^=deleteCustomizableProduct_]");
			var idAddressDelivery = false;

			if (customizableProductDiv && $(customizableProductDiv).length)
			{
				var ids = customizableProductDiv.data('id').split('_');
				if (typeof(ids[1]) != 'undefined')
				{
					customizationId = parseInt(ids[1]);
					productId = parseInt(ids[2]);
				}
			}

			// Common product management
			if (!customizationId)
			{
				//retrieve idProduct and idCombination from the displayed product in the block cart
				var firstCut = $(this).parent().parent().data('id').replace('cart_block_product_', '');
				firstCut = firstCut.replace('deleteCustomizableProduct_', '');
				ids = firstCut.split('_');
				productId = parseInt(ids[0]);
			}

			// Removing product from the cart
      dataLayer.push({
        event: 'removeFromCart',
        ecommerce: {
          remove: {
            products: [{
              id: productId
            }]
          }
        }
      });
		});
    
    $('.cart_quantity_delete' ).on('click', function(e){
      var ids = $(this).attr('id').split('_');
      var productId = parseInt(ids[0]);      
			// Removing product from the cart
      dataLayer.push({
        event: 'removeFromCart',
        ecommerce: {
          remove: {
            products: [{
              id: productId
            }]
          }
        }
      });
    });
    
    $('.cart_quantity_down').on('click', function(e){
      var id = $(this).attr('id').replace('cart_quantity_down_', '');
      if ($('input[name=quantity_' + id + ']').val() <= 1) {
        var ids = id.split('_');
        var productId = parseInt(ids[0]);      
  			// Removing product from the cart
        dataLayer.push({
          event: 'removeFromCart',
          ecommerce: {
            remove: {
              products: [{
                id: productId
              }]
            }
          }
        });
      }
    });
});
