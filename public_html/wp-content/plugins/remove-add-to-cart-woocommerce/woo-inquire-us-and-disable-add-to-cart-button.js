/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


jQuery(function(){

    if( jQuery('#wpiudacb_disable_add_to_cart').val() != 'inquire_us' ){
        jQuery('.wpiudacb_inqure_us_link_field').hide();
    }
    jQuery('#wpiudacb_disable_add_to_cart').change(function(){
        if(jQuery(this).val() == 'inquire_us'){
            jQuery('.wpiudacb_inqure_us_link_field').fadeIn();
        }else{
            jQuery('.wpiudacb_inqure_us_link_field').fadeOut();
        }
    });
});