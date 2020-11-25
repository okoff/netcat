/*$Id: order_edit.js 4356 2011-03-27 10:24:29Z denis $*/

var nc_ms_last = 0;

function nc_ms_add_row ( pos ) {
    var tpl = '<div class="row">' +
    '<div style="width: 25%"><input type="text" id="name_%x" name="goods[%x][name]" class="name"  /></div>' +
    '<div style="width: 30%"><input type="text" id="url_%x" name="goods[%x][uri]" class="uri"  /></div>' +
    '<div style="width: 10%"><input type="text" id="quantity_%x" name="goods[%x][quantity]" class="quantity"  /></div>' +
    '<div style="width: 15%"><input type="text" id="price_%x" name="goods[%x][price]" class="price" size="7" /></div>' +
    '<div style="width: 15%" class="del"><div class="icons icon_delete"></div>удалить</div>' +
    '<br/></div>';
    var div = jQuery(tpl.replace(/%x/g, nc_ms_last));
    div.find("input.name").val(pos.name);
    div.find("input.uri").val(pos.uri);
    div.find("input.quantity").val(pos.quantity);
    div.find("input.price").val(pos.price);
    div.find("div.del").click(function() {
        jQuery(this).parent().remove();
        nc_ms_update_cost ();
    });
    div.find(".price").change(function() {
        nc_ms_update_cost({} )
        });
    div.find(".quantity").change(function() {
        nc_ms_update_cost({} )
        });
    div.appendTo('#positions');
    nc_ms_last++;
   
}
// обновление стоимости по позициям в заказе
function nc_ms_update_cost () {
    var sum = 0;
    jQuery('#adminForm').find(".price").each(function(){
        var elem = jQuery(this);
        var id = elem.attr('id').replace('price', 'quantity');
        sum += jQuery('#'+id).val()*elem.val();
    });

    jQuery('#Cost').val(sum);
    nc_ms_update_finalcost ();
}
// обновление окончательной стоимости по стоимости без скидки и самой скидки
function nc_ms_update_finalcost () {
    var cost = jQuery('#Cost').val();
    var discount = jQuery('#Discount').val();

    jQuery('#FinalCost').val ( (100-discount)*cost/100 );
}