// jQuery is loaded in the backend anyway.
jQuery(document).ready(function($) {
    $('.taxonomy').change(function(event) {
        var value = $(this).val();
        var name = $(this).attr('name');
        var objectID = name.replace('[]', '') + '_empty';
        var form = $(this).closest('form');

        if(!value) {
            var element = document.createElement('input');
            element.id = objectID;
            element.value = '';
            element.type = 'hidden';
            element.name = name;

            $(form).append(element);
        } else {
            $('#' + objectID).remove();
        }
    });

    // We also have to set the initial values using the same data.
    $('.taxonomy').each(function(value, key) {
        var value = $(this).val();
        var name = $(this).attr('name');
        var objectID = name.replace('[]', '') + '_empty';
        var form = $(this).closest('form');

        if(!value) {
            var element = document.createElement('input');
            element.id = objectID;
            element.value = '';
            element.type = 'hidden';
            element.name = name;

            $(form).append(element);
        }
    })
});