jQuery(document).ready(function ($) {
    jQuery.fn.rotate = function (degrees) {
        $(this).css({'transform': 'rotate(' + degrees + 'deg)'});
        return $(this);
    };

    $('input[name=rotation_f]').change(function () {
        if (parseInt($(this).val()) % 90 === 0)
            $('img.imgf').rotate($(this).val());
    });

    $('input[name=rotation_b]').change(function () {
        if (parseInt($(this).val()) % 90 === 0)
            $('img.imgb').rotate($(this).val());
    });

    $('input[name=rotation_f]').trigger('change');
    $('input[name=rotation_b]').trigger('change');
});