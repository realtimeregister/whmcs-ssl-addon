jQuery(document).ready(function(){
    jQuery('#AddonNextIsWHMCSConfig').next().hide();
    
    var relation = {};
    
    jQuery('#AddonNextIsWHMCSConfig').next().find('input').each(function(){
        
        var name = jQuery(this).parent().prev().text();
        
        relation[name] = jQuery(this).attr('name');
        
        jQuery('*[name="'+name+'"]').change(function(){
            var tname = jQuery(this).attr('name');
            jQuery('input[name="'+relation[tname]+'"]').val(jQuery(this).val());
        }).change();
    });
});