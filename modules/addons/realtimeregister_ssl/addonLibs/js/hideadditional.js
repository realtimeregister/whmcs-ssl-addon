/* Hide label with additional information, you may need to edit this for your specific template, a variable called
'template' can be accessed ie. if(template == 'twentyone') {..} */

$(function() {
    $('label[for^="customfield"]').parent('div').parent('div').hide();
    $('.sub-heading.pb-1').hide();
});